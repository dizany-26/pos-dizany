<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectronicCreditNoteAttempt extends Model
{
    protected $fillable = ['attempt_number', 'environment', 'result', 'sunat_code', 'message', 'duration_ms'];
    public function creditNote() { return $this->belongsTo(ElectronicCreditNote::class, 'electronic_credit_note_id'); }
}
