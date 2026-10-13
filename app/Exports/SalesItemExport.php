<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesItemExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected $items;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $outletName;

    public function __construct($items, $summary, $startDate, $endDate, $outletName = 'Semua Outlet')
    {
        $this->items = $items;
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->outletName = $outletName;
    }

    public function view(): View
    {
        return view('reports.sales_item.excel', [
            'items' => $this->items,
            'summary' => $this->summary,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'outletName' => $this->outletName,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Filter otomatis pada baris header (Baris ke-3, kolom A sampai K)
        $sheet->setAutoFilter('A3:K3');

        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Item Penjualan';
    }
}
