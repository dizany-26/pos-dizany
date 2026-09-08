<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';
    public $timestamps = false;

    protected $fillable = [
        'nombre_empresa',
        'ruc',
        'logo',
        'moneda',
        'igv',
        'direccion',
        'telefono',
        'correo',
        'lema',
        'light_theme',
    ];

    protected $casts = [
        'light_theme' => 'array',
    ];

    public static function lightThemeDefaults(): array
    {
        return [
            'enabled' => false,
            'accent' => '#0d6efd',
            'header_from' => '#ffffff', 'header_to' => '#eef4ff', 'header_gradient' => true,
            'sidebar_from' => '#ffffff', 'sidebar_to' => '#f4f7fb', 'sidebar_gradient' => false,
            'footer_from' => '#ffffff', 'footer_to' => '#eef4ff', 'footer_gradient' => false,
            'table_from' => '#eaf2ff', 'table_to' => '#dceaff', 'table_gradient' => false,
            'modal_from' => '#ffffff', 'modal_to' => '#eef4ff', 'modal_gradient' => false,
        ];
    }
}
