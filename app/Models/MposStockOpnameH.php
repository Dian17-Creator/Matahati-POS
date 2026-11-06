<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposStockOpnameH extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'mpos_stock_opname_h';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cno_opname',
        'dopname',
        'nid_outlet',
        'nid_user',
        'cstatus',
        'cnote',
        'ntotal_system_qty',
        'ntotal_actual_qty',
        'ntotal_diff_qty',
        'ntotal_diff_amount',
        'dposted',
        'dcreated',
        'dupdated',
    ];

    protected function casts(): array
    {
        return [
            'dopname' => 'date',
            'dposted' => 'datetime',
            'dcreated' => 'datetime',
            'dupdated' => 'datetime',
            'ntotal_system_qty' => 'decimal:2',
            'ntotal_actual_qty' => 'decimal:2',
            'ntotal_diff_qty' => 'decimal:2',
            'ntotal_diff_amount' => 'decimal:2',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public function user()
    {
        return $this->belongsTo(Muser::class, 'nid_user', 'nid');
    }

    public function details()
    {
        return $this->hasMany(MposStockOpnameD::class, 'nid_opname', 'nid');
    }

    public function isDraft(): bool
    {
        return strtoupper((string) $this->cstatus) === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return strtoupper((string) $this->cstatus) === self::STATUS_POSTED;
    }

    public function isCancelled(): bool
    {
        return strtoupper((string) $this->cstatus) === self::STATUS_CANCELLED;
    }

    /**
     * Hitung ulang total kuantitas dan selisih berdasarkan detail
     */
    public function recalculateTotals(): void
    {
        $details = $this->details()->get();
        $this->ntotal_system_qty = $details->sum('nqty_system');
        $this->ntotal_actual_qty = $details->sum('nqty_actual');
        $this->ntotal_diff_qty = $details->sum('nqty_diff');
        $this->ntotal_diff_amount = $details->sum('nsubtotal_diff');
        $this->dupdated = Carbon::now();
        $this->save();
    }

    /**
     * Generate format Olsera nomor Stock Opname:
     * 'OP' + 6 digit YYMMDD + 8 digit running sequence, total 16 chars (e.g. OP26080100000045)
     */
    public static function generateNumber(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $prefix = 'OP' . $date->format('ymd');

        $last = self::where('cno_opname', 'LIKE', 'OP%')
            ->orderBy('nid', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last && !empty($last->cno_opname)) {
            $lastCno = trim($last->cno_opname);
            if (strlen($lastCno) > 8) {
                $lastSeqNum = (int) substr($lastCno, 8);
                if ($lastSeqNum > 0) {
                    $nextSeq = $lastSeqNum + 1;
                }
            }
        }

        $seq = str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
        $generated = $prefix . $seq;

        while (self::where('cno_opname', $generated)->exists()) {
            $nextSeq++;
            $seq = str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
            $generated = $prefix . $seq;
        }

        return $generated;
    }
}

