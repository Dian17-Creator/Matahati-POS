<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPurchaseD extends Model
{
    use HasFactory;

    public const TYPE_PRODUK = 'PRODUK';
    public const TYPE_SETENGAH_JADI = 'SETENGAH_JADI';
    public const TYPE_BAHAN_BAKU = 'BAHAN_BAKU';

    // Legacy aliases for backward compatibility
    public const TYPE_PRODUCT = 'PRODUCT';
    public const TYPE_INGREDIENT = 'INGREDIENT';

    protected $table = 'mpos_purchase_d';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_purchase',
        'nid_purchase_detail_ref',
        'citem_type',
        'nid_product',
        'nid_ingredient',
        'nqty',
        'csatuan',
        'ncost',
        'nsubtotal',
        'creason',
        'cnote',
    ];

    protected function casts(): array
    {
        return [
            'nqty' => 'integer',
            'ncost' => 'decimal:2',
            'nsubtotal' => 'decimal:2',
        ];
    }

    public function isIngredient(): bool
    {
        return !empty($this->nid_ingredient) && empty($this->nid_product);
    }

    public function getItemTypeNormalized(): string
    {
        $type = strtoupper(trim((string) $this->citem_type));
        if ($type === self::TYPE_SETENGAH_JADI) {
            return self::TYPE_SETENGAH_JADI;
        }
        if ($type === self::TYPE_BAHAN_BAKU || $type === self::TYPE_INGREDIENT) {
            return self::TYPE_BAHAN_BAKU;
        }
        return self::TYPE_PRODUK;
    }

    public function getItemTypeLabel(): string
    {
        return match ($this->getItemTypeNormalized()) {
            self::TYPE_SETENGAH_JADI => 'Setengah Jadi',
            self::TYPE_BAHAN_BAKU => 'Bahan Baku',
            default => 'Produk',
        };
    }

    public function getItemUnit(): string
    {
        if (!empty($this->csatuan)) {
            return $this->csatuan;
        }
        if ($this->product && !empty($this->product->csatuan)) {
            return $this->product->csatuan;
        }
        if ($this->ingredient && !empty($this->ingredient->csatuan)) {
            return $this->ingredient->csatuan;
        }
        return 'PCS';
    }

    public function purchase()
    {
        return $this->belongsTo(
            MposPurchaseH::class,
            'nid_purchase',
            'nid'
        );
    }

    public function purchaseDetailRef()
    {
        return $this->belongsTo(
            self::class,
            'nid_purchase_detail_ref',
            'nid'
        );
    }

    public function product()
    {
        return $this->belongsTo(
            MposProduct::class,
            'nid_product',
            'nid'
        );
    }

    public function ingredient()
    {
        return $this->belongsTo(
            MposIngredients::class,
            'nid_ingredient',
            'nid'
        );
    }
}
