<!-- Archivo: cuentaPagarVerView.php -->
<!-- Vista para ver el detalle de una cuenta por pagar con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Cuenta por Pagar #<?php echo $cuenta['id_cuenta_pagar']; ?></h2>
    <a href="/SP%20Perfect%20Color/cuentaPagar" class="btn btn-secondary">Volver a la lista</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-credit-card-2-back-fill me-2 text-primary"></i>Informacion de la Cuenta</h5>
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
                <span><?php echo $cuenta['proveedor_telefonos'] ?? '-'; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Monto Total:</small>
                <span>$ <?php echo number_format($cuenta['monto_total'], 2, ',', '.'); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Saldo Pendiente:</small>
                <span class="fw-bold fs-5 <?php echo $cuenta['saldo_pendiente'] > 0 ? 'text-danger' : 'text-success'; ?>">
                    $ <?php echo number_format($cuenta['saldo_pendiente'], 2, ',', '.'); ?>
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
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i>Pagos Realizados</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Tipo de Pago</th>
                        <th>Banco</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?php echo $pago['fecha']; ?></td>
                        <td>$ <?php echo number_format($pago['monto'], 2, ',', '.'); ?></td>
                        <td><?php echo $pago['tipo_pago_nombre'] ?? '-'; ?></td>
                        <td><?php echo $pago['banco_nombre'] ?? '-'; ?></td>
                        <td><?php echo $pago['referencia'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pagos)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No hay pagos registrados</td></tr>
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
                    <input type="hidden" id="cuentaId" value="<?php echo $cuenta['id_cuenta_pagar']; ?>">

                    <div class="mb-3">
                        <label for="montoPago" class="form-label">Monto del Abono ($)</label>
                        <input type="number" id="montoPago" class="form-control" step="0.01" min="0.01" max="<?php echo $cuenta['saldo_pendiente']; ?>" value="<?php echo $cuenta['saldo_pendiente']; ?>" required>
                        <small class="text-muted">Saldo pendiente: $ <?php echo number_format($cuenta['saldo_pendiente'], 2, ',', '.'); ?></small>
                    </div>

                    <div class="mb-3">
                        <label for="fechaPago" class="form-label">Fecha del Pago</label>
                        <input type="date" id="fechaPago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="tipoPagoCuenta" class="form-label">Tipo de Pago</label>
                        <select id="tipoPagoCuenta" class="form-select" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>

                    <div class="mb-3" id="contenedorBancoPago" style="display:none">
                        <label for="bancoPagoCuenta" class="form-label">Banco</label>
                        <select id="bancoPagoCuenta" class="form-select">
                            <option value="">Seleccione un banco...</option>
                        </select>
                    </div>

                    <div class="mb-3" id="contenedorRefPago" style="display:none">
                        <label for="referenciaPago" class="form-label">Referencia</label>
                        <input type="text" id="referenciaPago" class="form-control" placeholder="Numero de confirmacion">
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
fetch('/SP%20Perfect%20Color/cuentaPagar/obtenerDatosPagoAjax')
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.estado === 'exito') {
            var selTipo = document.getElementById('tipoPagoCuenta');
            selTipo.innerHTML = '<option value="">Seleccione...</option>';
            res.datos.tipos_pago.forEach(function(tp) {
                var op = document.createElement('option');
                op.value = tp.id_tipo_pago;
                op.textContent = tp.nombre;
                selTipo.appendChild(op);
            });
            var selBanco = document.getElementById('bancoPagoCuenta');
            res.datos.bancos.forEach(function(b) {
                var op = document.createElement('option');
                op.value = b.id_banco;
                op.textContent = b.nombre;
                selBanco.appendChild(op);
            });
        }
    });

document.getElementById('tipoPagoCuenta').addEventListener('change', function() {
    var val = parseInt(this.value);
    var contB = document.getElementById('contenedorBancoPago');
    var contR = document.getElementById('contenedorRefPago');
    var bancoSel = document.getElementById('bancoPagoCuenta');
    var refInput = document.getElementById('referenciaPago');
    // Transferencia=2, Pago Movil=3
    if (val === 2 || val === 3) {
        contB.style.display = 'block';
        contR.style.display = 'block';
        if (bancoSel) bancoSel.required = true;
        if (refInput) refInput.required = true;
    } else {
        contB.style.display = 'none';
        contR.style.display = 'none';
        if (bancoSel) bancoSel.required = false;
        if (refInput) refInput.required = false;
    }
});

document.getElementById('formularioPago').addEventListener('submit', function(evento) {
    evento.preventDefault();
    var cuentaId = document.getElementById('cuentaId').value;
    var monto = document.getElementById('montoPago').value;
    var fecha = document.getElementById('fechaPago').value;
    var tipoPagoId = document.getElementById('tipoPagoCuenta').value;
    var bancoId = document.getElementById('bancoPagoCuenta').value;
    var referencia = document.getElementById('referenciaPago').value;
    var mensajeError = document.getElementById('mensajeErrorPago');
    mensajeError.classList.add('d-none');
    if (!monto || parseFloat(monto) <= 0) {
        mensajeError.textContent = 'Ingrese un monto valido';
        mensajeError.classList.remove('d-none');
        return;
    }
    if (!tipoPagoId) {
        mensajeError.textContent = 'Seleccione un tipo de pago';
        mensajeError.classList.remove('d-none');
        return;
    }
    var tipoVal = parseInt(tipoPagoId);
    if (tipoVal === 2 || tipoVal === 3) {
        if (!bancoId) {
            mensajeError.textContent = 'Debe seleccionar un banco para transferencia o pago movil';
            mensajeError.classList.remove('d-none');
            return;
        }
        if (!referencia.trim()) {
            mensajeError.textContent = 'Debe ingresar el numero de referencia para transferencia o pago movil';
            mensajeError.classList.remove('d-none');
            return;
        }
    }
    var formData = new FormData();
    formData.append('cuenta_id', cuentaId);
    formData.append('monto', monto);
    formData.append('fecha', fecha);
    formData.append('tipo_pago_id', tipoPagoId);
    formData.append('banco_id', bancoId || '');
    formData.append('referencia', referencia);
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
