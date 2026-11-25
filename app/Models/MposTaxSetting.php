<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MposTaxSetting extends Model
{
    use HasFactory;

    public const CATEGORY_OFFLINE = 'OFFLINE';

    public const CATEGORY_ONLINE = 'ONLINE';

    public const CATEGORIES = [
        self::CATEGORY_OFFLINE,
        self::CATEGORY_ONLINE,
    ];

    public const TAX_MODE_NONE = 'NONE';

    public const TAX_MODE_INCLUDE = 'INCLUDE';

    public const TAX_MODE_EXCLUDE = 'EXCLUDE';

    protected $table = 'mpos_tax_setting';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nid_outlet',
        'ccategory',
        'ctax_name',
        'ntax_percent',
        'bis_tax_included',
        'bppn_11_12',
        'btakeaway_tax_exempt',
        'ballow_manual_tax_exempt',
        'btax_before_discount',
        'nservice_charge_percent',
        'creceipt_title',
        'creceipt_note',
        'bhide_order_no',
    ];

    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
            'ntax_percent' => 'decimal:2',
            'bis_tax_included' => 'boolean',
            'bppn_11_12' => 'boolean',
            'btakeaway_tax_exempt' => 'boolean',
            'ballow_manual_tax_exempt' => 'boolean',
            'btax_before_discount' => 'boolean',
            'nservice_charge_percent' => 'decimal:2',
            'bhide_order_no' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public static function getForOutlet(int $outletId, string $category): ?self
    {
        $normalizedCategory = strtoupper(trim($category));

        return static::where('nid_outlet', $outletId)
            ->where('ccategory', $normalizedCategory)
            ->first();
    }

    /**
     * Menghitung pajak, DPP, dan grand total berdasarkan konfigurasi outlet & kategori.
     *
     * @return array{ctax_mode: string, ntax_percent: float, ndpp: float, net_sales: float, ntax: float, ngrandtotal: float}
     */
    public function calculateTax(float $subtotal, float $discount = 0.0, string $orderType = '', bool $manualExempt = false): array
    {
        $baseAfterDiscount = max(0.0, $subtotal - $discount);
        $taxBase = $this->btax_before_discount ? max(0.0, $subtotal) : $baseAfterDiscount;
        $rate = (float) $this->ntax_percent;

        $isOnline = strtoupper((string) $this->ccategory) === self::CATEGORY_ONLINE;
        $isTakeAwayExempt = $this->btakeaway_tax_exempt && strtoupper($orderType) === MposSalesH::ORDER_TYPE_TAKE_AWAY;
        $isManualExempt = $this->ballow_manual_tax_exempt && $manualExempt;

        if ($isOnline || $rate <= 0 || $isTakeAwayExempt || $isManualExempt) {
            return [
                'ctax_mode' => self::TAX_MODE_NONE,
                'ntax_percent' => 0.0,
                'ndpp' => round($baseAfterDiscount, 2),
                'net_sales' => round($baseAfterDiscount, 2),
                'ntax' => 0.0,
                'ngrandtotal' => round($baseAfterDiscount, 2),
            ];
        }

        $use1112 = (bool) $this->bppn_11_12;
        $effectiveRate = $use1112 ? ($rate * (11 / 12)) : $rate;

        if ($this->bis_tax_included) {
            $grandTotal = round($baseAfterDiscount, 2);
            $hargaDasar = round($taxBase / (1 + ($effectiveRate / 100)), 2);
            $tax = round($taxBase - $hargaDasar, 2);
            $dpp = $use1112 ? round($hargaDasar * (11 / 12), 2) : $hargaDasar;
            $netSales = round($baseAfterDiscount - $tax, 2);

            return [
                'ctax_mode' => self::TAX_MODE_INCLUDE,
                'ntax_percent' => round($rate, 2),
                'ndpp' => $dpp,
                'net_sales' => $netSales,
                'ntax' => $tax,
                'ngrandtotal' => $grandTotal,
            ];
        }

        $dpp = $use1112 ? round($taxBase * (11 / 12), 2) : round($taxBase, 2);
        $tax = round($dpp * ($rate / 100), 2);
        $grandTotal = round($baseAfterDiscount + $tax, 2);

        return [
            'ctax_mode' => self::TAX_MODE_EXCLUDE,
            'ntax_percent' => round($rate, 2),
            'ndpp' => $dpp,
            'net_sales' => round($baseAfterDiscount, 2),
            'ntax' => $tax,
            'ngrandtotal' => $grandTotal,
        ];
    }
}
