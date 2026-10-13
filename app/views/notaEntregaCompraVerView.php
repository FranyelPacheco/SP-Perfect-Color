<?php
// VISTA: notaEntregaCompraVerView.php
// OBJETIVO: Comprobante y detalle de recepción de nota de entrega de compra a proveedor
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="/SP%20Perfect%20Color/notaEntregaCompra" class="btn btn-outline-secondary" title="Volver" data-bs-toggle="tooltip">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="h4 mb-0">Nota de Entrega (Compra) #<?php echo str_pad((string)$nota['id_nota_entrega'], 5, '0', STR_PAD_LEFT); ?></h2>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" onclick="window.print();" title="Imprimir" data-bs-toggle="tooltip">
            <i class="bi bi-printer"></i>
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fs-6 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Información de la Recepción de Insumos</h5>
        <span class="badge <?php echo ($nota['situacion_pago'] === 'pagado') ? 'bg-success' : 'bg-warning text-dark'; ?> fs-6">
            <?php echo strtoupper($nota['situacion_pago']); ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Fecha de Recepción:</small>
                <span class="fw-semibold"><?php echo date('d/m/Y h:i A', strtotime($nota['fecha'])); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Proveedor:</small>
                <span class="fw-bold"><?php echo htmlspecialchars($nota['proveedor_nombre']); ?></span>
            </div>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">RIF:</small>
                <span><?php echo htmlspecialchars($nota['proveedor_rif']); ?></span>
            </div>
            <?php if (!empty($nota['proveedor_telefonos'])): ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Teléfono(s):</small>
                <span><?php echo htmlspecialchars($nota['proveedor_telefonos']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($nota['proveedor_direccion'])): ?>
            <div class="col-md-8 col-12">
                <small class="text-muted d-block">Dirección Proveedor:</small>
                <span><?php echo htmlspecialchars($nota['proveedor_direccion']); ?></span>
            </div>
            <?php endif; ?>
<?php
$tasaDocCompra = !empty($nota['tasa_bcv']) ? (float)$nota['tasa_bcv'] : (float)($tasaCambioGlobal ?? 0);
$totalUsdCompra = (float)($nota['total'] ?? 0);
$totalBsCompra = $totalUsdCompra * $tasaDocCompra;
$saldoUsdCompra = (float)($nota['saldo_pendiente'] ?? 0);
$saldoBsCompra = $saldoUsdCompra * $tasaDocCompra;
?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Condición de Pago:</small>
                <span class="badge bg-light text-dark border"><?php echo !empty($nota['id_cuenta_pagar']) && $saldoUsdCompra > 0 ? 'A Crédito' : 'Contado'; ?></span>
                <?php if (!empty($nota['id_cuenta_pagar'])): ?>
                    <a href="/SP%20Perfect%20Color/cuentaPagar/ver?id=<?php echo (int)$nota['id_cuenta_pagar']; ?>" class="btn btn-link btn-sm p-0 ms-2 text-decoration-none">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Ver Cuenta/Pagos
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($nota['id_cuenta_pagar']) && $saldoUsdCompra > 0): ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Saldo Pendiente CxP:</small>
                <span class="text-danger fw-bold">$ <?php echo number_format($saldoUsdCompra, 2, ',', '.'); ?></span>
                <?php if ($tasaDocCompra > 0): ?>
                    <small class="text-muted d-block">Equiv. Fecha: Bs. <?php echo number_format($saldoBsCompra, 2, ',', '.'); ?></small>
                <?php endif; ?>
                <?php if (!empty($nota['fecha_vencimiento'])): ?>
                <small class="text-muted d-block">Vence: <?php echo date('d/m/Y', strtotime($nota['fecha_vencimiento'])); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="col-md-4 col-6">
                <small class="text-muted d-block">Monto Total:</small>
                <span class="fw-bold fs-5 text-success">$ <?php echo number_format($totalUsdCompra, 2, ',', '.'); ?></span>
                <?php if ($tasaDocCompra > 0): ?>
                    <div class="text-muted small">
                        Bs. <?php echo number_format($totalBsCompra, 2, ',', '.'); ?>
                        <span class="badge bg-light text-secondary border ms-1">Tasa fecha: Bs. <?php echo number_format($tasaDocCompra, 2, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($nota['observaciones'])): ?>
            <div class="col-12 mt-2">
                <small class="text-muted d-block">Observaciones:</small>
                <p class="mb-0 text-secondary bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($nota['observaciones'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fs-6 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Insumos Recibidos e Ingresados al Inventario</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Código</th>
                        <th>Insumo / Descripción</th>
                        <th class="text-center" style="width: 150px;">Cantidad Recibida</th>
                        <th class="text-end" style="width: 150px;">Precio Compra ($)</th>
                        <th class="text-end" style="width: 160px;">Subtotal ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($detalle as $item): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($item['producto_codigo']); ?></span></td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                        <td class="text-center fw-bold text-primary">
                            +<?php echo (int)round((float)$item['cantidad']); ?>
                        </td>
                        <td class="text-end">$ <?php echo number_format((float)$item['precio_compra'], 2, ',', '.'); ?></td>
                        <td class="text-end fw-bold">$ <?php echo number_format((float)$item['subtotal'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="5" class="text-end">TOTAL DE LA NOTA DE ENTREGA:</td>
                        <td class="text-end">
                            <div class="fs-5 text-primary">$ <?php echo number_format($totalUsdCompra, 2, ',', '.'); ?></div>
                            <?php if ($tasaDocCompra > 0): ?>
                                <div class="text-muted small fw-normal">Bs. <?php echo number_format($totalBsCompra, 2, ',', '.'); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
