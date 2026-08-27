<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('users')
            ->with(['headOfDepartment:id,name', 'parent:id,name', 'children:id,name,parent_id'])
            ->latest()
            ->get();
        $users = User::orderBy('name')->get(['id', 'name', 'department_id']);
        $topLevelDepartments = $departments->whereNull('parent_id');

        return view('departments.index', compact('departments', 'users', 'topLevelDepartments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
            'parent_id' => 'nullable|exists:departments,id',
        ]);

        // Only two levels are supported: a department chosen as a parent
        // here must itself be top-level (no parent of its own), so the
        // hierarchy can never grow a third level.
        if (! empty($data['parent_id']) && Department::whereKey($data['parent_id'])->whereNotNull('parent_id')->exists()) {
            return back()->withErrors(['parent_id' => 'A sub-department cannot itself be a parent department.']);
        }

        Department::create($data);

        return back()->with('success', 'Department created successfully!');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            // The unique rule ignores the current department so you can save without changing the name
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'head_of_department_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id',
        ]);

        if (! empty($data['parent_id'])) {
            if ((int) $data['parent_id'] === $department->id) {
                return back()->withErrors(['parent_id' => 'A department cannot be its own parent.']);
            }

            // Only two levels: the chosen parent must itself be top-level,
            // and this department must not already have sub-departments of
            // its own (becoming someone's child while having children would
            // make a third level).
            if (Department::whereKey($data['parent_id'])->whereNotNull('parent_id')->exists()) {
                return back()->withErrors(['parent_id' => 'A sub-department cannot itself be a parent department.']);
            }

            if ($department->children()->exists()) {
                return back()->withErrors(['parent_id' => 'This department already has sub-departments, so it cannot become a sub-department itself.']);
            }
        }

        $department->update($data);

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