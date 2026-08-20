<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ConsultaDocumentoController extends Controller
{
    public function show(string $tipo, string $numero): JsonResponse
    {
        $tipo = strtolower($tipo);
        $numero = preg_replace('/\D+/', '', $numero);
        $longitudEsperada = $tipo === 'dni' ? 8 : ($tipo === 'ruc' ? 11 : 0);

        if (! $longitudEsperada || strlen($numero) !== $longitudEsperada) {
            return response()->json(['message' => 'El tipo o número de documento no es válido.'], 422);
        }

        $token = config('services.apiperu.token');

        if (! $token) {
            return response()->json(['message' => 'La consulta de documentos no está configurada.'], 503);
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->retry(2, 250)
                ->get("https://apiperu.dev/api/{$tipo}/{$numero}");
        } catch (\Throwable $error) {
            report($error);

            return response()->json(['message' => 'No se pudo conectar con el servicio de documentos.'], 503);
        }

        $payload = $response->json();

        if (! $response->successful() || ! data_get($payload, 'success')) {
            return response()->json([
                'message' => data_get($payload, 'message', 'Documento no encontrado.'),
            ], $response->status() === 429 ? 429 : 404);
        }

        $data = data_get($payload, 'data', []);

        if ($tipo === 'dni') {
            $nombre = trim(implode(' ', array_filter([
                data_get($data, 'nombres'),
                data_get($data, 'apellido_paterno'),
                data_get($data, 'apellido_materno'),
            ])));

            return response()->json([
                'tipo' => 'DNI',
                'numero' => $numero,
                'nombre' => $nombre,
                'direccion' => null,
                'estado' => null,
            ]);
        }

        return response()->json([
            'tipo' => 'RUC',
            'numero' => $numero,
            'nombre' => data_get($data, 'nombre_o_razon_social', ''),
            'direccion' => data_get($data, 'direccion'),
            'ubigeo' => data_get($data, 'ubigeo_sunat', data_get($data, 'ubigeo')),
            'departamento' => data_get($data, 'departamento'),
            'provincia' => data_get($data, 'provincia'),
            'distrito' => data_get($data, 'distrito'),
            'estado' => data_get($data, 'estado'),
            'condicion' => data_get($data, 'condicion'),
        ]);
    }
}
