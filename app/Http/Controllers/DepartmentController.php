<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        // withCount() automatically creates an 'employees_count' variable for us!
        $departments = Department::withCount('employees')->latest()->get();

        return view('departments.index', compact('departments'));
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

    public function update(Request $request, Department $department)
    {
        $request->validate([
            // The unique rule ignores the current department so you can save without changing the name
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
        ]);

        $department->update([
            'name' => $request->name
        ]);

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