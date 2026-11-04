<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MposProduct extends Model
{
    use HasFactory;

    protected $table = 'mpos_product';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cname',
        'nid_category',
        'nid_outlet',
        'nprice',
        'nprice_online',
        'nprice_buy',
        'nqty',
        'cphotos',
        'cstatus',
        'fcombo',
        'updated_by',
        'updated_at',
        'dcreated',
    ];

    protected function casts(): array
    {
        return [
            'nprice' => 'decimal:2',
            'nprice_online' => 'decimal:2',
            'nprice_buy' => 'decimal:2',
            'nqty' => 'integer',
            'fcombo' => 'boolean',
            'updated_at' => 'datetime',
            'dcreated' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MposProduct $product) {
            if (empty($product->dcreated)) {
                $product->dcreated = Carbon::now();
            }
            if (Auth::check() && empty($product->updated_by)) {
                $product->updated_by = Auth::id();
            }
            if (empty($product->updated_at)) {
                $product->updated_at = Carbon::now();
            }
        });

        static::updating(function (MposProduct $product) {
            if (Auth::check()) {
                $product->updated_by = Auth::id();
            }
            $product->updated_at = Carbon::now();
        });
    }

    public function updatedBy()
    {
        return $this->belongsTo(Muser::class, 'updated_by', 'nid');
    }


    public function category()
    {
        return $this->belongsTo(
            MposGrpProduct::class,
            'nid_category',
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

    public function recipes()
    {
        return $this->hasMany(
            MposRecipe::class,
            'nid_product',
            'nid'
        );
    }


    public function comboItems()
    {
        return $this->hasMany(
            MposCombo::class,
            'nid_combo_product',
            'nid'
        );
    }


    public function includedInCombos()
    {
        return $this->hasMany(
            MposCombo::class,
            'nid_product',
            'nid'
        );
    }

    public function salesDetails()
    {
        return $this->hasMany(
            MposSalesD::class,
            'nid_product',
            'nid'
        );
    }

    public function productPrices()
    {
        return $this->hasMany(
            MposProductPrice::class,
            'nid_product',
            'nid'
        );
    }
}
