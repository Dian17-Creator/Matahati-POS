<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Muser extends Authenticatable
{
    use HasApiTokens, HasFactory;
    protected $table = 'muser';
    protected $primaryKey = 'nid';
    public $timestamps = false;
    protected $fillable = [
        'cemail',
        'cmailaddress',
        'cphone',
        'cktp',
        'cname',
        'cfullname',
        'caccnumber',
        'cpassword',
        'dtanggalmasuk',
        'dcreated',
        'fadmin',
        'fsuper',
        'fhrd',
        'factive',
        'fsenior',
        'niddept',
        'niddeptpayroll',
        'cdeptname',
        'finger_id',
        'rekening_id',
        'bank',
        'fface_approved',
        'fnotif',
    ];
    protected $hidden = [
        'cpassword',
    ];
    protected $casts = [
        'dcreated' => 'datetime:Y-m-d H:i:s',
        'fadmin' => 'boolean',
        'fsuper' => 'boolean',
        'fhrd' => 'boolean',
        'fsenior' => 'boolean',
        'finger_id' => 'integer',
        'factive' => 'boolean',
        'fnotif' => 'boolean',
        'fface_approved' => 'boolean',
    ];
    public function getAuthPassword()
    {
        return $this->cpassword;
    }

    public function getAuthPasswordName()
    {
        return 'cpassword';
    }
    public function isSuperAdmin()
    {
        return $this->fsuper == 1;
    }
    public function isAdmin()
    {
        return $this->fadmin == 1;
    }
    public function isHrd()
    {
        return $this->fhrd == 1;
    }
    public function isSenior()
    {
        return $this->fsenior == 1;
    }

    public function posUser()
    {
        return $this->hasOne(MposUser::class, 'nid_user', 'nid');
    }

    public function posUsers()
    {
        return $this->hasMany(MposUser::class, 'nid_user', 'nid');
    }

    public function isOwner()
    {
        if ($this->posUser && (bool) $this->posUser->fowner) {
            return true;
        }

        return $this->posUsers()->where('fowner', 1)->exists();
    }

    public function spvOutlets()
    {
        return $this->hasMany(MposUserSpv::class, 'nid_user', 'nid');
    }

    public function isSpv()
    {
        if ($this->posUser && (bool) $this->posUser->fspv) {
            return true;
        }

        if ($this->spvOutlets()->exists()) {
            return true;
        }

        return $this->posUsers()->where('fspv', 1)->exists();
    }

    public function department()
    {
        return $this->belongsTo(Mdepartment::class, 'niddept', 'nid');
    }
}
