<?php

namespace Tests\Unit;

use App\Exports\ProductsExport;
use App\Models\MposGrpProduct;
use App\Models\MposOutlet;
use App\Models\MposProduct;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ProductsExportTest extends TestCase
{
    public function test_products_export_generates_olsera_style_excel_with_project_fields(): void
    {
        $category = new MposGrpProduct(['cname' => 'TEREA']);
        $outlet = new MposOutlet(['cname' => 'Iqos Matahati KDR']);

        $product = new MposProduct([
            'cname' => 'TEREA Strawberry Pearl for iqos iluma',
            'cproduct_type' => 'PRODUK',
            'nprice_buy' => 28000,
            'nprice' => 32000,
            'nprice_online' => 32000,
            'nqty' => 37,
            'csatuan' => 'PCS',
            'cstatus' => 'ACTIVE',
            'fcombo' => false,
        ]);
        $product->setRelation('category', $category);
        $product->setRelation('outlet', $outlet);

        $export = new ProductsExport(collect([$product]));

        $this->assertSame('product', $export->title());

        $binary = Excel::raw($export, ExcelFormat::XLSX);
        $this->assertNotEmpty($binary);

        $tempFile = tempnam(sys_get_temp_dir(), 'products_export_') . '.xlsx';
        file_put_contents($tempFile, $binary);

        try {
            $spreadsheet = IOFactory::load($tempFile);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame('product', $sheet->getTitle());
            $this->assertSame('A1:K1', $sheet->getAutoFilter()->getRange());
            $this->assertSame('Nama Produk', $sheet->getCell('A1')->getValue());
            $this->assertSame('TEREA Strawberry Pearl for iqos iluma', $sheet->getCell('A2')->getValue());
            $this->assertSame('TEREA', $sheet->getCell('B2')->getValue());
            $this->assertSame('Iqos Matahati KDR', $sheet->getCell('C2')->getValue());
            $this->assertSame('Produk (Barang Jadi)', $sheet->getCell('D2')->getValue());
            $this->assertEquals(28000, $sheet->getCell('E2')->getValue());
            $this->assertEquals(32000, $sheet->getCell('F2')->getValue());
            $this->assertEquals(32000, $sheet->getCell('G2')->getValue());
            $this->assertEquals(37, $sheet->getCell('H2')->getValue());
            $this->assertSame('PCS', $sheet->getCell('I2')->getValue());
            $this->assertSame('ACTIVE', $sheet->getCell('J2')->getValue());
            $this->assertSame('Tidak', $sheet->getCell('K2')->getValue());
        } finally {
            @unlink($tempFile);
        }
    }
}
