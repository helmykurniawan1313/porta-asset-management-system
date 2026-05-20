<?php

namespace App\Exports;

use App\Models\Device;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterInventoryExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $sheets = [];
        
        // --- 1. HARDWARE INVENTORY ---
        $peripherals = Device::where('device_group', 'Peripheral')->orderBy('name')->get();
        
        foreach ($peripherals as $item) {
            $item->category = strtoupper(trim($item->category)); 
        }
        
        $groupedPeripherals = $peripherals->groupBy('category');
        
        // Add the Main PC/Laptop Sheet
        $sheets[] = new PcsSheetExport($groupedPeripherals);
        
        // Add a Sheet for each Component Category (Monitors, Keyboards, etc.)
        foreach ($groupedPeripherals as $category => $items) {
            $sheets[] = new ComponentsSheetExport($category, $items);
        }
        
        // --- 2. USERS, SAAS, & MAINTENANCE ---
        $sheets[] = new EmployeesSheetExport();
        $sheets[] = new AccountsSheetExport();
        $sheets[] = new SubscriptionsSheetExport();
        $sheets[] = new ServiceLogsSheetExport(); 
        
        return $sheets;
    }
}