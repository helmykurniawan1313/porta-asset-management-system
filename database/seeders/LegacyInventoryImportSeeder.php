<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-time import of the pre-merge it_inventory.sql dump into the current
 * merged schema. The dump's `employees` table and `App\Models\Employee`
 * polymorphic rows are translated to `users`/`App\Models\User`, matching
 * the same approach as the 2026_08_20_020500_migrate_employees_to_users
 * migration: match by email if possible, else create a fresh user. Devices
 * reference `user_id` (not `employee_id`) in this schema. IDs from the dump
 * are preserved so devices/activity_logs/subscription_allocations foreign
 * keys resolve correctly relative to each other.
 */
class LegacyInventoryImportSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $departmentIdMap = $this->importDepartments();
            $employeeIdToUserId = $this->importEmployeesAsUsers($departmentIdMap);
            $this->importDevices($employeeIdToUserId);
            $this->importAccounts();
            $this->importSubscriptions();
            $this->importSubscriptionAllocations($employeeIdToUserId);
            $this->importHealthRecords();
            $this->importActivityLogs($employeeIdToUserId);
        });
    }

    /**
     * Departments in the dump: (1, Designer), (2, IT). Current DB already has
     * (1, Design). Match by name where possible, otherwise create; return a
     * map from dump department_id -> current departments.id.
     */
    private function importDepartments(): array
    {
        $dump = [
            1 => 'Designer',
            2 => 'IT',
        ];

        $map = [];

        foreach ($dump as $dumpId => $name) {
            $existing = DB::table('departments')->where('name', $name)->first();

            if ($existing) {
                $map[$dumpId] = $existing->id;
                continue;
            }

            $map[$dumpId] = DB::table('departments')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $map;
    }

    /**
     * Dump's `employees` table -> users, matched by email (mostly absent
     * here, so this mostly creates fresh users), department remapped via
     * $departmentIdMap. Returns dump employee_id -> users.id.
     */
    private function importEmployeesAsUsers(array $departmentIdMap): array
    {
        $dump = [
            1 => ['name' => 'Helmy', 'email' => null, 'department_id' => 2],
            2 => ['name' => 'Sonya', 'email' => null, 'department_id' => 1],
            3 => ['name' => 'Putra', 'email' => null, 'department_id' => 1],
            4 => ['name' => 'Zaldy', 'email' => null, 'department_id' => 1],
            5 => ['name' => 'Vania', 'email' => null, 'department_id' => 1],
            6 => ['name' => 'Feby', 'email' => null, 'department_id' => 1],
        ];

        $map = [];

        foreach ($dump as $dumpId => $employee) {
            $existing = null;

            if ($employee['email']) {
                $existing = DB::table('users')->where('email', $employee['email'])->first();
            }

            if (! $existing) {
                // No email in the dump to match on; fall back to matching an
                // existing user by name so re-running this seeder (or a DB
                // that already has these people) doesn't create duplicates.
                $existing = DB::table('users')->where('name', $employee['name'])->first();
            }

            if ($existing) {
                $map[$dumpId] = $existing->id;

                if (! $existing->department_id && isset($departmentIdMap[$employee['department_id']])) {
                    DB::table('users')->where('id', $existing->id)->update([
                        'department_id' => $departmentIdMap[$employee['department_id']],
                    ]);
                }

                continue;
            }

            $email = $employee['email'] ?: Str::slug($employee['name']).'-'.$dumpId.'@placeholder.local';

            $map[$dumpId] = DB::table('users')->insertGetId([
                'name' => $employee['name'],
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'department_id' => $departmentIdMap[$employee['department_id']] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $map;
    }

    private function importDevices(array $employeeIdToUserId): void
    {
        // [id, device_group, category, name, device_password, manufacturer, model,
        //  purchase_date, price, component_type, size, ram, rom, current_health,
        //  employee_id, parent_id, created_at, updated_at]
        $rows = [
            [3, 'Device', 'PC', 'Porta-PC-01', 'porta', '-', 'Generic PC', null, null, null, null, null, null, null, 2, null, '2026-03-06 00:38:52', '2026-06-07 19:19:15'],
            [4, 'Peripheral', 'Motherboard', 'PC-MOBO-01', null, 'ASRock', 'A320M-HDV R4', null, null, null, null, null, null, null, null, 3, '2026-03-06 00:39:55', '2026-06-02 00:48:52'],
            [5, 'Peripheral', 'Processor', 'PC-PROC-01', null, 'AMD', 'Ryzen 5 2600', null, null, null, null, null, null, null, 2, 3, '2026-03-06 00:40:24', '2026-05-10 23:25:11'],
            [6, 'Peripheral', 'RAM', 'PC-RAM-01', null, 'KLEVV', null, null, null, 'DDR4', '8 GB', null, null, null, 2, 3, '2026-03-06 00:41:14', '2026-05-10 23:52:13'],
            [7, 'Peripheral', 'RAM', 'PC-RAM-02', null, 'KLEVV', null, null, null, 'DDR 4', '8 GB', null, null, null, 2, 3, '2026-03-06 00:41:47', '2026-05-10 23:52:20'],
            [8, 'Peripheral', 'SSD', 'SSD-01', null, 'SK HYNIX', 'BC501 HFM256GDJT', null, null, 'M.2 NVMe', '256 GB', null, null, 83, 2, 3, '2026-03-06 00:44:59', '2026-03-26 20:37:06'],
            [9, 'Peripheral', 'HDD', 'HDD-01', null, 'Toshiba', 'HDWD220', null, null, 'Sata 3.5', '2 TB', null, null, 100, 2, 3, '2026-03-06 00:46:20', '2026-03-06 00:48:00'],
            [10, 'Peripheral', 'GPU', 'PC-GPU-01', null, 'Zotac', 'GTX 1050 Ti', null, null, null, '4 GB', null, null, null, 2, 3, '2026-03-06 00:47:13', '2026-06-03 02:24:35'],
            [11, 'Peripheral', 'Monitor', 'MON-04', null, 'Acer', 'EK240Y', null, null, null, '24 Inch', null, null, null, 2, 3, '2026-03-06 00:49:31', '2026-06-01 23:56:26'],
            [12, 'Peripheral', 'UPS', 'UPS-06', null, 'Acer', 'Altos City 120', '2026-02-20', null, null, '1200 VA', null, null, null, 2, null, '2026-03-11 19:06:00', '2026-06-01 23:45:26'],
            [13, 'Peripheral', 'Mouse', 'Mouse-01', null, 'Fantech', 'Crypto II', null, null, 'Wired', null, null, null, null, 2, 3, '2026-03-11 19:06:52', '2026-05-11 00:42:37'],
            [14, 'Peripheral', 'Keyboard', 'Keyboard-01', null, 'Logitech', 'K120', null, null, null, null, null, null, null, 2, 3, '2026-03-11 19:07:22', '2026-04-09 19:06:15'],
            [15, 'Peripheral', 'OS', 'OS-01', null, 'Microsoft', 'Windows 10', null, null, null, null, null, null, null, 2, 3, '2026-03-11 19:07:53', '2026-04-09 19:05:40'],
            [16, 'Device', 'Laptop', 'Porta-LP-31', null, 'Asus', 'Vivobook Go E1404FA', null, null, null, null, null, null, null, null, null, '2026-03-11 20:37:27', '2026-03-11 20:37:27'],
            [17, 'Device', 'PC', 'Porta-PC-02', null, '-', 'Generic PC', null, null, null, null, null, null, null, 3, null, '2026-04-09 19:12:15', '2026-04-09 19:12:15'],
            [21, 'Device', 'Phone', 'Porta-HP-01', '0000', 'Apple', 'Iphone 7', null, null, null, null, '2 GB', '128 GB', 77, null, null, '2026-05-10 20:18:12', '2026-05-10 21:09:23'],
            [22, 'Device', 'Laptop', 'Porta-LP-01', 'portabranding01', 'Lenovo', 'ThinkPad X260', null, null, null, null, null, null, null, null, null, '2026-05-10 23:14:54', '2026-05-10 23:14:54'],
            [23, 'Peripheral', 'Motherboard', 'LP-MOBO-01', null, 'Lenovo', '20F5S2NG00', null, null, null, null, null, null, null, null, 22, '2026-05-10 23:26:52', '2026-05-11 00:05:17'],
            [24, 'Peripheral', 'Processor', 'LP-PROC-01', null, 'Intel', 'Core i5-6300U', null, null, null, null, null, null, null, null, 22, '2026-05-10 23:51:44', '2026-05-11 00:05:23'],
            [25, 'Peripheral', 'RAM', 'LP-RAM-01', null, '-', '-', null, null, 'DDR 4', '8 GB', null, null, null, null, 22, '2026-05-10 23:53:38', '2026-05-11 00:05:27'],
            [26, 'Peripheral', 'SSD', 'SSD-17', null, 'V-Gen', null, null, null, 'Sata 2.5', '256 GB', null, null, 100, null, 22, '2026-05-10 23:56:14', '2026-05-11 00:05:32'],
            [27, 'Peripheral', 'GPU', 'LP-GPU-01', null, 'Intel', 'UHD 520', null, null, null, null, null, null, null, null, 22, '2026-05-11 00:15:33', '2026-05-11 00:38:26'],
            [28, 'Peripheral', 'Battery', 'LP-Batt-01', null, 'Lenovo', null, null, null, null, null, null, null, 5, null, 22, '2026-05-11 00:37:47', '2026-05-11 02:51:31'],
            [29, 'Peripheral', 'Power Supply', 'PSU-01', null, 'Acer Altos', 'City 120', null, null, null, '1200 V', null, null, null, 2, 3, '2026-05-11 00:40:59', '2026-06-03 00:37:30'],
            [30, 'Peripheral', 'UPS', 'UPS-01', null, 'APC', 'Back Ups 650', null, null, null, '650 V', null, null, null, 2, null, '2026-05-11 00:44:00', '2026-06-02 00:02:10'],
            [31, 'Peripheral', 'GPU', 'PC-GPU-02', null, 'Nvidia', 'GTX 1660', null, null, null, '6 GB', null, null, null, 3, 17, '2026-05-11 02:17:47', '2026-05-11 02:39:09'],
            [32, 'Peripheral', 'HDD', 'HDD-02', null, 'Toshiba', 'HDWD220', null, null, 'Sata 3.5', '2 TB', null, null, 100, 3, 17, '2026-05-11 02:21:37', '2026-05-11 02:39:24'],
            [33, 'Peripheral', 'HDD', 'HDD-03', null, 'Toshiba', 'DT01ACA100', null, null, 'Sata 3.5', '1 TB', null, null, 100, 3, 17, '2026-05-11 02:22:19', '2026-05-11 02:39:35'],
            [34, 'Peripheral', 'Keyboard', 'Keyboard-02', null, 'Logitech', 'K120', null, null, null, null, null, null, null, 3, 17, '2026-05-11 02:23:05', '2026-05-11 02:40:08'],
            [35, 'Peripheral', 'Monitor', 'MON-13', null, 'LG', '24MK430H', null, null, null, '24 Inch', null, null, null, 3, 17, '2026-05-11 02:25:05', '2026-05-11 02:40:19'],
            [36, 'Peripheral', 'Motherboard', 'PC-MOBO-02', null, 'Gigabyte', 'B550M Gaming', null, null, null, null, null, null, null, 3, 17, '2026-05-11 02:25:39', '2026-05-11 02:40:29'],
            [37, 'Peripheral', 'Mouse', 'Mouse-02', null, 'Fantech', 'Crypto VX7', null, null, 'Wired', null, null, null, null, 3, 17, '2026-05-11 02:26:30', '2026-05-11 02:40:42'],
            [38, 'Peripheral', 'Processor', 'PC-PROC-02', null, 'AMD', 'Ryzen 5 Pro 4650G', null, null, null, null, null, null, null, 3, 17, '2026-05-11 02:27:07', '2026-05-11 02:40:50'],
            [39, 'Peripheral', 'RAM', 'PC-RAM-03', null, 'Teamgroup', null, null, null, 'DDR4', '16 GB', null, null, null, 3, 17, '2026-05-11 02:28:15', '2026-05-11 02:41:01'],
            [40, 'Peripheral', 'RAM', 'PC-RAM-04', null, 'Teamgroup', null, null, null, 'DDR4', '16 GB', null, null, null, 3, 17, '2026-05-11 02:28:38', '2026-05-11 02:43:07'],
            [41, 'Peripheral', 'SSD', 'SSD-02', null, 'V-Gen', 'V-GEN 07SM19EG512GP3X4IT', null, null, 'M.2 NVMe 2280', '512 GB', null, null, 83, 3, 17, '2026-05-11 02:32:04', '2026-05-11 02:49:37'],
            [42, 'Peripheral', 'UPS', 'UPS-02', null, 'APC', 'Back Ups 650', null, null, null, '650 V', null, null, null, 3, 17, '2026-05-11 02:37:58', '2026-05-11 02:43:25'],
            [43, 'Device', 'Laptop', 'Porta-LP-02', null, 'Lenovo', 'Thinkpad X260', null, null, null, null, null, null, null, null, null, '2026-05-11 02:44:52', '2026-05-11 02:44:52'],
            [44, 'Peripheral', 'Motherboard', 'LP-MOBO-02', null, 'Lenovo', '20F5S77Y00', null, null, null, null, null, null, null, null, 43, '2026-05-11 02:45:27', '2026-06-01 21:16:38'],
            [45, 'Peripheral', 'Processor', 'LP-PROC-02', null, 'Intel', 'Core i5 6300U', null, null, null, null, null, null, null, null, 43, '2026-05-11 02:46:11', '2026-06-01 21:16:45'],
            [46, 'Peripheral', 'RAM', 'LP-RAM-02', null, null, null, null, null, 'DDR 4', '8 GB', null, null, null, null, 43, '2026-05-11 02:47:49', '2026-06-01 21:16:17'],
            [47, 'Peripheral', 'SSD', 'SSD-18', null, 'Adata', 'SU650', null, null, 'Sata 2.5', '256 GB', null, null, 80, null, null, '2026-05-11 02:49:14', '2026-05-11 02:49:14'],
            [48, 'Peripheral', 'Battery', 'LP-Batt-02', null, 'Lenovo', null, null, null, null, null, null, null, 42, null, 43, '2026-05-11 02:51:22', '2026-06-01 21:16:29'],
            [49, 'Device', 'Phone', 'Porta-HP-07', null, 'Apple', 'Iphone X', null, null, null, null, '3 GB', '256 GB', 80, null, null, '2026-05-11 02:54:46', '2026-05-11 02:56:05'],
            [50, 'Device', 'PC', 'Porta-PC-03', 'porta', null, 'Generic PC', null, null, null, null, null, null, null, 4, null, '2026-05-11 02:57:47', '2026-06-01 21:09:44'],
            [53, 'Peripheral', 'Motherboard', 'PC-MOBO-03', null, 'MSI', 'B650M Gaming Wifi (MS-7E30)', '2025-11-17', null, null, null, null, null, null, null, 50, '2026-06-01 19:30:34', '2026-06-01 21:10:12'],
            [54, 'Peripheral', 'Processor', 'PC-PROC-03', null, 'AMD', 'Ryzen 5 7500F', '2025-11-17', null, null, null, null, null, null, null, 50, '2026-06-01 19:31:44', '2026-06-01 21:11:41'],
            [55, 'Peripheral', 'RAM', 'PC-RAM-05', null, 'Micron', null, '2025-11-17', null, 'DDR5', '16 GB', null, null, null, null, 50, '2026-06-01 19:32:36', '2026-06-01 21:11:03'],
            [56, 'Peripheral', 'RAM', 'PC-RAM-06', null, 'Micron', null, '2025-11-17', null, 'DDR5', '16 GB', null, null, null, null, 50, '2026-06-01 19:33:45', '2026-06-01 21:11:16'],
            [57, 'Peripheral', 'SSD', 'SSD-03', null, 'Adata', 'Legend 710', '2025-11-17', null, 'M.2 NVME', '256 GB', null, null, 90, null, 50, '2026-06-01 19:37:47', '2026-06-01 21:10:50'],
            [58, 'Peripheral', 'HDD', 'HDD-04', null, 'Toshiba', 'HDWD220', null, null, 'Sata 3.5', '2 TB', null, null, 100, null, 50, '2026-06-01 19:38:38', '2026-06-01 21:12:15'],
            [59, 'Peripheral', 'HDD', 'HDD-05', null, 'Toshiba', 'HDWD240', null, null, 'Sata 3.5', '4 TB', null, null, 100, null, 50, '2026-06-01 19:39:13', '2026-06-01 21:12:24'],
            [60, 'Peripheral', 'GPU', 'PC-GPU-03', null, 'Nvidia', 'RTX 3060', null, null, null, '12 GB', null, null, null, null, 50, '2026-06-01 19:40:25', '2026-06-03 02:24:46'],
            [61, 'Peripheral', 'Monitor', 'MON-12', null, 'LG', 'LG24MK430H', null, null, null, '24 Inch', null, null, null, null, 50, '2026-06-01 19:45:16', '2026-06-01 21:12:48'],
            [62, 'Peripheral', 'UPS', 'UPS-03', null, 'Prolink', 'PRO1201SFCU-4U', null, null, null, '1200 VA', null, null, null, null, 50, '2026-06-01 19:47:08', '2026-06-01 21:10:37'],
            [63, 'Peripheral', 'Power Supply', 'PSU-02', null, null, null, null, null, null, null, null, null, null, null, 17, '2026-06-01 19:47:48', '2026-06-01 21:14:18'],
            [64, 'Peripheral', 'Power Supply', 'PSU-03', null, null, null, null, null, null, null, null, null, null, null, 50, '2026-06-01 19:48:00', '2026-06-01 21:13:05'],
            [65, 'Peripheral', 'Keyboard', 'Keyboard-03', null, 'Logitech', 'K120', null, null, null, null, null, null, null, null, 50, '2026-06-01 21:00:42', '2026-06-01 21:12:34'],
            [66, 'Device', 'PC', 'Porta-PC-04', null, null, null, '2025-11-17', null, null, null, null, null, null, 5, null, '2026-06-01 21:15:31', '2026-06-01 23:28:49'],
            [67, 'Peripheral', 'Motherboard', 'PC-MOBO-04', null, 'MSI', 'B550M Gaming', null, null, null, null, null, null, null, null, 66, '2026-06-01 21:19:46', '2026-06-01 23:29:44'],
            [68, 'Peripheral', 'Processor', 'PC-PROC-04', null, 'AMD', 'Ryzen 5 PRO 4650G', null, null, null, null, null, null, null, null, 66, '2026-06-01 21:20:27', '2026-06-01 23:30:38'],
            [69, 'Peripheral', 'RAM', 'PC-RAM-07', null, 'TeamGroup', null, null, null, 'DDR4', '16 GB', null, null, null, null, 66, '2026-06-01 21:21:11', '2026-06-01 23:30:48'],
            [70, 'Peripheral', 'RAM', 'PC-RAM-08', null, 'TeamGroup', null, null, null, 'DDR4', '16 GB', null, null, null, null, 66, '2026-06-01 21:21:39', '2026-06-01 23:30:58'],
            [71, 'Peripheral', 'SSD', 'SSD-04', null, 'Adata', 'SU650', null, null, 'Sata 2.5', '256 GB', null, null, 100, null, 66, '2026-06-01 21:22:38', '2026-06-01 23:31:10'],
            [72, 'Peripheral', 'HDD', 'HDD-06', null, 'Toshiba', 'HDWD240', null, null, 'Sata 3.5', '4 TB', null, null, 100, null, 66, '2026-06-01 21:23:53', '2026-06-01 23:29:13'],
            [73, 'Peripheral', 'Monitor', 'MON-11', null, 'Acer', 'EK240Y', null, null, null, '24 Inch', null, null, null, null, 66, '2026-06-01 21:27:17', '2026-06-01 23:29:35'],
            [74, 'Peripheral', 'UPS', 'UPS-04', null, 'ProLink', 'PRO700SFC', null, null, null, '650 V', null, null, null, null, 66, '2026-06-01 21:28:18', '2026-06-01 23:31:20'],
            [75, 'Peripheral', 'Power Supply', 'PSU-04', null, null, null, null, null, null, null, null, null, null, null, 66, '2026-06-01 21:40:02', '2026-06-01 23:30:04'],
            [76, 'Peripheral', 'Keyboard', 'Keyboard-04', null, 'Logitech', 'K120', null, null, null, null, null, null, null, null, 66, '2026-06-01 21:41:50', '2026-06-01 23:29:24'],
            [77, 'Peripheral', 'Mouse', 'Mouse-04', null, 'Fantech', 'Crypto II', null, null, 'Wired', null, null, null, null, null, 66, '2026-06-01 21:42:36', '2026-06-01 23:31:54'],
            [78, 'Device', 'PC', 'Porta-PC-05', null, null, null, null, null, null, null, null, null, null, null, null, '2026-06-03 02:18:25', '2026-06-03 02:18:25'],
            [79, 'Peripheral', 'Motherboard', 'PC-MOBO-05', null, 'Gigabyte Technology', 'Z390 AORUS ELITE-CF', null, null, null, null, null, null, null, null, 78, '2026-06-03 02:19:30', '2026-06-03 02:32:17'],
            [80, 'Peripheral', 'Processor', 'PC-PROC-05', null, 'Intel', 'Core i7 9700K', null, null, null, null, null, null, null, null, 78, '2026-06-03 02:20:12', '2026-06-03 02:33:05'],
            [81, 'Peripheral', 'RAM', 'PC-RAM-09', null, 'KLEVV', null, null, null, 'DDR4', '16 GB', null, null, null, null, 78, '2026-06-03 02:21:14', '2026-06-03 02:33:16'],
            [82, 'Peripheral', 'RAM', 'PC-RAM-10', null, 'Klevv', null, null, null, 'DDR4', '16 GB', null, null, null, null, 78, '2026-06-03 02:21:55', '2026-06-04 19:41:42'],
            [83, 'Peripheral', 'SSD', 'SSD-05', null, 'Adata', 'Legend 710', null, null, 'M.2 NVME', '512 GB', null, null, 98, null, 78, '2026-06-03 02:22:41', '2026-06-03 02:33:39'],
            [84, 'Peripheral', 'HDD', 'HDD-07', null, 'Seagate', 'ST4000DM004-2U9104', null, null, 'Sata 3.5', '4 TB', null, null, 100, null, 78, '2026-06-03 02:23:36', '2026-06-03 02:31:40'],
            [85, 'Peripheral', 'GPU', 'PC-GPU-04', null, 'Nvidia', 'GTX 1660', null, null, null, '6 GB', null, null, null, null, 78, '2026-06-03 02:24:24', '2026-06-03 02:31:23'],
            [86, 'Peripheral', 'Monitor', 'MON-10', null, 'LG', 'LG24MK430H', null, null, null, '24 Inch', null, null, null, null, 78, '2026-06-03 02:26:14', '2026-06-03 02:32:04'],
            [87, 'Peripheral', 'UPS', 'UPS-05', null, 'ProLink', 'Superfast', null, null, null, '650 V', null, null, null, null, 78, '2026-06-03 02:27:53', '2026-06-03 02:33:49'],
            [88, 'Peripheral', 'Power Supply', 'PSU-05', null, null, null, null, null, null, null, null, null, null, null, 78, '2026-06-03 02:28:11', '2026-06-03 02:32:53'],
            [89, 'Peripheral', 'Keyboard', 'Keyboard-05', null, 'Logitech', 'K120', null, null, null, null, null, null, null, null, 78, '2026-06-03 02:28:43', '2026-06-03 02:31:52'],
            [90, 'Peripheral', 'Mouse', 'Mouse-05', null, 'Fantech', 'Kanata VX9', null, null, 'Wired', null, null, null, null, null, 78, '2026-06-03 02:30:37', '2026-06-03 02:32:36'],
            [91, 'Device', 'PC', 'Porta-PC-06', 'porta', null, null, null, null, null, null, null, null, null, 6, null, '2026-06-04 19:20:19', '2026-06-07 18:58:31'],
            [92, 'Peripheral', 'Motherboard', 'PC-MOBO-06', null, 'MSI', '8450M-A PRO MAX (MS-7C52)', null, null, null, null, null, null, null, null, 91, '2026-06-04 19:37:04', '2026-06-07 18:49:26'],
            [93, 'Peripheral', 'Processor', 'PC-PROC-06', null, 'AMD', 'Ryzen 5 2400G', null, null, null, null, null, null, null, null, 91, '2026-06-04 19:38:28', '2026-06-07 18:55:37'],
            [94, 'Peripheral', 'RAM', 'PC-RAM-11', null, 'Corsair', null, null, null, 'DDR4', '16 GB', null, null, null, null, 91, '2026-06-04 19:41:17', '2026-06-07 18:55:46'],
            [95, 'Peripheral', 'SSD', 'SSD-06', null, 'Adata', 'SX8200PNP', null, null, 'M.2 NVME', '512 GB', null, null, 72, null, 91, '2026-06-04 19:55:21', '2026-06-07 18:55:55'],
            [96, 'Peripheral', 'HDD', 'HDD-08', null, 'Toshiba', 'HDWD120', null, null, 'Sata 3.5', '2 TB', null, null, 100, null, 91, '2026-06-04 20:06:05', '2026-06-07 18:48:46'],
            [97, 'Peripheral', 'Monitor', 'MON-07', null, 'LG', 'LG24MK430H', null, null, null, '24 Inch', null, null, null, null, 91, '2026-06-04 20:13:29', '2026-06-07 18:49:09'],
            [98, 'Peripheral', 'Power Supply', 'PSU-06', null, null, null, null, null, null, null, null, null, null, null, 91, '2026-06-07 18:45:30', '2026-06-07 18:55:21'],
            [99, 'Peripheral', 'Keyboard', 'Keyboard-06', null, 'Logitech', 'K120', null, null, null, null, null, null, null, null, 91, '2026-06-07 18:46:57', '2026-06-07 18:48:57'],
            [100, 'Peripheral', 'Mouse', 'Mouse-06', null, null, null, null, null, null, null, null, null, null, null, 91, '2026-06-07 18:47:30', '2026-06-07 18:55:11'],
            [101, 'Peripheral', 'SSD', 'Helmy', null, null, null, null, null, null, null, null, null, 100, 1, null, '2026-06-07 19:30:14', '2026-06-07 19:30:14'],
        ];

        foreach ($rows as $row) {
            [$id, $deviceGroup, $category, $name, $devicePassword, $manufacturer, $model,
                $purchaseDate, $price, $componentType, $size, $ram, $rom, $currentHealth,
                $employeeId, $parentId, $createdAt, $updatedAt] = $row;

            if (DB::table('devices')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('devices')->insert([
                'id' => $id,
                'device_group' => $deviceGroup,
                'category' => $category,
                'name' => $name,
                'device_password' => $devicePassword,
                'manufacturer' => $manufacturer,
                'model' => $model,
                'purchase_date' => $purchaseDate,
                'price' => $price,
                'component_type' => $componentType,
                'size' => $size,
                'ram' => $ram,
                'rom' => $rom,
                'current_health' => $currentHealth,
                'user_id' => $employeeId ? ($employeeIdToUserId[$employeeId] ?? null) : null,
                'parent_id' => $parentId,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }

    private function importAccounts(): void
    {
        $rows = [
            [1, 'portadesigner01@gmail.com', null, '2026-03-05 01:38:20', '2026-03-05 01:38:20'],
            [2, 'portadesigner07@gmail.com', null, '2026-03-11 19:43:40', '2026-03-11 19:43:40'],
            [3, 'asset@portabranding.com', null, '2026-03-11 20:44:06', '2026-05-11 02:08:24'],
        ];

        foreach ($rows as [$id, $email, $purpose, $createdAt, $updatedAt]) {
            if (DB::table('accounts')->where('id', $id)->exists() || DB::table('accounts')->where('email', $email)->exists()) {
                continue;
            }

            DB::table('accounts')->insert([
                'id' => $id,
                'email' => $email,
                'purpose' => $purpose,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }

    private function importSubscriptions(): void
    {
        $rows = [
            [1, 1, 'Adobe Photoshop & Lightroom', 'Yearly', 782000.00, 'IDR', '2026-03-19', '2026-03-05 01:54:05', '2026-03-11 00:15:08'],
            [3, 2, 'Adobe Photoshop & Lightroom', 'Yearly', 1579752.00, 'IDR', '2027-05-07', '2026-03-11 20:28:32', '2026-05-11 02:08:51'],
            [4, 2, 'Adobe Ilustrator', 'Yearly', 1722276.00, 'IDR', '2027-01-29', '2026-03-11 20:30:10', '2026-03-11 20:30:25'],
        ];

        foreach ($rows as [$id, $accountId, $plan, $period, $bill, $currency, $nextPaymentDate, $createdAt, $updatedAt]) {
            if (DB::table('subscriptions')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('subscriptions')->insert([
                'id' => $id,
                'account_id' => $accountId,
                'plan' => $plan,
                'period' => $period,
                'bill' => $bill,
                'currency' => $currency,
                'next_payment_date' => $nextPaymentDate,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }

    private function importSubscriptionAllocations(array $employeeIdToUserId): void
    {
        // [id, subscription_id, assignable_type, assignable_id, created_at, updated_at]
        // Dump's App\Models\Employee -> App\Models\User via the employee map.
        // App\Models\Device ids are preserved as-is (devices imported above).
        $rows = [
            [4, 1, 'App\\Models\\Employee', 2, '2026-03-08 19:25:08', '2026-03-08 19:25:08'],
            [5, 4, 'App\\Models\\Device', 3, '2026-03-11 20:31:26', '2026-03-11 20:31:26'],
            [6, 3, 'App\\Models\\Device', 3, '2026-03-11 20:31:52', '2026-03-11 20:31:52'],
            [7, 4, 'App\\Models\\Employee', 2, '2026-05-05 22:01:37', '2026-05-05 22:01:37'],
        ];

        foreach ($rows as [$id, $subscriptionId, $assignableType, $assignableId, $createdAt, $updatedAt]) {
            if (DB::table('subscription_allocations')->where('id', $id)->exists()) {
                continue;
            }

            if ($assignableType === 'App\\Models\\Employee') {
                $assignableType = 'App\\Models\\User';
                $assignableId = $employeeIdToUserId[$assignableId] ?? $assignableId;
            }

            DB::table('subscription_allocations')->insert([
                'id' => $id,
                'subscription_id' => $subscriptionId,
                'assignable_type' => $assignableType,
                'assignable_id' => $assignableId,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }

    private function importHealthRecords(): void
    {
        $rows = [
            [2, 8, '2026-03-27', 83, null, '2026-03-26 20:37:06', '2026-03-26 20:37:06'],
            [3, 49, '2025-12-30', 80, null, '2026-05-11 02:56:05', '2026-05-11 02:56:05'],
        ];

        foreach ($rows as [$id, $deviceId, $recordedDate, $healthPercentage, $notes, $createdAt, $updatedAt]) {
            if (DB::table('health_records')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('health_records')->insert([
                'id' => $id,
                'device_id' => $deviceId,
                'recorded_date' => $recordedDate,
                'health_percentage' => $healthPercentage,
                'notes' => $notes,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }

    private function importActivityLogs(array $employeeIdToUserId): void
    {
        // [id, device_id, employee_id, action, description, created_at, updated_at]
        // employee_id is NULL for every row in the dump, so this is a
        // straightforward column rename to user_id (still NULL).
        $rows = [
            [16, 4, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:09'],
            [17, 4, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:09', '2026-03-06 00:42:09'],
            [18, 3, null, 'Component Attached', 'Installed Motherboard: MOBO-01.', '2025-11-16 17:00:00', '2026-03-06 00:42:09'],
            [19, 5, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:22'],
            [20, 5, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:22', '2026-03-06 00:42:22'],
            [21, 3, null, 'Component Attached', 'Installed Processor: PROC-01.', '2025-11-16 17:00:00', '2026-03-06 00:42:22'],
            [22, 6, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:36'],
            [23, 6, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:36', '2026-03-06 00:42:36'],
            [24, 3, null, 'Component Attached', 'Installed RAM: RAM-01.', '2025-11-16 17:00:00', '2026-03-06 00:42:36'],
            [25, 7, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:42:49'],
            [26, 7, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:42:49', '2026-03-06 00:42:49'],
            [27, 3, null, 'Component Attached', 'Installed RAM: RAM-02.', '2025-11-16 17:00:00', '2026-03-06 00:42:49'],
            [28, 8, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:47:44'],
            [29, 8, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:47:44', '2026-03-06 00:47:44'],
            [30, 3, null, 'Component Attached', 'Installed SSD: SSD-01.', '2025-11-16 17:00:00', '2026-03-06 00:47:44'],
            [31, 9, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:48:00'],
            [32, 9, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:48:00', '2026-03-06 00:48:00'],
            [33, 3, null, 'Component Attached', 'Installed HDD: HDD-01.', '2025-11-16 17:00:00', '2026-03-06 00:48:00'],
            [34, 10, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-06 00:48:10'],
            [35, 10, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-06 00:48:10', '2026-03-06 00:48:10'],
            [36, 3, null, 'Component Attached', 'Installed GPU: GPU-01.', '2025-11-16 17:00:00', '2026-03-06 00:48:10'],
            [37, 11, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-03-10 17:00:00', '2026-03-10 23:55:35'],
            [38, 11, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-10 23:55:35', '2026-03-10 23:55:35'],
            [39, 3, null, 'Component Attached', 'Installed Monitor: MON-14.', '2026-03-10 17:00:00', '2026-03-10 23:55:35'],
            [40, 11, null, 'Transfer', 'Asset assigned from Sonya to STOCK.', '2026-03-10 23:56:08', '2026-03-10 23:56:08'],
            [41, 11, null, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2025-11-16 17:00:00', '2026-03-10 23:56:09'],
            [42, 3, null, 'Component Detached', 'Removed Monitor: MON-14.', '2025-11-16 17:00:00', '2026-03-10 23:56:09'],
            [43, 11, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-03-10 23:56:17'],
            [44, 11, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-10 23:56:17', '2026-03-10 23:56:17'],
            [45, 3, null, 'Component Attached', 'Installed Monitor: MON-14.', '2025-11-16 17:00:00', '2026-03-10 23:56:17'],
            [46, 14, null, 'Attach', 'Installed into main device: Porta-LP-31.', '2026-03-11 17:00:00', '2026-03-11 20:41:15'],
            [47, 16, null, 'Component Attached', 'Installed Keyboard: Keyboard-01.', '2026-03-11 17:00:00', '2026-03-11 20:41:15'],
            [48, 12, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-02-19 17:00:00', '2026-03-26 20:35:20'],
            [49, 12, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-03-26 20:35:20', '2026-03-26 20:35:20'],
            [50, 3, null, 'Component Attached', 'Installed UPS: UPS-01.', '2026-02-19 17:00:00', '2026-03-26 20:35:20'],
            [51, 8, null, 'Health Log: 83%', 'Diagnostic recorded.', '2026-03-26 17:00:00', '2026-03-26 20:37:06'],
            [52, 13, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-04-09 19:04:33'],
            [53, 13, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-04-09 19:04:33', '2026-04-09 19:04:33'],
            [54, 3, null, 'Component Attached', 'Installed Mouse: Mouse-01.', '2025-11-16 17:00:00', '2026-04-09 19:04:33'],
            [55, 15, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-04-09 19:05:40'],
            [56, 15, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-04-09 19:05:40', '2026-04-09 19:05:40'],
            [57, 3, null, 'Component Attached', 'Installed OS: OS-01.', '2025-11-16 17:00:00', '2026-04-09 19:05:40'],
            [58, 14, null, 'Detach', 'Removed from Porta-LP-31 and returned to STOCK.', '2026-04-09 17:00:00', '2026-04-09 19:06:01'],
            [59, 16, null, 'Component Detached', 'Removed Keyboard: Keyboard-01.', '2026-04-09 17:00:00', '2026-04-09 19:06:01'],
            [60, 14, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2025-11-16 17:00:00', '2026-04-09 19:06:15'],
            [61, 14, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-04-09 19:06:15', '2026-04-09 19:06:15'],
            [62, 3, null, 'Component Attached', 'Installed Keyboard: Keyboard-01.', '2025-11-16 17:00:00', '2026-04-09 19:06:15'],
            [63, 23, null, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:17'],
            [64, 22, null, 'Component Attached', 'Installed Motherboard: LP-MOBO-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:17'],
            [65, 24, null, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:23'],
            [66, 22, null, 'Component Attached', 'Installed Processor: LP-PROC-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:23'],
            [67, 25, null, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:27'],
            [68, 22, null, 'Component Attached', 'Installed RAM: LP-RAM-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:27'],
            [69, 26, null, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:05:32'],
            [70, 22, null, 'Component Attached', 'Installed SSD: SSD-17.', '2026-05-10 17:00:00', '2026-05-11 00:05:32'],
            [71, 27, null, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:26'],
            [72, 22, null, 'Component Attached', 'Installed GPU: LP-GPU-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:26'],
            [73, 28, null, 'Attach', 'Installed into main device: Porta-LP-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:29'],
            [74, 22, null, 'Component Attached', 'Installed Battery: LP-Batt-01.', '2026-05-10 17:00:00', '2026-05-11 00:38:29'],
            [75, 29, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-05-10 17:00:00', '2026-05-11 00:44:18'],
            [76, 29, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-05-11 00:44:18', '2026-05-11 00:44:18'],
            [77, 3, null, 'Component Attached', 'Installed Power Supply: PSU-01.', '2026-05-10 17:00:00', '2026-05-11 00:44:18'],
            [78, 30, null, 'Transfer', 'Asset assigned from STOCK to Sonya.', '2026-05-10 17:00:00', '2026-05-11 00:44:24'],
            [79, 30, null, 'Attach', 'Installed into main device: Porta-PC-01.', '2026-05-11 00:44:24', '2026-05-11 00:44:24'],
            [80, 3, null, 'Component Attached', 'Installed UPS: UPS-01.', '2026-05-10 17:00:00', '2026-05-11 00:44:24'],
            [81, 31, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:39:09'],
            [82, 31, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:39:09', '2026-05-11 02:39:09'],
            [83, 17, null, 'Component Attached', 'Installed GPU: PC-GPU-02.', '2025-11-16 17:00:00', '2026-05-11 02:39:09'],
            [84, 32, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-12-10 17:00:00', '2026-05-11 02:39:24'],
            [85, 32, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:39:24', '2026-05-11 02:39:24'],
            [86, 17, null, 'Component Attached', 'Installed HDD: HDD-02.', '2025-12-10 17:00:00', '2026-05-11 02:39:24'],
            [87, 33, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-12-10 17:00:00', '2026-05-11 02:39:35'],
            [88, 33, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:39:35', '2026-05-11 02:39:35'],
            [89, 17, null, 'Component Attached', 'Installed HDD: HDD-03.', '2025-12-10 17:00:00', '2026-05-11 02:39:35'],
            [90, 34, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-12-16 17:00:00', '2026-05-11 02:40:08'],
            [91, 34, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:08', '2026-05-11 02:40:08'],
            [92, 17, null, 'Component Attached', 'Installed Keyboard: Keyboard-02.', '2025-12-16 17:00:00', '2026-05-11 02:40:08'],
            [93, 35, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:19'],
            [94, 35, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:19', '2026-05-11 02:40:19'],
            [95, 17, null, 'Component Attached', 'Installed Monitor: MON-13.', '2025-11-16 17:00:00', '2026-05-11 02:40:19'],
            [96, 36, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:29'],
            [97, 36, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:29', '2026-05-11 02:40:29'],
            [98, 17, null, 'Component Attached', 'Installed Motherboard: PC-MOBO-02.', '2025-11-16 17:00:00', '2026-05-11 02:40:29'],
            [99, 37, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:42'],
            [100, 37, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:42', '2026-05-11 02:40:42'],
            [101, 17, null, 'Component Attached', 'Installed Mouse: Mouse-02.', '2025-11-16 17:00:00', '2026-05-11 02:40:42'],
            [102, 38, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:40:50'],
            [103, 38, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:40:50', '2026-05-11 02:40:50'],
            [104, 17, null, 'Component Attached', 'Installed Processor: PC-PROC-02.', '2025-11-16 17:00:00', '2026-05-11 02:40:50'],
            [105, 39, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:41:01'],
            [106, 39, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:41:01', '2026-05-11 02:41:01'],
            [107, 17, null, 'Component Attached', 'Installed RAM: PC-RAM-03.', '2025-11-16 17:00:00', '2026-05-11 02:41:01'],
            [108, 40, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:43:07'],
            [109, 40, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:43:07', '2026-05-11 02:43:07'],
            [110, 17, null, 'Component Attached', 'Installed RAM: PC-RAM-04.', '2025-11-16 17:00:00', '2026-05-11 02:43:07'],
            [111, 41, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:43:15'],
            [112, 41, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:43:15', '2026-05-11 02:43:15'],
            [113, 17, null, 'Component Attached', 'Installed SSD: SSD-02.', '2025-11-16 17:00:00', '2026-05-11 02:43:15'],
            [114, 42, null, 'Transfer', 'Asset assigned from STOCK to Putra.', '2025-11-16 17:00:00', '2026-05-11 02:43:25'],
            [115, 42, null, 'Attach', 'Installed into main device: Porta-PC-02.', '2026-05-11 02:43:25', '2026-05-11 02:43:25'],
            [116, 17, null, 'Component Attached', 'Installed UPS: UPS-02.', '2025-11-16 17:00:00', '2026-05-11 02:43:25'],
            [117, 49, null, 'Health Log: 80%', 'Diagnostic recorded.', '2025-12-29 17:00:00', '2026-05-11 02:56:05'],
            [118, 50, null, 'Transfer', 'Asset assigned from STOCK to Zaldy.', '2026-05-12 01:51:01', '2026-05-12 01:51:01'],
            [121, 53, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:30:34', '2026-06-01 19:30:34'],
            [122, 54, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:31:44', '2026-06-01 19:31:44'],
            [123, 55, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:32:36', '2026-06-01 19:32:36'],
            [124, 56, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:33:45', '2026-06-01 19:33:45'],
            [125, 57, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:37:47', '2026-06-01 19:37:47'],
            [126, 58, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:38:38', '2026-06-01 19:38:38'],
            [127, 59, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:39:13', '2026-06-01 19:39:13'],
            [128, 60, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:40:25', '2026-06-01 19:40:25'],
            [129, 61, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:45:16', '2026-06-01 19:45:16'],
            [130, 62, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:47:08', '2026-06-01 19:47:08'],
            [131, 63, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:47:48', '2026-06-01 19:47:48'],
            [132, 64, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 19:48:00', '2026-06-01 19:48:00'],
            [133, 65, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:00:42', '2026-06-01 21:00:42'],
            [134, 53, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:10:12', '2026-06-01 21:10:12'],
            [135, 50, null, 'Component Added', 'Installed Motherboard: PC-MOBO-03.', '2026-06-01 21:10:12', '2026-06-01 21:10:12'],
            [136, 50, null, 'Hardware Upgrade', 'PC-MOBO-03 (Motherboard) was installed.', '2026-11-16 17:00:00', '2026-06-01 21:10:12'],
            [137, 62, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:10:37', '2026-06-01 21:10:37'],
            [138, 50, null, 'Component Added', 'Installed UPS: UPS-03.', '2026-06-01 21:10:37', '2026-06-01 21:10:37'],
            [139, 50, null, 'Hardware Upgrade', 'UPS-03 (UPS) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:10:37'],
            [140, 57, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:10:50', '2026-06-01 21:10:50'],
            [141, 50, null, 'Component Added', 'Installed SSD: SSD-03.', '2026-06-01 21:10:50', '2026-06-01 21:10:50'],
            [142, 50, null, 'Hardware Upgrade', 'SSD-03 (SSD) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:10:50'],
            [143, 55, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:11:03', '2026-06-01 21:11:03'],
            [144, 50, null, 'Component Added', 'Installed RAM: PC-RAM-05.', '2026-06-01 21:11:03', '2026-06-01 21:11:03'],
            [145, 50, null, 'Hardware Upgrade', 'PC-RAM-05 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:11:03'],
            [146, 56, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:11:16', '2026-06-01 21:11:16'],
            [147, 50, null, 'Component Added', 'Installed RAM: PC-RAM-06.', '2026-06-01 21:11:16', '2026-06-01 21:11:16'],
            [148, 50, null, 'Hardware Upgrade', 'PC-RAM-06 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:11:16'],
            [149, 54, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:11:41', '2026-06-01 21:11:41'],
            [150, 50, null, 'Component Added', 'Installed Processor: PC-PROC-03.', '2026-06-01 21:11:41', '2026-06-01 21:11:41'],
            [151, 50, null, 'Hardware Upgrade', 'PC-PROC-03 (Processor) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:11:41'],
            [152, 60, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:11:51', '2026-06-01 21:11:51'],
            [153, 50, null, 'Component Added', 'Installed GPU: PC-GPU-03.', '2026-06-01 21:11:51', '2026-06-01 21:11:51'],
            [154, 50, null, 'Hardware Upgrade', 'PC-GPU-03 (GPU) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:11:51'],
            [155, 58, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:12:15', '2026-06-01 21:12:15'],
            [156, 50, null, 'Component Added', 'Installed HDD: HDD-04.', '2026-06-01 21:12:15', '2026-06-01 21:12:15'],
            [157, 50, null, 'Hardware Upgrade', 'HDD-04 (HDD) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:12:15'],
            [158, 59, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:12:24', '2026-06-01 21:12:24'],
            [159, 50, null, 'Component Added', 'Installed HDD: HDD-05.', '2026-06-01 21:12:24', '2026-06-01 21:12:24'],
            [160, 50, null, 'Hardware Upgrade', 'HDD-05 (HDD) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:12:24'],
            [161, 65, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:12:34', '2026-06-01 21:12:34'],
            [162, 50, null, 'Component Added', 'Installed Keyboard: Keyboard-03.', '2026-06-01 21:12:34', '2026-06-01 21:12:34'],
            [163, 50, null, 'Hardware Upgrade', 'Keyboard-03 (Keyboard) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:12:34'],
            [164, 61, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:12:48', '2026-06-01 21:12:48'],
            [165, 50, null, 'Component Added', 'Installed Monitor: MON-12.', '2026-06-01 21:12:48', '2026-06-01 21:12:48'],
            [166, 50, null, 'Hardware Upgrade', 'MON-12 (Monitor) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:12:48'],
            [167, 64, null, 'Attachment', 'Installed inside Porta-PC-03.', '2026-06-01 21:13:05', '2026-06-01 21:13:05'],
            [168, 50, null, 'Component Added', 'Installed Power Supply: PSU-03.', '2026-06-01 21:13:05', '2026-06-01 21:13:05'],
            [169, 50, null, 'Hardware Upgrade', 'PSU-03 (Power Supply) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:13:05'],
            [170, 63, null, 'Attachment', 'Installed inside Porta-PC-02.', '2026-06-01 21:14:18', '2026-06-01 21:14:18'],
            [171, 17, null, 'Component Added', 'Installed Power Supply: PSU-02.', '2026-06-01 21:14:18', '2026-06-01 21:14:18'],
            [172, 17, null, 'Hardware Upgrade', 'PSU-02 (Power Supply) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:14:18'],
            [173, 66, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:15:31', '2026-06-01 21:15:31'],
            [174, 46, null, 'Attachment', 'Installed inside Porta-LP-02.', '2026-06-01 21:16:17', '2026-06-01 21:16:17'],
            [175, 43, null, 'Component Added', 'Installed RAM: LP-RAM-02.', '2026-06-01 21:16:17', '2026-06-01 21:16:17'],
            [176, 43, null, 'Hardware Upgrade', 'LP-RAM-02 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:16:17'],
            [177, 48, null, 'Attachment', 'Installed inside Porta-LP-02.', '2026-06-01 21:16:29', '2026-06-01 21:16:29'],
            [178, 43, null, 'Component Added', 'Installed Battery: LP-Batt-02.', '2026-06-01 21:16:29', '2026-06-01 21:16:29'],
            [179, 43, null, 'Hardware Upgrade', 'LP-Batt-02 (Battery) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:16:29'],
            [180, 44, null, 'Attachment', 'Installed inside Porta-LP-02.', '2026-06-01 21:16:38', '2026-06-01 21:16:38'],
            [181, 43, null, 'Component Added', 'Installed Motherboard: LP-MOBO-02.', '2026-06-01 21:16:38', '2026-06-01 21:16:38'],
            [182, 43, null, 'Hardware Upgrade', 'LP-MOBO-02 (Motherboard) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:16:38'],
            [183, 45, null, 'Attachment', 'Installed inside Porta-LP-02.', '2026-06-01 21:16:45', '2026-06-01 21:16:45'],
            [184, 43, null, 'Component Added', 'Installed Processor: LP-PROC-02.', '2026-06-01 21:16:45', '2026-06-01 21:16:45'],
            [185, 43, null, 'Hardware Upgrade', 'LP-PROC-02 (Processor) was installed.', '2025-11-16 17:00:00', '2026-06-01 21:16:45'],
            [186, 67, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:19:46', '2026-06-01 21:19:46'],
            [187, 68, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:20:27', '2026-06-01 21:20:27'],
            [188, 69, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:21:11', '2026-06-01 21:21:11'],
            [189, 70, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:21:39', '2026-06-01 21:21:39'],
            [190, 71, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:22:38', '2026-06-01 21:22:38'],
            [191, 72, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:23:53', '2026-06-01 21:23:53'],
            [192, 73, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:27:17', '2026-06-01 21:27:17'],
            [193, 74, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:28:18', '2026-06-01 21:28:18'],
            [194, 75, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:40:02', '2026-06-01 21:40:02'],
            [195, 76, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:41:50', '2026-06-01 21:41:50'],
            [196, 77, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-01 21:42:36', '2026-06-01 21:42:36'],
            [197, 66, null, 'Reassigned', 'Assigned to Vania', '2026-06-01 23:28:49', '2026-06-01 23:28:49'],
            [198, 66, null, 'Deployment', 'Asset assigned to Vania.', '2026-06-01 23:28:49', '2026-06-01 23:28:49'],
            [199, 72, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:29:13', '2026-06-01 23:29:13'],
            [200, 66, null, 'Component Added', 'Installed HDD: HDD-06.', '2026-06-01 23:29:13', '2026-06-01 23:29:13'],
            [201, 66, null, 'Hardware Upgrade', 'HDD-06 (HDD) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:29:13'],
            [202, 76, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:29:24', '2026-06-01 23:29:24'],
            [203, 66, null, 'Component Added', 'Installed Keyboard: Keyboard-04.', '2026-06-01 23:29:24', '2026-06-01 23:29:24'],
            [204, 66, null, 'Hardware Upgrade', 'Keyboard-04 (Keyboard) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:29:24'],
            [205, 73, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:29:35', '2026-06-01 23:29:35'],
            [206, 66, null, 'Component Added', 'Installed Monitor: MON-11.', '2026-06-01 23:29:35', '2026-06-01 23:29:35'],
            [207, 66, null, 'Hardware Upgrade', 'MON-11 (Monitor) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:29:35'],
            [208, 67, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:29:44', '2026-06-01 23:29:44'],
            [209, 66, null, 'Component Added', 'Installed Motherboard: PC-MOBO-04.', '2026-06-01 23:29:44', '2026-06-01 23:29:44'],
            [210, 66, null, 'Hardware Upgrade', 'PC-MOBO-04 (Motherboard) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:29:44'],
            [211, 75, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:30:04', '2026-06-01 23:30:04'],
            [212, 66, null, 'Component Added', 'Installed Power Supply: PSU-04.', '2026-06-01 23:30:04', '2026-06-01 23:30:04'],
            [213, 66, null, 'Hardware Upgrade', 'PSU-04 (Power Supply) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:30:04'],
            [214, 68, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:30:38', '2026-06-01 23:30:38'],
            [215, 66, null, 'Component Added', 'Installed Processor: PC-PROC-04.', '2026-06-01 23:30:38', '2026-06-01 23:30:38'],
            [216, 66, null, 'Hardware Upgrade', 'PC-PROC-04 (Processor) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:30:38'],
            [217, 69, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:30:48', '2026-06-01 23:30:48'],
            [218, 66, null, 'Component Added', 'Installed RAM: PC-RAM-07.', '2026-06-01 23:30:48', '2026-06-01 23:30:48'],
            [219, 66, null, 'Hardware Upgrade', 'PC-RAM-07 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:30:48'],
            [220, 70, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:30:58', '2026-06-01 23:30:58'],
            [221, 66, null, 'Component Added', 'Installed RAM: PC-RAM-08.', '2026-06-01 23:30:58', '2026-06-01 23:30:58'],
            [222, 66, null, 'Hardware Upgrade', 'PC-RAM-08 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:30:58'],
            [223, 71, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:31:10', '2026-06-01 23:31:10'],
            [224, 66, null, 'Component Added', 'Installed SSD: SSD-04.', '2026-06-01 23:31:10', '2026-06-01 23:31:10'],
            [225, 66, null, 'Hardware Upgrade', 'SSD-04 (SSD) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:31:10'],
            [226, 74, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:31:20', '2026-06-01 23:31:20'],
            [227, 66, null, 'Component Added', 'Installed UPS: UPS-04.', '2026-06-01 23:31:20', '2026-06-01 23:31:20'],
            [228, 66, null, 'Hardware Upgrade', 'UPS-04 (UPS) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:31:20'],
            [229, 77, null, 'Attachment', 'Installed inside Porta-PC-04.', '2026-06-01 23:31:33', '2026-06-01 23:31:33'],
            [230, 66, null, 'Component Added', 'Installed Mouse: MON-04.', '2026-06-01 23:31:33', '2026-06-01 23:31:33'],
            [231, 66, null, 'Hardware Upgrade', 'MON-04 (Mouse) was installed.', '2025-11-16 17:00:00', '2026-06-01 23:31:33'],
            [232, 11, null, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2026-06-01 23:40:15', '2026-06-01 23:40:15'],
            [233, 3, null, 'Component Detached', 'Removed Monitor: MON-14.', '2026-06-01 23:40:15', '2026-06-01 23:40:15'],
            [234, 3, null, 'Hardware Removed', 'UPS-06 (UPS) was detached.', '2026-06-01 17:00:00', '2026-06-01 23:45:26'],
            [235, 12, null, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2026-06-01 23:45:26', '2026-06-01 23:45:26'],
            [236, 3, null, 'Component Detached', 'Removed UPS: UPS-06.', '2026-06-01 23:45:26', '2026-06-01 23:45:26'],
            [237, 11, null, 'Attachment', 'Installed inside Porta-PC-01.', '2026-06-01 23:56:26', '2026-06-01 23:56:26'],
            [238, 3, null, 'Component Added', 'Installed Monitor: MON-04.', '2026-06-01 23:56:26', '2026-06-01 23:56:26'],
            [239, 3, null, 'Hardware Removed', 'UPS-01 (UPS) was detached.', '2026-06-01 17:00:00', '2026-06-02 00:02:10'],
            [240, 30, null, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2026-06-02 00:02:10', '2026-06-02 00:02:10'],
            [241, 3, null, 'Component Detached', 'Removed UPS: UPS-01.', '2026-06-02 00:02:10', '2026-06-02 00:02:10'],
            [242, 3, null, 'Hardware Removed', 'PC-MOBO-01 (Motherboard) was detached.', '2026-06-01 17:00:00', '2026-06-02 00:47:23'],
            [243, 4, null, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2026-06-02 00:47:23', '2026-06-02 00:47:23'],
            [244, 3, null, 'Component Detached', 'Removed Motherboard: PC-MOBO-01.', '2026-06-02 00:47:23', '2026-06-02 00:47:23'],
            [245, 4, null, 'Returned to Stock', 'Asset ownership removed.', '2026-06-02 00:48:30', '2026-06-02 00:48:30'],
            [246, 4, null, 'Attachment', 'Installed inside Porta-PC-01.', '2026-06-02 00:48:30', '2026-06-02 00:48:30'],
            [247, 3, null, 'Component Added', 'Installed Motherboard: PC-MOBO-01.', '2026-06-02 00:48:30', '2026-06-02 00:48:30'],
            [248, 3, null, 'Hardware Removed', 'PC-MOBO-01 (Motherboard) was detached.', '2026-06-01 17:00:00', '2026-06-02 00:48:45'],
            [249, 4, null, 'Detach', 'Removed from Porta-PC-01 and returned to STOCK.', '2026-06-02 00:48:45', '2026-06-02 00:48:45'],
            [250, 3, null, 'Component Detached', 'Removed Motherboard: PC-MOBO-01.', '2026-06-02 00:48:45', '2026-06-02 00:48:45'],
            [251, 4, null, 'Attachment', 'Installed inside Porta-PC-01.', '2026-06-02 00:48:52', '2026-06-02 00:48:52'],
            [252, 3, null, 'Component Added', 'Installed Motherboard: PC-MOBO-01.', '2026-06-02 00:48:52', '2026-06-02 00:48:52'],
            [253, 3, null, 'Hardware Upgrade', 'PC-MOBO-01 (Motherboard) was installed.', '2026-06-01 17:00:00', '2026-06-02 00:48:52'],
            [254, 78, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:18:25', '2026-06-03 02:18:25'],
            [255, 79, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:19:30', '2026-06-03 02:19:30'],
            [256, 80, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:20:12', '2026-06-03 02:20:12'],
            [257, 81, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:21:14', '2026-06-03 02:21:14'],
            [258, 82, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:21:55', '2026-06-03 02:21:55'],
            [259, 83, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:22:41', '2026-06-03 02:22:41'],
            [260, 84, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:23:36', '2026-06-03 02:23:36'],
            [261, 85, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:24:24', '2026-06-03 02:24:24'],
            [262, 86, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:26:14', '2026-06-03 02:26:14'],
            [263, 87, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:27:53', '2026-06-03 02:27:53'],
            [264, 88, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:28:11', '2026-06-03 02:28:11'],
            [265, 89, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:28:43', '2026-06-03 02:28:43'],
            [266, 90, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-03 02:30:37', '2026-06-03 02:30:37'],
            [267, 85, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:31:23', '2026-06-03 02:31:23'],
            [268, 78, null, 'Component Added', 'Installed GPU: PC-GPU-04.', '2026-06-03 02:31:23', '2026-06-03 02:31:23'],
            [269, 78, null, 'Hardware Upgrade', 'PC-GPU-04 (GPU) was installed.', '2026-06-02 17:00:00', '2026-06-03 02:31:23'],
            [270, 84, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:31:40', '2026-06-03 02:31:40'],
            [271, 78, null, 'Component Added', 'Installed HDD: HDD-07.', '2026-06-03 02:31:40', '2026-06-03 02:31:40'],
            [272, 78, null, 'Hardware Upgrade', 'HDD-07 (HDD) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:31:40'],
            [273, 89, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:31:52', '2026-06-03 02:31:52'],
            [274, 78, null, 'Component Added', 'Installed Keyboard: Keyboard-05.', '2026-06-03 02:31:52', '2026-06-03 02:31:52'],
            [275, 78, null, 'Hardware Upgrade', 'Keyboard-05 (Keyboard) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:31:52'],
            [276, 86, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:32:04', '2026-06-03 02:32:04'],
            [277, 78, null, 'Component Added', 'Installed Monitor: MON-10.', '2026-06-03 02:32:04', '2026-06-03 02:32:04'],
            [278, 78, null, 'Hardware Upgrade', 'MON-10 (Monitor) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:32:04'],
            [279, 79, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:32:17', '2026-06-03 02:32:17'],
            [280, 78, null, 'Component Added', 'Installed Motherboard: PC-MOBO-05.', '2026-06-03 02:32:17', '2026-06-03 02:32:17'],
            [281, 78, null, 'Hardware Upgrade', 'PC-MOBO-05 (Motherboard) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:32:17'],
            [282, 90, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:32:36', '2026-06-03 02:32:36'],
            [283, 78, null, 'Component Added', 'Installed Mouse: Mouse-05.', '2026-06-03 02:32:36', '2026-06-03 02:32:36'],
            [284, 78, null, 'Hardware Upgrade', 'Mouse-05 (Mouse) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:32:36'],
            [285, 88, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:32:53', '2026-06-03 02:32:53'],
            [286, 78, null, 'Component Added', 'Installed Power Supply: PSU-05.', '2026-06-03 02:32:53', '2026-06-03 02:32:53'],
            [287, 78, null, 'Hardware Upgrade', 'PSU-05 (Power Supply) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:32:53'],
            [288, 80, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:33:05', '2026-06-03 02:33:05'],
            [289, 78, null, 'Component Added', 'Installed Processor: PC-PROC-05.', '2026-06-03 02:33:05', '2026-06-03 02:33:05'],
            [290, 78, null, 'Hardware Upgrade', 'PC-PROC-05 (Processor) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:33:05'],
            [291, 81, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:33:16', '2026-06-03 02:33:16'],
            [292, 78, null, 'Component Added', 'Installed RAM: PC-RAM-09.', '2026-06-03 02:33:16', '2026-06-03 02:33:16'],
            [293, 78, null, 'Hardware Upgrade', 'PC-RAM-09 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:33:16'],
            [294, 82, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:33:24', '2026-06-03 02:33:24'],
            [295, 78, null, 'Component Added', 'Installed RAM: PC-RAM-10.', '2026-06-03 02:33:24', '2026-06-03 02:33:24'],
            [296, 78, null, 'Hardware Upgrade', 'PC-RAM-10 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:33:24'],
            [297, 83, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:33:39', '2026-06-03 02:33:39'],
            [298, 78, null, 'Component Added', 'Installed SSD: SSD-05.', '2026-06-03 02:33:39', '2026-06-03 02:33:39'],
            [299, 78, null, 'Hardware Upgrade', 'SSD-05 (SSD) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:33:39'],
            [300, 87, null, 'Attachment', 'Installed inside Porta-PC-05.', '2026-06-03 02:33:49', '2026-06-03 02:33:49'],
            [301, 78, null, 'Component Added', 'Installed UPS: UPS-05.', '2026-06-03 02:33:49', '2026-06-03 02:33:49'],
            [302, 78, null, 'Hardware Upgrade', 'UPS-05 (UPS) was installed.', '2025-11-16 17:00:00', '2026-06-03 02:33:49'],
            [303, 91, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 19:20:19', '2026-06-04 19:20:19'],
            [304, 92, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 19:37:04', '2026-06-04 19:37:04'],
            [305, 93, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 19:38:28', '2026-06-04 19:38:28'],
            [306, 94, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 19:41:17', '2026-06-04 19:41:17'],
            [307, 95, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 19:55:21', '2026-06-04 19:55:21'],
            [308, 96, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 20:06:05', '2026-06-04 20:06:05'],
            [309, 97, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-04 20:13:29', '2026-06-04 20:13:29'],
            [310, 98, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-07 18:45:30', '2026-06-07 18:45:30'],
            [311, 99, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-07 18:46:57', '2026-06-07 18:46:57'],
            [312, 100, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-07 18:47:30', '2026-06-07 18:47:30'],
            [313, 96, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:48:46', '2026-06-07 18:48:46'],
            [314, 91, null, 'Component Added', 'Installed HDD: HDD-08.', '2026-06-07 18:48:46', '2026-06-07 18:48:46'],
            [315, 91, null, 'Hardware Upgrade', 'HDD-08 (HDD) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:48:46'],
            [316, 99, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:48:57', '2026-06-07 18:48:57'],
            [317, 91, null, 'Component Added', 'Installed Keyboard: Keyboard-06.', '2026-06-07 18:48:57', '2026-06-07 18:48:57'],
            [318, 91, null, 'Hardware Upgrade', 'Keyboard-06 (Keyboard) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:48:57'],
            [319, 97, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:49:09', '2026-06-07 18:49:09'],
            [320, 91, null, 'Component Added', 'Installed Monitor: MON-07.', '2026-06-07 18:49:09', '2026-06-07 18:49:09'],
            [321, 91, null, 'Hardware Upgrade', 'MON-07 (Monitor) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:49:09'],
            [322, 92, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:49:26', '2026-06-07 18:49:26'],
            [323, 91, null, 'Component Added', 'Installed Motherboard: PC-MOBO-06.', '2026-06-07 18:49:26', '2026-06-07 18:49:26'],
            [324, 91, null, 'Hardware Upgrade', 'PC-MOBO-06 (Motherboard) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:49:26'],
            [325, 100, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:55:11', '2026-06-07 18:55:11'],
            [326, 91, null, 'Component Added', 'Installed Mouse: Mouse-06.', '2026-06-07 18:55:11', '2026-06-07 18:55:11'],
            [327, 91, null, 'Hardware Upgrade', 'Mouse-06 (Mouse) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:55:11'],
            [328, 98, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:55:21', '2026-06-07 18:55:21'],
            [329, 91, null, 'Component Added', 'Installed Power Supply: PSU-06.', '2026-06-07 18:55:21', '2026-06-07 18:55:21'],
            [330, 91, null, 'Hardware Upgrade', 'PSU-06 (Power Supply) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:55:21'],
            [331, 93, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:55:37', '2026-06-07 18:55:37'],
            [332, 91, null, 'Component Added', 'Installed Processor: PC-PROC-06.', '2026-06-07 18:55:37', '2026-06-07 18:55:37'],
            [333, 91, null, 'Hardware Upgrade', 'PC-PROC-06 (Processor) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:55:37'],
            [334, 94, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:55:46', '2026-06-07 18:55:46'],
            [335, 91, null, 'Component Added', 'Installed RAM: PC-RAM-11.', '2026-06-07 18:55:46', '2026-06-07 18:55:46'],
            [336, 91, null, 'Hardware Upgrade', 'PC-RAM-11 (RAM) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:55:46'],
            [337, 95, null, 'Attachment', 'Installed inside Porta-PC-06.', '2026-06-07 18:55:55', '2026-06-07 18:55:55'],
            [338, 91, null, 'Component Added', 'Installed SSD: SSD-06.', '2026-06-07 18:55:55', '2026-06-07 18:55:55'],
            [339, 91, null, 'Hardware Upgrade', 'SSD-06 (SSD) was installed.', '2025-11-16 17:00:00', '2026-06-07 18:55:55'],
            [340, 91, null, 'Reassigned', 'Assigned to Feby', '2026-06-07 18:58:31', '2026-06-07 18:58:31'],
            [341, 91, null, 'Deployment', 'Asset assigned to Feby.', '2026-06-07 18:58:31', '2026-06-07 18:58:31'],
            [342, 101, null, 'Asset Registered', 'Asset was added to the inventory.', '2026-06-07 19:30:14', '2026-06-07 19:30:14'],
        ];

        foreach ($rows as [$id, $deviceId, $employeeId, $action, $description, $createdAt, $updatedAt]) {
            if (DB::table('activity_logs')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('activity_logs')->insert([
                'id' => $id,
                'device_id' => $deviceId,
                'user_id' => $employeeId ? ($employeeIdToUserId[$employeeId] ?? null) : null,
                'action' => $action,
                'description' => $description,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }
    }
}
