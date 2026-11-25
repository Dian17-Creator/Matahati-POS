<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MposSalesoffD extends Model
{
    use HasFactory;

    protected $table = 'mpos_sls_d';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nid_transaction',
        'nid_product',
        'cname',
        'nqty',
        'nqty_void',
        'nqty_refund',
        'nprice',
        'ncost',
        'nsubtotal',
        'cnote',
    ];

    protected function casts(): array
    {
        return [
            'nid_transaction' => 'integer',
            'nid_product' => 'integer',

            'nqty' => 'integer',
            'nqty_void' => 'integer',
            'nqty_refund' => 'integer',

            'nprice' => 'decimal:2',
            'ncost' => 'decimal:2',
            'nsubtotal' => 'decimal:2',

            'dcreated' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MposSalesoffH::class, 'nid_transaction', 'nid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }
}
