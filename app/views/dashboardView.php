<?php
// VISTA: dashboardView.php
// OBJETIVO: Panel principal con estadísticas, alertas de stock, acceso rápido y gráfica de ingresos
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0 toolbar-title">Panel Principal</h2>
    <span class="text-muted small">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span>
</div>

<?php if (!empty($alertaTasaPendiente)): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-left: 5px solid #F59E0B !important;">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning text-dark flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.3rem;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <h6 class="mb-1 text-dark fw-bold">¡Atención! La tasa de cambio del día no ha sido actualizada</h6>
            <p class="mb-0 text-secondary small">
                No se ha registrado la tasa oficial para hoy (<strong><?php echo date('d/m/Y'); ?></strong>).
                <?php if (!empty($ultimaTasa)): ?>
                Última tasa registrada: <strong>Bs. <?php echo number_format((float)$ultimaTasa['tasa'], 2, ',', '.'); ?></strong> (del <?php echo date('d/m/Y', strtotime($ultimaTasa['fecha'])); ?>).
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (\App\Helpers\tienePermiso('tasaCambio')): ?>
        <a href="/SP%20Perfect%20Color/tasaCambio" class="btn btn-warning btn-sm fw-bold px-3 shadow-sm text-dark"><i class="bi bi-currency-exchange me-1"></i>Actualizar Tasa Ahora</a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); border-left: 5px solid #10B981 !important;">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.2rem;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
            <h6 class="mb-0 text-dark fw-bold">Tasa del día actualizada: <span class="badge bg-success fs-6 ms-1">Bs. <?php echo number_format((float)$tasaHoy['tasa'], 2, ',', '.'); ?> / $</span></h6>
            <small class="text-muted">Vigente para hoy <?php echo date('d/m/Y'); ?>. Cobros y conversiones al día.</small>
        </div>
    </div>
    <?php if (\App\Helpers\tienePermiso('tasaCambio')): ?>
    <a href="/SP%20Perfect%20Color/tasaCambio" class="btn btn-outline-success btn-sm fw-semibold"><i class="bi bi-pencil-square me-1"></i>Gestionar Tasas</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($_SESSION['usuario_rol'] === 1): ?>
<div class="entrada-animada row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-label">Clientes</div>
            <div class="stat-value" data-valor="<?php echo $totalClientes; ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="bi bi-truck"></i></div>
            <div class="stat-label">Proveedores</div>
            <div class="stat-value" data-valor="<?php echo $totalProveedores; ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-label">Productos</div>
            <div class="stat-value" data-valor="<?php echo $totalInsumos; ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-orange">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-label">Alertas Stock</div>
            <div class="stat-value" data-valor="<?php echo count($alertasStock); ?>">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">Ingresos Hoy</div>
            <div class="stat-value" data-valor="<?php echo $pagosRecibidosHoy; ?>" data-moneda="1">$ 0,00</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-red">
            <div class="stat-icon"><i class="bi bi-credit-card-2-back-fill"></i></div>
            <div class="stat-label">Egresos Hoy</div>
            <div class="stat-value" data-valor="<?php echo $pagosRealizadosHoy; ?>" data-moneda="1">$ 0,00</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card stat-info stat-ventas-mes">
            <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            <div class="stat-label">Ventas Mes</div>
            <div class="stat-value" data-valor="<?php echo $ventasMes; ?>" data-moneda="1">$ 0,00</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($_SESSION['usuario_rol'] === 1): ?>
