<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class MposIngredients extends Model
{
    use HasFactory;

    protected $table = 'mpos_ingredients';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    public const SATUAN_LIST = [
        'BUNGKUS',
        'PACK',
        'DUS',
        'GRAM',
        'PCS',
        'Liter',
        'Ml',
        'Kaleng',
        'Botol',
        'Sachet',
        'Tabung',
        'Butir',
        'Biji',
    ];

    protected static ?bool $priceColumnExists = null;

    protected $fillable = [
        'cname',
        'nid_outlet',
        'csatuan',
        'nprice',
        'nstock',
    ];

    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
            'nprice' => 'decimal:2',
        ];
    }

    public static function hasPriceColumn(): bool
    {
        if (static::$priceColumnExists === null) {
            static::$priceColumnExists = Schema::hasColumn('mpos_ingredients', 'nprice');
        }

        return static::$priceColumnExists;
    }

    public function outlet()
    {
        return $this->belongsTo(
            MposOutlet::class,
            'nid_outlet',
            'nid'
        );
    }

    public function recipes()
    {
        return $this->hasMany(
            MposRecipe::class,
            'nid_ingredient',
            'nid'
        );
    }

    public function purchaseDetails()
    {
        return $this->hasMany(
            MposPurchaseD::class,
            'nid_ingredient',
            'nid'
        );
    }
}
