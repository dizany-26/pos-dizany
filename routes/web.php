<?php
use Illuminate\Http\Request;

use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Exports\UsuariosExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\DashboardAdminController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\NotificacionController;
//consultar dni y ruc
use Illuminate\Support\Facades\Http;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\ConfiguracionCatalogo;


// Rutas autenticación
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::post('/perfil/cambiar-clave', [UsuarioController::class, 'cambiarMiClave'])
    ->middleware('auth')
    ->name('perfil.cambiar-clave');

// Rutas exclusivas para Administrador
Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])
    ->name('admin.dashboard');
    // Otras rutas solo para admin...
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
    Route::get('/productos/export', [ProductoController::class, 'export'])->name('productos.export');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/{id}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{id}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/ganancias', [ReporteController::class, 'ganancias'])->name('reportes.ganancias');
    Route::get('/reportes/resumen', [ReporteController::class, 'resumen'])
     ->name('reportes.resumen');
    Route::get('/movimientos', [MovimientoController::class, 'index'])
    ->name('movimientos.index');


    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');


});

// Rutas para Empleado
Route::middleware(['auth', 'role:Empleado'])->group(function () {
    Route::get('/empleado/dashboard', [EmpleadoController::class, 'index'])->name('empleado.dashboard');
    Route::get('/empleado/dashboard', [EmpleadoController::class, 'dashboard'])->name('empleado.dashboard');
    // Otras rutas solo para empleado...
});

// Rutas compartidas por Admin y Empleado
Route::middleware(['auth', 'role:Administrador,Empleado'])->group(function () {
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
    Route::get('/gastos/crear', [GastoController::class, 'create'])->name('gastos.create');
    Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    Route::get('/ventas/lista', [VentaController::class, 'listar'])->name('ventas.listar');
    

    // Otras rutas comunes aquí...
    Route::get('/ventas/exportar-excel', [VentaController::class, 'exportarExcel'])->name('ventas.exportarExcel');
    Route::get('/ventas/exportar-pdf', [VentaController::class, 'exportarPDF'])->name('ventas.exportarPDF');
});

// Ruta raíz redirige según rol
Route::get('/', function () {
    if (auth()->check()) {
        $rol = auth()->user()->rol->nombre;
        if ($rol == 'Administrador') {
            return redirect()->route('admin.dashboard');
        } elseif ($rol == 'Empleado') {
            return redirect()->route('empleado.dashboard');
        }
    }
    return redirect()->route('login');
});

// Ruta para Buscar productos

Route::get('/buscar-producto', [ProductoController::class, 'buscar'])->name('producto.buscar');

// Rutas para editar y actualizar un producto
Route::get('productos/{id}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
Route::put('productos/{id}', [ProductoController::class, 'update'])->name('productos.update');

// Ruta para activar y desactivar un producto
Route::patch('/productos/{id}/toggle-estado', [ProductoController::class, 'toggleEstado'])->name('productos.toggleEstado');
// Ruta para validar codigo de barras existente
Route::get('/productos/validar-codigo-barras', [ProductoController::class, 'validarCodigoBarras'])->name('productos.validarCodigoBarras');
Route::get('/productos/validar-codigo-barras-edicion', [ProductoController::class, 'validarCodigoBarrasEdicion'])->name('productos.validarCodigoBarrasEdicion');

// ruta para crear usuarios
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
Route::resource('usuarios', UsuarioController::class)->only([
    'index', 'store', 'update', 'destroy'
]);
Route::post('/usuarios/cambiar-clave', [UsuarioController::class, 'cambiarClave'])->name('usuarios.cambiarClave');
Route::get('/usuarios/exportar-excel', [UsuarioController::class, 'exportarExcel'])->name('usuarios.exportarExcel');

// Buscar cliente por DNI o RUC
Route::get('/buscar-cliente/{dniRuc}', [ClienteController::class, 'buscarCliente']);
// Guardar cliente
Route::post('/guardar-cliente', [ClienteController::class, 'guardar'])->name('clientes.guardar');
Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');

// Guardar proveedor
Route::resource('proveedores', App\Http\Controllers\ProveedorController::class);

// Ruta para registrar la venta
Route::post('/ventas/registrar', [VentaController::class, 'registrarVenta'])->middleware('auth');
Route::get('/ventas/obtener-serie-correlativo', [VentaController::class, 'obtenerSerieCorrelativo']);

// Ruta para mostrar detalles de venta
Route::get('/ventas/{id}', [VentaController::class, 'show'])->name('ventas.show');
// Ruta editar y eliminar
Route::get('/ventas/{venta}/edit', [VentaController::class, 'edit'])->name('ventas.edit');
Route::put('/ventas/{venta}', [VentaController::class, 'update'])->name('ventas.update');
// Ruta buscar productos para editar venta
//Route::get('/api/productos/buscar', [ProductoController::class, 'buscar']);

// Ruta eliminar
Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])
     ->name('ventas.destroy')
     ->middleware('auth'); // Asegúrate de tener autenticación

