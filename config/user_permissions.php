<?php

return [
    'groups' => [
        'Inicio' => [
            'dashboard.admin' => 'Dashboard administrador',
            'dashboard.empleado' => 'Dashboard empleado',
        ],
        'Gestión' => [
            'gestion.usuarios' => 'Usuarios',
            'gestion.clientes' => 'Clientes',
            'gestion.proveedores' => 'Proveedores',
        ],
        'Inventario' => [
            'inventario.productos' => 'Productos',
            'inventario.parametros' => 'Parámetros de productos',
            'inventario.resumen' => 'Resumen inventario',
            'inventario.lotes' => 'Ingreso de lotes',
        ],
        'Operaciones' => [
            'operaciones.ventas' => 'Ventas',
            'operaciones.movimientos' => 'Movimientos',
            'operaciones.gastos' => 'Gastos',
        ],
        'Análisis' => [
            'analisis.reportes' => 'Reportes',
        ],
        'Sistema' => [
            'sistema.configuracion' => 'Configuración',
        ],
        'Catálogo Web' => [
            'catalogo.admin' => 'Vista catálogo',
            'catalogo.config' => 'Configurar catálogo',
        ],
    ],
    'role_defaults' => [
        'Administrador' => ['*'],
        'Empleado' => [
            'dashboard.empleado',
            'operaciones.ventas',
            'operaciones.gastos',
        ],
    ],
];
