<?php
// VISTA: dashboardView.php
// OBJETIVO: Panel principal con estadísticas, alertas de stock, acceso rápido y gráfica de ingresos
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0 toolbar-title">Panel Principal</h2>
    <span class="text-muted small">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-label">Clientes</div>
            <div class="stat-value" data-valor="<?php echo $totalClientes; ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-truck"></i></div>
            <div class="stat-label">Proveedores</div>
            <div class="stat-value" data-valor="<?php echo $totalProveedores; ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-label">Insumos</div>
            <div class="stat-value" data-valor="<?php echo $totalInsumos; ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card stat-orange">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-label">Alertas Stock</div>
            <div class="stat-value" data-valor="<?php echo count($alertasStock); ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">Ingresos Hoy</div>
            <div class="stat-value" data-valor="<?php echo $pagosRecibidosHoy; ?>" data-moneda="1">$ 0,00</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card stat-red">
            <div class="stat-icon"><i class="bi bi-credit-card-2-back-fill"></i></div>
            <div class="stat-label">Egresos Hoy</div>
            <div class="stat-value" data-valor="<?php echo $pagosRealizadosHoy; ?>" data-moneda="1">$ 0,00</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Alertas de Stock Bajo</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($alertasStock)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                    <span>Todos los insumos tienen stock suficiente.</span>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Insumo</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertasStock as $alerta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($alerta['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($alerta['nombre']); ?></td>
                                <td class="text-danger fw-bold"><?php echo number_format($alerta['stock_actual'], 2); ?></td>
                                <td><?php echo number_format($alerta['stock_minimo'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle-fill me-2 text-info"></i>Acceso Rápido</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="/SP%20Perfect%20Color/cliente" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-people-fill fs-4"></i>
                            <span class="small">Clientes</span>
                        </a>
                    </div>
                    <?php if ($_SESSION['usuario_rol'] === 1): ?>
                    <div class="col-6">
                        <a href="/SP%20Perfect%20Color/proveedor" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-truck fs-4"></i>
                            <span class="small">Proveedores</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-6">
                        <a href="/SP%20Perfect%20Color/presupuesto" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-file-earmark-text-fill fs-4"></i>
                            <span class="small">Presupuestos</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-receipt-cutoff fs-4"></i>
                            <span class="small">Notas Entrega</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Ingresos Diarios (Últimos 7 Días)</h5>
            </div>
            <div class="card-body">
                <canvas id="graficoIngresos" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var datos = <?php echo json_encode($ingresosPorDia); ?>;
    if (typeof inicializarGraficoIngresos === 'function') {
        inicializarGraficoIngresos(datos);
    }
});
</script>
