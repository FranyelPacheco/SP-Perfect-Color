<?php
// VISTA: notaEntregaFormView.php
// OBJETIVO: Formulario para crear nota de entrega desde un presupuesto aprobado
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nueva Nota de Entrega desde Presupuesto #<?php echo $presupuesto['id_presupuesto']; ?></h2>
    <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form id="formularioNotaEntrega">
    <input type="hidden" id="presupuestoId" name="id_presupuesto" value="<?php echo $presupuesto['id_presupuesto']; ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-fill me-2 text-primary"></i>Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="clienteNota" class="form-label">Cliente</label>
                <select id="clienteNota" name="id_cliente" class="form-select" required>
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Presupuesto de origen:</small>
                    <a href="/SP%20Perfect%20Color/presupuesto/ver?id=<?php echo $presupuesto['id_presupuesto']; ?>" class="text-decoration-none">#<?php echo $presupuesto['id_presupuesto']; ?> - <?php echo $presupuesto['cliente_nombre']; ?></a>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Total del presupuesto:</small>
                    <span class="fw-bold fs-5">$ <?php echo number_format($presupuesto['total'], 2, ',', '.'); ?></span>
                </div>
                <div class="col-md-4">
                    <label for="condicionPago" class="form-label">Condicion de Pago</label>
                    <select id="condicionPago" name="condicion_pago" class="form-select" onchange="toggleCondicionPago()">
                        <option value="contado" selected>Contado</option>
                        <option value="credito">Credito</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorTipoPago" style="display:block">
                    <label for="tipoPago" class="form-label">Tipo de Pago</label>
                    <select id="tipoPago" name="id_tipo_pago" class="form-select" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorBanco" style="display:none">
                    <label for="bancoPago" class="form-label">Banco</label>
                    <select id="bancoPago" name="id_banco" class="form-select">
                        <option value="">Seleccione un banco...</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorReferencia" style="display:none">
                    <label for="referenciaPago" class="form-label">Referencia</label>
                    <input type="text" id="referenciaPago" name="referencia" class="form-control" placeholder="Numero de confirmacion">
                </div>
                <div class="col-md-4" id="contenedorVencimiento" style="display:none">
                    <label for="fechaVencimiento" class="form-label">Fecha de Vencimiento</label>
                    <input type="date" id="fechaVencimiento" name="fecha_vencimiento" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Items del Presupuesto</h5>
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
                            <td colspan="7" class="text-center text-muted">Cargando items del presupuesto...</td>
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
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-lg me-2"></i>Crear Nota de Entrega</button>
    </div>
</form>

<script>
function toggleCondicionPago() {
    var sel = document.getElementById('condicionPago');
    var contV = document.getElementById('contenedorVencimiento');
    var inpV = document.getElementById('fechaVencimiento');
    var contT = document.getElementById('contenedorTipoPago');
    var contB = document.getElementById('contenedorBanco');
    var contR = document.getElementById('contenedorReferencia');
    var tipoPagoSel = document.getElementById('tipoPago');
    if (!sel) return;
    if (sel.value === 'credito') {
        if (contV) { contV.style.display = 'block'; }
        if (contT) { contT.style.display = 'none'; }
        if (contB) { contB.style.display = 'none'; }
        if (contR) { contR.style.display = 'none'; }
        if (tipoPagoSel) tipoPagoSel.required = false;
        if (inpV) {
            inpV.required = true;
            if (!inpV.value) {
                var fecha = new Date();
                fecha.setDate(fecha.getDate() + 10);
                inpV.value = fecha.toISOString().split('T')[0];
            }
        }
    } else {
        if (contT) { contT.style.display = 'block'; }
        if (contV) { contV.style.display = 'none'; }
        if (inpV) { inpV.required = false; inpV.value = ''; }
        if (contB) { contB.style.display = 'block'; }
        if (contR) { contR.style.display = 'block'; }
        if (tipoPagoSel) tipoPagoSel.required = true;
    }
}

// Mostrar/ocultar banco segun tipo de pago
document.addEventListener('DOMContentLoaded', function() {
    var tipoPagoSelect = document.getElementById('tipoPago');
    var bancoSelect = document.getElementById('bancoPago');
    var refInput = document.getElementById('referenciaPago');
    if (tipoPagoSelect) {
        function toggleBancoRef() {
            var contB = document.getElementById('contenedorBanco');
            var contR = document.getElementById('contenedorReferencia');
            var val = parseInt(tipoPagoSelect.value);
            // Transferencia=2, Pago Movil=3
            if (val === 2 || val === 3) {
                if (contB) { contB.style.display = 'block'; }
                if (contR) { contR.style.display = 'block'; }
                if (bancoSelect) bancoSelect.required = true;
                if (refInput) refInput.required = true;
            } else {
                if (contB) { contB.style.display = 'none'; }
                if (contR) { contR.style.display = 'none'; }
                if (bancoSelect) bancoSelect.required = false;
                if (refInput) refInput.required = false;
            }
        }
        tipoPagoSelect.addEventListener('change', toggleBancoRef);
        // Ejecutar al cargar en caso de valor preseleccionado
        setTimeout(toggleBancoRef, 100);
    }
});

toggleCondicionPago();
var presupuestoDetalle = <?php echo json_encode($detalle); ?>;
var presupuestoClienteId = <?php echo (int)$presupuesto['id_cliente']; ?>;
</script>
<script src="/SP%20Perfect%20Color/assets/js/notaEntregaForm.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntregaForm.js'); ?>"></script>
