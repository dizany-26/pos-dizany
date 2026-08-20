<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxProfileCapability extends Model
{
    protected $fillable = ['tax_profile_id', 'capability', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];
    public function profile() { return $this->belongsTo(TaxProfile::class, 'tax_profile_id'); }
}
