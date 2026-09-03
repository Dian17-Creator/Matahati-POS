<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposOutlet extends Model
{
    use HasFactory;


    protected $table = 'mpos_outlet';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_dept',
        'cname',
    ];


    public function posUsers()
    {
        return $this->hasMany(
            MposUser::class,
            'nid_outlet',
            'nid'
        );
    }

    public function sales()
    {
        return $this->hasMany(
            MposSalesH::class,
            'nid_outlet',
            'nid'
        );
    }

    public function department()
    {
        return $this->belongsTo(Mdepartment::class, 'nid_dept', 'nid');
    }
}
