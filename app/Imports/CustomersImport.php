<?php

namespace App\Imports;

use App\Models\MposCust;
use App\Models\MposCustType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomersImport implements ToCollection, WithHeadingRow
{
    protected $outletId;
    protected $customerTypes;

    public function __construct($outletId)
    {
        $this->outletId = $outletId;
        // Cache customer types for quick lookup (case-insensitive mapping)
        $this->customerTypes = MposCustType::whereIn('nid', function($q) {
            $q->select(DB::raw('MIN(nid)'))
              ->from('mpos_cust_type')
              ->groupBy('cname');
        })->get()->keyBy(function($item) {
            return strtolower(trim($item->cname));
        });
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Check if essential fields are present
            if (!isset($row['name']) || trim($row['name']) === '') {
                continue;
            }

            // Map Customer Type
            $typeId = null;
            if (isset($row['customer_type']) && trim($row['customer_type']) !== '') {
                $typeKey = strtolower(trim($row['customer_type']));
                if ($this->customerTypes->has($typeKey)) {
                    $typeId = $this->customerTypes[$typeKey]->nid;
                }
            }

            if (empty($typeId)) {
                $guestTypeKey = 'guest';
                if ($this->customerTypes->has($guestTypeKey)) {
                    $typeId = $this->customerTypes[$guestTypeKey]->nid;
                }
            }

            // Map Gender
            $gender = null;
            if (isset($row['gender'])) {
                $g = strtoupper(trim($row['gender']));
                if ($g === 'M' || $g === 'MALE' || $g === 'L' || $g === 'LAKI-LAKI') {
                    $gender = 'MALE';
                } elseif ($g === 'F' || $g === 'FEMALE' || $g === 'P' || $g === 'PEREMPUAN') {
                    $gender = 'FEMALE';
                }
            }

            // Map Non Active (factive)
            $factive = 1; // Default active
            if (isset($row['non_active'])) {
                $na = strtoupper(trim($row['non_active']));
                if ($na === 'YES' || $na === 'Y' || $na === 'TRUE' || $na === '1') {
                    $factive = 0;
                }
            }

            // Map Dates
            $dbirth = $this->parseDate($row['birth_date'] ?? null);
            $dlast_transaction = $this->parseDate($row['last_transaction_date'] ?? null, true);

            MposCust::updateOrCreate([
                'cphone' => $row['phone'] ?? null,
                'cname' => $row['name'],
                'nid_outlet' => $this->outletId,
            ], [
                'cmembership_no' => $row['id'] ?? null,
                'cemail' => $row['email'] ?? null,
                'cgender' => $gender,
                'dbirth' => $dbirth,
                'nid_type' => $typeId,
                'factive' => $factive,
                'caddress' => $row['address'] ?? null,
                'cpostal_code' => $row['postal_code'] ?? null,
                'cdistrict' => $row['subdistrict'] ?? null,
                'ccity' => $row['city'] ?? null,
                'cprovince' => $row['stateprovince'] ?? $row['state_province'] ?? $row['province'] ?? null,
                'ccountry' => $row['country'] ?? null,
                'dlast_transaction' => $dlast_transaction,
            ]);
        }
    }

    private function parseDate($value, $includeTime = false)
    {
        if (empty($value) || trim($value) === '-' || trim($value) === '0000-00-00') {
            return null;
        }

        try {
            // Excel can pass dates as numeric floats
            if (is_numeric($value)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                if ((int)$date->format('Y') < 1000) return null;
                return $includeTime ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
            }
            
            // Otherwise try parsing via Carbon
            $parsed = Carbon::parse($value);
            if ($parsed->year < 1000) return null;
            return $includeTime ? $parsed->format('Y-m-d H:i:s') : $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
