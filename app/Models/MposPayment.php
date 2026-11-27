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
        'nid_outlet',
        'cname',
    ];

    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(
            MposOutlet::class,
            'nid_outlet',
            'nid'
        );
    }

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_payment',
            'nid'
        );
    }
}
