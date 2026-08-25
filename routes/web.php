<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\SunatConfigurationController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ConsultaDocumentoController;
use App\Http\Controllers\DashboardAdminController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PublicElectronicDocumentController;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\ConfiguracionCatalogo;
use App\Models\Producto;
use App\Services\Tax\TaxProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/login-ajax', [LoginController::class, 'loginAjax'])
        ->middleware('throttle:10,1')
        ->name('login.ajax');

    Route::get('/password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    // Compatibilidad con enlaces emitidos por versiones anteriores del sistema.
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset.legacy');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::get('/', function () {
    $config = ConfiguracionCatalogo::first();
    $taxProfile = app(TaxProfileService::class)->current();
    $igv = $taxProfile?->default_tax_treatment === 'gravada'
        ? max(0, (float) $taxProfile->igv_rate)
        : 0;

    $productos = Producto::with(['categoria', 'imagenesCatalogo'])
        ->withSum(['lotes as stock_total' => function ($query) {
            $query->where('activo', 1)
                ->where('stock_actual', '>', 0);
        }], 'stock_actual')
        ->where('visible_en_catalogo', 1)
        ->where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $categorias = Categoria::whereHas('productos', function ($query) {
        $query->where('visible_en_catalogo', 1)
            ->where('activo', 1);
    })->orderBy('nombre')->get();

    return view('catalogo.index', compact('productos', 'categorias', 'config', 'igv'));
})->name('inicio');

Route::get('/catalogo', function () {
    return redirect()->route('inicio');
})->name('catalogo');
Route::get('/consultar-comprobante', [PublicElectronicDocumentController::class,'index'])->name('sunat.public.index');
Route::post('/consultar-comprobante', [PublicElectronicDocumentController::class,'search'])->middleware('throttle:10,1')->name('sunat.public.search');
Route::get('/consultar-comprobante/{document}/{kind}', [PublicElectronicDocumentController::class,'download'])->whereIn('kind',['pdf','xml'])->name('sunat.public.download');

