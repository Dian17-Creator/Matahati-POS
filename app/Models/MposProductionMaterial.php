<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposProductionMaterial extends Model
{
    use HasFactory;

    protected $table = 'mpos_production_material';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_production',
        'nid_production_product',
        'citem_type',
        'nid_product',
        'nid_ingredient',
        'nqty',
        'ncost',
        'nsubtotal',
        'cnote',
        'dcreated',
        'dupdated',
    ];

    protected function casts(): array
    {
        return [
            'dcreated' => 'datetime',
            'dupdated' => 'datetime',
            'nqty' => 'decimal:2',
            'ncost' => 'decimal:2',
            'nsubtotal' => 'decimal:2',
        ];
    }

    public function production()
    {
        return $this->belongsTo(MposProductionH::class, 'nid_production', 'nid');
    }

    public function productionProduct()
    {
        return $this->belongsTo(MposProductionProduct::class, 'nid_production_product', 'nid');
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
        if ($this->nid_product && $this->product) {
            return $this->product->cname;
        }

        if ($this->nid_ingredient && $this->ingredient) {
            return $this->ingredient->cname;
        }

        return '-';
    }

    public function getItemUnitAttribute(): string
    {
        if ($this->nid_product && $this->product) {
            return $this->product->csatuan ?: 'pcs';
        }

        if ($this->nid_ingredient && $this->ingredient) {
            return $this->ingredient->csatuan ?: 'pcs';
        }

        return 'pcs';
    }
}

