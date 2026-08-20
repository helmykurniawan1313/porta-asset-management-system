<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('users')
            ->with('headOfDepartment:id,name')
            ->latest()
            ->get();
        $users = User::orderBy('name')->get(['id', 'name', 'department_id']);

        return view('departments.index', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
        ]);

        Department::create([
            'name' => $request->name
        ]);

        return back()->with('success', 'Department created successfully!');
    }

    /**
     * Assigning a new Head of Department sets that user's job_title to
     * "Head of Department"; the previous head (if any and if different)
     * has their job_title cleared back to null since it's no longer accurate.
     */
    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            // The unique rule ignores the current department so you can save without changing the name
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'head_of_department_id' => 'nullable|exists:users,id',
        ]);

        $newHeadId = $data['head_of_department_id'] ?? null;
        $previousHeadId = $department->head_of_department_id;

        DB::transaction(function () use ($department, $data, $newHeadId, $previousHeadId) {
            $department->update($data);

            if ($newHeadId !== $previousHeadId) {
                if ($previousHeadId) {
                    User::where('id', $previousHeadId)->update(['job_title' => null]);
                }

                if ($newHeadId) {
                    User::where('id', $newHeadId)->update(['job_title' => 'Head of Department']);
                }
            }
        });

        return back()->with('success', 'Department updated successfully!');
    }

    public function destroy(Department $department)
    {
        // Because you used nullOnDelete() in your migration, 
        // deleting a department just safely unassigns the employees.
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}