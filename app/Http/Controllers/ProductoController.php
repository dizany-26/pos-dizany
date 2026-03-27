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
    $productos = $query->orderBy('id', 'desc')->paginate(10);

    // ===============================
    // CALCULAR STOCK Y PRECIO DESDE LOTES
    // ===============================
    $productos->getCollection()->transform(function ($producto) {

        // 🔥 STOCK TOTAL = suma de lotes
        $producto->stock_total = $producto->lotes->sum('stock_actual');

        // 🔥 PRECIO ACTUAL = primer lote FIFO
        $loteActivo = $producto->lotes->first();
        $producto->precio_venta_actual = $loteActivo?->precio_unidad ?? 0;

        return $producto;
    });

    // ===============================
    // VISTA
    // ===============================
    return view('productos.index', compact(
        'productos',
        'categorias',
        'marcas'
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

        'categoria_id'         => 'required|exists:categorias,id',
        'marca_id'             => 'nullable|exists:marcas,id',
    ]);

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

    Producto::create($validated);

    return redirect()
        ->route('productos.create')
        ->with('success', 'Producto creado correctamente.');
}


    public function edit($id)
    {
        $producto = Producto::findOrFail($id);
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
        
    ]);

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

    return redirect()
        ->route('productos.edit', $producto->id)
        ->with('success', 'Producto actualizado correctamente.');
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
                    'numero' => $l->id,
                    'stock' => (int) $l->stock_actual,
                    'fecha_vencimiento' => $l->fecha_vencimiento,
                    'precio_unidad' => (float) $l->precio_unidad,
                    'precio_paquete' => (float) $l->precio_paquete,
                    'precio_caja' => (float) $l->precio_caja,
                ])->values(),

                'unidades_por_paquete' => $p->unidades_por_paquete,
                'paquetes_por_caja'    => $p->paquetes_por_caja,
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
        // Obtener producto con relaciones
        $producto = Producto::with('categoria', 'marca')->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,

            'id'                    => $producto->id,
            'codigo_barras'         => $producto->codigo_barras,
            'nombre'                => $producto->nombre,
            'slug'                  => $producto->slug,
            'descripcion'           => $producto->descripcion,

            'precio_compra'         => $producto->precio_compra,
            'precio_venta'          => $producto->precio_venta,
            'precio_paquete'        => $producto->precio_paquete,
            'unidades_por_paquete'  => $producto->unidades_por_paquete,
            'paquetes_por_caja'     => $producto->paquetes_por_caja,
            'precio_caja'           => $producto->precio_caja,
            'tipo_paquete'          => $producto->tipo_paquete,

            'stock'                 => $producto->stock,
            'ubicacion'             => $producto->ubicacion,
            'imagen'                => $producto->imagen,
            'fecha_vencimiento'     => $producto->fecha_vencimiento,

            'categoria_nombre'      => $producto->categoria ? $producto->categoria->nombre : 'Sin categoría',
            'marca_nombre'          => $producto->marca ? $producto->marca->nombre : 'Sin marca',

            'activo'                => $producto->activo ? 'Sí' : 'No',
            'visible_en_catalogo'   => $producto->visible_en_catalogo ? 'Sí' : 'No',
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
            'numero' => $l->id, // o código_lote si tienes
            'stock' => $l->stock_actual,
            'fecha_vencimiento' => $l->fecha_vencimiento,
            'precio_unidad' => $l->precio_unidad,
            'precio_paquete' => $l->precio_paquete,
            'precio_caja' => $l->precio_caja,
        ])->values(), // 👈 importante

            // presentaciones
            'unidades_por_paquete' => $p->unidades_por_paquete,
            'paquetes_por_caja'    => $p->paquetes_por_caja,
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
