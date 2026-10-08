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
        'nid_outlet',
        'factive',
        'dlast_transaction',
    ];

    protected function casts(): array
    {
        return [
            'nid_type' => 'integer',
            'dbirth' => 'date:Y-m-d',
            'factive' => 'boolean',
            'dlast_transaction' => 'datetime',
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

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public function customerType()
    {
        return $this->belongsTo(MposCustType::class, 'nid_type', 'nid');
    }
}
