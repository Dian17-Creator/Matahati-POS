<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MposSalesoffH extends Model
{
    use HasFactory;

    protected $table = 'mpos_sls_h';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'cnotransaction',
        'dtransaction',
        'dpayment_date',

        'nid_customer',
        'nid_user',
        'nid_shift',
        'nid_outlet',
        'nid_voucher',
        'nid_payment',
        'nid_payment_dual',
        'nid_parent_transaction',

        'cname_customer',

        'nqueue',
        'cordertype',
        'nvisitor',
        'ctable',

        'nsubtotal',
        'ndiscount',
        'ntax',
        'ctax_mode',
        'ntax_percent',
        'ndpp',
        'ngrandtotal',

        'npaid',
        'ndp_amount',
        'nremaining_amount',
        'nchange',

        'nitem',
        'cnote',

        'cstatus',
        'ccancel_note',
        'cvoid_note',
        'crefund_note',
        'csource',
        'nservice_charge',
        'nrounding',
        'ntotal_cost',
        'ntotal_profit',
        'cpdf_url',
        'dcreated',
    ];

    protected function casts(): array
    {
        return [
            'dtransaction' => 'datetime',
            'dpayment_date' => 'datetime',
            'dcreated' => 'datetime',

            'nid_customer' => 'integer',
            'nid_user' => 'integer',
            'nid_shift' => 'integer',
            'nid_outlet' => 'integer',
            'nid_voucher' => 'integer',
            'nid_payment' => 'integer',
            'nid_payment_dual' => 'integer',
            'nid_parent_transaction' => 'integer',

            'nqueue' => 'integer',
            'nvisitor' => 'integer',
            'nitem' => 'integer',

            'nsubtotal' => 'decimal:2',
            'ndiscount' => 'decimal:2',
            'ntax' => 'decimal:2',
            'ntax_percent' => 'decimal:2',
            'ndpp' => 'decimal:2',
            'ngrandtotal' => 'decimal:2',
            'ntotal_cost' => 'decimal:2',
            'ntotal_profit' => 'decimal:2',

            'npaid' => 'decimal:2',
            'ndp_amount' => 'decimal:2',
            'nremaining_amount' => 'decimal:2',
            'nchange' => 'decimal:2',
            'nservice_charge' => 'decimal:2',
            'nrounding' => 'decimal:2',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected static function booted(): void
    {
        static::saved(function (self $sale): void {
            if ($sale->nid_customer && $sale->dtransaction) {
                $customer = MposCust::find($sale->nid_customer);
                if ($customer) {
                    MposCust::where('cname', $customer->cname)
                        ->where('cphone', $customer->cphone)
                        ->update(['dlast_transaction' => $sale->dtransaction]);
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MposCust::class, 'nid_customer', 'nid');
    }

    public function posUser(): BelongsTo
    {
        return $this->belongsTo(MposUser::class, 'nid_user', 'nid');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(MposVoucher::class, 'nid_voucher', 'nid');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(MposPayment::class, 'nid_payment', 'nid');
    }

    public function paymentDual(): BelongsTo
    {
        return $this->belongsTo(MposPayment::class, 'nid_payment_dual', 'nid');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MposSalesoffD::class, 'nid_transaction', 'nid');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(MposShift::class, 'nid_shift', 'nid');
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'nid_parent_transaction', 'nid');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'nid_parent_transaction', 'nid');
    }
}
