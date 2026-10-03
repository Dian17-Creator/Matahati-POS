<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposGrpProduct extends Model
{
    use HasFactory;

    protected $table = 'mpos_grp_product';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cname',
        'nid_outlet',
    ];

    public function products()
    {
        return $this->hasMany(
            MposProduct::class,
            'nid_category',
            'nid'
        );
    }

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }
}
