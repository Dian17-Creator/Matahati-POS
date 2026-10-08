<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPriceCategory extends Model
{
    use HasFactory;

    protected $table = 'mpos_price_category';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cname',
        'nid_outlet',
    ];

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public function productPrices()
    {
        return $this->hasMany(MposProductPrice::class, 'nid_price_category', 'nid');
    }

    public function customerTypes()
    {
        return $this->hasMany(MposCustType::class, 'nid_price_category', 'nid');
    }
}
