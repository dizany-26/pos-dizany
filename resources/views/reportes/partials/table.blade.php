<article class="report-panel report-table-panel">
    <div class="report-panel-heading"><div><span class="panel-icon"><i class="fas {{ $icon }}"></i></span><h2>{{ $title }}</h2></div><span class="table-count">{{ count($rows) }} registros</span></div>
    <div class="report-table-scroll">
        <table class="report-table">
            <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                @switch($type)
                    @case('ventas')
                        <td data-label="Fecha">{{ $row->fecha }}</td><td data-label="Comprobante"><strong>{{ $row->comprobante }}</strong></td><td data-label="Cliente"><span class="report-expandable" data-report-expand><span class="report-expandable-value">{{ $row->cliente }}</span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Responsable"><span class="report-expandable" data-report-expand><span class="report-expandable-value">{{ $row->responsable }}</span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Método"><span class="method-badge method-{{ strtolower($row->metodo_pago) }}">{{ ucfirst($row->metodo_pago) }}</span></td><td data-label="Estado"><span class="status-badge status-{{ strtolower($row->estado) }}">{{ ucfirst($row->estado) }}</span></td><td data-label="Total" class="money-cell">S/ {{ number_format($row->total, 2) }}</td>
                        @break
                    @case('productos')
                        @php($margin = $row->ventas > 0 ? ($row->utilidad / $row->ventas) * 100 : 0)
                        <td data-label="Producto"><span class="report-expandable" data-report-expand><span class="report-expandable-value"><strong>{{ $row->producto }}</strong></span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Categoría">{{ $row->categoria }}</td><td data-label="Unidades">{{ number_format($row->unidades) }}</td><td data-label="Ventas">S/ {{ number_format($row->ventas, 2) }}</td><td data-label="Utilidad" class="positive-cell">S/ {{ number_format($row->utilidad, 2) }}</td><td data-label="Margen">{{ number_format($margin, 1) }}%</td>
                        @break
                    @case('caja')
                        <td data-label="Cajero"><strong>{{ $row->cajero }}</strong></td><td data-label="Apertura">{{ $row->apertura }}</td><td data-label="Cierre">{{ $row->cierre ?: '—' }}</td><td data-label="Inicial">S/ {{ number_format($row->monto_inicial, 2) }}</td><td data-label="Esperado">S/ {{ number_format($row->monto_esperado, 2) }}</td><td data-label="Contado">S/ {{ number_format($row->monto_contado, 2) }}</td><td data-label="Diferencia" @class(['negative-cell' => $row->diferencia < 0, 'positive-cell' => $row->diferencia >= 0])>S/ {{ number_format($row->diferencia, 2) }}</td><td data-label="Estado"><span class="status-badge status-{{ strtolower($row->estado) }}">{{ ucfirst(str_replace('_',' ',$row->estado)) }}</span></td>
                        @break
                    @case('creditos') @case('clientes')
                        <td data-label="Cliente"><span class="report-expandable" data-report-expand><span class="report-expandable-value"><strong>{{ $row->cliente }}</strong></span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Compras">{{ $row->compras }}</td><td data-label="Consumo">S/ {{ number_format($row->consumo, 2) }}</td><td data-label="Última compra">{{ \Carbon\Carbon::parse($row->ultima_compra)->format('d/m/Y') }}</td><td data-label="Deuda" @class(['negative-cell' => $row->deuda > 0])>S/ {{ number_format($row->deuda, 2) }}</td>
                        @break
                    @case('inventario')
                        <td data-label="Producto"><strong>{{ $row->producto }}</strong></td><td data-label="Categoría">{{ $row->categoria }}</td><td data-label="Stock">{{ number_format($row->stock) }}</td><td data-label="Mínimo">{{ number_format($row->minimo) }}</td><td data-label="Estado"><span class="status-badge {{ $row->stock <= 0 ? 'status-anulado' : 'status-pendiente' }}">{{ $row->stock <= 0 ? 'Sin stock' : 'Stock bajo' }}</span></td>
                        @break
                    @case('vencimientos')
                        <td data-label="Lote">{{ $row->lote ?: '—' }}</td><td data-label="Producto"><strong>{{ $row->producto }}</strong></td><td data-label="Stock">{{ $row->stock }}</td><td data-label="Vencimiento">{{ $row->vencimiento }}</td><td data-label="Días"><span class="status-badge {{ $row->dias <= 15 ? 'status-anulado' : 'status-pendiente' }}">{{ $row->dias }} días</span></td>
                        @break
                    @case('compras')
                        <td data-label="Fecha">{{ $row->fecha }}</td><td data-label="Comprobante"><strong>{{ $row->comprobante }}</strong></td><td data-label="Proveedor"><span class="report-expandable" data-report-expand><span class="report-expandable-value">{{ $row->proveedor }}</span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Productos">{{ $row->productos }}</td><td data-label="Total" class="money-cell">S/ {{ number_format($row->total, 2) }}</td><td data-label="Pagado" class="positive-cell">S/ {{ number_format($row->pagado, 2) }}</td><td data-label="Saldo" @class(['negative-cell' => $row->saldo > 0])>S/ {{ number_format($row->saldo, 2) }}</td><td data-label="Estado"><span class="status-badge status-{{ $row->estado_pago === 'parcial' ? 'pendiente' : $row->estado_pago }}">{{ ucfirst($row->estado_pago) }}</span></td>
                        @break
                    @case('gastos')
                        <td data-label="Fecha">{{ $row->fecha }}</td><td data-label="Descripción"><span class="report-expandable" data-report-expand><span class="report-expandable-value"><strong>{{ $row->descripcion }}</strong></span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Método"><span class="method-badge method-{{ strtolower($row->metodo) }}">{{ ucfirst($row->metodo) }}</span></td><td data-label="Responsable"><span class="report-expandable" data-report-expand><span class="report-expandable-value">{{ $row->responsable }}</span><button type="button" class="report-expand-toggle">Ver más</button></span></td><td data-label="Monto" class="negative-cell">- S/ {{ number_format($row->monto, 2) }}</td>
                        @break
                @endswitch
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}"><div class="report-empty"><i class="fas fa-folder-open"></i><p>No hay información para los filtros seleccionados.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</article>
