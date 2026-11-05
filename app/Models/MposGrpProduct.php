<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MposGrpProduct extends Model
{
    use HasFactory;

    protected $table = 'mpos_grp_product';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected static ?bool $showPosColumnExists = null;

    protected $fillable = [
        'cname',
        'nid_outlet',
        'ftrack_stock',
        'fshow_pos',
    ];

    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
            'ftrack_stock' => 'boolean',
            'fshow_pos' => 'boolean',
        ];
    }

    public static function hasShowPosColumn(): bool
    {
        if (static::$showPosColumnExists === null) {
            try {
                static::$showPosColumnExists = \Illuminate\Support\Facades\Schema::hasColumn('mpos_grp_product', 'fshow_pos');
            } catch (\Throwable $e) {
                static::$showPosColumnExists = false;
            }
        }

        return static::$showPosColumnExists;
    }

    public function products()
    {
        return $this->hasMany(
            MposProduct::class,
            'nid_category',
            'nid'
        );
    }

    public function outlet()
    {
        return $this->belongsTo(MposOutlet::class, 'nid_outlet', 'nid');
    }
}
