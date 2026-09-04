<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class ProductoController extends Controller
{
    public function index(Request $request)
{
    $categoria_id = $request->input('categoria_id');
    $marca_id     = $request->input('marca_id');
    $search       = $request->input('search');

    // categorías y marcas para filtros
    extract($this->obtenerCategoriasYMarcas());
    $totalProductos = Producto::count();

    // ===============================
    // QUERY BASE DE PRODUCTOS
    // ===============================
    $query = Producto::with([
        'categoria',
        'marca',
        'lotes' => function ($q) {
            $q->where('stock_actual', '>', 0)
              ->orderBy('fecha_ingreso'); // FIFO
        }
    ]);

    // ===============================
    // FILTROS
    // ===============================
    if ($categoria_id && $categoria_id !== 'todos') {
        $query->where('categoria_id', $categoria_id);
    }

    if ($marca_id && $marca_id !== 'todos') {
        $query->where('marca_id', $marca_id);
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('codigo_barras', 'like', "%{$search}%")
              ->orWhere('nombre', 'like', "%{$search}%");
        });
    }

    // ===============================
    // PAGINACIÓN
    // ===============================
    $productos = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

    // ===============================
    // CALCULAR STOCK Y PRECIO DESDE LOTES
    // ===============================
    $productos->getCollection()->transform(function ($producto) {

        // 🔥 STOCK TOTAL = suma de lotes
        $producto->stock_total = $producto->lotes->sum('stock_actual');

        $producto->precio_venta_actual = $producto->precio_venta ?? 0;

        return $producto;
    });

    // ===============================
    // VISTA
    // ===============================
    return view('productos.index', compact(
        'productos',
        'categorias',
        'marcas',
        'totalProductos'
    ));
}



    public function create()
    {
        extract($this->obtenerCategoriasYMarcas());

        return view('productos.create', compact('categorias', 'marcas'));
    }

   public function store(Request $request)
{
    $validated = $request->validate([
        'codigo_barras'        => 'nullable|string|max:50|unique:productos,codigo_barras',
        'nombre'               => 'required|string|max:255',
        'descripcion'          => 'nullable|string',

        // Presentaciones
        'unidades_por_paquete' => 'nullable|integer|min:1',
        'paquetes_por_caja'    => 'nullable|integer|min:1',
        'unidades_por_caja'    => 'nullable|integer|min:1',

        'ubicacion'            => 'nullable|string|max:255',
        'imagen'               => 'nullable|image|mimes:jpeg,png,jpg,webp,avif|max:2048',
        'imagenes_catalogo'    => 'nullable|array|max:2',
        'imagenes_catalogo.*'  => 'image|mimes:jpeg,png,jpg,webp,avif|max:2048',

        'categoria_id'         => 'required|exists:categorias,id',
        'marca_id'             => 'nullable|exists:marcas,id',
    ]);

    $validated['nombre'] = mb_strtoupper($validated['nombre'], 'UTF-8');

    if (!empty($validated['descripcion'])) {
        $descripcion = mb_strtolower($validated['descripcion'], 'UTF-8');
        $validated['descripcion'] = preg_replace_callback(
            '/\p{L}/u',
            fn (array $coincidencia) => mb_strtoupper($coincidencia[0], 'UTF-8'),
            $descripcion,
            1
        );
    }

    /* =====================
       VALIDACIONES LÓGICAS
    ===================== */

    $up = $validated['unidades_por_paquete'] ?? null;
    $pc = $validated['paquetes_por_caja'] ?? null;
    $uc = $validated['unidades_por_caja'] ?? null;

    // ❌ paquetes por caja sin paquete
    if ($pc && !$up) {
        return back()->withErrors([
            'paquetes_por_caja' => 'No puede definir paquetes por caja sin definir unidades por paquete.'
        ])->withInput();
    }

    // ❌ paquete + caja directa (ambos)
    if ($up && $uc) {
        return back()->withErrors([
            'unidades_por_caja' => 'No puede definir unidades por caja si la caja se compone de paquetes.'
        ])->withInput();
    }

    /* =====================
       EXTRAS
    ===================== */

    $validated['slug'] = Str::slug($validated['nombre']);
    $validated['activo'] = $request->has('activo') ? 1 : 0;
    $validated['visible_en_catalogo'] = $request->has('visible_en_catalogo') ? 1 : 0;
    $validated['maneja_vencimiento'] = $request->has('maneja_vencimiento') ? 1 : 0;

    /* =====================
       IMAGEN
    ===================== */

    if ($request->hasFile('imagen')) {
        $image = $request->file('imagen');
        $imageName = Str::slug($validated['nombre']) . '-' . time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads/productos'), $imageName);
        $validated['imagen'] = $imageName;
    }

    $producto = Producto::create($validated);
    $this->guardarImagenesCatalogo($request, $producto);

    return redirect()
        ->route('productos.create')
        ->with('success', 'Producto creado correctamente.');
}


    public function edit($id)
    {
        $producto = Producto::with('imagenesCatalogo')->findOrFail($id);
        extract($this->obtenerCategoriasYMarcas());

        return view('productos.edit', compact('producto', 'categorias', 'marcas'));
    }

    public function update(Request $request, $id)
{
    $producto = Producto::findOrFail($id);

    $validated = $request->validate([
        'codigo_barras'        => 'nullable|string|max:50|unique:productos,codigo_barras,' . $producto->id,
        'nombre'               => 'required|string|max:255',
        'descripcion'          => 'nullable|string',

        'unidades_por_paquete' => 'nullable|integer|min:1',
        'paquetes_por_caja'    => 'nullable|integer|min:1',
        'unidades_por_caja'    => 'nullable|integer|min:1',

        'ubicacion'            => 'nullable|string|max:255',

        'categoria_id'         => 'required|exists:categorias,id',
        'marca_id'             => 'nullable|exists:marcas,id',

        'imagen'               => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:2048',
        'imagenes_catalogo'    => 'nullable|array|max:2',
        'imagenes_catalogo.*'  => 'image|mimes:jpg,jpeg,png,webp,avif|max:2048',
        'eliminar_imagenes_catalogo'   => 'nullable|array',
        'eliminar_imagenes_catalogo.*' => 'integer',
    ]);

    $validated['nombre'] = mb_strtoupper($validated['nombre'], 'UTF-8');

    if (!empty($validated['descripcion'])) {
        $descripcion = mb_strtolower($validated['descripcion'], 'UTF-8');
        $validated['descripcion'] = preg_replace_callback(
            '/\p{L}/u',
            fn (array $coincidencia) => mb_strtoupper($coincidencia[0], 'UTF-8'),
            $descripcion,
            1
        );
    }

    $idsEliminar = collect($request->input('eliminar_imagenes_catalogo', []))
        ->map(fn ($id) => (int) $id)
        ->unique();
    $secundariasRestantes = $producto->imagenesCatalogo()
        ->whereNotIn('id', $idsEliminar)
        ->count();
    $nuevasSecundarias = count($request->file('imagenes_catalogo', []));

    if ($secundariasRestantes + $nuevasSecundarias > 2) {
        return back()->withErrors([
            'imagenes_catalogo' => 'El catálogo admite como máximo dos imágenes secundarias.'
        ])->withInput();
    }

    // Normalize the selected box model before checking logical conflicts.
    // A hidden direct-box value may still be submitted after switching to packages.
    $usaPaquete = $request->boolean('usa_paquete');
    $usaCaja = $request->boolean('usa_caja');

    if ($usaPaquete && $usaCaja) {
        $validated['unidades_por_caja'] = null;
    } elseif ($usaCaja) {
        $validated['paquetes_por_caja'] = null;
    }

    // ===== VALIDACIONES LÓGICAS =====
    if (
        !empty($validated['paquetes_por_caja']) &&
        empty($validated['unidades_por_paquete'])
    ) {
        return back()->withErrors([
            'paquetes_por_caja' => 'No puede definir paquetes por caja sin definir unidades por paquete.'
        ])->withInput();
    }

    if (
        !empty($validated['unidades_por_paquete']) &&
        !empty($validated['unidades_por_caja'])
    ) {
        return back()->withErrors([
            'unidades_por_caja' => 'No puede definir unidades por caja si la caja se arma por paquetes.'
        ])->withInput();
    }

    // ===== FLAGS =====
    $validated['activo'] = $request->has('activo') ? 1 : 0;
    $validated['visible_en_catalogo'] = $request->has('visible_en_catalogo') ? 1 : 0;
    $validated['maneja_vencimiento'] = $request->has('maneja_vencimiento') ? 1 : 0;

    // slug NO cambia
    $validated['slug'] = $producto->slug;

    // ===== IMAGEN =====
    if ($request->hasFile('imagen')) {
        if ($producto->imagen && file_exists(public_path('uploads/productos/' . $producto->imagen))) {
            unlink(public_path('uploads/productos/' . $producto->imagen));
        }

        $image = $request->file('imagen');
        $imageName = Str::slug($validated['nombre']) . '-' . time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads/productos'), $imageName);

        $validated['imagen'] = $imageName;
    }

    // ===== LIMPIAR CONVERSIONES SEGÚN CHECKBOX =====

    // Si NO usa paquete → limpiar todo lo relacionado
    if (!$request->has('usa_paquete')) {
        $validated['unidades_por_paquete'] = null;
        $validated['paquetes_por_caja'] = null;
    }

    // Si NO usa caja → limpiar datos de caja
    if (!$request->has('usa_caja')) {
        $validated['paquetes_por_caja'] = null;
        $validated['unidades_por_caja'] = null;
    }

    // Si usa caja pero también usa paquete → caja por paquetes
    if ($request->has('usa_caja') && $request->has('usa_paquete')) {
        $validated['unidades_por_caja'] = null;
    }

    // Si usa caja directa (sin paquete)
    if ($request->has('usa_caja') && !$request->has('usa_paquete')) {
        $validated['paquetes_por_caja'] = null;
    }

    $producto->update($validated);

    $producto->imagenesCatalogo()
        ->whereIn('id', $idsEliminar)
        ->get()
        ->each(function ($imagen) {
            $ruta = public_path('uploads/productos/' . $imagen->imagen);
            if (is_file($ruta)) {
                unlink($ruta);
            }
            $imagen->delete();
        });

    $this->guardarImagenesCatalogo($request, $producto);

    return redirect()
        ->route('productos.edit', $producto->id)
        ->with('success', 'Producto actualizado correctamente.');
}

