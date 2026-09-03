<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposCust extends Model
{
    use HasFactory;

    protected $table = 'mpos_cust';

    protected $primaryKey = 'nid';
    public $timestamps = false;
    protected $fillable = [
        'cname',
        'cphone',
        'cemail',
        'caddress',
    ];

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_customer',
            'nid'
        );
    }
}
