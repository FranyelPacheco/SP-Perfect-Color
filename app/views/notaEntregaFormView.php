<!-- Archivo: notaEntregaFormView.php -->
<!-- Vista para crear nota de entrega desde presupuesto aprobado con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nueva Nota de Entrega desde Presupuesto #<?php echo $presupuesto['id']; ?></h2>
    <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-secondary">Volver a la lista</a>
</div>

<form id="formularioNotaEntrega">
    <input type="hidden" id="presupuestoId" name="presupuesto_id" value="<?php echo $presupuesto['id']; ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="clienteNota" class="form-label">Cliente</label>
                <select id="clienteNota" name="cliente_id" class="form-select" required>
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Presupuesto de origen:</small>
                    <a href="/SP%20Perfect%20Color/presupuesto/ver?id=<?php echo $presupuesto['id']; ?>" class="text-decoration-none">#<?php echo $presupuesto['id']; ?> - <?php echo $presupuesto['cliente_nombre']; ?></a>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Total del presupuesto:</small>
                    <span class="fw-bold fs-5">Bs. <?php echo number_format($presupuesto['total'], 2, ',', '.'); ?></span>
                </div>
                <div class="col-md-4">
                    <label for="tipoPago" class="form-label">Tipo de Pago</label>
                    <select id="tipoPago" name="tipo_pago" class="form-select" onchange="toggleVencimiento()">
                        <option value="contado" selected>Contado</option>
                        <option value="credito">Credito</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorVencimiento" style="display:none">
                    <label for="fechaVencimiento" class="form-label">Fecha de Vencimiento</label>
                    <input type="date" id="fechaVencimiento" name="fecha_vencimiento" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Agregar Insumos</h5>
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
        <div class="card-header bg-white">
            <h5 class="mb-0">Items a Entregar</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaItemsNota" class="table table-hover mb-0">
                    <thead class="table-light">
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
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total:</td>
                            <td id="totalNota" class="fw-bold">Bs. 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="mensajeErrorNota" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-end gap-2">
        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">Crear Nota de Entrega</button>
    </div>
</form>

<script>
function toggleVencimiento() {
    console.log('[toggleVencimiento] Cambio a:', document.getElementById('tipoPago').value);
    var sel = document.getElementById('tipoPago');
    var cont = document.getElementById('contenedorVencimiento');
    var inp = document.getElementById('fechaVencimiento');
    if (!sel || !cont) return;
    if (sel.value === 'credito') {
        cont.style.display = 'block';
        if (inp) inp.required = true;
    } else {
        cont.style.display = 'none';
        if (inp) { inp.required = false; inp.value = ''; }
    }
}
console.log('[notaEntregaFormView] Script inline ejecutado');
var presupuestoDetalle = <?php echo json_encode($detalle); ?>;
var presupuestoClienteId = <?php echo (int)$presupuesto['cliente_id']; ?>;
</script>
<script src="/SP%20Perfect%20Color/assets/js/notaEntregaForm.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntregaForm.js'); ?>"></script>
