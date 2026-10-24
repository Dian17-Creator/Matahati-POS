<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MposSupplier extends Model
{
    use HasFactory;

    protected $table = 'mpos_supplier';

    protected $primaryKey = 'nid';

    public $timestamps = false;

    protected $fillable = [
        'nid_outlet',
        'cname',
        'ccontact_person',
        'cphone',
        'cemail',
        'caddress',
        'cnote',
        'clogo',
        'factive',
        'dcreated',
    ];

    protected function casts(): array
    {
        return [
            'nid_outlet' => 'integer',
            'factive' => 'boolean',
            'dcreated' => 'datetime',
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

    public function purchases()
    {
        return $this->hasMany(
            MposPurchaseH::class,
            'nid_supplier',
            'nid'
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->clogo) {
            return null;
        }

        if (file_exists(public_path($this->clogo))) {
            return asset($this->clogo);
        }

        if (Storage::disk('public')->exists($this->clogo)) {
            return asset('storage/' . $this->clogo);
        }

        return null;
    }
}
