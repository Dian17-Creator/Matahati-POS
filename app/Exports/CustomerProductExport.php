<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Export;
use App\Exports\CustomerProductSummarySheet;
use App\Exports\CustomerProductDetailSheet;

class CustomerProductExport implements WithMultipleSheets, Export
{
    protected $summaryData;
    protected $detailData;
    protected $grandTotals;
    protected $startDate;
    protected $endDate;

    public function __construct($summaryData, $detailData, $grandTotals, $startDate, $endDate)
    {
        $this->summaryData = $summaryData;
        $this->detailData = $detailData;
        $this->grandTotals = $grandTotals;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            new CustomerProductSummarySheet($this->summaryData, $this->grandTotals, $this->startDate, $this->endDate),
            new CustomerProductDetailSheet($this->detailData, $this->grandTotals, $this->startDate, $this->endDate),
        ];
    }
}
