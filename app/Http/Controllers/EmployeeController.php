<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        // Get all employees and their connected department
        $employees = Employee::with('department')->latest()->get();
        $departments = Department::all(); // Needed for the Add/Edit dropdowns

        return view('employees.index', compact('employees', 'departments'));
    }

    public function store(Request $request)
    {
        Employee::create($request->validate([
            'name' => 'nullable|string', // <-- Changed to nullable
            'email' => 'nullable|email|unique:employees,email',
            'department_id' => 'nullable|exists:departments,id'
        ]));
        
        return back()->with('success', 'Employee added successfully!');
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($request->validate([
            'name' => 'nullable|string', // <-- Changed to nullable
            'email' => 'nullable|email|unique:employees,email,' . $employee->id,
            'department_id' => 'nullable|exists:departments,id'
        ]));
        
        return back()->with('success', 'Employee updated successfully!');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return back()->with('success', 'Employee deleted.');
    }

    public function show(Employee $employee)
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