<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposCashMovement extends Model
{
    use HasFactory;

    public const TYPE_CASH_IN = 'CASH_IN';

    public const TYPE_CASH_OUT = 'CASH_OUT';

    protected $table = 'mpos_cash_movement';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false; // Using custom dcreated_at, updated_at is nullable

    protected $fillable = [
        'nid_shift',
        'nid_user',
        'ctype',
        'namount',
        'cdescription',
        'dcreated_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'nid_shift' => 'integer',
            'nid_user' => 'integer',
            'namount' => 'decimal:2',
            'dcreated_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function shift()
    {
        return $this->belongsTo(MposShift::class, 'nid_shift', 'nid');
    }

    public function user()
    {
        return $this->belongsTo(MposUser::class, 'nid_user', 'nid');
    }
}
