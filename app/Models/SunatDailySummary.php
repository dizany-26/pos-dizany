<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatDailySummary extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_SENDING = 'sending';
    public const STATUS_TICKET = 'ticket';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_OBSERVED = 'observed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'reference_date', 'issue_date', 'sequence', 'identifier', 'status', 'ticket',
        'xml_path', 'cdr_path', 'xml_hash', 'sunat_code', 'sunat_description',
        'sent_at', 'processed_at',
    ];

    protected $casts = [
        'reference_date' => 'date',
        'issue_date' => 'date',
        'sent_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SunatDailySummaryItem::class);
    }

    public function attempts()
    {
        return $this->hasMany(SunatDailySummaryAttempt::class);
    }
}
