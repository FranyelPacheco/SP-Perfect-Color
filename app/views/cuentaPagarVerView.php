<!-- Archivo: cuentaPagarVerView.php -->
<!-- Vista para ver el detalle de una cuenta por pagar con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Cuenta por Pagar #<?php echo $cuenta['id']; ?></h2>
    <a href="/SP%20Perfect%20Color/cuentaPagar" class="btn btn-secondary">Volver a la lista</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Informacion de la Cuenta</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Proveedor:</small>
                <span><?php echo $cuenta['proveedor_nombre']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">RIF:</small>
                <span><?php echo $cuenta['proveedor_rif']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Telefono:</small>
                <span><?php echo $cuenta['proveedor_telefono'] ?? '-'; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Monto Total:</small>
                <span>Bs. <?php echo number_format($cuenta['monto_total'], 2, ',', '.'); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Saldo Pendiente:</small>
                <span class="fw-bold fs-5 <?php echo $cuenta['saldo_pendiente'] > 0 ? 'text-danger' : 'text-success'; ?>">
                    Bs. <?php echo number_format($cuenta['saldo_pendiente'], 2, ',', '.'); ?>
                </span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Vencimiento:</small>
                <span><?php echo $cuenta['fecha_vencimiento'] ?? '-'; ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Pagos Realizados</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Metodo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?php echo $pago['fecha']; ?></td>
                        <td>Bs. <?php echo number_format($pago['monto'], 2, ',', '.'); ?></td>
                        <td><?php echo $pago['metodo_pago']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pagos)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No hay pagos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($cuenta['saldo_pendiente'] > 0 && $_SESSION['usuario_rol'] == 1): ?>
<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPago">
        <i class="bi bi-cash-stack me-2"></i>Registrar Abono
    </button>
</div>

<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Abono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioPago">
                <div class="modal-body">
                    <input type="hidden" id="cuentaId" value="<?php echo $cuenta['id']; ?>">

                    <div class="mb-3">
                        <label for="montoPago" class="form-label">Monto del Abono (Bs.)</label>
                        <input type="number" id="montoPago" class="form-control" step="0.01" min="0.01" max="<?php echo $cuenta['saldo_pendiente']; ?>" value="<?php echo $cuenta['saldo_pendiente']; ?>" required>
                        <small class="text-muted">Saldo pendiente: Bs. <?php echo number_format($cuenta['saldo_pendiente'], 2, ',', '.'); ?></small>
                    </div>

                    <div class="mb-3">
                        <label for="fechaPago" class="form-label">Fecha del Pago</label>
                        <input type="date" id="fechaPago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="metodoPagoCuenta" class="form-label">Metodo de Pago</label>
                        <select id="metodoPagoCuenta" class="form-select">
                            <option value="Transferencia">Transferencia</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Pago Movil">Pago Movil</option>
                        </select>
                    </div>

                    <div id="mensajeErrorPago" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Registrar Abono</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formularioPago').addEventListener('submit', function(evento) {
    evento.preventDefault();
    var cuentaId = document.getElementById('cuentaId').value;
    var monto = document.getElementById('montoPago').value;
    var fecha = document.getElementById('fechaPago').value;
    var metodoPago = document.getElementById('metodoPagoCuenta').value;
    var mensajeError = document.getElementById('mensajeErrorPago');
    mensajeError.classList.add('d-none');
    if (!monto || parseFloat(monto) <= 0) {
        mensajeError.textContent = 'Ingrese un monto valido';
        mensajeError.classList.remove('d-none');
        return;
    }
    var formData = new FormData();
    formData.append('cuenta_id', cuentaId);
    formData.append('monto', monto);
    formData.append('fecha', fecha);
    formData.append('metodo_pago', metodoPago);
    fetch('/SP%20Perfect%20Color/cuentaPagar/registrarPago', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(resultado) {
        if (resultado.estado === 'exito') {
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalPago'));
            if (modal) modal.hide();
            location.reload();
        } else {
            mensajeError.textContent = resultado.mensaje;
            mensajeError.classList.remove('d-none');
        }
    })
    .catch(function() {
        mensajeError.textContent = 'Error de conexion';
        mensajeError.classList.remove('d-none');
    });
});
</script>
<?php endif; ?>
