<?php
namespace App\Models;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';
    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'tax_profile_id',
        'fecha',
        'tipo_comprobante',
        'emission_system',
        'tax_treatment',
        'igv_rate',
        'serie',
        'correlativo',
        'metodo_pago',
        'estado',
        'estado_sunat',
        'op_gravadas',
        'op_exoneradas',
        'op_inafectas',
        'op_nrus',
        'igv',
        'total',
        'credit_due_date',
        'saldo',        // ✅ FALTABA
        'activo'
    ];
    
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class);
    }
    protected $casts = [
        'fecha' => 'datetime',
        'credit_due_date' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function electronicDocument()
    {
        return $this->hasOne(ElectronicDocument::class);
    }

    public function dailySummaryItems()
    {
        return $this->hasMany(SunatDailySummaryItem::class);
    }

    public function electronicCreditNotes()
    {
        return $this->hasMany(ElectronicCreditNote::class);
    }

    public function taxProfile() { return $this->belongsTo(TaxProfile::class); }
    public function manualTaxDocument() { return $this->hasOne(ManualTaxDocument::class); }

    public function calcularTotalNuevo()
    {
        return $this->detalleVentas->sum(function ($detalle) {
            $precio = $detalle->precio_mayor && $detalle->precio_mayor > 0
                ? $detalle->precio_mayor
                : $detalle->precio_unitario;
            return $precio * $detalle->cantidad;
        });
    }

    public function calcularGanancia()
    {
        return $this->detalleVentas->sum('ganancia');
    }

    public function calcularSubtotal()
    {
        return $this->detalleVentas->sum('subtotal');
    }

    public function estaActiva()
    {
        return $this->estado === 'activa';
    }

    public function estaAnulada()
    {
        return $this->estado === 'anulada';
    }
}
