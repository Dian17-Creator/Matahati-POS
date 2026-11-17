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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromView, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    /**
     * @param Collection<int, \App\Models\MposProduct> $products
     */
    public function __construct(
        protected Collection $products
    ) {}

    public function view(): View
    {
        return view('products.excel', [
            'products' => $this->products,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Filter otomatis pada baris header (Baris ke-1, kolom A sampai K)
        $sheet->setAutoFilter('A1:K1');

        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->calculateColumnWidths();

                $minWidths = [
                    'A' => 30, // Nama Produk
                    'B' => 18, // Kategori
                    'C' => 22, // Outlet
                    'D' => 22, // Tipe Produk
                    'E' => 18, // Harga Beli
                    'F' => 18, // Harga POS
                    'G' => 18, // Harga Online
                    'H' => 16, // Stok / Qty
                    'I' => 14, // Satuan
                    'J' => 14, // Status
                    'K' => 14, // Kombo
                ];

                foreach (range('A', 'K') as $col) {
                    $dimension = $sheet->getColumnDimension($col);
                    $calculatedWidth = $dimension->getWidth();
                    $minWidth = $minWidths[$col] ?? 14;

                    $finalWidth = max($calculatedWidth + 5, $minWidth);

                    $dimension->setAutoSize(false);
                    $dimension->setWidth($finalWidth);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'product';
    }
}
