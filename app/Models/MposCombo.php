<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposCombo extends Model
{
    use HasFactory;

    protected $table = 'mpos_combo';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_combo_product',
        'nid_product',
        'nqty',
    ];

    public function comboProduct()
    {
        return $this->belongsTo(
            MposProduct::class,
            'nid_combo_product',
            'nid'
        );
    }

    public function product()
    {
        return $this->belongsTo(
            MposProduct::class,
            'nid_product',
            'nid'
        );
    }
}
