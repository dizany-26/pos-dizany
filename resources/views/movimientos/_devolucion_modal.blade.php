<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content return-modal">
        <form id="return-form">@csrf
            <div class="modal-header"><div><h5 class="modal-title"><i class="fas fa-rotate-left me-2"></i>Registrar devolución</h5><small>La venta original y su caja permanecen intactas.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="return-search-row"><div class="input-group"><span class="input-group-text"><i class="fas fa-receipt"></i></span><input id="return-sale-search" class="form-control" placeholder="Número de comprobante, ej. B001-000074" autocomplete="off"><button class="btn btn-primary return-search-button" id="return-search-button" type="button" title="Buscar venta" aria-label="Buscar venta"><i class="fas fa-magnifying-glass"></i></button></div></div>
                <div id="return-feedback" class="alert d-none mt-3" role="alert"></div>
                <div id="return-sale-content" class="d-none">
                    <input type="hidden" name="venta_id" id="return-sale-id"><div class="return-sale-header" id="return-sale-header"></div>
                    <div id="return-tax-warning" class="alert alert-warning d-none"><i class="fas fa-triangle-exclamation me-2"></i>Este comprobante fue emitido fiscalmente. La devolución quedará marcada para emitir la nota de crédito correspondiente.</div>
                    <div class="table-responsive"><table class="table return-items-table"><thead><tr><th>Producto</th><th>Disponible</th><th style="width:120px">Cantidad</th><th class="text-end">Importe</th><th>Inventario</th></tr></thead><tbody id="return-items"></tbody></table></div>
                    <div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label">Método de reembolso</label><select class="form-select" name="metodo_reembolso"><option value="efectivo">Efectivo</option><option value="yape">Yape</option><option value="plin">Plin</option><option value="tarjeta">Tarjeta</option><option value="transferencia">Transferencia</option><option value="otro">Otro</option></select><small class="text-muted">Solo se usará si corresponde devolver dinero.</small></div><div class="col-md-6"><label class="form-label">Motivo</label><textarea class="form-control" name="motivo" rows="2" maxlength="500" required placeholder="Describe por qué se devuelve el producto"></textarea></div></div>
                    <div class="return-total"><span>Total seleccionado</span><strong id="return-total">S/ 0.00</strong></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-soft btn-soft-info btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn-soft btn-soft-primary btn-sm" id="return-submit" disabled><i class="fas fa-floppy-disk"></i> Guardar</button></div>
        </form>
    </div></div>
</div>
