<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposUser extends Model
{
    use HasFactory;

    protected $table = 'mpos_user';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_user',
        'nid_outlet',
        'fowner',
        'fcashier',
        'fcaptain',
    ];

    protected function casts(): array
    {
        return [
            'fowner' => 'boolean',
            'fcashier' => 'boolean',
            'fcaptain' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(
            Muser::class,
            'nid_user',
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

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_user',
            'nid'
        );
    }
}
