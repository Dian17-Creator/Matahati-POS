<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MposDeliveryD extends Model
{
    use HasFactory;

    protected $table = 'mpos_delivery_d';

    protected $primaryKey = 'nid';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nid_delivery_s',
        'nid_product',
        'nprice',
        'bis_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nid_delivery_s' => 'integer',
            'nid_product' => 'integer',
            'nprice' => 'decimal:2',
            'bis_active' => 'boolean',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(MposDeliveryS::class, 'nid_delivery_s', 'nid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MposProduct::class, 'nid_product', 'nid');
    }
}
