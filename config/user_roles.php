<?php

return [
    'permissions' => [
        'dashboard.admin',
        'dashboard.empleado',
        'usuarios',
        'clientes',
        'proveedores',
        'productos',
        'productos.create',
        'inventario.resumen',
        'parametros.productos',
        'inventario.lote',
        'ventas',
        'movimientos',
        'gastos',
        'reportes',
        'configuracion',
        'backups',
        'catalogo.ver',
        'catalogo.config',
    ],

    'templates' => [
        'Administrador' => [
            'dashboard.admin', 'dashboard.empleado', 'usuarios', 'clientes',
            'proveedores', 'productos', 'productos.create', 'inventario.resumen',
            'parametros.productos', 'inventario.lote', 'ventas', 'movimientos',
            'gastos', 'reportes', 'configuracion', 'backups', 'catalogo.ver', 'catalogo.config',
        ],
        'Encargado' => [
            'dashboard.empleado', 'clientes', 'proveedores', 'productos',
            'productos.create', 'inventario.resumen', 'inventario.lote', 'ventas',
            'movimientos', 'gastos', 'reportes', 'catalogo.ver',
        ],
        'Cajero' => [
            'dashboard.empleado', 'clientes', 'ventas', 'movimientos',
        ],
        'Almacén' => [
            'dashboard.empleado', 'proveedores', 'productos', 'productos.create',
            'inventario.resumen', 'inventario.lote',
        ],
        'Empleado' => [
            'dashboard.empleado',
        ],
    ],

    'descriptions' => [
        'Administrador' => 'Acceso total y protegido a todo el sistema.',
        'Encargado' => 'Supervisa ventas, inventario, movimientos y reportes.',
        'Cajero' => 'Atiende ventas, clientes y operaciones de caja.',
        'Almacén' => 'Gestiona productos, proveedores e inventario.',
        'Empleado' => 'Acceso básico; completa manualmente los permisos necesarios.',
    ],
];
