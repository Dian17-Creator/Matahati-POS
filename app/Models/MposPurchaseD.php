<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPurchaseD extends Model
{
    use HasFactory;

    public const TYPE_PRODUCT = 'PRODUCT';
    public const TYPE_INGREDIENT = 'INGREDIENT';

    protected $table = 'mpos_purchase_d';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_purchase',
        'citem_type',
        'nid_product',
        'nid_ingredient',
        'nqty',
        'ncost',
        'nsubtotal',
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
        return strtoupper((string) $this->citem_type) === self::TYPE_INGREDIENT
            || (!empty($this->nid_ingredient) && empty($this->nid_product));
    }

    public function purchase()
    {
        return $this->belongsTo(
            MposPurchaseH::class,
            'nid_purchase',
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
