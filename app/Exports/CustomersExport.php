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

class CustomersExport implements FromView, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    /**
     * @param Collection<int, \App\Models\MposCust> $customers
     */
    public function __construct(
        protected Collection $customers
    ) {}

    public function view(): View
    {
        return view('customers.excel', [
            'customers' => $this->customers,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setAutoFilter('A1:Q1');

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
                    'A' => 26, // Nama Pelanggan
                    'B' => 22, // Outlet
                    'C' => 14, // Status
                    'D' => 16, // Tipe
                    'E' => 16, // No Member
                    'F' => 14, // Gender
                    'G' => 18, // No HP
                    'H' => 28, // Email
                    'I' => 18, // Tanggal Lahir
                    'J' => 28, // Alamat
                    'K' => 14, // Kode Pos
                    'L' => 16, // Kota
                    'M' => 16, // Kecamatan
                    'N' => 16, // Provinsi
                    'O' => 14, // Negara
                    'P' => 22, // Catatan
                    'Q' => 22, // Transaksi Terakhir
                ];

                foreach (range('A', 'Q') as $col) {
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
        return 'customer';
    }
}
