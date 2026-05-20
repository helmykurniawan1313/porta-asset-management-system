<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ComponentsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $category;
    protected $items;

    public function __construct($category, $items)
    {
        $this->category = $category;
        $this->items = $items;
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return ['Asset Name', 'Manufacturer', 'Model', 'Type', 'Size', 'Health %', 'Purchase Date', 'Price', 'Image 1', 'Image 2', 'Image 3', 'Installed In'];
    }

    public function map($item): array
    {
        return [
            $item->name,
            $item->manufacturer ?? '-',
            $item->model ?? '-',
            $item->component_type ?? '-',
            $item->size ?? '-',
            $item->current_health ? $item->current_health . '%' : '-',
            ($item->purchase_date instanceof \DateTimeInterface) ? $item->purchase_date->format('d M Y') : ($item->purchase_date ?? '-'),
            is_numeric($item->price) ? number_format($item->price, 2) : ($item->price ?? '-'),
            $item->image1 ?? '-',
            $item->image2 ?? '-',
            $item->image3 ?? '-',
            $item->parentDevice->name ?? 'STOCK',
        ];
    }

    public function title(): string
    {
        // Excel breaks if a tab name is longer than 31 characters
        // Forbidden characters: / \ ? * : [ ]
        $safeName = str_replace(['/', '\\', '?', '*', ':', '[', ']'], '', $this->category);
        return substr($safeName, 0, 31);
    }
}