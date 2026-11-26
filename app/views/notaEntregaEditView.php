<?php
// VISTA: notaEntregaEditView.php
// OBJETIVO: Modificación de una nota de entrega aprobada (RF 4.1.1)
$tieneAbonos = (($nota['condicion_pago'] ?? '') === 'credito') && ((int)($nota['cantidad_pagos'] ?? 0) > 0);
$fechaVencVal = !empty($nota['fecha_vencimiento']) ? substr((string)$nota['fecha_vencimiento'], 0, 10) : date('Y-m-d', strtotime('+10 days'));
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Modificar Nota de Entrega #<?php echo $nota['id_nota_entrega']; ?></h2>
    <a href="<?php echo url(); ?>notaEntrega/ver?id=<?php echo $nota['id_nota_entrega']; ?>" class="btn btn-outline-secondary" title="Volver" data-bs-toggle="tooltip">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<form id="formEditarNotaEntrega">
    <input type="hidden" name="id_venta" value="<?php echo (int)$nota['id_nota_entrega']; ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-vcard me-2 text-primary"></i>Datos Generales</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Cliente:</small>
                    <span class="fw-semibold"><?php echo htmlspecialchars($nota['cliente_nombre'] ?? ''); ?></span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Cédula:</small>
                    <span><?php echo htmlspecialchars($nota['cliente_cedula'] ?? ''); ?></span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Total:</small>
                    <span class="fw-bold fs-5 text-success">$ <?php echo number_format((float)($nota['total'] ?? 0), 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i>Condición y Método de Pago</h5>
        </div>
        <div class="card-body">
            <?php if ($tieneAbonos): ?>
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle-fill me-2"></i>Esta nota a crédito ya posee abonos registrados en Cuentas por Cobrar; solo puede modificar la fecha de vencimiento y las observaciones.
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="editCondicionPago" class="form-label fw-semibold">Condición de Pago</label>
                    <?php if ($tieneAbonos): ?>
                        <input type="hidden" name="condicion_pago" value="credito">
                        <input type="text" class="form-control bg-light" value="Crédito (Con abonos)" readonly>
                    <?php else: ?>
                        <select id="editCondicionPago" name="condicion_pago" class="form-select">
                            <option value="contado" <?php echo ($nota['condicion_pago'] ?? '') === 'contado' ? 'selected' : ''; ?>>Contado</option>
                            <option value="credito" <?php echo ($nota['condicion_pago'] ?? '') === 'credito' ? 'selected' : ''; ?>>Crédito (Cuenta por Cobrar)</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="col-md-4" id="editGrupoTipoPago" style="display:none">
                    <label for="editTipoPago" class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                    <select id="editTipoPago" name="id_tipo_pago" class="form-select">
                        <option value="">Seleccione...</option>
                        <?php foreach ($tiposPago as $tp): ?>
                            <option value="<?php echo $tp['id_tipo_pago']; ?>" <?php echo (int)($nota['id_tipo_pago'] ?? 0) === (int)$tp['id_tipo_pago'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tp['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4" id="editGrupoBanco" style="display:none">
                    <label for="editBanco" class="form-label fw-semibold">Banco Receptor <span class="text-danger">*</span></label>
                    <select id="editBanco" name="id_banco" class="form-select">
                        <option value="">Seleccione un banco...</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?php echo $b['id_banco']; ?>" <?php echo (int)($nota['id_banco'] ?? 0) === (int)$b['id_banco'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4" id="editGrupoReferencia" style="display:none">
                    <label for="editReferencia" class="form-label fw-semibold">Referencia (últimos 5 dígitos) <span class="text-danger">*</span></label>
                    <input type="text" id="editReferencia" name="referencia" class="form-control" value="<?php echo htmlspecialchars($nota['referencia'] ?? ''); ?>" placeholder="Ej: 48215" minlength="5" maxlength="5" pattern="[0-9]{5}" inputmode="numeric">
                </div>

                <div class="col-md-4" id="editGrupoVencimiento" style="display:none">
                    <label for="editFechaVencimiento" class="form-label fw-semibold">Fecha de Vencimiento <span class="text-danger">*</span></label>
                    <input type="date" id="editFechaVencimiento" name="fecha_vencimiento" class="form-control" value="<?php echo htmlspecialchars($fechaVencVal); ?>">
                </div>

                <div class="col-12">
                    <label for="editObservaciones" class="form-label fw-semibold">Observaciones</label>
                    <textarea id="editObservaciones" name="observaciones" class="form-control" rows="2" maxlength="250" placeholder="Observaciones de la entrega..."><?php echo htmlspecialchars($nota['observaciones'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Productos Despachados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['insumo_codigo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['insumo_nombre'] ?? ''); ?></td>
                            <td><?php echo (int)round((float)$item['cantidad']); ?></td>
                            <td>$ <?php echo number_format((float)$item['precio_unitario'], 2, ',', '.'); ?></td>
                            <td>$ <?php echo number_format((float)$item['subtotal'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="alertaErrorEditarNota" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?php echo url(); ?>notaEntrega/ver?id=<?php echo $nota['id_nota_entrega']; ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btnGuardarEdicionNota"><i class="bi bi-check-lg me-1"></i>Guardar Cambios</button>
    </div>
</form>

<script>
$(function() {
    var tieneAbonos = <?php echo $tieneAbonos ? 'true' : 'false'; ?>;

    function actualizarCampos() {
        if (tieneAbonos) {
            $('#editGrupoVencimiento').show();
            $('#editFechaVencimiento').prop('required', true);
            return;
        }
        var cond = $('#editCondicionPago').val();
        if (cond === 'credito') {
            $('#editGrupoVencimiento').show();
            $('#editFechaVencimiento').prop('required', true);
            $('#editGrupoTipoPago, #editGrupoBanco, #editGrupoReferencia').hide();
            $('#editTipoPago, #editBanco, #editReferencia').prop('required', false);
        } else {
            $('#editGrupoVencimiento').hide();
            $('#editFechaVencimiento').prop('required', false);
            $('#editGrupoTipoPago').show();
            $('#editTipoPago').prop('required', true);
            var tp = parseInt($('#editTipoPago').val() || 0, 10);
            var reqBanco = !(tp === 1 || tp === 0);
            $('#editGrupoBanco, #editGrupoReferencia').css('display', reqBanco ? 'block' : 'none');
            $('#editBanco, #editReferencia').prop('required', reqBanco);
        }
    }

    $('#editCondicionPago, #editTipoPago').on('change', actualizarCampos);
    actualizarCampos();

    $('#formEditarNotaEntrega').on('submit', function(e) {
        e.preventDefault();
        var $err = $('#alertaErrorEditarNota').addClass('d-none');
        var fd = new FormData(this);
        api.post('notaEntrega/actualizar', fd)
            .then(function(res) {
                if (res.estado === 'exito') {
                    mostrarNotificacion(res.mensaje, 'exito');
                    setTimeout(function() {
                        window.location.href = BASE_URL + 'notaEntrega/ver?id=<?php echo (int)$nota['id_nota_entrega']; ?>';
                    }, 900);
                } else {
                    $err.text(res.mensaje || 'No se pudo actualizar la nota').removeClass('d-none');
                }
            })
            .catch(function() {
                $err.text('No se pudo conectar con el servidor. Intente de nuevo.').removeClass('d-none');
            });
    });
});
</script>
