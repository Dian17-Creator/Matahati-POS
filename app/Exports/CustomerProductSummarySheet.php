<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerProductSummarySheet implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected $data;
    protected $grandTotals;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $grandTotals, $startDate, $endDate)
    {
        $this->data = $data;
        $this->grandTotals = $grandTotals;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        return view('reports.customer_product.excel_summary', [
            'data' => $this->data,
            'grandTotals' => $this->grandTotals,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Tambahkan filter otomatis pada baris header (Baris ke-3, dari kolom A sampai G)
        $sheet->setAutoFilter('A3:G3');

        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Rangkuman Pelanggan';
    }
}
