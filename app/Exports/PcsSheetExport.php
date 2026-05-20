<?php

namespace App\Exports;

use App\Models\Device;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PcsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $peripherals;
    protected $pcs;
    protected $categories = [];
    protected $maxCounts = [];
    protected array $peripheralIndexMap = [];

    public function __construct($peripherals)
    {
        $this->peripherals = $peripherals;
        
        // 1. Fetch the PCs IMMEDIATELY so we can count their components before Excel asks for the headers
        $this->pcs = Device::whereIn('category', ['PC', 'Laptop'])->with(['components', 'employee'])->get();
        
        $catArray = [];

        // 2. Loop through every PC to find out the absolute MAXIMUM number of RAMs, HDDs, etc.
        foreach ($this->pcs as $pc) {
            $countsForThisPc = [];
            
            foreach ($pc->components as $comp) {
                $cat = strtoupper(trim($comp->category));
                $comp->category = $cat; // Fix formatting in memory
                
                // Track unique categories
                if (!in_array($cat, $catArray)) {
                    $catArray[] = $cat;
                }
                
                // Count how many of this specific component this specific PC has
                $countsForThisPc[$cat] = isset($countsForThisPc[$cat]) ? $countsForThisPc[$cat] + 1 : 1;
            }
            
            // Update the global maximums (e.g., if this PC has 3 HDDs, the max is now 3)
            foreach ($countsForThisPc as $cat => $count) {
                if (!isset($this->maxCounts[$cat]) || $count > $this->maxCounts[$cat]) {
                    $this->maxCounts[$cat] = $count;
                }
            }
        }

        // Pre-calculate indices for performance
        foreach ($peripherals as $cat => $items) {
            foreach ($items as $index => $item) {
                $this->peripheralIndexMap[$cat][$item->id] = $index + 2;
            }
        }

        // 3. Make sure empty stock categories still get a column
        $peripheralKeys = $peripherals->keys()->toArray();
        foreach ($peripheralKeys as $pkey) {
            if (!in_array($pkey, $catArray)) {
                $catArray[] = $pkey;
            }
        }

        sort($catArray); // Sort alphabetically (HDD, MONITOR, RAM)
        $this->categories = $catArray;

        // Ensure every category gets at least 1 column, even if no PC currently has it
        foreach ($this->categories as $cat) {
            if (!isset($this->maxCounts[$cat])) {
                $this->maxCounts[$cat] = 1;
            }
        }
    }

    public function collection()
    {
        // Simply return the PCs we already fetched
        return $this->pcs;
    }

    public function headings(): array
    {
        $headers = ['Asset Name', 'Assigned User'];
        
        // Build dynamic headers (e.g., "RAM 1", "RAM 2", "HDD 1")
        foreach ($this->categories as $cat) {
            $max = $this->maxCounts[$cat];
            for ($i = 1; $i <= $max; $i++) {
                $headerName = $max > 1 ? $cat . ' ' . $i : $cat;
                $headers[] = $headerName;
                // REMOVED: Price header creation
            }
        }
        return $headers;
    }

    public function map($pc): array
    {
        $row = [
            $pc->name,
            $pc->employee?->name ?? 'STOCK'
        ];

        foreach ($this->categories as $cat) {
            $max = $this->maxCounts[$cat];
            
            // Grab all components of this type attached to this specific PC
            $pcComponents = $pc->components->where('category', $cat)->values();

            // Fill out the row up to the maximum count we calculated earlier
            for ($i = 0; $i < $max; $i++) {
                if (isset($pcComponents[$i])) {
                    $comp = $pcComponents[$i];
                    
                    // Hyperlink to peripheral sheets logic
                    $rowIndex = $this->peripheralIndexMap[$cat][$comp->id] ?? null;
                    
                    if ($rowIndex !== null) {
                        $escapedName = str_replace('"', '""', $comp->name);
                        $safeSheetName = substr(str_replace(['/', '\\', '?', '*', ':', '[', ']'], '', $cat), 0, 31);
                        $row[] = '=HYPERLINK("#\''.$safeSheetName.'\'!A'.$rowIndex.'", "'.$escapedName.'")';
                    } else {
                        $row[] = $comp->name;
                    }
                    // REMOVED: Component Price row mapping
                } else {
                    // If this PC has fewer parts than the max, print a dash (-)
                    $row[] = '-';
                    // REMOVED: The second dash that was holding the empty space for the price column
                }
            }
        }

        return $row;
    }

    public function title(): string
    {
        return 'Master PC List';
    }
}