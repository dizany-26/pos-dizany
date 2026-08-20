<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectronicCreditNote extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_SENDING = 'sending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_OBSERVED = 'observed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'venta_id', 'electronic_document_id', 'document_type', 'series', 'number',
        'reason_code', 'reason', 'status', 'snapshot', 'xml_path', 'cdr_path',
        'xml_hash', 'sunat_code', 'sunat_description', 'sent_at', 'accepted_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function venta() { return $this->belongsTo(Venta::class); }
    public function originalDocument() { return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id'); }
    public function attempts() { return $this->hasMany(ElectronicCreditNoteAttempt::class); }
}
