<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SunatDailySummaryItem extends Model
{
    protected $fillable = ['sunat_daily_summary_id', 'venta_id', 'condition_code', 'snapshot'];

    protected $casts = ['snapshot' => 'array'];

    public function summary()
    {
        return $this->belongsTo(SunatDailySummary::class, 'sunat_daily_summary_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
