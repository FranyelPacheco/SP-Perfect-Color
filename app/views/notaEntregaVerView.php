<?php
// VISTA: notaEntregaVerView.php
// OBJETIVO: Detalle de una nota de entrega
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Nota de Entrega #<?php echo $nota['id_nota_entrega']; ?></h2>
    <div class="d-flex gap-2">
        <a href="/SP%20Perfect%20Color/notaEntrega/pdf?id=<?php echo $nota['id_nota_entrega']; ?>" target="_blank" class="btn btn-primary shadow-sm" title="Ver PDF" data-bs-toggle="tooltip">
            <i class="bi bi-file-earmark-pdf-fill"></i>
        </a>
        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary" title="Volver" data-bs-toggle="tooltip">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Información de la Nota de Entrega</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Fecha:</small>
                <span><?php echo $nota['fecha']; ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cliente:</small>
                <span><?php echo htmlspecialchars($nota['cliente_nombre'] ?? ''); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Cédula:</small>
                <span><?php echo htmlspecialchars($nota['cliente_cedula'] ?? ''); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Vendedor:</small>
                <span><?php echo htmlspecialchars($nota['usuario_nombre'] ?? ''); ?></span>
            </div>
            <?php if (!empty($nota['cliente_direccion'])): ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Dirección:</small>
                <span><?php echo htmlspecialchars($nota['cliente_direccion']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($nota['cliente_telefonos'])): ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Teléfono:</small>
                <span><?php echo htmlspecialchars($nota['cliente_telefonos']); ?></span>
            </div>
            <?php endif; ?>
<?php
$tasaDocNota = !empty($nota['tasa_bcv']) ? (float)$nota['tasa_bcv'] : (float)($tasaCambioGlobal ?? 0);
$totalUsdNota = (float)($nota['total'] ?? 0);
$totalBsNota = $totalUsdNota * $tasaDocNota;
?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Total:</small>
                <span class="fw-bold fs-5">$ <?php echo number_format($totalUsdNota, 2, ',', '.'); ?></span>
                <?php if ($tasaDocNota > 0): ?>
                    <div class="text-muted small">
                        Bs. <?php echo number_format($totalBsNota, 2, ',', '.'); ?>
                        <span class="badge bg-light text-secondary border ms-1">Tasa fecha: Bs. <?php echo number_format($tasaDocNota, 2, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Condición de Pago:</small>
                <span><?php echo ucfirst($nota['condicion_pago'] ?? '-'); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Tipo de Pago:</small>
                <span><?php echo htmlspecialchars($nota['tipo_pago_nombre'] ?? '-'); ?></span>
            </div>
        </div>

        <?php if (!empty($nota['id_presupuesto'])): ?>
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted d-block mb-1">Presupuesto de origen:</small>
            <a href="/SP%20Perfect%20Color/presupuesto/ver?id=<?php echo $nota['id_presupuesto']; ?>">Ver Presupuesto #<?php echo $nota['id_presupuesto']; ?></a>
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
                            <div class="fw-bold">$ <?php echo number_format($totalUsdNota, 2, ',', '.'); ?></div>
                            <?php if ($tasaDocNota > 0): ?>
                                <div class="text-muted small">Bs. <?php echo number_format($totalBsNota, 2, ',', '.'); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver al Listado</a>
    <a href="/SP%20Perfect%20Color/notaEntrega/pdf?id=<?php echo $nota['id_nota_entrega']; ?>" target="_blank" class="btn btn-primary shadow-sm">
        <i class="bi bi-file-earmark-pdf-fill me-2"></i>Descargar Comprobante PDF
    </a>
</div>
