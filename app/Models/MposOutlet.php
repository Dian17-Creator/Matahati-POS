<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposOutlet extends Model
{
    use HasFactory;


    public const TRANSACTION_TYPE_ONLINE = 'ONLINE';

    public const TRANSACTION_TYPE_OFFLINE = 'OFFLINE';

    public const TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_ONLINE,
        self::TRANSACTION_TYPE_OFFLINE,
    ];

    protected $table = 'mpos_outlet';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_dept',
        'cname',
        'ctransaction_type',
    ];

    public function resolvedTransactionType(): string
    {
        return strtoupper(trim((string) ($this->ctransaction_type ?? ''))) === self::TRANSACTION_TYPE_OFFLINE
            ? self::TRANSACTION_TYPE_OFFLINE
            : self::TRANSACTION_TYPE_ONLINE;
    }

    public function isOfflineTransaction(): bool
    {
        return $this->resolvedTransactionType() === self::TRANSACTION_TYPE_OFFLINE;
    }

    public static function resolveTransactionTypeForOutlet(?int $outletId): ?string
    {
        if (! $outletId || $outletId <= 0) {
            return null;
        }

        $outlet = static::find($outletId);

        return $outlet ? $outlet->resolvedTransactionType() : self::TRANSACTION_TYPE_ONLINE;
    }


    public function posUsers()
    {
        return $this->hasMany(
            MposUser::class,
            'nid_outlet',
            'nid'
        );
    }

    public function products()
    {
        return $this->hasMany(
            MposProduct::class,
            'nid_outlet',
            'nid'
        );
    }

    public function ingredients()
    {
        return $this->hasMany(
            MposIngredients::class,
            'nid_outlet',
            'nid'
        );
    }

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_outlet',
            'nid'
        );
    }

    public function offlineSales()
    {
        return $this->hasMany(
            MposSalesoffH::class,
            'nid_outlet',
            'nid'
        );
    }

    public function taxSettings()
    {
        return $this->hasMany(
            MposTaxSetting::class,
            'nid_outlet',
            'nid'
        );
    }

    public function department()
    {
        return $this->belongsTo(Mdepartment::class, 'nid_dept', 'nid');
    }
}
