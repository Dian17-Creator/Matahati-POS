<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposStockInH extends Model
{
    use HasFactory;

    public const STATUS_POSTED = 'POSTED';
    public const STATUS_DRAFT = 'DRAFT';

    protected $table = 'mpos_stock_in_h';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cno_stock_in',
        'dstock_in',
        'nid_purchase',
        'nid_supplier',
        'nid_outlet',
        'nid_user',
        'creceived_by',
        'cstatus',
        'cnote',
        'ntotal_qty',
        'ntotal_cost',
        'dcreated',
        'dupdated',
    ];

    protected function casts(): array
    {
        return [
            'dstock_in' => 'datetime',
            'dcreated' => 'datetime',
            'dupdated' => 'datetime',
            'ntotal_qty' => 'integer',
            'ntotal_cost' => 'decimal:2',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(MposPurchaseH::class, 'nid_purchase', 'nid');
    }

    public function supplier()
    {
        return $this->belongsTo(MposSupplier::class, 'nid_supplier', 'nid');
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
        return $this->hasMany(MposStockInD::class, 'nid_stock_in', 'nid');
    }

    /**
     * Generate unique Stock In number matching Olsera format:
     * 2 letters 'IN' + 14 digit numbers (6 digits YYMMDD + 8 digits running sequence), total 16 chars.
     * Example: IN26102900000404
     */
    public static function generateStockInNumber(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $prefix = 'IN' . $date->format('ymd');

        $last = self::where('cno_stock_in', 'LIKE', 'IN%')
            ->orderBy('nid', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last && !empty($last->cno_stock_in)) {
            $lastCno = trim($last->cno_stock_in);
            if (strlen($lastCno) > 8) {
                $lastSeqNum = (int) substr($lastCno, 8);
                if ($lastSeqNum > 0) {
                    $nextSeq = $lastSeqNum + 1;
                }
            }
        }

        $seq = str_pad($nextSeq, 8, '0', STR_PAD_LEFT);
        $stockInNumber = $prefix . $seq;

        while (self::where('cno_stock_in', $stockInNumber)->exists()) {
            $nextSeq++;
            $seq = str_pad($nextSeq, 8, '0', STR_PAD_LEFT);
            $stockInNumber = $prefix . $seq;
        }

        return $stockInNumber;
    }
}

