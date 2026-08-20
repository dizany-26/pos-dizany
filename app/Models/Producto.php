<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Lote;



class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo_barras',
        'nombre',
        'slug',
        'descripcion',

        // Presentaciones / conversiones
        'unidades_por_paquete',
        'paquetes_por_caja',
        'unidades_por_caja',
        'precio_venta',
        'precio_paquete',
        'precio_caja',

        // Ubicación / imagen
        'ubicacion',
        'imagen',

        // Vencimiento
        'maneja_vencimiento',
        'stock_minimo',

        // Relaciones
        'categoria_id',
        'marca_id',

        // Estados
        'activo',
        'visible_en_catalogo',
    ];

    protected $casts = [
        'activo'               => 'boolean',
        'visible_en_catalogo'  => 'boolean',
        'maneja_vencimiento'   => 'boolean',
        'stock_minimo'         => 'integer',
        'precio_venta'         => 'decimal:2',
        'precio_paquete'       => 'decimal:2',
        'precio_caja'          => 'decimal:2',
    ];


    /* -------------------
       Relaciones
    --------------------*/
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function detalleVentas()
    {
        return $this->hasMany(\App\Models\DetalleVenta::class, 'producto_id');
    }

    public function imagenesCatalogo()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    /* -------------------
       Utilidades
    --------------------*/
    public function tieneStock()
    {
        return $this->stock > 0;
    }

    /* -------------------
       Slug automático
    --------------------*/
    protected static function booted()
    {
        static::creating(function ($producto) {
            if (empty($producto->slug)) {
                $producto->slug = Str::slug($producto->nombre);
            }
        });
    }

    public function lotes()
{
    return $this->hasMany(Lote::class);
}
}
