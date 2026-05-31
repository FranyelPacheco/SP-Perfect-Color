<!-- Archivo: presupuestoVerView.php -->
<!-- Vista para ver el detalle de un presupuesto con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Presupuesto #<?php echo $presupuesto['id']; ?></h2>
    <a href="../presupuesto" class="btn btn-secondary">Volver a la lista</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Informacion del Presupuesto</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Fecha:</small>
                <span><?php echo $presupuesto['fecha']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cliente:</small>
                <span><?php echo $presupuesto['cliente_nombre']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cedula:</small>
                <span><?php echo $presupuesto['cliente_cedula']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Vendedor:</small>
                <span><?php echo $presupuesto['usuario_nombre']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Estado:</small>
                <span class="badge <?php echo $presupuesto['estado'] === 'aprobado' ? 'bg-success' : ($presupuesto['estado'] === 'rechazado' ? 'bg-danger' : ($presupuesto['estado'] === 'convertido' ? 'bg-info text-dark' : 'bg-warning text-dark')); ?>">
                    <?php echo ucfirst($presupuesto['estado']); ?>
                </span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Total:</small>
                <span class="fw-bold fs-5">Bs. <?php echo number_format($presupuesto['total'], 2, ',', '.'); ?></span>
            </div>
        </div>

        <?php if (!empty($presupuesto['observaciones'])): ?>
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted d-block mb-1">Observaciones:</small>
            <p class="mb-0"><?php echo $presupuesto['observaciones']; ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Items del Presupuesto</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Codigo</th>
                        <th>Insumo</th>
                        <th>Marca</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle as $item): ?>
                    <tr>
                        <td><?php echo $item['insumo_codigo']; ?></td>
                        <td><?php echo $item['insumo_nombre']; ?></td>
                        <td><?php echo $item['insumo_marca'] ?: '-'; ?></td>
                        <td><?php echo number_format($item['cantidad'], 2, ',', '.'); ?></td>
                        <td>Bs. <?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></td>
                        <td>Bs. <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Total:</td>
                        <td class="fw-bold">Bs. <?php echo number_format($presupuesto['total'], 2, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($presupuesto['estado'] == 'pendiente'): ?>
<div class="d-flex justify-content-end gap-2">
    <button class="btn btn-danger" onclick="cambiarEstado(<?php echo $presupuesto['id']; ?>, 'rechazado')">Rechazar</button>
    <button class="btn btn-success" onclick="cambiarEstado(<?php echo $presupuesto['id']; ?>, 'aprobado')">Aprobar</button>
</div>
<?php endif; ?>

<?php if ($presupuesto['estado'] == 'aprobado'): ?>
<div class="d-flex justify-content-end">
    <a href="../notaEntrega/crearDesdePresupuesto?id=<?php echo $presupuesto['id']; ?>" class="btn btn-primary btn-lg">Crear Nota de Entrega</a>
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
