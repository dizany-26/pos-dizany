<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatEstablishment extends Model
{
    protected $fillable = [
        'code', 'name', 'ubigeo', 'department', 'province', 'district',
        'address', 'is_default', 'active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active' => 'boolean',
    ];

    public static function defaultLocation(): ?self
    {
        return static::where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }
}
