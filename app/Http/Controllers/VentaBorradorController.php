<?php

namespace App\Http\Controllers;

use App\Models\VentaBorrador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaBorradorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $borradores = VentaBorrador::query()
            ->where('usuario_id', $request->user()->id)
            ->orderBy('updated_at')
            ->get(['uuid', 'datos', 'eliminado_en', 'updated_at']);

        return response()->json([
            'borradores' => $borradores->map(fn (VentaBorrador $borrador) => [
                'id' => $borrador->uuid,
                'datos' => $borrador->datos,
                'eliminado' => $borrador->eliminado_en !== null,
                'actualizado_en' => $borrador->updated_at?->toISOString(),
            ])->values(),
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'borradores' => ['present', 'array', 'max:30'],
            'borradores.*.id' => ['required', 'string', 'max:80'],
            'borradores.*.datos' => ['required', 'array'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            foreach ($data['borradores'] as $item) {
                // El usuario se toma siempre de la sesión; nunca del navegador.
                $borrador = VentaBorrador::firstOrNew([
                    'usuario_id' => $request->user()->id,
                    'uuid' => $item['id'],
                ]);

                // Una copia antigua de otro dispositivo no puede resucitar un
                // carrito que ya fue vendido o eliminado.
                if ($borrador->exists && $borrador->eliminado_en !== null) {
                    continue;
                }

                $borrador->datos = $item['datos'];
                $borrador->save();
            }
        });

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $borrador = VentaBorrador::query()
            ->where('usuario_id', $request->user()->id)
            ->where('uuid', $uuid)
            ->first();

        if ($borrador) {
            $borrador->update([
                'datos' => null,
                'eliminado_en' => now(),
            ]);
        } else {
            VentaBorrador::create([
                'usuario_id' => $request->user()->id,
                'uuid' => $uuid,
                'datos' => null,
                'eliminado_en' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
