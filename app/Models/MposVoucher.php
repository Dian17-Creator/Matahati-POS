<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposVoucher extends Model
{
    use HasFactory;

    protected $table = 'mpos_voucher';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'ckode',
        'cdesc',
        'dstart',
        'dend',
        'nqty',
        'nredeem',
        'nmin_spend',
        'ndisc_percent',
        'ndisc_amount',
        'cstatus',
    ];

    protected function casts(): array
    {
        return [
            'dstart' => 'date',
            'dend' => 'date',

            'nqty' => 'integer',
            'nredeem' => 'integer',

            'nmin_spend' => 'decimal:2',
            'ndisc_percent' => 'decimal:2',
            'ndisc_amount' => 'decimal:2',
        ];
    }

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_voucher',
            'nid'
        );
    }
}
