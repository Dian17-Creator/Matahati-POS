<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder,
    WithStyles,
    WithTitle,
    WithEvents
{
    /**
     * @param Collection<int, \App\Models\MposCust> $customers
     */
    public function __construct(
        protected Collection $customers
    ) {}

    public function collection(): Collection
    {
        return $this->customers;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Nama Pelanggan',
            'Outlet',
            'Status',
            'Tipe',
            'No Member',
            'Gender',
            'No HP',
            'Email',
            'Tanggal Lahir',
            'Alamat',
            'Kode Pos',
            'Kota',
            'Kecamatan',
            'Provinsi',
            'Negara',
            'Catatan',
            'Transaksi Terakhir',
            'Total Transaksi',
        ];
    }

    /**
     * @param \App\Models\MposCust $customer
     * @return array<int, mixed>
     */
    public function map(mixed $customer): array
    {
        $lastTrx = $customer->dlast_transaction ?: $customer->sales_max_dtransaction;

        return [
            $customer->cname,
            $customer->outlet?->cname ?? '-',
            $customer->factive ? 'Aktif' : 'Non-Aktif',
            $customer->type?->cname ?? '-',
            $customer->cmembership_no !== null && $customer->cmembership_no !== '' ? (string) $customer->cmembership_no : '-',
            $customer->cgender ?? '-',
            $customer->cphone !== null && $customer->cphone !== '' ? (string) $customer->cphone : '-',
            $customer->cemail ?? '-',
            $customer->dbirth ? Carbon::parse($customer->dbirth)->format('d M Y') : '-',
            $customer->caddress ?? '-',
            $customer->cpostal_code !== null && $customer->cpostal_code !== '' ? (string) $customer->cpostal_code : '-',
            $customer->ccity ?? '-',
            $customer->cdistrict ?? '-',
            $customer->cprovince ?? '-',
            $customer->ccountry ?? '-',
            $customer->cnotes ?? '-',
            $lastTrx ? Carbon::parse($lastTrx)->format('d M Y H:i') : '-',
            (int) ($customer->sales_count ?? 0),
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() > 1 && in_array($cell->getColumn(), ['E', 'G', 'K'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'R' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setAutoFilter('A1:R1');

        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2'],
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
                    'G' => 20, // No HP
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
                    'R' => 18, // Total Transaksi
                ];

                foreach (range('A', 'R') as $col) {
                    $dimension = $sheet->getColumnDimension($col);
                    $calculatedWidth = $dimension->getWidth();
                    $minWidth = $minWidths[$col] ?? 14;

                    $finalWidth = max($calculatedWidth + 5, $minWidth);

                    $dimension->setAutoSize(false);
                    $dimension->setWidth($finalWidth);
                }

                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 1) {
                    $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F2:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("I2:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("Q2:Q{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("R2:R{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'customer';
    }
}
