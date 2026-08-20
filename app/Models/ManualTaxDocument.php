<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualTaxDocument extends Model
{
    protected $fillable = ['venta_id','document_type','series','number','issued_at','total','status','pdf_path','notes','linked_by'];
    protected $casts = ['issued_at' => 'datetime', 'total' => 'decimal:2'];
    public function venta() { return $this->belongsTo(Venta::class); }
}
