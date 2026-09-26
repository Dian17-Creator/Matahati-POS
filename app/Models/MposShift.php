<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposShift extends Model
{
    use HasFactory;

    protected $table = 'mpos_shift';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nid_outlet',
        'nid_user',
        'cshift_no',
        'dopened_at',
        'dclosed_at',
        'nopening_cash',
        'nsales_cash',
        'nrefund_cash',
        'ncancellation_cash',
        'ncash_in',
        'ncash_out',
        'nexpected_cash',
        'nactual_cash',
        'ndifference',
        'nid_closed_by',
        'cstatus',
    ];

    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
            'nid_user' => 'integer',
            'dopened_at' => 'datetime',
            'dclosed_at' => 'datetime',
            'nopening_cash' => 'decimal:2',
            'nsales_cash' => 'decimal:2',
            'nrefund_cash' => 'decimal:2',
            'ncancellation_cash' => 'decimal:2',
            'ncash_in' => 'decimal:2',
            'ncash_out' => 'decimal:2',
            'nexpected_cash' => 'decimal:2',
            'nactual_cash' => 'decimal:2',
            'ndifference' => 'decimal:2',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function user()
    {
        return $this->belongsTo(MposUser::class, 'nid_user', 'nid');
    }

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }

    public function sales()
    {
        return $this->hasMany(MposSalesH::class, 'nid_shift', 'nid');
    }

    public function cashMovements()
    {
        return $this->hasMany(MposCashMovement::class, 'nid_shift', 'nid');
    }
}
