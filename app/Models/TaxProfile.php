<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxProfile extends Model
{
    protected $fillable = [
        'name', 'tax_regime', 'emission_system', 'environment',
        'default_tax_treatment', 'igv_rate', 'active', 'valid_from',
        'valid_until', 'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'active' => 'boolean', 'valid_from' => 'date', 'valid_until' => 'date',
        'confirmed_at' => 'datetime', 'igv_rate' => 'decimal:2',
    ];

    public function capabilities() { return $this->hasMany(TaxProfileCapability::class); }
}