private function guardarImagenesCatalogo(Request $request, Producto $producto): void
{
    $archivos = $request->file('imagenes_catalogo', []);
    if (empty($archivos)) {
        return;
    }

    $orden = (int) $producto->imagenesCatalogo()->max('orden');
    foreach ($archivos as $archivo) {
        $orden++;
        $nombre = Str::slug($producto->nombre)
            . '-catalogo-' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $archivo->move(public_path('uploads/productos'), $nombre);
        $producto->imagenesCatalogo()->create([
            'imagen' => $nombre,
            'orden' => $orden,
        ]);
    }
}


public function toggleEstado($id)
{
    $producto = Producto::findOrFail($id);
    $producto->activo = !$producto->activo; // ← corregido
    $producto->save();

    return redirect()->route('productos.index')->with('estado_actualizado', $producto->activo ? 'activado' : 'desactivado');

}

   public function buscar(Request $request)
{
    $searchTerm = trim($request->input('search'));

    $productos = Producto::with(['lotes' => function ($q) {
            $q->where('stock_actual', '>', 0)
              ->orderByRaw('fecha_vencimiento IS NULL')
              ->orderBy('fecha_vencimiento', 'asc')
              ->orderBy('fecha_ingreso', 'asc')
              ->orderBy('id', 'asc');
        }])
        ->where('activo', 1)
        ->where('visible_en_catalogo', 1)
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('codigo_barras', 'like', "%{$searchTerm}%");
            });
        })
        ->whereHas('lotes', function ($q) {
            $q->where('stock_actual', '>', 0);
        })
        ->limit(10)
        ->get();

    return response()->json(
        $productos->map(function ($p) {

            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo_barras' => $p->codigo_barras,
                'descripcion' => $p->descripcion,
                'imagen' => $p->imagen,

                // 🔥 STOCK TOTAL REAL
                'stock' => $p->lotes->sum('stock_actual'),

                // 🔥 LOTES FIFO (CLAVE PARA PRECIO)
                'lotes_fifo' => $p->lotes->map(fn ($l) => [
                    'id' => $l->id,
                    'numero' => $l->numero_lote,
                    'stock' => (int) $l->stock_actual,
                    'fecha_vencimiento' => $l->fecha_vencimiento,
                    'precio_unidad' => (float) $p->precio_venta,
                    'precio_paquete' => (float) $p->precio_paquete,
                    'precio_caja' => (float) $p->precio_caja,
                ])->values(),

                'unidades_por_paquete' => $p->unidades_por_paquete,
                'paquetes_por_caja'    => $p->paquetes_por_caja,
                'unidades_por_caja'    => $p->unidades_por_caja,
                'categoria_id'         => $p->categoria_id,
            ];
        })
    );
}

    // validar si el código de barras existe
    public function validarCodigoBarras(Request $request)
    {
        $codigo_barras = $request->input('codigo_barras');

        // Verificar si el código de barras existe
        $exists = Producto::where('codigo_barras', $codigo_barras)->exists();

        // Devolver un valor booleano si existe o no
        return response()->json(['exists' => $exists]);
    }
   // Validar si el código de barras existe, pero excluir el producto actual si estamos editando
   public function validarCodigoBarrasEdicion(Request $request)
    {
        $codigo_barras = $request->input('codigo_barras');
        $producto_id = $request->input('producto_id');  // Obtener el ID del producto si estamos editando

        // Verificar si el código de barras existe, pero excluir el producto actual (si estamos editando)
        $exists = Producto::where('codigo_barras', $codigo_barras)
                        ->where('id', '!=', $producto_id)  // Excluir el producto actual si estamos editando
                        ->exists();
        // Devolver un valor booleano si existe o no
        return response()->json(['exists' => $exists]);
    }
    /**
     * Función privada reutilizable para obtener categorías y marcas
     */
    private function obtenerCategoriasYMarcas()
    {
        return [
            'categorias' => Categoria::all(),
            'marcas' => Marca::all(),
        ];
    }

    public function mostrarDetalles($id)
    {
        $producto = Producto::with([
            'categoria',
            'marca',
            'lotes' => fn ($query) => $query
                ->with('proveedor')
                ->orderByDesc('activo')
                ->orderByRaw('fecha_vencimiento IS NULL')
                ->orderBy('fecha_vencimiento')
                ->orderByDesc('fecha_ingreso'),
        ])->find($id);

        if (! $producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $lotesActivos = $producto->lotes
            ->where('activo', true)
            ->where('stock_actual', '>', 0);
        $stockTotal = $lotesActivos->sum('stock_actual');
        $valorInventario = $lotesActivos->sum(
            fn ($lote) => (float) $lote->stock_actual * (float) $lote->precio_compra
        );

        return response()->json([
            'success' => true,
            'producto' => [
                'id' => $producto->id,
                'codigo_barras' => $producto->codigo_barras,
                'nombre' => $producto->nombre,
                'slug' => $producto->slug,
                'descripcion' => $producto->descripcion,
                'ubicacion' => $producto->ubicacion,
                'imagen' => $producto->imagen,
                'categoria' => $producto->categoria?->nombre,
                'marca' => $producto->marca?->nombre,
                'activo' => (bool) $producto->activo,
                'visible_en_catalogo' => (bool) $producto->visible_en_catalogo,
                'maneja_vencimiento' => (bool) $producto->maneja_vencimiento,
                'creado_en' => optional($producto->created_at)->format('d/m/Y H:i'),
                'actualizado_en' => optional($producto->updated_at)->format('d/m/Y H:i'),
            ],
            'presentaciones' => [
                'unidad' => [
                    'habilitada' => true,
                    'contenido' => 1,
                    'precio' => $producto->precio_venta,
                ],
                'paquete' => [
                    'habilitada' => ! empty($producto->unidades_por_paquete),
                    'contenido' => $producto->unidades_por_paquete,
                    'precio' => $producto->precio_paquete,
                ],
                'caja' => [
                    'habilitada' => ! empty($producto->unidades_por_caja) || ! empty($producto->paquetes_por_caja),
                    'paquetes' => $producto->paquetes_por_caja,
                    'contenido' => $producto->unidades_por_caja
                        ?: (($producto->paquetes_por_caja && $producto->unidades_por_paquete)
                            ? $producto->paquetes_por_caja * $producto->unidades_por_paquete
                            : null),
                    'precio' => $producto->precio_caja,
                ],
            ],
            'inventario' => [
                'stock_total' => $stockTotal,
                'lotes_con_stock' => $lotesActivos->count(),
                'lotes_registrados' => $producto->lotes->count(),
                'valor_compra' => round($valorInventario, 2),
                'proximo_vencimiento' => optional(
                    $lotesActivos->whereNotNull('fecha_vencimiento')->sortBy('fecha_vencimiento')->first()
                )->fecha_vencimiento,
            ],
            'lotes' => $producto->lotes->map(fn ($lote) => [
                'id' => $lote->id,
                'numero' => $lote->numero_lote,
                'comprobante' => $lote->codigo_comprobante,
                'proveedor' => $lote->proveedor?->nombre,
                'fecha_ingreso' => $lote->fecha_ingreso,
                'fecha_vencimiento' => $lote->fecha_vencimiento,
                'stock_inicial' => $lote->stock_inicial,
                'stock_actual' => $lote->stock_actual,
                'precio_compra' => $lote->precio_compra,
                'precio_unidad' => $lote->precio_unidad,
                'precio_paquete' => $lote->precio_paquete,
                'precio_caja' => $lote->precio_caja,
                'activo' => (bool) $lote->activo,
            ])->values(),
        ]);
    }

public function parametros()
{
    $marcas = Marca::all();
    $categorias = Categoria::all();
    return view('productos.parametros', compact('marcas', 'categorias'));
}

public function productosIniciales()
{
    $productos = Producto::with(['lotes' => function ($q) {
        $q->where('stock_actual', '>', 0)
        ->orderByRaw('fecha_vencimiento IS NULL') // 👈 los que NO tienen vencimiento al final
        ->orderBy('fecha_vencimiento', 'asc')    // 👈 FEFO real
        ->orderBy('fecha_ingreso', 'asc')        // desempate
        ->orderBy('id', 'asc');                  // último desempate
    }])

    ->where('activo', 1)
    ->where('visible_en_catalogo', 1)
    ->get();

    return $productos->map(function ($p) {

    return [
        'id' => $p->id,
        'nombre' => $p->nombre,
        'imagen' => $p->imagen,
        'descripcion' => $p->descripcion,
        'categoria_id' => $p->categoria_id,

        // 👇 STOCK TOTAL
        'stock' => $p->lotes->sum('stock_actual'),

        // 👇 LOTES ORDENADOS FEFO (CLAVE)
        'lotes_fifo' => $p->lotes->map(fn($l) => [
            'id' => $l->id,
            'numero' => $l->numero_lote,
            'stock' => $l->stock_actual,
            'fecha_vencimiento' => $l->fecha_vencimiento,
            'precio_unidad' => $p->precio_venta,
            'precio_paquete' => $p->precio_paquete,
            'precio_caja' => $p->precio_caja,
        ])->values(), // 👈 importante

            // presentaciones
            'unidades_por_paquete' => $p->unidades_por_paquete,
            'paquetes_por_caja'    => $p->paquetes_por_caja,
            'unidades_por_caja'    => $p->unidades_por_caja,
        ];
    });
}


public function ordenar(Request $request)
{
    $tipo = $request->tipo;

    $query = Producto::query()->where('activo', 1);

    switch ($tipo) {

        case 'az':
            $query->orderBy('nombre', 'asc');
            break;

        case 'za':
            $query->orderBy('nombre', 'desc');
            break;

        case 'precio_mayor':
            $query->orderBy('precio_venta', 'desc');
            break;

        case 'precio_menor':
            $query->orderBy('precio_venta', 'asc');
            break;

        case 'stock_mayor':
            $query->orderBy('stock', 'desc');
            break;

        case 'stock_menor':
            $query->orderBy('stock', 'asc');
            break;

        case 'mas_vendidos':
            $query->withSum('detalles as total_vendido', 'cantidad')
                  ->orderBy('total_vendido', 'desc');
            break;

        case 'menos_vendidos':
            $query->withSum('detalles as total_vendido', 'cantidad')
                  ->orderBy('total_vendido', 'asc');
            break;

        case 'fecha_asc':   // ⭐ AÑADIR
            $query->orderBy('created_at', 'asc');
            break;

        case 'fecha_desc':  // ⭐ AÑADIR
            $query->orderBy('created_at', 'desc');
            break;

        default:
            $query->orderBy('created_at', 'desc');
    }

    return response()->json($query->get());
}



}
