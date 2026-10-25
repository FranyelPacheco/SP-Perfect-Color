<?php
// VISTA: presupuestoVerView.php
// OBJETIVO: Detalle de un presupuesto con acciones de aprobar/rechazar
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Presupuesto #<?php echo $presupuesto['id_presupuesto']; ?></h2>
    <a href="../presupuesto" class="btn btn-outline-secondary" title="Volver" data-bs-toggle="tooltip"><i class="bi bi-arrow-left"></i></a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>Informacion del Presupuesto</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Fecha de Creacion:</small>
                <span><?php echo $presupuesto['fecha']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Fecha de Aprobacion:</small>
                <span><?php echo !empty($presupuesto['fecha_aprobacion']) ? $presupuesto['fecha_aprobacion'] : '<span class="text-muted fst-italic">Pendiente</span>'; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cliente:</small>
                <span><?php echo htmlspecialchars($presupuesto['cliente_nombre'] ?? ''); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cédula:</small>
                <span><?php echo htmlspecialchars($presupuesto['cliente_cedula'] ?? ''); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Estado:</small>
                <span class="estado-<?php echo htmlspecialchars($presupuesto['estado'] ?? ''); ?>">
                    <?php echo ucfirst($presupuesto['estado'] ?? ''); ?>
                </span>
            </div>
<?php
$tasaDocPres = !empty($presupuesto['tasa_bcv']) ? (float)$presupuesto['tasa_bcv'] : (float)($tasaCambioGlobal ?? 0);
$totalUsdPres = (float)($presupuesto['total'] ?? 0);
$totalBsPres = $totalUsdPres * $tasaDocPres;
?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Total:</small>
                <span class="fw-bold fs-5">$ <?php echo number_format($totalUsdPres, 2, ',', '.'); ?></span>
                <?php if ($tasaDocPres > 0): ?>
                    <div class="text-muted small">
                        Bs. <?php echo number_format($totalBsPres, 2, ',', '.'); ?>
                        <span class="badge bg-light text-secondary border ms-1">Tasa fecha: Bs. <?php echo number_format($tasaDocPres, 2, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($presupuesto['observaciones'])): ?>
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted d-block mb-1">Observaciones:</small>
            <p class="mb-0"><?php echo htmlspecialchars($presupuesto['observaciones']); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Items del Presupuesto</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle as $item): ?>
                    <tr>
                        <td><?php echo $item['insumo_codigo']; ?></td>
                        <td><?php echo htmlspecialchars($item['insumo_nombre'] ?? ''); ?></td>
                        <td><?php echo (int)round((float)$item['cantidad']); ?></td>
                        <td>$ <?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></td>
                        <td>$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total:</td>
                        <td>
                            <div class="fw-bold">$ <?php echo number_format($totalUsdPres, 2, ',', '.'); ?></div>
                            <?php if ($tasaDocPres > 0): ?>
                                <div class="text-muted small">Bs. <?php echo number_format($totalBsPres, 2, ',', '.'); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($presupuesto['estado'] == 'pendiente' || $presupuesto['estado'] == 'presupuesto' || (int)($presupuesto['id_estado_venta'] ?? 0) === 1): ?>
<div class="d-flex justify-content-end gap-2">
    <button class="btn btn-outline-danger" onclick="cambiarEstado(<?php echo $presupuesto['id_presupuesto']; ?>, 'rechazado')"><i class="bi bi-x-lg me-1"></i>Rechazar</button>
    <a href="../notaEntrega/crearDesdePresupuesto?id=<?php echo $presupuesto['id_presupuesto']; ?>" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Aprobar</a>
</div>
<?php endif; ?>

<?php if ($presupuesto['estado'] == 'aprobado' || (int)($presupuesto['id_estado_venta'] ?? 0) === 2): ?>
<div class="d-flex justify-content-end">
    <a href="../notaEntrega/crearDesdePresupuesto?id=<?php echo $presupuesto['id_presupuesto']; ?>" class="btn btn-primary"><i class="bi bi-receipt-cutoff me-1"></i>Procesar Entrega</a>
</div>
<?php endif; ?>

<script>
function cambiarEstado(id, estado) {
    var mensaje = estado === 'aprobado' ? 'Esta seguro de aprobar este presupuesto?' : 'Esta seguro de rechazar este presupuesto?';
    if (!confirm(mensaje)) return;
    var formData = new FormData();
    formData.append('id', id);
    formData.append('estado', estado);
    fetch('../presupuesto/cambiarEstado', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(resultado) {
        if (resultado.estado === 'exito') { location.reload(); }
        else { alert(resultado.mensaje); }
    })
    .catch(function(error) { console.error('Error:', error); alert('Error de conexion'); });
}
</script>
