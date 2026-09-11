<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposSalesH extends Model
{
    use HasFactory;

    // Order Types
    public const ORDER_TYPE_DINE_IN = 'DINE_IN';
    public const ORDER_TYPE_TAKE_AWAY = 'TAKE_AWAY';
    public const ORDER_TYPE_ONLINE = 'ONLINE';

    public const ORDER_TYPES = [
        self::ORDER_TYPE_DINE_IN,
        self::ORDER_TYPE_TAKE_AWAY,
        self::ORDER_TYPE_ONLINE,
    ];

    // Statuses
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'mpos_sales_h';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'cnotransaction',
        'dtransaction',

        'nid_customer',
        'nid_user',
        'nid_outlet',
        'nid_voucher',
        'nid_payment',

        'cname_customer',

        'nqueue',
        'cordertype',
        'nvisitor',
        'ctable',

        'nsubtotal',
        'ndiscount',
        'ntax',
        'ngrandtotal',

        'npaid',
        'nchange',

        'nitem',

        'cstatus',
        'ccancel_note',
    ];

    protected function casts(): array
    {
        return [
            'dtransaction' => 'datetime',
            'dcreated' => 'datetime',

            'nid_customer' => 'integer',
            'nid_user' => 'integer',
            'nid_outlet' => 'integer',
            'nid_voucher' => 'integer',
            'nid_payment' => 'integer',

            'nqueue' => 'integer',
            'nvisitor' => 'integer',
            'nitem' => 'integer',

            'nsubtotal' => 'decimal:2',
            'ndiscount' => 'decimal:2',
            'ntax' => 'decimal:2',
            'ngrandtotal' => 'decimal:2',

            'npaid' => 'decimal:2',
            'nchange' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(
            MposCust::class,
            'nid_customer',
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

    public function outlet()
    {
        return $this->belongsTo(
            MposOutlet::class,
            'nid_outlet',
            'nid'
        );
    }

    public function voucher()
    {
        return $this->belongsTo(
            MposVoucher::class,
            'nid_voucher',
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

    public function details()
    {
        return $this->hasMany(
            MposSalesD::class,
            'nid_transaction',
            'nid'
        );
    }
}
