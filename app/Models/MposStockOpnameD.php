<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposStockOpnameD extends Model
{
    use HasFactory;

    public const TYPE_PRODUCT = 'PRODUCT';
    public const TYPE_PRODUK = 'PRODUCT';
    public const TYPE_SETENGAH_JADI = 'SETENGAH_JADI';
    public const TYPE_BAHAN_BAKU = 'INGREDIENT';
    public const TYPE_INGREDIENT = 'INGREDIENT';

    protected $table = 'mpos_stock_opname_d';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_opname',
        'citem_type',
        'nid_product',
        'nid_ingredient',
        'nqty_system',
        'nqty_actual',
        'nqty_diff',
        'ncost',
        'nsubtotal_diff',
        'dexpired',
        'crack',
        'cnote',
        'dtime',
        'dcreated',
    ];

    protected function casts(): array
    {
        return [
            'dexpired' => 'date',
            'dcreated' => 'datetime',
            'nqty_system' => 'decimal:2',
            'nqty_actual' => 'decimal:2',
            'nqty_diff' => 'decimal:2',
            'ncost' => 'decimal:2',
            'nsubtotal_diff' => 'decimal:2',
        ];
    }

    public function opname()
    {
        return $this->belongsTo(MposStockOpnameH::class, 'nid_opname', 'nid');
    }

    public function product()
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }

    public function ingredient()
    {
        return $this->belongsTo(MposIngredients::class, 'nid_ingredient', 'nid');
    }

    public function getItemNameAttribute(): string
    {
        if ($this->citem_type === self::TYPE_BAHAN_BAKU && $this->ingredient) {
            return $this->ingredient->cname . ($this->ingredient->csatuan ? ' (' . $this->ingredient->csatuan . ')' : '');
        }

        return $this->product?->cname ?: 'Item #' . ($this->nid_product ?: $this->nid_ingredient);
    }
}

