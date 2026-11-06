<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposStockMutation extends Model
{
    use HasFactory;

    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';

    public const STATUS_POSTED = 'POSTED';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_VOID = 'VOID';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const ITEM_PRODUCT = 'PRODUCT';
    public const ITEM_INGREDIENT = 'INGREDIENT';

    protected $table = 'mpos_stock_mutations';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cno_ref',
        'dmutation',
        'ctype',
        'nid_outlet',
        'nid_target_outlet',
        'nid_user',
        'citem_type',
        'nid_product',
        'nid_ingredient',
        'nqty',
        'ncost',
        'nsubtotal',
        'creason',
        'cstatus',
        'cnote',
        'dexpired',
        'crack',
        'dcreated',
        'dupdated',
    ];

    protected function casts(): array
    {
        return [
            'dmutation' => 'datetime',
            'dexpired' => 'date',
            'dcreated' => 'datetime',
            'dupdated' => 'datetime',
            'nqty' => 'decimal:2',
            'ncost' => 'decimal:2',
            'nsubtotal' => 'decimal:2',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public function targetOutlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_target_outlet', 'nid');
    }

    public function user()
    {
        return $this->belongsTo(Muser::class, 'nid_user', 'nid');
    }

    public function product()
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }

    public function ingredient()
    {
        return $this->belongsTo(MposIngredients::class, 'nid_ingredient', 'nid');
    }

    public function isIngredient(): bool
    {
        return strtoupper((string) $this->citem_type) === self::ITEM_INGREDIENT
            || (!empty($this->nid_ingredient) && empty($this->nid_product));
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
        return in_array(strtoupper((string) $this->cstatus), [self::STATUS_CANCELLED, self::STATUS_VOID], true);
    }

    /**
     * Generate unique reference number matching Olsera format:
     * - Stock IN: 'IN' + 6 digit YYMMDD + 8 digit running sequence, total 16 chars (e.g. IN26110300000001)
     * - Stock OUT: 'OG' + 6 digit YYMMDD + 8 digit running sequence, total 16 chars (e.g. OG26110300000001)
     */
    public static function generateNumber(string $type = 'IN', ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $code = strtoupper(trim($type)) === self::TYPE_OUT ? 'OG' : 'IN';
        $prefix = $code . $date->format('ymd');

        $last = self::where('cno_ref', 'LIKE', $code . '%')
            ->orderBy('nid', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last && !empty($last->cno_ref)) {
            $lastCno = trim($last->cno_ref);
            if (strlen($lastCno) > 8) {
                $lastSeqNum = (int) substr($lastCno, 8);
                if ($lastSeqNum > 0) {
                    $nextSeq = $lastSeqNum + 1;
                }
            }
        }

        $seq = str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
        $generated = $prefix . $seq;

        while (self::where('cno_ref', $generated)->exists()) {
            $nextSeq++;
            $seq = str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
            $generated = $prefix . $seq;
        }

        return $generated;
    }
}

