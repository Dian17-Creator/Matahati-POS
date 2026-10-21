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
     * Generate unique PO number matching Olsera format:
     * 2 letters 'PO' + 14 digit numbers (6 digits YYMMDD + 8 digits running sequence), total 16 chars.
     * Example: PO26101400000361
     */
    public static function generatePurchaseNumber(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $prefix = 'PO' . $date->format('ymd');

        // Look for the latest purchase order to get continuous running sequence
        $last = self::where('cno_purchase', 'LIKE', 'PO%')
            ->orderBy('nid', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last && !empty($last->cno_purchase)) {
            $lastCno = trim($last->cno_purchase);
            // Prefix 'PO' (2 chars) + 'ymd' (6 chars) = 8 chars offset.
            if (strlen($lastCno) > 8) {
                $lastSeqNum = (int) substr($lastCno, 8);
                if ($lastSeqNum > 0) {
                    $nextSeq = $lastSeqNum + 1;
                }
            }
        }

        $seq = str_pad($nextSeq, 8, '0', STR_PAD_LEFT);
        $poNumber = $prefix . $seq;

        // Ensure uniqueness in case of race conditions
        while (self::where('cno_purchase', $poNumber)->exists()) {
            $nextSeq++;
            $seq = str_pad($nextSeq, 8, '0', STR_PAD_LEFT);
            $poNumber = $prefix . $seq;
        }

        return $poNumber;
    }
}
