<!-- Archivo: cajaView.php -->
<!-- Vista para la gestion de caja -->

<div class="modulo-caja">
    <div class="modulo-header">
        <h2>Gestion de Caja</h2>
    </div>
    
    <div class="modulo-body">
        <!-- Estado actual de la caja -->
        <div class="seccion-formulario" id="seccionEstadoCaja">
            <h3>Estado de Caja</h3>
            <div id="contenidoEstadoCaja">
                <p>Cargando estado de caja...</p>
            </div>
        </div>
        
        <!-- Botones de accion -->
        <div id="accionesCaja" style="display: flex; gap: 10px; margin-bottom: 20px;">
            <!-- Se llena dinamicamente -->
        </div>
        
        <!-- Historial de cajas -->
        <div class="seccion-formulario">
            <h3>Historial de Cajas</h3>
            <div class="tabla-contenedor">
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th>Usuario</th>
                            <th>Monto Inicial</th>
                            <th>Monto Final</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cajas as $caja): ?>
                        <tr>
                            <td>#<?php echo $caja['id']; ?></td>
                            <td><?php echo $caja['fecha_apertura']; ?></td>
                            <td><?php echo $caja['fecha_cierre'] ?? 'En curso'; ?></td>
                            <td><?php echo $caja['usuario_nombre']; ?></td>
                            <td>Bs. <?php echo number_format($caja['monto_inicial'], 2, ',', '.'); ?></td>
                            <td><?php echo $caja['monto_final'] ? 'Bs. ' . number_format($caja['monto_final'], 2, ',', '.') : '-'; ?></td>
                            <td>
                                <span class="estado-caja-<?php echo $caja['estado']; ?>">
                                    <?php echo $caja['estado'] == 'abierta' ? 'Abierta' : 'Cerrada'; ?>
                                </span>
                            </td>
                            <td class="acciones">
                                <?php if ($caja['estado'] == 'cerrada'): ?>
                                <a href="/SP%20Perfect%20Color/caja/reporte?id=<?php echo $caja['id']; ?>" class="btn-primario">Ver Reporte</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cajas)): ?>
                        <tr><td colspan="8" style="text-align: center;">No hay cajas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.estado-caja-abierta {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-caja-cerrada {
    background: #e3f2fd;
    color: #1565c0;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

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
    font-size: 20px;
    font-weight: 600;
    color: #1a1a2e;
}

.resumen-item .resumen-label {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .resumen-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/caja.js"></script>