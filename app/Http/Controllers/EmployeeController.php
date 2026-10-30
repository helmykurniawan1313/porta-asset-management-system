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
     * Expected CSV header (first row): name,email,password,department,role,job_level
     * (job_level is optional and may be blank/omitted for any row) — seniority
     * (Intern, Probation, Junior Associate, Associate, Senior Associate). A
     * person's job title is their department; their manager is derived from
     * that department's Head of Department — not set here.
     */
    public function index()
    {
        $employees = User::with(['department', 'roles'])->orderBy('name')->get();
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->pluck('name');

        return view('employees.index', compact('employees', 'departments', 'roles'));
    }

    public function store(Request $request)
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|exists:departments,id',
            'role' => $isSuperAdmin ? 'required|string|exists:roles,name' : 'nullable',
            'job_level' => 'nullable|in:Intern,Probation,Junior Associate,Associate,Senior Associate',
            'joined_at' => 'nullable|date',
            'resigned_at' => 'nullable|date|after_or_equal:joined_at',
        ]);

        // Only a Super Admin can choose a role for the new account; anyone
        // else creating an employee gets them defaulted to the baseline
        // "Employee" role, which a Super Admin can change later.
        $role = $isSuperAdmin ? $data['role'] : 'Employee';

        $this->createEmployee(
            $data['name'],
            $data['email'],
            $data['password'],
            $data['department_id'] ?? null,
            $role,
            $data['job_level'] ?? null,
            $data['joined_at'] ?? null,
            $data['resigned_at'] ?? null,
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

            return back()->withErrors(['file' => 'CSV header missing columns: '.implode(', ', $missing).'. Expected header: name,email,password,department,role,job_level']);
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
                $this->importRow($record, $rowNumber, $results, $request->user()->hasRole('Super Admin'));
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

    protected function importRow(array $record, int $rowNumber, array &$results, bool $isSuperAdmin): void
    {
        $name = trim((string) ($record['name'] ?? ''));
        $email = trim((string) ($record['email'] ?? ''));
        $password = (string) ($record['password'] ?? '');
        $departmentName = trim((string) ($record['department'] ?? ''));
        $role = trim((string) ($record['role'] ?? ''));
        $jobLevel = trim((string) ($record['job_level'] ?? ''));

        // Only a Super Admin can assign a role via import; anyone else's CSV
        // rows are silently forced to the baseline "Employee" role instead
        // of whatever the file says, so a non-admin can't self-escalate by
        // uploading a CSV with role=Super Admin.
        if (! $isSuperAdmin) {
            $role = 'Employee';
        }

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

        $validJobLevels = ['Intern', 'Probation', 'Junior Associate', 'Associate', 'Senior Associate'];
        if ($jobLevel !== '' && ! in_array($jobLevel, $validJobLevels, true)) {
            $results['failed'][] = ['row' => $rowNumber, 'email' => $email, 'reason' => "Invalid job_level '{$jobLevel}'."];

            return;
        }

        $departmentId = null;
        if ($departmentName !== '') {
            $departmentId = Department::firstOrCreate(['name' => $departmentName])->id;
        }

        $this->createEmployee(
            $name,
            $email,
            $password,
            $departmentId,
            $role,
            $jobLevel !== '' ? $jobLevel : null,
        );

        $results['success'][] = ['row' => $rowNumber, 'email' => $email];
    }

    protected function createEmployee(string $name, string $email, string $password, ?int $departmentId, string $role, ?string $jobLevel = null, ?string $joinedAt = null, ?string $resignedAt = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'department_id' => $departmentId,
            'job_level' => $jobLevel,
            'joined_at' => $joinedAt,
            'resigned_at' => $resignedAt,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * Manager is not stored here at all — it's derived from department_id
     * via User::manager().
     */
    public function update(Request $request, User $employee)
    {
        $isSuperAdmin = $request->user()->hasRole('Super Admin');

        $data = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $employee->id,
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'nullable|string|exists:roles,name',
            'job_level' => 'nullable|in:Intern,Probation,Junior Associate,Associate,Senior Associate',
            'joined_at' => 'nullable|date',
            'resigned_at' => 'nullable|date|after_or_equal:joined_at',
        ]);

        $employee->update([
            'name' => $data['name'] ?? $employee->name,
            'email' => $data['email'] ?? $employee->email,
            'department_id' => $data['department_id'] ?? null,
            'job_level' => $data['job_level'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'resigned_at' => $data['resigned_at'] ?? null,
        ]);

        // Only a Super Admin can change a user's role — everyone else's
        // submitted role value (if any, e.g. from a tampered request) is
        // silently ignored rather than applied.
        if ($isSuperAdmin && ! empty($data['role'])) {
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
            'accounts.subscriptions',
            'accounts.users',
            'department',
            'devices.parentDevice',
            'softwareAllocations.subscription.account'
        ]);

        $allAccounts = \App\Models\Account::orderBy('email')->pluck('email');

        // Active licenses this person doesn't have yet, for the "Link software" picker.
        $heldIds = $employee->softwareAllocations->pluck('subscription_id');
        $availableLicenses = \App\Models\Subscription::with('account')
            ->where('status', 'Active')
            ->whereNotIn('id', $heldIds)
            ->orderBy('app')->orderBy('plan')
            ->get();

        return view('employees.show', compact('employee', 'allAccounts', 'availableLicenses'));
    }

    /**
     * Link a company login email to this employee. An email that isn't
     * registered yet is created as a new account on the fly.
     */
    public function attachAccount(Request $request, User $employee)
    {
        $data = $request->validate([
            'email'   => 'required|email|max:255',
            'purpose' => 'nullable|string|max:255',
        ]);

        $account = \App\Models\Account::firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            ['purpose' => $data['purpose'] ?? null],
        );
        $employee->accounts()->syncWithoutDetaching([$account->id]);

        return back()->with('success', "{$account->email} linked to {$employee->name}.");
    }

    public function detachAccount(User $employee, \App\Models\Account $account)
    {
        $employee->accounts()->detach($account->id);

        return back()->with('success', "{$account->email} unlinked from {$employee->name}.");
    }
}
