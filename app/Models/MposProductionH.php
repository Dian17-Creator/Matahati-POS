<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposProductionH extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'mpos_production_h';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cno_production',
        'dproduction',
        'nid_outlet',
        'nid_user',
        'cstatus',
        'cnote',
        'ntotal_product_qty',
        'ntotal_material_cost',
        'nadditional_cost',
        'ntotal_production_cost',
        'dposted',
        'dcreated',
        'dupdated',
    ];

    protected function casts(): array
    {
        return [
            'dproduction' => 'date',
            'dposted' => 'datetime',
            'dcreated' => 'datetime',
            'dupdated' => 'datetime',
            'ntotal_product_qty' => 'decimal:2',
            'ntotal_material_cost' => 'decimal:2',
            'nadditional_cost' => 'decimal:2',
            'ntotal_production_cost' => 'decimal:2',
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

    public function products()
    {
        return $this->hasMany(MposProductionProduct::class, 'nid_production', 'nid');
    }

    public function materials()
    {
        return $this->hasMany(MposProductionMaterial::class, 'nid_production', 'nid');
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
     * Generate nomor produksi format Olsera: SP + ymd + 8 digit sequence
     * Contoh: SP26110900000004
     */
    public static function generateNumber(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $datePrefix = 'SP' . $date->format('ymd');

        $last = self::where('cno_production', 'LIKE', $datePrefix . '%')
            ->orderBy('nid', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last && !empty($last->cno_production)) {
            $lastCno = trim($last->cno_production);
            if (strlen($lastCno) >= 16) {
                $lastSeqNum = (int) substr($lastCno, 8);
                if ($lastSeqNum > 0) {
                    $nextSeq = $lastSeqNum + 1;
                }
            }
        }

        return $datePrefix . str_pad((string) $nextSeq, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung ulang total qty produk dan total biaya dari detail
     */
    public function recalculateTotals(): void
    {
        $totalQty = (float) $this->products()->sum('nqty');
        $materialCost = (float) $this->materials()->sum('nsubtotal');
        $additionalCost = (float) $this->nadditional_cost;
        $totalCost = $materialCost + $additionalCost;

        $this->update([
            'ntotal_product_qty' => $totalQty,
            'ntotal_material_cost' => $materialCost,
            'ntotal_production_cost' => $totalCost,
        ]);
    }
}

