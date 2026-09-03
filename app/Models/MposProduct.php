<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposProduct extends Model
{
    use HasFactory;

    protected $table = 'mpos_product';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cname',
        'nid_category',
        'nprice',
        'cphotos',
        'cstatus',
        'fcombo',
    ];


    protected function casts(): array
    {
        return [
            'nprice' => 'decimal:2',
            'fcombo' => 'boolean',
        ];
    }


    public function category()
    {
        return $this->belongsTo(
            MposGrpProduct::class,
            'nid_category',
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
}
