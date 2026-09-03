<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposIngredients extends Model
{
    use HasFactory;

    protected $table = 'mpos_ingredients';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cname',
        'csatuan',
        'nstock',
    ];

    public function recipes()
    {
        return $this->hasMany(
            MposRecipe::class,
            'nid_ingredient',
            'nid'
        );
    }
}
