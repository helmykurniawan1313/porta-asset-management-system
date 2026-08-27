<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AccountController;
use App\ClientOps\Http\Controllers\ClientController;
use App\ClientOps\Http\Controllers\ContractController;
use App\ClientOps\Http\Controllers\ContractCycleController;
use App\ClientOps\Http\Controllers\InvoiceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. PUBLIC ENTRY
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// 2. SECURED ROUTES
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* --- INVENTORY EXPORTS & ACTIONS --- */
    Route::get('/inventory/export/{category?}', [DeviceController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/attach', [DeviceController::class, 'attach'])->name('inventory.attach');
    Route::post('/inventory/detach/{device}', [DeviceController::class, 'detach'])->name('inventory.detach');
    Route::post('/inventory/{device}/service', [DeviceController::class, 'storeService'])->name('inventory.service');
    Route::post('/inventory/{device}/health', [DeviceController::class, 'storeHealth'])->name('inventory.health');

    /* --- INVENTORY CORE --- */
    Route::get('/inventory/{category?}', [DeviceController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/store', [DeviceController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/item/{device}', [DeviceController::class, 'show'])->name('inventory.show');
    Route::put('/inventory/{device}', [DeviceController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{device}', [DeviceController::class, 'destroy'])->name('inventory.destroy');

    /* --- HR & STAFF --- */
    Route::post('/employees/import', [EmployeeController::class, 'importCsv'])->name('employees.import');
    Route::resource('employees', EmployeeController::class)->except(['create', 'edit']);
    Route::resource('departments', DepartmentController::class)->except(['create', 'edit', 'show']);

    /* --- ACCOUNTS & SUBSCRIPTIONS --- */
    Route::resource('accounts', AccountController::class);
    Route::resource('subscriptions', SubscriptionController::class);
    Route::post('/subscriptions/{subscription}/allocate', [SubscriptionController::class, 'allocate'])->name('subscriptions.allocate');
    Route::delete('/allocations/{allocation}', [SubscriptionController::class, 'revoke'])->name('subscriptions.revoke');

    /* --- CLIENT OPS: CLIENTS, CONTRACTS, CYCLES --- */
    Route::middleware(['can:access-client-ops'])->group(function () {
        Route::resource('clients', ClientController::class)->except(['create', 'edit']);
        Route::post('/clients/{client}/reassign', [ClientController::class, 'reassign'])->name('clients.reassign');

        Route::post('/clients/{client}/contracts', [ContractController::class, 'store'])->name('contracts.store');
        Route::put('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
        Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');

        Route::put('/contract-cycles/{cycle}', [ContractCycleController::class, 'update'])->name('contract-cycles.update');
    });

    /* --- CLIENT OPS: INVOICES (Finance) --- */
    Route::middleware(['role:Finance|Super Admin'])->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/contract-cycles/{cycle}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    /* --- USER PROFILE --- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';