<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPurchaseH extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const PAYMENT_UNPAID = 'UNPAID';
    public const PAYMENT_PAID = 'PAID';

    protected $table = 'mpos_purchase_h';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cno_purchase',
        'dpurchase',
        'nid_supplier',
        'nid_outlet',
        'nid_user',
        'cstatus',
        'cpayment_status',
        'dpayment',
        'ddue_date',
        'dreceived',
        'creceived_by',
        'cno_reference',
        'ntotal_amount',
        'cnote',
        'dcreated',
    ];

    protected function casts(): array
    {
        return [
            'dpurchase' => 'datetime',
            'dpayment' => 'datetime',
            'ddue_date' => 'date',
            'dreceived' => 'datetime',
            'dcreated' => 'datetime',
            'ntotal_amount' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(
            MposSupplier::class,
            'nid_supplier',
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

    public function user()
    {
        return $this->belongsTo(
            Muser::class,
            'nid_user',
            'nid'
        );
    }

    public function details()
    {
        return $this->hasMany(
            MposPurchaseD::class,
            'nid_purchase',
            'nid'
        );
    }

    /**
     * Generate unique PO number (e.g. PO2610200001)
     */
    public static function generatePurchaseNumber(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $prefix = 'PO' . $date->format('ymd');

        $last = self::where('cno_purchase', 'LIKE', "{$prefix}%")
            ->orderBy('cno_purchase', 'desc')
            ->first();

        if ($last) {
            $lastSeq = (int) substr($last->cno_purchase, -4);
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix . $seq;
    }
}
