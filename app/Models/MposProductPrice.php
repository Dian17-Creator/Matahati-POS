<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposProductPrice extends Model
{
    use HasFactory;

    protected $table = 'mpos_product_price';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_product',
        'nid_cust_type',
        'nqty_start',
        'nprice',
    ];

    protected function casts(): array
    {
        return [
            'nid_product' => 'integer',
            'nid_cust_type' => 'integer',
            'nqty_start' => 'integer',
            'nprice' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }

    public function customerType()
    {
        return $this->belongsTo(MposCustType::class, 'nid_cust_type', 'nid');
    }
}
