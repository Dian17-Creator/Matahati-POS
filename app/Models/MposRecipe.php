<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposRecipe extends Model
{
    use HasFactory;

    protected $table = 'mpos_recipe';

    protected $primaryKey = 'nid';

    public $timestamps = false;


    protected $fillable = [
        'nid_product',
        'nid_ingredient',
        'nqty',
    ];

    protected function casts(): array
    {
        return [
            'nqty' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(
            MposProduct::class,
            'nid_product',
            'nid'
        );
    }

    public function ingredient()
    {
        return $this->belongsTo(
            MposIngredients::class,
            'nid_ingredient',
            'nid'
        );
    }
}
