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
        'nid_type',
        'cname',
        'cgender',
        'cmembership_no',
        'cphone',
        'dbirth',
        'cnotes',
        'cemail',
        'caddress',
        'cpostal_code',
        'ccountry',
        'cprovince',
        'ccity',
        'cdistrict',
    ];

    protected function casts(): array
    {
        return [
            'nid_type' => 'integer',
            'dbirth' => 'date:Y-m-d',
        ];
    }

    public function type()
    {
        return $this->belongsTo(
            MposCustType::class,
            'nid_type',
            'nid'
        );
    }

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_customer',
            'nid'
        );
    }
}
