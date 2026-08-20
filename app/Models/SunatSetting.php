<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatSetting extends Model
{
    protected $fillable = [
        'enabled',
        'environment',
        'fiscal_ruc',
        'legal_name',
        'trade_name',
        'sol_user',
        'sol_password',
        'certificate_path',
        'certificate_password',
        'certificate_expires_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sol_password' => 'encrypted',
        'certificate_password' => 'encrypted',
        'certificate_expires_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'enabled' => false,
            'environment' => 'beta',
        ]);
    }
}