/*
|--------------------------------------------------------------------------
| Rutas para cualquier usuario autenticado
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/perfil/cambiar-clave', [UsuarioController::class, 'cambiarMiClave'])
        ->name('perfil.cambiar-clave');
    Route::get('/notificaciones/{id}/abrir', [NotificacionController::class, 'abrir'])
        ->whereUuid('id')
        ->name('notificaciones.abrir');
    Route::get('/notificaciones/caja/pendientes', [NotificacionController::class, 'caja'])
        ->name('notificaciones.caja');
    Route::get('/sin-permisos', function () {
        abort(403, 'Tu usuario no tiene módulos asignados. Contacta al administrador.');
    })->name('sin-permisos');

    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])
        ->middleware('permission:dashboard.admin')
        ->name('admin.dashboard');
    Route::get('/empleado/dashboard', [EmpleadoController::class, 'dashboard'])
        ->middleware('permission:dashboard.empleado')
        ->name('empleado.dashboard');

    /*
    | Usuarios
    */
    Route::middleware('permission:usuarios')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::post('/usuarios/roles', [UsuarioController::class, 'storeRole'])->name('usuarios.roles.store');
        Route::put('/usuarios/roles/{role}', [UsuarioController::class, 'updateRole'])->name('usuarios.roles.update');
        Route::delete('/usuarios/roles/{role}', [UsuarioController::class, 'destroyRole'])->name('usuarios.roles.destroy');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        Route::post('/usuarios/cambiar-clave', [UsuarioController::class, 'cambiarClave'])
            ->name('usuarios.cambiarClave');
        Route::get('/usuarios/exportar-excel', [UsuarioController::class, 'exportarExcel'])
            ->name('usuarios.exportarExcel');
    });

    /*
    | Clientes
    */
    Route::middleware('permission:clientes')->group(function () {
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/verificar-documento', [ClienteController::class, 'verificarDocumento'])
            ->middleware('throttle:120,1')
            ->name('clientes.verificar-documento');
        Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show');
    });

    Route::middleware('permission:clientes,ventas')->group(function () {
        Route::get('/buscar-cliente/{dniRuc}', [ClienteController::class, 'buscarCliente']);
        Route::post('/guardar-cliente', [ClienteController::class, 'guardar'])->name('clientes.guardar');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    });

    /*
    | Proveedores
    */
    Route::get('/proveedores/verificar-documento', [ProveedorController::class, 'verificarDocumento'])
        ->middleware(['permission:proveedores', 'throttle:60,1'])
        ->name('proveedores.verificar-documento');

    Route::resource('proveedores', ProveedorController::class)
        ->middleware('permission:proveedores');

    Route::get('/consulta-documento/{tipo}/{numero}', [ConsultaDocumentoController::class, 'show'])
        ->whereIn('tipo', ['dni', 'ruc'])
        ->whereNumber('numero')
        ->middleware(['permission:proveedores,ventas,configuracion,usuarios', 'throttle:30,1'])
        ->name('documentos.consultar');

    /*
    | Productos
    */
    Route::middleware('permission:productos')->group(function () {
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::get('/productos/export', [ProductoController::class, 'export'])->name('productos.export');
        Route::get('/productos/{id}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{id}', [ProductoController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{id}/toggle-estado', [ProductoController::class, 'toggleEstado'])
            ->name('productos.toggleEstado');
    });

    Route::middleware('permission:productos.create')->group(function () {
        Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    });

    Route::middleware('permission:productos,productos.create,ventas')->group(function () {
        Route::get('/buscar-producto', [ProductoController::class, 'buscar'])->name('producto.buscar');
        Route::get('/producto/detalles/{id}', [ProductoController::class, 'mostrarDetalles']);
        Route::get('/productos/iniciales', [ProductoController::class, 'productosIniciales']);
        Route::get('/productos/ordenar', [ProductoController::class, 'ordenar'])
            ->name('productos.ordenar');
        Route::get('/productos/validar-codigo-barras', [ProductoController::class, 'validarCodigoBarras'])
            ->name('productos.validarCodigoBarras');
        Route::get('/productos/validar-codigo-barras-edicion', [ProductoController::class, 'validarCodigoBarrasEdicion'])
            ->name('productos.validarCodigoBarrasEdicion');
    });

    /*
    | Parámetros de productos
    */
    Route::middleware('permission:parametros.productos')->group(function () {
        Route::get('/productos/parametros', [ParametrosController::class, 'index'])
            ->name('productos.parametros');
        Route::post('/marcas', [ParametrosController::class, 'storeMarca'])
            ->name('parametros.marcas.store');
        Route::delete('/marcas/{id}', [ParametrosController::class, 'destroyMarca'])
            ->name('parametros.marcas.destroy');
        Route::put('/marcas/{id}', [ParametrosController::class, 'updateMarca'])
            ->name('parametros.marcas.update');
        Route::post('/marca/crear', [MarcaController::class, 'ajaxStore'])->name('marca.ajax.store');

        Route::post('/categorias', [ParametrosController::class, 'storeCategoria'])
            ->name('parametros.categorias.store');
        Route::delete('/categorias/{id}', [ParametrosController::class, 'destroyCategoria'])
            ->name('parametros.categorias.destroy');
        Route::put('/categorias/{id}', [ParametrosController::class, 'updateCategoria'])
            ->name('parametros.categorias.update');
        Route::post('/categoria/crear', [CategoriaController::class, 'ajaxStore'])
            ->name('categoria.ajax.store');

        Route::get('/validar-marca', function (Request $request) {
            return response()->json([
                'existe' => \App\Models\Marca::where('nombre', $request->nombre)->exists(),
            ]);
        });
        Route::get('/validar-categoria', function (Request $request) {
            return response()->json([
                'existe' => Categoria::where('nombre', $request->nombre)->exists(),
            ]);
        });
    });

    /*
    | Inventario
    */
    Route::middleware('permission:inventario.resumen')->group(function () {
        Route::get('/inventario/resumen', [InventarioController::class, 'resumen'])
            ->name('inventario.resumen');
        Route::get('/notificaciones/inventario', [NotificacionController::class, 'inventario']);
    });

    Route::middleware('permission:inventario.lote')->group(function () {
        Route::get('/inventario/lote', [InventarioController::class, 'lote'])
            ->name('inventario.lote');
        Route::post('/inventario/lote', [InventarioController::class, 'storeLote'])
            ->name('inventario.lote.store');
        Route::post('/inventario/compra-en-curso/limpiar', [InventarioController::class, 'limpiarCompraEnCurso'])
            ->name('inventario.compra-en-curso.limpiar');
        Route::get('/inventario/lotes', [InventarioController::class, 'lotes'])
            ->name('inventario.lotes');
        Route::get('/inventario/compras', [InventarioController::class, 'historialCompras'])
            ->name('inventario.compras');
        Route::get('/inventario/compras/lotes/{lote}/detalle', [InventarioController::class, 'detalleCompraLote'])
            ->name('inventario.compras.detalle');
        Route::post('/inventario/compras/lotes/{lote}/pagos', [InventarioController::class, 'registrarPagoCompra'])
            ->middleware('role:Administrador')
            ->name('inventario.compras.pagos');
        Route::get('/lotes/{lote}/edit', [InventarioController::class, 'edit'])
            ->name('lotes.edit');
        Route::put('/lotes/{lote}', [InventarioController::class, 'update'])
            ->name('lotes.update');
        Route::get('/lotes/{lote}/movimientos', [InventarioController::class, 'movimientos'])
            ->name('lotes.movimientos');
    });

    Route::post('/lotes/{lote}/ajuste', [InventarioController::class, 'ajustarStock'])
        ->middleware('role:Administrador')
        ->name('lotes.ajustar');

    /*
    | Ventas
    */
    Route::middleware('permission:ventas')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/lista', [VentaController::class, 'listar'])->name('ventas.listar');
        Route::get('/ventas/exportar-excel', [VentaController::class, 'exportarExcel'])
            ->name('ventas.exportarExcel');
        Route::get('/ventas/exportar-pdf', [VentaController::class, 'exportarPDF'])
            ->name('ventas.exportarPDF');
        Route::get('/ventas/filtrar-productos', [VentaController::class, 'filtrarPorCategoria']);
        Route::get('/ventas/obtener-serie-correlativo', [VentaController::class, 'obtenerSerieCorrelativo']);
        Route::get('/ventas/stock-fifo/{producto}', [VentaController::class, 'stockFIFO']);
        Route::post('/ventas/registrar', [VentaController::class, 'registrarVenta']);
        Route::post('/ventas/{venta}/cerrar-pendiente', [VentaController::class, 'cerrarPendiente']);
        Route::post('/ventas/{venta}/pagar-credito', [VentaController::class, 'pagarCredito']);
        Route::post('/ventas/{venta}/vincular-boleta-sol', [SunatConfigurationController::class, 'linkSolDocument'])
            ->middleware('role:Administrador')
            ->name('sunat.sol.link');
        Route::get('/ventas/{id}/detalle', [VentaController::class, 'detalle']);
        Route::get('/ventas/{id}', [VentaController::class, 'show'])->name('ventas.show');
        Route::get('/comprobantes/descargar/{filename}', [VentaController::class, 'descargarComprobante']);
        Route::post('/autorizar', [VentaController::class, 'autorizar'])
            ->middleware('throttle:10,1')
            ->name('ventas.autorizar');
    });

    Route::middleware('permission:reportes')->group(function () {
        Route::get('/ventas/{venta}/edit', [VentaController::class, 'edit'])->name('ventas.edit');
        Route::put('/ventas/{venta}', [VentaController::class, 'update'])->name('ventas.update');
    });

    /*
    | Gastos
    */
    Route::middleware('permission:gastos')->group(function () {
        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/gastos/crear', [GastoController::class, 'create'])->name('gastos.create');
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/gastos/{id}/edit', [GastoController::class, 'edit'])->name('gastos.edit');
        Route::put('/gastos/{id}', [GastoController::class, 'update'])->name('gastos.update');
        Route::delete('/gastos/{id}', [GastoController::class, 'destroy'])->name('gastos.destroy');
    });

    /*
    | Movimientos, reportes y configuración
    */
    Route::middleware('permission:movimientos')->group(function () {
        Route::get('/movimientos', [MovimientoController::class, 'index'])
            ->name('movimientos.index');
        Route::post('/cajas/{caja}/solicitar-cierre', [CajaController::class, 'solicitarCierre'])
            ->name('cajas.solicitar-cierre');
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/cajas/abrir', [CajaController::class, 'abrir'])
                ->name('cajas.abrir');
            Route::post('/cajas/{caja}/aprobar', [CajaController::class, 'aprobar'])
                ->name('cajas.aprobar');
            Route::post('/cajas/{caja}/reabrir', [CajaController::class, 'reabrir'])
                ->name('cajas.reabrir');
            Route::post('/cajas/{caja}/operaciones', [CajaController::class, 'registrarOperacion'])
                ->name('cajas.operaciones');
        });
        Route::get('/movimientos/reporte', [MovimientoController::class, 'reporte'])
            ->middleware('role:Administrador')
            ->name('movimientos.reporte');
        Route::get('/movimientos/gastos/{id}/detalle', [MovimientoController::class, 'detalleGasto'])
            ->name('movimientos.gastos.detalle');
        Route::get('/movimientos/compras/{movimiento}/detalle', [MovimientoController::class, 'detalleCompra'])
            ->middleware('role:Administrador')
            ->name('movimientos.compras.detalle');
        Route::post('/movimientos/compras/{movimiento}/pagos', [MovimientoController::class, 'registrarPagoCompra'])
            ->middleware('role:Administrador')
            ->name('movimientos.compras.pagos');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/ganancias', [ReporteController::class, 'ganancias'])
            ->name('reportes.ganancias');
        Route::get('/reportes/resumen', [ReporteController::class, 'resumen'])
            ->name('reportes.resumen');
        Route::get('/reportes/exportar/{formato}', [ReporteController::class, 'exportar'])
            ->whereIn('formato', ['csv', 'pdf'])
            ->name('reportes.exportar');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])
            ->name('configuracion.index');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])
            ->name('configuracion.update');
        Route::get('/configuracion/facturacion-electronica', [SunatConfigurationController::class, 'edit'])
            ->name('sunat.settings.edit');
        Route::put('/configuracion/facturacion-electronica', [SunatConfigurationController::class, 'update'])
            ->name('sunat.settings.update');
        Route::post('/configuracion/facturacion-electronica/perfil-tributario', [SunatConfigurationController::class, 'activateTaxProfile'])
            ->name('sunat.tax-profile.activate');
        Route::post('/configuracion/facturacion-electronica/ventas/{venta}/preparar', [SunatConfigurationController::class, 'prepareXml'])
            ->middleware('throttle:10,1')
            ->name('sunat.documents.prepare');
        Route::post('/configuracion/facturacion-electronica/demostracion', [SunatConfigurationController::class, 'downloadDemo'])
            ->middleware('throttle:5,1')
            ->name('sunat.demo.download');
        Route::post('/configuracion/facturacion-electronica/demostracion/zip', [SunatConfigurationController::class, 'downloadDemoZip'])
            ->middleware('throttle:5,1')
            ->name('sunat.demo.zip');
        Route::post('/configuracion/facturacion-electronica/documentos/{document}/reintentar', [SunatConfigurationController::class, 'retryDocument'])
            ->middleware('throttle:10,1')->name('sunat.documents.retry');
        Route::get('/configuracion/facturacion-electronica/documentos/{document}/{kind}', [SunatConfigurationController::class, 'downloadDocument'])
            ->whereIn('kind', ['xml', 'cdr'])->name('sunat.documents.download');
        Route::post('/configuracion/facturacion-electronica/resumenes', [SunatConfigurationController::class, 'createDailySummary'])
            ->middleware('throttle:10,1')->name('sunat.summaries.create');
        Route::post('/configuracion/facturacion-electronica/boletas/{venta}/anular', [SunatConfigurationController::class, 'cancelBoleta'])
            ->middleware('throttle:5,1')->name('sunat.boletas.cancel');
        Route::post('/configuracion/facturacion-electronica/resumenes/{summary}/reintentar', [SunatConfigurationController::class, 'retrySummary'])
            ->middleware('throttle:10,1')->name('sunat.summaries.retry');
        Route::get('/configuracion/facturacion-electronica/resumenes/{summary}/{kind}', [SunatConfigurationController::class, 'downloadSummary'])
            ->whereIn('kind', ['xml', 'cdr'])->name('sunat.summaries.download');
        Route::post('/configuracion/facturacion-electronica/ventas/{venta}/notas-credito', [SunatConfigurationController::class, 'createCreditNote'])
            ->middleware('throttle:5,1')->name('sunat.credit-notes.store');
        Route::post('/configuracion/facturacion-electronica/notas-credito/{note}/reintentar', [SunatConfigurationController::class, 'retryCreditNote'])
            ->middleware('throttle:10,1')->name('sunat.credit-notes.retry');
        Route::get('/configuracion/facturacion-electronica/notas-credito/{note}/{kind}', [SunatConfigurationController::class, 'downloadCreditNote'])
            ->whereIn('kind', ['xml', 'cdr'])->name('sunat.credit-notes.download');
    });

    Route::middleware(['role:Administrador', 'permission:backups'])->prefix('configuracion/copias-seguridad')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/', [BackupController::class, 'store'])->middleware('throttle:3,1')->name('backups.store');
        Route::get('/{filename}/descargar', [BackupController::class, 'download'])->name('backups.download');
        Route::post('/{filename}/restaurar', [BackupController::class, 'restore'])
            ->middleware('throttle:2,5')
            ->name('backups.restore');
        Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');
    });

    /*
    | Administración del catálogo público
    */
    Route::prefix('catalogo-admin')->group(function () {
        Route::get('/', function () {
            $config = ConfiguracionCatalogo::first();

            $productosVisibles = Producto::where('visible_en_catalogo', 1)
                ->where('activo', 1)
                ->count();
            $productosOcultos = Producto::where('visible_en_catalogo', 0)
                ->where('activo', 1)
                ->count();
            $categoriasPublicas = Categoria::whereHas('productos', function ($query) {
                $query->where('visible_en_catalogo', 1)
                    ->where('activo', 1);
            })->count();

            $camposConfig = [
                'nombre_empresa',
                'rubro',
                'telefono',
                'correo',
                'direccion',
                'mensaje_bienvenida',
                'texto_boton_whatsapp',
                'logo',
            ];

            $camposCompletos = collect($camposConfig)
                ->filter(fn ($campo) => $config && ! empty($config->{$campo}))
                ->count();
            $porcentajeConfig = (int) round(
                ($camposCompletos / count($camposConfig)) * 100
            );

            return view('catalogo.admin.index', compact(
                'config',
                'productosVisibles',
                'productosOcultos',
                'categoriasPublicas',
                'porcentajeConfig'
            ));
        })->middleware('permission:catalogo.ver,catalogo.config')
            ->name('catalogo.admin.index');

        Route::get('/configuracion', function () {
            $config = ConfiguracionCatalogo::first();

            return view('catalogo.admin.config', compact('config'));
        })->middleware('permission:catalogo.config')
            ->name('catalogo.admin.config');

        Route::post('/configuracion', function (Request $request) {
            $config = ConfiguracionCatalogo::first();
            $data = $request->except('logo');

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = time() . '.' . $file->getClientOriginalExtension();

                if (! file_exists(public_path('uploads/config'))) {
                    mkdir(public_path('uploads/config'), 0755, true);
                }

                $file->move(public_path('uploads/config'), $filename);
                $data['logo'] = $filename;
            }

            $config->update($data);

            return back()->with('success', 'Configuración actualizada correctamente');
        })->middleware('permission:catalogo.config')
            ->name('catalogo.admin.config.update');
    });
});
