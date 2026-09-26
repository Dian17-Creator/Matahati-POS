<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposCustType extends Model
{
    use HasFactory;

    protected $table = 'mpos_cust_type';

    protected $primaryKey = 'nid';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false; // Assuming no timestamps as per description

    protected $fillable = [
        'cname',
    ];

    public function customers()
    {
        return $this->hasMany(
            MposCust::class,
            'nid_type',
            'nid'
        );
    }
}
