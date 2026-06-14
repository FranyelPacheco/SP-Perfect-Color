<!-- Archivo: notaEntregaDirectaView.php -->
<!-- Vista para crear nota de entrega directa con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nueva Nota de Entrega</h2>
    <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form id="formularioNotaEntrega">
    <input type="hidden" id="presupuestoId" name="presupuesto_id" value="">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-fill me-2 text-primary"></i>Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="clienteNota" class="form-label">Cliente</label>
                <select id="clienteNota" name="cliente_id" class="form-select" required>
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
            <div class="mb-0">
                <label for="tipoPago" class="form-label">Tipo de Pago</label>
                <select id="tipoPago" name="tipo_pago" class="form-select" onchange="toggleVencimiento()">
                    <option value="contado" selected>Contado</option>
                    <option value="credito">Credito</option>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6" id="contenedorMetodoPago" style="display:none">
                    <label for="metodoPago" class="form-label">Metodo de Pago</label>
                    <select id="metodoPago" name="metodo_pago" class="form-select">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pago Movil">Pago Movil</option>
                        <option value="Punto de Venta">Punto de Venta</option>
                        <option value="Divisas">Divisas</option>
                    </select>
                </div>
                <div class="col-md-6" id="contenedorVencimiento" style="display:none">
                    <label for="fechaVencimiento" class="form-label">Fecha de Vencimiento</label>
                    <input type="date" id="fechaVencimiento" name="fecha_vencimiento" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Agregar Insumos</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="busquedaInsumoNota" class="form-label">Buscar Insumo</label>
                <input type="text" id="busquedaInsumoNota" class="form-control" placeholder="Buscar por nombre o codigo...">
            </div>
            <div id="listaInsumosDisponibles" class="list-group" style="max-height: 250px; overflow-y: auto;">
                <div class="list-group-item text-center text-muted">Cargando insumos disponibles...</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Items a Entregar</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaItemsNota" class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Insumo</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaItems">
                        <tr id="filaVacia">
                            <td colspan="7" class="text-center text-muted">No hay items agregados</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total:</td>
                            <td id="totalNota" class="fw-bold">$ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="mensajeErrorNota" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-end gap-2">
        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
        <button type="submit" name="accion" value="pendiente" class="btn btn-primary btn-lg"><i class="bi bi-check-lg me-2"></i>Crear Nota de Entrega</button>
        <button type="submit" name="accion" value="en_espera" class="btn btn-warning btn-lg"><i class="bi bi-pause-circle me-2"></i>Poner en Espera</button>
    </div>
</form>

<script>
function toggleVencimiento() {
    console.log('[toggleVencimiento] Cambio a:', document.getElementById('tipoPago').value);
    var sel = document.getElementById('tipoPago');
    var contV = document.getElementById('contenedorVencimiento');
    var inpV = document.getElementById('fechaVencimiento');
    var contM = document.getElementById('contenedorMetodoPago');
    if (!sel) return;
    if (sel.value === 'credito') {
        if (contV) { contV.style.display = 'block'; }
        if (contM) { contM.style.display = 'none'; }
        if (inpV) {
            inpV.required = true;
            if (!inpV.value) {
                var fecha = new Date();
                fecha.setDate(fecha.getDate() + 10);
                inpV.value = fecha.toISOString().split('T')[0];
            }
        }
    } else {
        if (contM) { contM.style.display = 'block'; }
        if (contV) { contV.style.display = 'none'; }
        if (inpV) { inpV.required = false; inpV.value = ''; }
    }
}
console.log('[notaEntregaDirectaView] Script inline ejecutado');
toggleVencimiento();
</script>
<script src="/SP%20Perfect%20Color/assets/js/notaEntregaForm.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntregaForm.js'); ?>"></script>
