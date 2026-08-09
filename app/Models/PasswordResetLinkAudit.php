<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetLinkAudit extends Model
{
    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public static function fingerprint(string $token): string
    {
        return hash('sha256', $token);
    }
}
