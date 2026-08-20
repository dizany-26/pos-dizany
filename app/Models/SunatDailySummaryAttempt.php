<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatDailySummaryAttempt extends Model
{
    protected $fillable = [
        'sunat_daily_summary_id', 'attempt_number', 'operation', 'result',
        'sunat_code', 'message', 'duration_ms',
    ];

    public function summary()
    {
        return $this->belongsTo(SunatDailySummary::class, 'sunat_daily_summary_id');
    }
}
