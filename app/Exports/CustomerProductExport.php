<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerProductExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $startDate;
    protected $endDate;

    public function __construct($data, $startDate, $endDate)
    {
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $grandTotals = [
            'qty' => 0,
            'total_penjualan' => 0,
            'diskon' => 0,
            'modal' => 0,
            'laba' => 0,
            'jml_transaksi' => 0
        ];

        foreach ($this->data as $row) {
            $grandTotals['qty'] += $row->qty;
            $grandTotals['total_penjualan'] += $row->total_penjualan;
            $grandTotals['diskon'] += $row->diskon;
            $grandTotals['modal'] += 0; // Assuming modal is 0
            $grandTotals['laba'] += ($row->total_penjualan - $row->diskon - 0);
            $grandTotals['jml_transaksi'] += $row->jml_transaksi;
        }

        return view('reports.customer_product.excel', [
            'data' => $this->data,
            'grandTotals' => $grandTotals,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
            2    => ['font' => ['bold' => true]],
            3    => ['font' => ['bold' => true]],
            4    => ['font' => ['bold' => true]],
        ];
    }
}