<?php
$maxVendido = !empty($topProductos) ? max(array_map(fn($p) => (float)$p['total_vendido'], $topProductos)) : 0;
$iniciales = '';
if ($clienteTopMes && (float)$clienteTopMes['total_comprado'] > 0) {
    $iniciales = mb_strtoupper(mb_substr($clienteTopMes['nombres'], 0, 1) . mb_substr($clienteTopMes['apellidos'], 0, 1));
}
?>
<div class="entrada-animada row g-3">
    <div class="col-lg-5">
        <div class="dash-card h-100">
            <div class="dash-card-head">
                <span class="dash-dot dash-dot-red"></span>
                <div>
                    <div class="dash-title">Stock por reponer</div>
                    <div class="dash-sub">Productos bajo su mínimo</div>
                </div>
                <?php if (!empty($alertasStock)): ?><span class="dash-badge dash-badge-red"><?php echo count($alertasStock); ?></span><?php endif; ?>
            </div>
            <?php if (empty($alertasStock)): ?>
            <div class="dash-empty">
                <i class="bi bi-check2-circle"></i>
                <span>Todo el inventario está por encima del mínimo.</span>
            </div>
            <?php else: ?>
            <ul class="dash-list">
                <?php foreach ($alertasStock as $alerta):
                    $actual = (float)$alerta['stock_actual'];
                    $minimo = (float)$alerta['stock_minimo'];
                    $pct = $minimo > 0 ? max(4, min(100, round($actual / $minimo * 100))) : 4;
                    $critico = $minimo > 0 && $actual / $minimo <= 0.5;
                ?>
                <li class="dash-row">
                    <div class="dash-row-main">
                        <div class="dash-row-name"><?php echo htmlspecialchars($alerta['nombre']); ?></div>
                        <div class="dash-row-meta"><span class="dash-chip"><?php echo htmlspecialchars($alerta['codigo']); ?></span></div>
                        <div class="dash-bar"><span class="<?php echo $critico ? 'dash-bar-red' : 'dash-bar-amber'; ?>" style="width: <?php echo $pct; ?>%"></span></div>
                    </div>
                    <div class="dash-row-value">
                        <strong class="<?php echo $critico ? 'text-danger' : 'text-warning'; ?>"><?php echo number_format($actual, 0, ',', '.'); ?></strong>
                        <span>de <?php echo number_format($minimo, 0, ',', '.'); ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card-head">
                <span class="dash-dot dash-dot-blue"></span>
                <div>
                    <div class="dash-title">Más vendidos hoy</div>
                    <div class="dash-sub">Top 5 por unidades</div>
                </div>
            </div>
            <?php if (empty($topProductos)): ?>
            <div class="dash-empty">
                <i class="bi bi-palette2"></i>
                <span>Aún no hay ventas registradas hoy.</span>
            </div>
            <?php else: ?>
            <ul class="dash-list">
                <?php $i = 1; foreach ($topProductos as $prod):
                    $vendido = (float)$prod['total_vendido'];
                    $pct = $maxVendido > 0 ? max(6, round($vendido / $maxVendido * 100)) : 6;
                ?>
                <li class="dash-row">
                    <span class="dash-rank dash-rank-<?php echo $i <= 3 ? $i : 'n'; ?>"><?php echo $i; ?></span>
                    <div class="dash-row-main">
                        <div class="dash-row-name"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                        <div class="dash-row-meta"><span class="dash-chip"><?php echo htmlspecialchars($prod['codigo']); ?></span></div>
                        <div class="dash-bar"><span class="dash-bar-blue" style="width: <?php echo $pct; ?>%"></span></div>
                    </div>
                    <div class="dash-row-value"><strong><?php echo number_format($vendido, 0, ',', '.'); ?></strong><span>uds.</span></div>
                </li>
                <?php $i++; endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="dash-hero h-100">
            <div class="dash-hero-top">
                <span class="dash-hero-label"><i class="bi bi-stars me-1"></i>Cliente del mes</span>
            </div>
            <?php if ($clienteTopMes && (float)$clienteTopMes['total_comprado'] > 0): ?>
            <div class="dash-hero-body">
                <div class="dash-avatar"><?php echo htmlspecialchars($iniciales); ?></div>
                <div class="dash-hero-name"><?php echo htmlspecialchars($clienteTopMes['nombres'] . ' ' . $clienteTopMes['apellidos']); ?></div>
                <div class="dash-hero-caption">Total comprado este mes</div>
                <div class="dash-hero-amount">$ <?php echo number_format((float)$clienteTopMes['total_comprado'], 2, ',', '.'); ?></div>
            </div>
            <?php else: ?>
            <div class="dash-hero-body">
                <div class="dash-avatar dash-avatar-empty"><i class="bi bi-person"></i></div>
                <div class="dash-hero-caption">Aún no hay compras este mes</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($_SESSION['usuario_rol'] === 1): ?>
<div class="entrada-animada row g-3 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Ingresos de los últimos 7 días</h5>
            </div>
            <div class="card-body">
                <div id="graficoIngresos" class="grafico-barras" aria-label="Ingresos de los últimos 7 días"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var datos = <?php echo json_encode($ingresosPorDia); ?>;
    if (typeof inicializarGraficoIngresos === 'function') {
        inicializarGraficoIngresos(datos);
    }
});
</script>
<?php endif; ?>
