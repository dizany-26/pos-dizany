<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $clientes = Cliente::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('ruc', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function verificarDocumento(Request $request)
    {
        $data = $request->validate([
            'tipo' => 'required|in:DNI,RUC',
            'numero' => 'required|digits_between:8,11',
            'excepto' => 'nullable|integer',
        ]);

        $columna = $data['tipo'] === 'DNI' ? 'dni' : 'ruc';
        $cliente = Cliente::query()
            ->where($columna, $data['numero'])
            ->when($data['excepto'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->first(['id', 'nombre', 'dni', 'ruc']);

        return response()->json(['existe' => (bool) $cliente, 'cliente' => $cliente]);
    }

    public function buscarCliente($dniRuc)
    {
        $cliente = Cliente::where('dni', $dniRuc)->orWhere('ruc', $dniRuc)->first();

        return response()->json($cliente ? [
            'encontrado' => true,
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'direccion' => $cliente->direccion,
            'telefono' => $cliente->telefono,
            'ruc' => $cliente->ruc,
            'dni' => $cliente->dni,
        ] : ['encontrado' => false]);
    }

    // Compatibilidad con el registro rápido usado en ventas.
    public function guardar(Request $request)
    {
        try {
            $data = $request->validate([
                'dni_ruc' => 'required|string|max:11',
                'razon_social' => 'required|string|max:255',
                'direccion' => 'nullable|string|max:255',
            ]);

            $tipo = strlen($data['dni_ruc']) === 8 ? 'DNI' : (strlen($data['dni_ruc']) === 11 ? 'RUC' : null);
            if (! $tipo) {
                return response()->json(['exito' => false, 'mensaje' => 'Número de documento no válido.'], 422);
            }

            $columna = $tipo === 'DNI' ? 'dni' : 'ruc';
            $cliente = Cliente::firstOrCreate(
                [$columna => $data['dni_ruc']],
                ['nombre' => $data['razon_social'], 'direccion' => $data['direccion'] ?? null]
            );

            return response()->json(['exito' => true, 'cliente' => $cliente]);
        } catch (\Throwable $exception) {
            Log::error('Error al guardar cliente: '.$exception->getMessage());
            return response()->json(['exito' => false, 'mensaje' => 'No se pudo guardar el cliente.'], 500);
        }
    }

    public function show($id)
    {
        return view('clientes.show', ['cliente' => Cliente::findOrFail($id)]);
    }

    public function edit($id)
    {
        return response()->json(Cliente::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $this->validarCliente($request);
        $cliente = Cliente::create($this->datosCliente($data));

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado correctamente.',
            'cliente' => $cliente,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $data = $this->validarCliente($request, $cliente);
        $cliente->update($this->datosCliente($data));

        return response()->json(['success' => true, 'message' => 'Cliente actualizado correctamente.']);
    }

    private function validarCliente(Request $request, ?Cliente $cliente = null): array
    {
        $tipo = $request->input('tipo_documento');
        $columna = $tipo === 'RUC' ? 'ruc' : 'dni';
        $longitud = $tipo === 'RUC' ? 11 : 8;

        return $request->validate([
            'tipo_documento' => 'required|in:DNI,RUC',
            'numero_documento' => [
                'required',
                "digits:{$longitud}",
                Rule::unique('clientes', $columna)->ignore($cliente?->id),
            ],
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
        ], [
            'numero_documento.unique' => 'Este documento ya pertenece a otro cliente.',
            'numero_documento.digits' => "El documento debe contener {$longitud} dígitos.",
        ]);
    }

    private function datosCliente(array $data): array
    {
        return [
            'nombre' => $data['nombre'],
            'direccion' => $data['direccion'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'dni' => $data['tipo_documento'] === 'DNI' ? $data['numero_documento'] : null,
            'ruc' => $data['tipo_documento'] === 'RUC' ? $data['numero_documento'] : null,
        ];
    }
}
