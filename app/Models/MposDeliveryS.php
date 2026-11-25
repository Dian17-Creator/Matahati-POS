<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MposDeliveryS extends Model
{
    use HasFactory;

    public const PLATFORM_GOFOOD = 'GOFOOD';

    public const PLATFORM_GRABFOOD = 'GRABFOOD';

    public const PLATFORM_SHOPEEFOOD = 'SHOPEEFOOD';

    public const PLATFORM_TRAVELOKAEATS = 'TRAVELOKAEATS';

    /**
     * @var array<string, string>
     */
    public const DEFAULT_PLATFORMS = [
        self::PLATFORM_GOFOOD => 'GoFood',
        self::PLATFORM_GRABFOOD => 'GrabFood',
        self::PLATFORM_SHOPEEFOOD => 'ShopeeFood',
        self::PLATFORM_TRAVELOKAEATS => 'TravelokaEats',
    ];

    protected $table = 'mpos_delivery_s';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nid_outlet',
        'cplatform',
        'cname',
        'bis_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
            'bis_active' => 'boolean',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(MposDeliveryD::class, 'nid_delivery_s', 'nid');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    /**
     * Ensure all default food delivery platforms exist for the outlet (default OFF).
     */
    public static function ensureDefaultsForOutlet(int $outletId): void
    {
        if ($outletId <= 0) {
            return;
        }

        foreach (self::DEFAULT_PLATFORMS as $platform => $name) {
            static::firstOrCreate(
                [
                    'nid_outlet' => $outletId,
                    'cplatform' => $platform,
                ],
                [
                    'cname' => $name,
                    'bis_active' => false,
                ]
            );
        }
    }

    /**
     * Get active food delivery platforms for an outlet.
     *
     * @return Collection<int, self>
     */
    public static function getActiveForOutlet(int $outletId): Collection
    {
        if ($outletId <= 0) {
            return new Collection();
        }

        $order = array_keys(self::DEFAULT_PLATFORMS);

        return static::where('nid_outlet', $outletId)
            ->where('bis_active', true)
            ->get()
            ->sortBy(function (self $item) use ($order): int {
                $pos = array_search(strtoupper((string) $item->cplatform), $order, true);

                return $pos === false ? 999 : (int) $pos;
            })
            ->values();
    }
}
