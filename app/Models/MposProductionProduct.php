<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposProductionProduct extends Model
{
    use HasFactory;

    protected $table = 'mpos_production_product';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_production',
        'nid_product',
        'nqty',
        'ncost_unit',
        'nsubtotal',
        'dexpired',
        'crack',
        'cnote',
        'dcreated',
        'dupdated',
    ];

    protected function casts(): array
    {
        return [
            'dexpired' => 'date',
            'dcreated' => 'datetime',
            'dupdated' => 'datetime',
            'nqty' => 'decimal:2',
            'ncost_unit' => 'decimal:2',
            'nsubtotal' => 'decimal:2',
        ];
    }

    public function production()
    {
        return $this->belongsTo(MposProductionH::class, 'nid_production', 'nid');
    }

    public function product()
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }

    public function materials()
    {
        return $this->hasMany(MposProductionMaterial::class, 'nid_production_product', 'nid');
    }
}

