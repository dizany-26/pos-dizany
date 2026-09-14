<link rel="stylesheet" href="{{ asset('css/gasto-modal.css') }}?v={{ filemtime(public_path('css/gasto-modal.css')) }}">

<div class="modal fade" id="modalNuevoGasto" tabindex="-1" aria-labelledby="modalNuevoGastoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content gasto-modal">
            <div class="modal-header">
                <div>
                    <span class="gasto-modal-eyebrow">OPERACIONES · CAJA</span>
                    <h5 class="modal-title" id="modalNuevoGastoTitulo"><i class="fas fa-receipt me-2"></i>Nuevo gasto</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formNuevoGasto" action="{{ route('gastos.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div id="gastoModalError" class="alert alert-danger d-none" role="alert"></div>
                    <div class="gasto-modal-grid">
                        <div class="gasto-modal-field gasto-modal-field-wide">
                            <label for="gastoDescripcion" class="form-label">Descripción</label>
                            <input id="gastoDescripcion" name="descripcion" type="text" class="form-control" maxlength="255" placeholder="¿En qué se gastó?" required>
                        </div>
                        <div class="gasto-modal-field">
                            <label for="gastoMonto" class="form-label">Monto</label>
                            <div class="input-group"><span class="input-group-text">S/</span><input id="gastoMonto" name="monto" type="number" class="form-control" min="0.01" step="0.01" placeholder="0.00" required></div>
                        </div>
                        <div class="gasto-modal-field">
                            <label for="gastoMetodo" class="form-label">Método de pago</label>
                            <select id="gastoMetodo" name="metodo_pago" class="form-select" required>
                                <option value="">Selecciona un método</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="gasto-modal-field gasto-modal-field-wide">
                            <label for="gastoFecha" class="form-label">Fecha y hora</label>
                            <input id="gastoFecha" name="fecha" type="datetime-local" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                    <p class="gasto-modal-note mb-0"><i class="fas fa-user-circle me-1"></i> Registrado por {{ auth()->user()->nombre }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-soft btn-soft-success gasto-submit"><i class="fas fa-check me-1"></i> Registrar gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/gasto-modal.js') }}?v={{ filemtime(public_path('js/gasto-modal.js')) }}"></script>
