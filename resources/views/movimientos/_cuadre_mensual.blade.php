@php
    $mediosCuadre = \App\Models\Caja::mediosConciliables();
    $declaradoGuardado = $conciliacionMensual?->declarado ?? [];
    $diferenciaGuardada = $conciliacionMensual ? (float) $conciliacionMensual->diferencia : null;
@endphp

<div class="monthly-reconciliation">
    <div class="monthly-summary-grid">
        <article class="monthly-summary-card summary-primary">
            <i class="fas fa-wallet"></i>
            <span>Total recibido</span>
            <strong>S/ {{ number_format($cuadreMensual['total_recibido'], 2) }}</strong>
            <small>Todos los métodos de pago</small>
        </article>
        <article class="monthly-summary-card summary-success">
            <i class="fas fa-cash-register"></i>
            <span>Cobrado al vender</span>
            <strong>S/ {{ number_format($cuadreMensual['ventas_cobradas_directo'], 2) }}</strong>
            <small>Ventas pagadas y adelantos</small>
        </article>
        <article class="monthly-summary-card summary-info">
            <i class="fas fa-hand-holding-dollar"></i>
            <span>Fiados cobrados</span>
            <strong>S/ {{ number_format($cuadreMensual['cobros_fiados'], 2) }}</strong>
            <small>Pagos recibidos posteriormente</small>
        </article>
        <article class="monthly-summary-card summary-warning">
            <i class="fas fa-hourglass-half"></i>
            <span>Pendiente por cobrar</span>
            <strong>S/ {{ number_format($cuadreMensual['por_cobrar'], 2) }}</strong>
            <small>No está incluido en lo recibido</small>
        </article>
    </div>

    <details class="monthly-breakdown-collapsible monthly-collapsible">
        <summary class="monthly-panel monthly-panel-heading monthly-breakdown-heading">
            <div><i class="fas fa-chart-column"></i><span>Detalle de ingresos y salidas</span></div>
            <i class="fas fa-chevron-down monthly-panel-chevron"></i>
        </summary>

        <div class="monthly-breakdown-grid">
        <section class="monthly-panel">
            <div class="monthly-panel-heading">
                <div><i class="fas fa-layer-group"></i><span>Ingresos recibidos por método</span></div>
                <strong>S/ {{ number_format($cuadreMensual['total_recibido'], 2) }}</strong>
            </div>
            <div class="monthly-method-list">
                @foreach($mediosCuadre as $medio => $etiqueta)
                    <div><span>{{ $etiqueta }}</span><strong>S/ {{ number_format($cuadreMensual['por_metodo'][$medio] ?? 0, 2) }}</strong></div>
                @endforeach
            </div>
        </section>

        <section class="monthly-panel">
            <div class="monthly-panel-heading">
                <div><i class="fas fa-arrow-trend-down"></i><span>Salidas y control</span></div>
            </div>
            <div class="monthly-method-list monthly-control-list">
                <div><span>Gastos operativos</span><strong>S/ {{ number_format($cuadreMensual['gastos'], 2) }}</strong></div>
                <div><span>Compras de mercadería pagadas</span><strong>S/ {{ number_format($cuadreMensual['compras_pagadas'], 2) }}</strong></div>
                <div><span>Retiros de caja</span><strong>S/ {{ number_format($cuadreMensual['retiros'], 2) }}</strong></div>
                <div><span>Refuerzos de caja</span><strong>S/ {{ number_format($cuadreMensual['refuerzos'], 2) }}</strong></div>
                <div class="monthly-net-row"><span>Disponible después de gastos y compras</span><strong>S/ {{ number_format($cuadreMensual['disponible_despues_salidas'], 2) }}</strong></div>
                <div><span>Ganancia reconocida</span><strong>S/ {{ number_format($ganancias ?? 0, 2) }}</strong></div>
            </div>
            <p class="monthly-note"><i class="fas fa-circle-info"></i> Los retiros solo cambian la ubicación del efectivo; no reducen los ingresos del mes.</p>
        </section>
        </div>
    </details>

    <form method="POST" action="{{ route('movimientos.cuadre-mensual.guardar') }}" class="monthly-form" id="monthlyReconciliationForm">
        @csrf
        <input type="hidden" name="periodo" value="{{ $cuadreMensual['periodo'] }}">
        <div class="monthly-form-heading">
            <div>
                <span class="monthly-reconciliation-eyebrow">VERIFICACIÓN</span>
                <h4>¿Cuánto puedes comprobar realmente?</h4>
                <p>Usa los cierres de efectivo y los movimientos de Yape, Plin, tarjeta y transferencias del mes.</p>
            </div>
            <button type="button" class="btn-soft btn-soft-info" id="copyMonthlyExpected">
                <i class="fas fa-copy"></i> Copiar montos del sistema
            </button>
        </div>

        <div class="monthly-form-table">
            <div class="monthly-form-row monthly-form-header">
                <span>Método</span><span>Sistema</span><span>Verificado por ti</span><span>Diferencia</span>
            </div>
            @foreach($mediosCuadre as $medio => $etiqueta)
                @php
                    $esperadoMedio = (float) ($cuadreMensual['por_metodo'][$medio] ?? 0);
                    $valorDeclarado = old(
                        "declarado.$medio",
                        array_key_exists($medio, $declaradoGuardado)
                            ? $declaradoGuardado[$medio]
                            : ($esperadoMedio == 0.0 ? 0 : null)
                    );
                @endphp
                <div class="monthly-form-row" data-monthly-row data-expected="{{ number_format($esperadoMedio, 2, '.', '') }}">
                    <label for="monthly-{{ $medio }}">{{ $etiqueta }}</label>
                    <strong>S/ {{ number_format($esperadoMedio, 2) }}</strong>
                    <div class="monthly-money-input">
                        <span>S/</span>
                        <input id="monthly-{{ $medio }}" type="number" name="declarado[{{ $medio }}]"
                               min="0" step="0.01" inputmode="decimal" @if($esperadoMedio > 0) required @endif
                               value="{{ $valorDeclarado === null ? '' : number_format((float)$valorDeclarado, 2, '.', '') }}"
                               placeholder="0.00" data-monthly-declared>
                    </div>
                    <strong class="monthly-row-difference" data-monthly-difference>—</strong>
                </div>
                @error("declarado.$medio")<small class="text-danger d-block mb-2">{{ $message }}</small>@enderror
            @endforeach
        </div>

        <div class="monthly-form-footer">
            <div class="monthly-observation">
                <label for="monthly-observations">Observaciones</label>
                <textarea id="monthly-observations" name="observaciones" rows="3" maxlength="1000" placeholder="Explica cualquier diferencia encontrada...">{{ old('observaciones', $conciliacionMensual?->observaciones) }}</textarea>
                @error('observaciones')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
            <div class="monthly-totals">
                <div><span>Total del sistema</span><strong>S/ {{ number_format($cuadreMensual['total_recibido'], 2) }}</strong></div>
                <div><span>Total verificado</span><strong id="monthlyDeclaredTotal">S/ 0.00</strong></div>
                <div class="monthly-final-difference"><span>Diferencia</span><strong id="monthlyDifferenceTotal">S/ 0.00</strong></div>
                <small class="monthly-difference-help">Positivo: sobra · Negativo: falta</small>
                <button type="submit" class="btn-soft btn-soft-success monthly-save-button" @disabled($conciliacionMensual)>
                    <i class="fas {{ $conciliacionMensual ? 'fa-circle-check' : 'fa-floppy-disk' }}"></i>
                    {{ $conciliacionMensual ? 'Cuadre ya guardado' : 'Guardar cuadre mensual' }}
                </button>
            </div>
        </div>
        @if($conciliacionMensual)
            <small class="monthly-saved-by">Guardado por {{ $conciliacionMensual->usuario?->nombre ?? 'Administrador' }} el {{ $conciliacionMensual->updated_at->format('d/m/Y H:i') }}.</small>
        @endif
    </form>
</div>
