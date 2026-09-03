<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposPayment extends Model
{
    use HasFactory;

    protected $table = 'mpos_payment';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'cname',
    ];

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_payment',
            'nid'
        );
    }
}
