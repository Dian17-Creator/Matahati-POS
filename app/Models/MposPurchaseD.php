<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPurchaseD extends Model
{
    use HasFactory;

    protected $table = 'mpos_purchase_d';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_purchase',
        'nid_product',
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
}
