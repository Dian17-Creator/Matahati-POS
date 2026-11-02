<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposStockInD extends Model
{
    use HasFactory;

    public const TYPE_PRODUCT = 'PRODUCT';
    public const TYPE_INGREDIENT = 'INGREDIENT';

    protected $table = 'mpos_stock_in_d';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_stock_in',
        'citem_type',
        'nid_product',
        'nid_ingredient',
        'nqty',
        'ncost',
        'nsubtotal',
        'cserial_no',
        'crack',
        'dcreated',
    ];

    protected function casts(): array
    {
        return [
            'nqty' => 'integer',
            'ncost' => 'decimal:2',
            'nsubtotal' => 'decimal:2',
            'dcreated' => 'datetime',
        ];
    }

    public function isIngredient(): bool
    {
        return strtoupper((string) $this->citem_type) === self::TYPE_INGREDIENT
            || (!empty($this->nid_ingredient) && empty($this->nid_product));
    }

    public function stockIn()
    {
        return $this->belongsTo(MposStockInH::class, 'nid_stock_in', 'nid');
    }

    public function product()
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }

    public function ingredient()
    {
        return $this->belongsTo(MposIngredients::class, 'nid_ingredient', 'nid');
    }
}

