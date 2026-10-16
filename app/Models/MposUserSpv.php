<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposUserSpv extends Model
{
    use HasFactory;

    protected $table = 'mpos_user_spv';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_user',
        'nid_outlet',
    ];

    public function user()
    {
        return $this->belongsTo(Muser::class, 'nid_user', 'nid');
    }

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }
}
