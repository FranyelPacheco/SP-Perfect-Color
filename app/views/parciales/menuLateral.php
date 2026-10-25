<?php
// Menú lateral compartido por el offcanvas móvil y el sidebar de escritorio
// Requiere $ctrlLower, $isVentasActive, $isComprasActive, $isConfigPagoActive y opcionalmente $claseMenu
?>
<ul class="nav flex-column <?php echo $claseMenu ?? ''; ?>">
<li class="nav-item"><a href="/SP%20Perfect%20Color/dashboard" class="nav-link<?php echo ($ctrlLower === 'dashboard') ? ' active' : ''; ?>"><i class="bi bi-grid-fill me-2"></i><span>Inicio</span></a></li>

<?php if (\App\Helpers\tienePermiso('cliente')): ?>
<li class="nav-item"><a href="/SP%20Perfect%20Color/cliente" class="nav-link<?php echo ($ctrlLower === 'cliente') ? ' active' : ''; ?>"><i class="bi bi-people-fill me-2"></i><span>Clientes</span></a></li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('proveedor')): ?>
<li class="nav-item"><a href="/SP%20Perfect%20Color/proveedor" class="nav-link<?php echo ($ctrlLower === 'proveedor') ? ' active' : ''; ?>"><i class="bi bi-truck-front-fill me-2"></i><span>Proveedores</span></a></li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('inventario')): ?>
<li class="nav-item"><a href="/SP%20Perfect%20Color/inventario" class="nav-link<?php echo ($ctrlLower === 'inventario') ? ' active' : ''; ?>"><i class="bi bi-box-seam-fill me-2"></i><span>Gestión de Inventario</span></a></li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('presupuesto') || \App\Helpers\tienePermiso('notaEntrega') || \App\Helpers\tienePermiso('cuentaCobrar')): ?>
<li class="nav-item dropdown-hover">
    <a href="#" aria-haspopup="true" aria-expanded="false" class="nav-link<?php echo $isVentasActive ? ' active' : ''; ?>"><i class="bi bi-cart-check-fill me-2"></i><span>Ventas</span> <i class="bi bi-chevron-down ms-auto"></i></a>
    <ul class="dropdown-submenu">
        <?php if (\App\Helpers\tienePermiso('presupuesto')): ?>
        <li><a href="/SP%20Perfect%20Color/presupuesto" class="<?php echo ($ctrlLower === 'presupuesto') ? 'active' : ''; ?>"><i class="bi bi-file-earmark-text-fill me-2"></i><span>Presupuestos</span></a></li>
        <?php endif; ?>
        <?php if (\App\Helpers\tienePermiso('notaEntrega')): ?>
        <li><a href="/SP%20Perfect%20Color/notaEntrega" class="<?php echo ($ctrlLower === 'notaentrega') ? 'active' : ''; ?>"><i class="bi bi-receipt-cutoff me-2"></i><span>Notas de Entrega</span></a></li>
        <?php endif; ?>
        <?php if (\App\Helpers\tienePermiso('cuentaCobrar')): ?>
        <li><a href="/SP%20Perfect%20Color/cuentaCobrar" class="<?php echo ($ctrlLower === 'cuentacobrar') ? 'active' : ''; ?>"><i class="bi bi-cash-stack me-2"></i><span>Cuentas por Cobrar</span></a></li>
        <?php endif; ?>
    </ul>
</li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('notaEntregaCompra') || \App\Helpers\tienePermiso('cuentaPagar')): ?>
<li class="nav-item dropdown-hover">
    <a href="#" aria-haspopup="true" aria-expanded="false" class="nav-link<?php echo $isComprasActive ? ' active' : ''; ?>"><i class="bi bi-cart-plus-fill me-2"></i><span>Compras</span> <i class="bi bi-chevron-down ms-auto"></i></a>
    <ul class="dropdown-submenu">
        <?php if (\App\Helpers\tienePermiso('notaEntregaCompra')): ?>
        <li><a href="/SP%20Perfect%20Color/notaEntregaCompra" class="<?php echo ($ctrlLower === 'notaentregacompra') ? 'active' : ''; ?>"><i class="bi bi-inboxes-fill me-2"></i><span>Notas de Entrega</span></a></li>
        <?php endif; ?>
        <?php if (\App\Helpers\tienePermiso('cuentaPagar')): ?>
        <li><a href="/SP%20Perfect%20Color/cuentaPagar" class="<?php echo ($ctrlLower === 'cuentapagar') ? 'active' : ''; ?>"><i class="bi bi-credit-card-2-back-fill me-2"></i><span>Cuentas por Pagar</span></a></li>
        <?php endif; ?>
    </ul>
</li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('banco') || \App\Helpers\tienePermiso('tipoPago')): ?>
<li class="nav-item dropdown-hover">
    <a href="#" aria-haspopup="true" aria-expanded="false" class="nav-link<?php echo $isConfigPagoActive ? ' active' : ''; ?>"><i class="bi bi-gear-fill me-2"></i><span>Config. de Pago</span> <i class="bi bi-chevron-down ms-auto"></i></a>
    <ul class="dropdown-submenu">
        <?php if (\App\Helpers\tienePermiso('banco')): ?>
        <li><a href="/SP%20Perfect%20Color/banco" class="<?php echo ($ctrlLower === 'banco') ? 'active' : ''; ?>"><i class="bi bi-building-fill me-2"></i><span>Bancos</span></a></li>
        <?php endif; ?>
        <?php if (\App\Helpers\tienePermiso('tipoPago')): ?>
        <li><a href="/SP%20Perfect%20Color/tipoPago" class="<?php echo ($ctrlLower === 'tipopago') ? 'active' : ''; ?>"><i class="bi bi-credit-card-2-front-fill me-2"></i><span>Tipos de Pago</span></a></li>
        <?php endif; ?>
    </ul>
</li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('tasaCambio')): ?>
<li class="nav-item"><a href="/SP%20Perfect%20Color/tasaCambio" class="nav-link<?php echo ($ctrlLower === 'tasacambio') ? ' active' : ''; ?>"><i class="bi bi-currency-exchange me-2"></i><span>Tasa de Cambio</span></a></li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('reporte')): ?>
<li class="nav-item"><a href="/SP%20Perfect%20Color/reporte" class="nav-link<?php echo ($ctrlLower === 'reporte') ? ' active' : ''; ?>"><i class="bi bi-bar-chart-fill me-2"></i><span>Reportes</span></a></li>
<?php endif; ?>

<?php if (\App\Helpers\tienePermiso('usuario')): ?>
<li class="nav-item"><a href="/SP%20Perfect%20Color/usuario" class="nav-link<?php echo ($ctrlLower === 'usuario') ? ' active' : ''; ?>"><i class="bi bi-person-gear me-2"></i><span>Usuarios y Roles</span></a></li>
<?php endif; ?>
            </ul>
