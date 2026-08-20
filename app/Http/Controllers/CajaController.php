<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\CajaOperacion;
use App\Models\User;
use App\Notifications\CajaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function abrir(Request $request)
    {
        $this->soloAdministrador();

        $data = $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'monto_inicial' => 'required|numeric|min:0|max:999999999.99',
        ]);

        $resultado = DB::transaction(function () use ($data) {
            $responsable = User::with(['rol', 'permisos'])
                ->lockForUpdate()
                ->findOrFail($data['usuario_id']);

            if (! $responsable->esAdmin() && ! $responsable->tienePermiso('ventas')) {
                abort(422, 'El usuario seleccionado no tiene permiso para operar ventas y caja.');
            }

            $existente = Caja::where('usuario_id', $data['usuario_id'])
                ->whereIn('estado', ['abierta', 'pendiente_cierre'])
                ->lockForUpdate()
                ->exists();

            if ($existente) {
                abort(422, 'El usuario ya tiene una caja activa o pendiente de aprobación.');
            }

            $caja = Caja::create([
                'usuario_id' => $data['usuario_id'],
                'abierta_por' => auth()->id(),
                'abierta_en' => now(),
                'monto_inicial' => $data['monto_inicial'],
                'estado' => 'abierta',
            ]);

            $esCajaPropia = (int) $caja->usuario_id === (int) auth()->id();

            if (! $esCajaPropia) {
                $caja->usuario?->notify(new CajaNotification([
                    'titulo' => 'Caja asignada',
                    'mensaje' => (auth()->user()->nombre ?? 'El administrador')
                        .' te asignó una caja con un fondo inicial de S/ '
                        .number_format((float) $data['monto_inicial'], 2).'.',
                    'icono' => 'fa-cash-register',
                    'color' => 'success',
                    'url' => route('movimientos.index', [], false),
                ]));
            }

            return [
                'es_caja_propia' => $esCajaPropia,
                'responsable' => $responsable->nombre,
                'monto' => (float) $data['monto_inicial'],
            ];
        });

        $mensaje = $resultado['es_caja_propia']
            ? 'Tu caja fue abierta con un fondo inicial de S/ '.number_format($resultado['monto'], 2).'.'
            : 'Caja asignada a '.$resultado['responsable'].' con un fondo inicial de S/ '
                .number_format($resultado['monto'], 2).'.';

        return back()->with('success', $mensaje);
    }

    public function solicitarCierre(Request $request, Caja $caja)
    {
        abort_unless($caja->usuario_id === auth()->id() || auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'metodos' => 'required|array',
            'metodos.efectivo' => 'required|numeric|min:0|max:999999999.99',
            'metodos.yape' => 'required|numeric|min:0|max:999999999.99',
            'metodos.plin' => 'required|numeric|min:0|max:999999999.99',
            'metodos.tarjeta' => 'required|numeric|min:0|max:999999999.99',
            'metodos.transferencia' => 'required|numeric|min:0|max:999999999.99',
            'metodos.otro' => 'required|numeric|min:0|max:999999999.99',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $cierreDirecto = auth()->user()->esAdmin() && $caja->usuario_id === auth()->id();

        DB::transaction(function () use ($caja, $data, $cierreDirecto) {
            $caja = Caja::whereKey($caja->id)->lockForUpdate()->firstOrFail();

            if ($caja->estado !== 'abierta') {
                abort(422, 'La caja no se encuentra abierta.');
            }

            $conciliacion = $caja->calcularConciliacion();
            $declarados = collect(Caja::mediosConciliables())
                ->mapWithKeys(fn ($label, $medio) => [$medio => round((float) $data['metodos'][$medio], 2)])
                ->all();
            $esperados = collect($conciliacion)
                ->mapWithKeys(fn ($valores, $medio) => [$medio => round((float) $valores['esperado'], 2)])
                ->all();
            $diferencias = collect($declarados)
                ->mapWithKeys(fn ($declarado, $medio) => [$medio => round($declarado - ($esperados[$medio] ?? 0), 2)])
                ->all();

            $datosCierre = [
                'cierre_solicitado_en' => now(),
                'monto_declarado' => $declarados['efectivo'],
                'metodos_esperados' => $esperados,
                'metodos_declarados' => $declarados,
                'metodos_diferencias' => $diferencias,
                'cerrada_por' => auth()->id(),
                'observaciones' => $data['observaciones'] ?? null,
            ];

            // El administrador que opera su propia caja tiene autoridad para
            // realizar y aprobar el arqueo en una sola operación auditable.
            if ($cierreDirecto) {
                $totales = $conciliacion['efectivo'];
                $declarado = $datosCierre['monto_declarado'];

                $caja->update($datosCierre + [
                    'cerrada_en' => now(),
                    'ingresos_efectivo' => $totales['ingresos'],
                    'egresos_efectivo' => $totales['egresos'],
                    'monto_esperado' => $totales['esperado'],
                    'monto_contado' => $declarado,
                    'diferencia' => round($declarado - $totales['esperado'], 2),
                    'aprobada_por' => auth()->id(),
                    'estado' => 'cerrada',
                ]);

                return;
            }

            $caja->update($datosCierre + ['estado' => 'pendiente_cierre']);

            $cajero = $caja->usuario()->first();
            $administradores = User::where('rol_id', 1)
                ->whereKeyNot(auth()->id())
                ->get();

            if ($administradores->isNotEmpty()) {
                $administradores->each->notify(new CajaNotification([
                    'titulo' => 'Cierre de caja por revisar',
                    'mensaje' => ($cajero?->nombre ?? 'Un empleado')
                        .' envió el cuadre de todos los medios de pago para revisión.',
                    'icono' => 'fa-hourglass-half',
                    'color' => 'warning',
                    'url' => route('movimientos.index', ['tipo' => 'cierres'], false),
                ]));
            }
        });

        return back()->with('success', $cierreDirecto
            ? 'Caja cerrada correctamente. El arqueo quedó registrado.'
            : 'Conteo enviado. Un administrador debe revisar y aprobar el cierre.');
    }

    public function aprobar(Caja $caja)
    {
        $this->soloAdministrador();

        DB::transaction(function () use ($caja) {
            $caja = Caja::whereKey($caja->id)->lockForUpdate()->firstOrFail();

            if ($caja->estado !== 'pendiente_cierre' || $caja->monto_declarado === null) {
                abort(422, 'La caja no tiene un cierre pendiente de aprobación.');
            }

            $totales = $caja->calcularEfectivo();
            $declarado = round((float) $caja->monto_declarado, 2);

            $caja->update([
                'cerrada_en' => now(),
                'ingresos_efectivo' => $totales['ingresos'],
                'egresos_efectivo' => $totales['egresos'],
                'monto_esperado' => $totales['esperado'],
                'monto_contado' => $declarado,
                'diferencia' => round($declarado - $totales['esperado'], 2),
                'aprobada_por' => auth()->id(),
                'estado' => 'cerrada',
            ]);

            if ($caja->usuario_id !== auth()->id()) {
                $caja->usuario?->notify(new CajaNotification([
                    'titulo' => 'Cierre de caja aprobado',
                    'mensaje' => 'El administrador aprobó tu cuadre de todos los medios de pago.',
                    'icono' => 'fa-check-circle',
                    'color' => 'success',
                    'url' => route('movimientos.index', ['tipo' => 'cierres'], false),
                ]));
            }
        });

        return back()->with('success', 'Cierre revisado y aprobado.');
    }

    public function reabrir(Caja $caja)
    {
        $this->soloAdministrador();

        if ($caja->estado !== 'pendiente_cierre') {
            return back()->withErrors(['caja' => 'Solo se puede devolver una caja pendiente de aprobación.']);
        }

        DB::transaction(function () use ($caja) {
            $caja = Caja::whereKey($caja->id)->lockForUpdate()->firstOrFail();
            $montoDevuelto = (float) $caja->monto_declarado;

            $caja->update([
                'cierre_solicitado_en' => null,
                'monto_declarado' => null,
                'cerrada_por' => null,
                'observaciones' => null,
                'metodos_esperados' => null,
                'metodos_declarados' => null,
                'metodos_diferencias' => null,
                'estado' => 'abierta',
            ]);

            if ($caja->usuario_id !== auth()->id()) {
                $caja->usuario?->notify(new CajaNotification([
                    'titulo' => 'Cierre de caja devuelto',
                    'mensaje' => 'El cierre por S/ '.number_format($montoDevuelto, 2)
                        .' fue devuelto para revisar y realizar un nuevo conteo.',
                    'icono' => 'fa-undo-alt',
                    'color' => 'warning',
                    'url' => route('movimientos.index', [], false),
                ]));
            }
        });

        return back()->with('success', 'La caja fue devuelta al empleado para un nuevo conteo.');
    }

    public function registrarOperacion(Request $request, Caja $caja)
    {
        $this->soloAdministrador();

        $data = $request->validate([
            'tipo' => 'required|in:refuerzo,retiro',
            'monto' => 'required|numeric|min:0.01|max:999999999.99',
            'origen_destino' => 'required|string|max:120',
            'motivo' => 'required|string|max:255',
        ]);

        if ($caja->estado !== 'abierta') {
            return back()->withErrors(['caja' => 'Solo se puede modificar una caja abierta.']);
        }

        if ($data['tipo'] === 'retiro' && (float) $data['monto'] > $caja->calcularEfectivo()['esperado']) {
            return back()->withErrors(['caja' => 'El retiro supera el efectivo esperado de la caja.']);
        }

        CajaOperacion::create([
            'caja_id' => $caja->id,
            'usuario_id' => auth()->id(),
            'tipo' => $data['tipo'],
            'monto' => $data['monto'],
            'origen_destino' => $data['origen_destino'],
            'motivo' => $data['motivo'],
        ]);

        return back()->with('success', $data['tipo'] === 'refuerzo'
            ? 'Refuerzo de caja registrado.'
            : 'Retiro de caja registrado.');
    }

    private function soloAdministrador(): void
    {
        abort_unless(auth()->check() && auth()->user()->esAdmin(), 403);
    }
}
