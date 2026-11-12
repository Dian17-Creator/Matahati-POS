<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPurchaseH extends Model
{
    use HasFactory;

    public const DOC_TYPE_PURCHASE = 'PURCHASE';
    public const DOC_TYPE_RETURN = 'RETURN';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const PAYMENT_UNPAID = 'UNPAID';
    public const PAYMENT_PAID = 'PAID';

    protected $table = 'mpos_purchase_h';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cdoc_type',
        'cno_purchase',
        'nid_purchase_ref',
        'cno_purchase_ref',
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

    public function purchaseRef()
    {
        return $this->belongsTo(
            self::class,
            'nid_purchase_ref',
            'nid'
        );
    }

    public function returns()
    {
        return $this->hasMany(
            self::class,
            'nid_purchase_ref',
            'nid'
        )->where('cdoc_type', self::DOC_TYPE_RETURN);
    }

    public function scopeOnlyPurchases(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('cdoc_type', self::DOC_TYPE_PURCHASE)
              ->orWhereNull('cdoc_type');
        });
    }

    public function scopeOnlyReturns(Builder $query): Builder
    {
        return $query->where('cdoc_type', self::DOC_TYPE_RETURN);
    }

    public function isReturn(): bool
    {
        return strtoupper((string) $this->cdoc_type) === self::DOC_TYPE_RETURN;
    }

    /**
     * Generate unique PO number matching Olsera format:
     * 2 letters 'PO' + 14 digit numbers (6 digits YYMMDD + 8 digits running sequence), total 16 chars.
     * Example: PO26101400000361
     */
    public static function generatePurchaseNumber(?Carbon $date = null): string
    {
        return self::generatePrefixedNumber('PO', $date);
    }

    /**
     * Generate unique Purchase Return number matching Olsera format:
     * 2 letters 'RE' + 14 digit numbers (6 digits YYMMDD + 8 digits running sequence), total 16 chars.
     * Example: RE26041000000002
     */
    public static function generateReturnNumber(?Carbon $date = null): string
    {
        return self::generatePrefixedNumber('RE', $date);
    }

    protected static function generatePrefixedNumber(string $code, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $code = strtoupper(trim($code));
        $prefix = $code . $date->format('ymd');

        $last = self::where('cno_purchase', 'LIKE', $code . '%')
            ->orderBy('nid', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last && !empty($last->cno_purchase)) {
            $lastCno = trim($last->cno_purchase);
            if (strlen($lastCno) > 8) {
                $lastSeqNum = (int) substr($lastCno, 8);
                if ($lastSeqNum > 0) {
                    $nextSeq = $lastSeqNum + 1;
                }
            }
        }

        $seq = str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
        $generatedNumber = $prefix . $seq;

        while (self::where('cno_purchase', $generatedNumber)->exists()) {
            $nextSeq++;
            $seq = str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
            $generatedNumber = $prefix . $seq;
        }

        return $generatedNumber;
    }
}
