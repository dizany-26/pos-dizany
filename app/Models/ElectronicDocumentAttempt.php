<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectronicDocumentAttempt extends Model
{
    protected $fillable = [
        'electronic_document_id', 'attempt_number', 'environment', 'result',
        'sunat_code', 'message', 'duration_ms',
    ];

    public function document()
    {
        return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id');
    }
}
