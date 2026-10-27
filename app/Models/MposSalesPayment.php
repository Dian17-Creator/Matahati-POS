<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposSalesPayment extends Model
{
    use HasFactory;

    public const TYPE_FULL = 'FULL';
    public const TYPE_DP = 'DP';
    public const TYPE_PELUNASAN = 'PELUNASAN';
    public const TYPE_SPLIT = 'SPLIT';

    public const STATUS_PAID = 'PAID';
    public const STATUS_VOID = 'VOID';
    public const STATUS_REFUND = 'REFUND';

    protected $table = 'mpos_sales_payment';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_transaction',
        'nid_payment',
        'nid_shift',
        'nid_user',
        'namount',
        'nchange',
        'cpayment_type',
        'creference_no',
        'cnote',
        'dpaid_at',
        'cstatus',
    ];

    protected function casts(): array
    {
        return [
            'nid_transaction' => 'integer',
            'nid_payment' => 'integer',
            'nid_shift' => 'integer',
            'nid_user' => 'integer',
            'namount' => 'decimal:2',
            'nchange' => 'decimal:2',
            'dpaid_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(
            MposSalesH::class,
            'nid_transaction',
            'nid'
        );
    }

    public function payment()
    {
        return $this->belongsTo(
            MposPayment::class,
            'nid_payment',
            'nid'
        );
    }

    public function shift()
    {
        return $this->belongsTo(
            MposShift::class,
            'nid_shift',
            'nid'
        );
    }

    public function posUser()
    {
        return $this->belongsTo(
            MposUser::class,
            'nid_user',
            'nid'
        );
    }
}
