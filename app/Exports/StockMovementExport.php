<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementExport implements FromView, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    /**
     * @param Collection $movements
     * @param string $startDate
     * @param string $endDate
     * @param string $outletName
     */
    public function __construct(
        protected Collection $movements,
        protected string $startDate,
        protected string $endDate,
        protected string $outletName = 'Semua Outlet'
    ) {}

    public function view(): View
    {
        return view('inventory.excel.stock_movement', [
            'movements' => $this->movements,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'outletName' => $this->outletName,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Filter otomatis pada baris header (Baris ke-1, kolom A sampai J)
        $sheet->setAutoFilter('A1:J1');

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
                    'A' => 18, // Grup / Kategori
                    'B' => 25, // Produk
                    'C' => 16, // Stok Awal
                    'D' => 16, // Stok Masuk
                    'E' => 18, // Pengembalian
                    'F' => 16, // Penjualan
                    'G' => 16, // Stok Keluar
                    'H' => 14, // Sisa
                    'I' => 18, // Dibuat Oleh
                    'J' => 28, // Dibuat Tanggal dan Jam
                ];

                foreach (range('A', 'J') as $col) {
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
        return 'Pergerakan Stok';
    }
}

