<!-- Archivo: notaEntregaVerView.php -->
<!-- Vista para ver el detalle de una nota de entrega con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nota de Entrega #<?php echo $nota['id_nota_entrega']; ?></h2>
    <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Informacion de la Nota de Entrega</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Fecha:</small>
                <span><?php echo $nota['fecha']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cliente:</small>
                <span><?php echo $nota['cliente_nombre']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cedula:</small>
                <span><?php echo $nota['cliente_cedula']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Vendedor:</small>
                <span><?php echo $nota['usuario_nombre']; ?></span>
            </div>
            <?php if (!empty($nota['cliente_direccion'])): ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Direccion:</small>
                <span><?php echo $nota['cliente_direccion']; ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($nota['cliente_telefonos'])): ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Telefono:</small>
                <span><?php echo $nota['cliente_telefonos']; ?></span>
            </div>
            <?php endif; ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Estado:</small>
                <span class="estado-<?php echo $nota['estado']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $nota['estado'])); ?>
                </span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Total:</small>
                <span class="fw-bold fs-5">$ <?php echo number_format($nota['total'], 2, ',', '.'); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Condicion de Pago:</small>
                <span><?php echo ucfirst($nota['condicion_pago'] ?? '-'); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Tipo de Pago:</small>
                <span><?php echo $nota['tipo_pago_nombre'] ?? '-'; ?></span>
            </div>
        </div>

        <?php if (!empty($nota['presupuesto_id'])): ?>
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted d-block mb-1">Presupuesto de origen:</small>
            <a href="/SP%20Perfect%20Color/presupuesto/ver?id=<?php echo $nota['presupuesto_id']; ?>">Ver Presupuesto #<?php echo $nota['presupuesto_id']; ?></a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Items Entregados</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
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
                        <td>$ <?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></td>
                        <td>$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Total:</td>
                        <td class="fw-bold">$ <?php echo number_format($nota['total'], 2, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($nota['estado'] === 'pendiente'): ?>
<div class="d-flex justify-content-end gap-2 mt-4">
    <button class="btn btn-warning" onclick="cambiarEstado(<?php echo $nota['id_nota_entrega']; ?>, 'en_espera')"><i class="bi bi-pause-circle me-1"></i>Poner en Espera</button>
</div>
<?php elseif ($nota['estado'] === 'en_espera'): ?>
<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="/SP%20Perfect%20Color/notaEntrega/editar?id=<?php echo $nota['id_nota_entrega']; ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Editar Items</a>
    <button class="btn btn-success" onclick="cambiarEstado(<?php echo $nota['id_nota_entrega']; ?>, 'entregado')"><i class="bi bi-check-lg me-1"></i>Marcar como Entregado</button>
</div>
<?php endif; ?>

<script>
function cambiarEstado(id, estado) {
    var mensajes = { 'en_espera': 'Poner esta nota de entrega en espera?', 'entregado': 'Marcar esta nota como entregada?' };
    if (!confirm(mensajes[estado] || 'Cambiar estado a ' + estado + '?')) return;
    var fd = new FormData();
    fd.append('id', id);
    fd.append('estado', estado);
    fetch('/SP%20Perfect%20Color/notaEntrega/cambiarEstado', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.estado === 'exito') { location.reload(); }
        else { alert(res.mensaje); }
    })
    .catch(function(e) { console.error(e); alert('Error de conexion'); });
}
</script>
