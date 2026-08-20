<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /**
     * Expected CSV header (first row): name,email,password,department,role,job_title
     * (job_title is optional and may be blank/omitted for any row). A person's
     * manager is derived from their department's Head of Department — not set here.
     */
    public function index()
    {
        $employees = User::with('department')->orderBy('name')->paginate(20);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->pluck('name');

        return view('employees.index', compact('employees', 'departments', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|string|exists:roles,name',
            'job_title' => 'nullable|in:Intern,Probation,Junior Associate,Associate,Senior Associate',
        ]);

        $this->createEmployee(
            $data['name'],
            $data['email'],
            $data['password'],
            $data['department_id'] ?? null,
            $data['role'],
            $data['job_title'] ?? null,
        );

        return back()->with('success', 'Employee added successfully!');
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'The CSV file is empty or unreadable. Expected header: name,email,password,department,role']);
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $required = ['name', 'email', 'password', 'department', 'role'];
        $missing = array_diff($required, $header);

        if (! empty($missing)) {
            fclose($handle);

            return back()->withErrors(['file' => 'CSV header missing columns: '.implode(', ', $missing).'. Expected header: name,email,password,department,role,job_title']);
        }

        $rowNumber = 1;
        $results = ['success' => [], 'failed' => []];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $record = array_combine($header, array_pad($row, count($header), null));

            try {
                $this->importRow($record, $rowNumber, $results);
            } catch (\Throwable $e) {
                $results['failed'][] = [
                    'row' => $rowNumber,
                    'email' => $record['email'] ?? null,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        return back()->with([
            'success' => count($results['success']).' employees imported, '.count($results['failed']).' failed.',
            'importResults' => $results,
        ]);
    }

    protected function importRow(array $record, int $rowNumber, array &$results): void
    {
        $name = trim((string) ($record['name'] ?? ''));
        $email = trim((string) ($record['email'] ?? ''));
        $password = (string) ($record['password'] ?? '');
        $departmentName = trim((string) ($record['department'] ?? ''));
        $role = trim((string) ($record['role'] ?? ''));
        $jobTitle = trim((string) ($record['job_title'] ?? ''));

        if ($name === '' || $email === '' || $password === '' || $role === '') {
            $results['failed'][] = [
                'row' => $rowNumber,
                'email' => $email ?: null,
                'reason' => 'Missing required field(s): name, email, password, or role.',
            ];

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $results['failed'][] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'Invalid email format.'];

            return;
        }

        if (strlen($password) < 8) {
            $results['failed'][] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'Password must be at least 8 characters.'];

            return;
        }

        if (User::where('email', $email)->exists()) {
            $results['failed'][] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'A user with this email already exists.'];

            return;
        }

        if (! Role::where('name', $role)->exists()) {
            $results['failed'][] = ['row' => $rowNumber, 'email' => $email, 'reason' => "Invalid role '{$role}'."];

            return;
        }

        $validJobTitles = ['Intern', 'Probation', 'Junior Associate', 'Associate', 'Senior Associate'];
        if ($jobTitle !== '' && ! in_array($jobTitle, $validJobTitles, true)) {
            $results['failed'][] = ['row' => $rowNumber, 'email' => $email, 'reason' => "Invalid job_title '{$jobTitle}'. Head of Department is assigned via the Departments page, not here."];

            return;
        }

        $departmentId = null;
        if ($departmentName !== '') {
            $departmentId = Department::firstOrCreate(['name' => $departmentName])->id;
        }

        $this->createEmployee($name, $email, $password, $departmentId, $role, $jobTitle !== '' ? $jobTitle : null);

        $results['success'][] = ['row' => $rowNumber, 'email' => $email];
    }

    protected function createEmployee(string $name, string $email, string $password, ?int $departmentId, string $role, ?string $jobTitle = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'department_id' => $departmentId,
            'job_title' => $jobTitle,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * "Head of Department" is not a selectable option in this form (it's assigned
     * via DepartmentController::update, which also sets job_title). If this employee
     * currently holds that title, preserve it here rather than letting the form's
     * blank job_title value silently clear it. Manager is not stored here at all —
     * it's derived from department_id via User::manager().
     */
    public function update(Request $request, User $employee)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $employee->id,
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'nullable|string|exists:roles,name',
            'job_title' => 'nullable|in:Intern,Probation,Junior Associate,Associate,Senior Associate',
        ]);

        $jobTitle = $employee->job_title === 'Head of Department'
            ? 'Head of Department'
            : ($data['job_title'] ?? null);

        $employee->update([
            'name' => $data['name'] ?? $employee->name,
            'email' => $data['email'] ?? $employee->email,
            'department_id' => $data['department_id'] ?? null,
            'job_title' => $jobTitle,
        ]);

        if (! empty($data['role'])) {
            $employee->syncRoles([$data['role']]);
        }

        return back()->with('success', 'Employee updated successfully!');
    }

    public function destroy(User $employee)
    {
        $employee->delete();
        return back()->with('success', 'Employee deleted.');
    }

    public function show(User $employee)
    {
        // Eager load everything needed for the profile, including software allocations
        $employee->load([
            'department',
            'devices.parentDevice',
            'softwareAllocations.subscription.account'
        ]);

        return view('employees.show', compact('employee'));
    }
}
