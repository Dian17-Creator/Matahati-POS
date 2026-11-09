<?php

namespace App\Exports;

use App\Models\MposStockOpnameH;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOpnameDetailExport implements FromView, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    public function __construct(
        protected MposStockOpnameH $opname
    ) {}

    public function view(): View
    {
        return view('inventory.excel.stock_opname_detail', [
            'opname' => $this->opname,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Filter otomatis pada baris header (Baris ke-1, kolom A sampai H)
        $sheet->setAutoFilter('A1:H1');

        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Hitung lebar otomatis berdasarkan teks isi terlebih dahulu
                $sheet->calculateColumnWidths();

                // Beri ruang ekstra pada setiap kolom agar icon dropdown sorting/filter
                // tidak menutupi teks header (format: TEXT [ Sorting ])
                $minWidths = [
                    'A' => 28, // Nama
                    'B' => 16, // Qty System
                    'C' => 16, // Qty Aktual
                    'D' => 16, // Qty Selisih
                    'E' => 20, // Harga Beli Produk
                    'F' => 20, // Harga Jual Produk
                    'G' => 18, // Dibuat Oleh
                    'H' => 24, // Dibuat Pada
                ];

                foreach (range('A', 'H') as $col) {
                    $dimension = $sheet->getColumnDimension($col);
                    $calculatedWidth = $dimension->getWidth();
                    $minWidth = $minWidths[$col] ?? 14;

                    $finalWidth = max($calculatedWidth + 5, $minWidth);

                    // Matikan auto-size bawaan agar PhpSpreadsheet menggunakan nilai lebar yang sudah ditambah margin
                    $dimension->setAutoSize(false);
                    $dimension->setWidth($finalWidth);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Stok Opname - ' . $this->opname->cno_opname;
    }
}