// ruta para verificar credenciales de login
Route::post('/login-ajax', [LoginController::class, 'loginAjax'])->name('login.ajax');


// Mostrar formulario para ingresar email
Route::get('/password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
// Procesar email y enviar enlace
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
// Mostrar formulario para restablecer contraseña
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
// Guardar nueva contraseña
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
// Ruta para obtener los detalles del producto
Route::get('/producto/detalles/{id}', [ProductoController::class, 'mostrarDetalles']);

//INTERFAZ PARAMETROS
// Vista principal
Route::get('/productos/parametros', [ParametrosController::class, 'index'])->name('productos.parametros');

// Rutas de MARCAS
Route::post('/marcas', [ParametrosController::class, 'storeMarca'])->name('parametros.marcas.store');
Route::delete('/marcas/{id}', [ParametrosController::class, 'destroyMarca'])->name('parametros.marcas.destroy');
Route::post('/marca/crear', [MarcaController::class, 'ajaxStore'])->name('marca.ajax.store');

Route::put('/marcas/{id}', [ParametrosController::class, 'updateMarca'])
    ->name('parametros.marcas.update');

// Rutas de CATEGORÍAS
Route::post('/categorias', [ParametrosController::class, 'storeCategoria'])->name('parametros.categorias.store');
Route::delete('/categorias/{id}', [ParametrosController::class, 'destroyCategoria'])->name('parametros.categorias.destroy');
Route::post('/categoria/crear', [CategoriaController::class, 'ajaxStore'])->name('categoria.ajax.store');

Route::put('/categorias/{id}', [ParametrosController::class, 'updateCategoria'])
    ->name('parametros.categorias.update');

// Validación en tiempo real para marcas
Route::get('/validar-marca', function (Illuminate\Http\Request $request) {
    $existe = \App\Models\Marca::where('nombre', $request->nombre)->exists();
    return response()->json(['existe' => $existe]);
});
// Validación en tiempo real para categorías
Route::get('/validar-categoria', function (Request $request) {
    $existe = Categoria::where('nombre', $request->nombre)->exists();
    return response()->json(['existe' => $existe]);
});

// resumen
Route::get('/notificaciones/inventario', [NotificacionController::class, 'inventario']);

Route::get('/inventario/resumen', [InventarioController::class, 'resumen'])
    ->name('inventario.resumen');

Route::post('/autorizar', [VentaController::class, 'autorizar'])->name('ventas.autorizar');
Route::get('/comprobantes/descargar/{filename}', [VentaController::class, 'descargarComprobante']);

Route::get('/ventas/filtrar-productos', [VentaController::class, 'filtrarPorCategoria']);

Route::get('/productos/iniciales', [ProductoController::class, 'productosIniciales']);
// ORDENAR VENTAS (F)
Route::get('/productos/ordenar', [ProductoController::class, 'ordenar'])
    ->name('productos.ordenar');

Route::post('/ventas/{venta}/cerrar-pendiente', [VentaController::class, 'cerrarPendiente']);

Route::get('/ventas/{id}/detalle', [VentaController::class, 'detalle']);
Route::post('/ventas/{venta}/pagar-credito', [VentaController::class, 'pagarCredito']);

Route::get('/movimientos/reporte', 
    [MovimientoController::class, 'reporte']
)->name('movimientos.reporte');

Route::delete('/gastos/{id}', [GastoController::class, 'destroy'])
     ->name('gastos.destroy');
     
Route::get('/gastos/{id}/edit', [GastoController::class, 'edit'])
    ->name('gastos.edit');

Route::put('/gastos/{id}', [GastoController::class, 'update'])
    ->name('gastos.update');

Route::get('/inventario/lote', [InventarioController::class, 'lote'])
    ->name('inventario.lote');

Route::post('/inventario/lote', [InventarioController::class, 'storeLote'])
    ->name('inventario.lote.store');

Route::get('/inventario/lotes', [InventarioController::class, 'lotes'])
    ->name('inventario.lotes');

// ✏️ Editar lote
    Route::get('/lotes/{lote}/edit', [InventarioController::class, 'edit'])
    ->name('lotes.edit');

Route::put('/lotes/{lote}', [InventarioController::class, 'update'])
    ->name('lotes.update');

        
    Route::post('/lotes/{lote}/ajuste', [InventarioController::class, 'ajustarStock'])
    ->name('lotes.ajustar');

    Route::get('lotes/{lote}/movimientos', [InventarioController::class, 'movimientos'])
    ->name('lotes.movimientos');

Route::get(
    '/ventas/stock-fifo/{producto}',
    [VentaController::class, 'stockFIFO']
);
//CATALAGO
Route::get('/catalogo', function () {

    $config = ConfiguracionCatalogo::first();

    $productos = Producto::with('categoria')
        ->where('visible_en_catalogo', 1)
        ->where('activo', 1)
        ->get();

    $categorias = Categoria::whereHas('productos', function ($q) {
        $q->where('visible_en_catalogo', 1)
          ->where('activo', 1);
    })->get();

    return view('catalogo.index', compact('productos', 'categorias', 'config'));
});

Route::prefix('catalogo-admin')->middleware('auth')->group(function () {

    Route::get('/', function () {
        $config = ConfiguracionCatalogo::first();

        $productosVisibles = Producto::where('visible_en_catalogo', 1)
            ->where('activo', 1)
            ->count();

        $productosOcultos = Producto::where('visible_en_catalogo', 0)
            ->where('activo', 1)
            ->count();

        $categoriasPublicas = Categoria::whereHas('productos', function ($q) {
            $q->where('visible_en_catalogo', 1)
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

        $camposCompletos = collect($camposConfig)->filter(function ($campo) use ($config) {
            return $config && !empty($config->{$campo});
        })->count();

        $porcentajeConfig = (int) round(($camposCompletos / count($camposConfig)) * 100);

        return view('catalogo.admin.index', compact(
            'config',
            'productosVisibles',
            'productosOcultos',
            'categoriasPublicas',
            'porcentajeConfig'
        ));
        })->name('catalogo.admin.index');

    Route::get('/configuracion', function () {

        $config = ConfiguracionCatalogo::first();

        return view('catalogo.admin.config', compact('config'));

        })->name('catalogo.admin.config');

        Route::post('/configuracion', function (\Illuminate\Http\Request $request) {

            $config = ConfiguracionCatalogo::first();

            $data = $request->except('logo');

            // Si suben un logo nuevo
            if ($request->hasFile('logo')) {

                $file = $request->file('logo');
                $filename = time() . '.' . $file->getClientOriginalExtension();

                // Crear carpeta si no existe
                if (!file_exists(public_path('uploads/config'))) {
                    mkdir(public_path('uploads/config'), 0755, true);
                }

                $file->move(public_path('uploads/config'), $filename);

                $data['logo'] = $filename;
            }

            $config->update($data);

            return back()->with('success', 'Configuración actualizada correctamente');

        })->name('catalogo.admin.config.update');



});

