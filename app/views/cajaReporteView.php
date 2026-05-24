<!-- Archivo: cajaReporteView.php -->
<!-- Vista para el reporte de cierre de caja -->

<div class="modulo-caja">
    <div class="modulo-header">
        <h2>Reporte de Caja #<?php echo $caja['id']; ?></h2>
        <a href="/SP%20Perfect%20Color/caja" class="btn-secundario">Volver</a>
    </div>
    
    <div class="modulo-body">
        <!-- Datos de la caja -->
        <div class="seccion-formulario">
            <h3>Informacion de la Caja</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Usuario:</span>
                    <span class="info-valor"><?php echo $caja['usuario_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha Apertura:</span>
                    <span class="info-valor"><?php echo $caja['fecha_apertura']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha Cierre:</span>
                    <span class="info-valor"><?php echo $caja['fecha_cierre'] ?? 'En curso'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Monto Inicial:</span>
                    <span class="info-valor">Bs. <?php echo number_format($caja['monto_inicial'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Monto Final:</span>
                    <span class="info-valor" style="font-weight: 600; font-size: 18px;">
                        Bs. <?php echo number_format($caja['monto_final'] ?? 0, 2, ',', '.'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Facturas:</span>
                    <span class="info-valor"><?php echo $resumen['cantidad_facturas']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Resumen de ventas -->
        <div class="seccion-formulario">
            <h3>Resumen de Ventas por Metodo de Pago</h3>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <div class="resumen-valor">Bs. <?php echo number_format($resumen['efectivo'], 2, ',', '.'); ?></div>
                    <div class="resumen-label">Efectivo</div>
                </div>
                <div class="resumen-item">
                    <div class="resumen-valor">Bs. <?php echo number_format($resumen['punto_venta'], 2, ',', '.'); ?></div>
                    <div class="resumen-label">Punto de Venta</div>
                </div>
                <div class="resumen-item">
                    <div class="resumen-valor">Bs. <?php echo number_format($resumen['pago_movil'], 2, ',', '.'); ?></div>
                    <div class="resumen-label">Pago Movil</div>
                </div>
                <div class="resumen-item">
                    <div class="resumen-valor">Bs. <?php echo number_format($resumen['credito'], 2, ',', '.'); ?></div>
                    <div class="resumen-label">Credito</div>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 6px; text-align: center;">
                <span style="font-size: 14px; color: #666;">Total General:</span>
                <span style="font-size: 24px; font-weight: 600; color: #1a1a2e; margin-left: 10px;">
                    Bs. <?php echo number_format($resumen['total_general'], 2, ',', '.'); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
.seccion-formulario {
    background: #fff;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.seccion-formulario h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #1a1a2e;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.info-item {
    padding: 5px 0;
}

.info-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.info-valor {
    font-size: 14px;
    color: #333;
}

.resumen-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.resumen-item {
    text-align: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
}

.resumen-item .resumen-valor {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a2e;
}

.resumen-item .resumen-label {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .resumen-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>