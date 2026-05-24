<!-- Archivo: notaEntregaVerView.php -->
<!-- Vista para ver el detalle de una nota de entrega -->

<div class="modulo-nota-entrega">
    <div class="modulo-header">
        <h2>Nota de Entrega #<?php echo $nota['id']; ?></h2>
        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn-secundario">Volver a la lista</a>
    </div>
    
    <div class="modulo-body">
        <!-- Datos de la nota -->
        <div class="seccion-formulario">
            <h3>Informacion de la Nota de Entrega</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Fecha:</span>
                    <span class="info-valor"><?php echo $nota['fecha']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cliente:</span>
                    <span class="info-valor"><?php echo $nota['cliente_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cedula:</span>
                    <span class="info-valor"><?php echo $nota['cliente_cedula']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Vendedor:</span>
                    <span class="info-valor"><?php echo $nota['usuario_nombre']; ?></span>
                </div>
                <?php if (!empty($nota['cliente_direccion'])): ?>
                <div class="info-item">
                    <span class="info-label">Direccion:</span>
                    <span class="info-valor"><?php echo $nota['cliente_direccion']; ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($nota['cliente_telefono'])): ?>
                <div class="info-item">
                    <span class="info-label">Telefono:</span>
                    <span class="info-valor"><?php echo $nota['cliente_telefono']; ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Total:</span>
                    <span class="info-valor" style="font-weight: 600; font-size: 18px;">
                        Bs. <?php echo number_format($nota['total'], 2, ',', '.'); ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($nota['presupuesto_id'])): ?>
            <div class="info-observaciones">
                <span class="info-label">Presupuesto de origen:</span>
                <p>
                    <a href="/SP%20Perfect%20Color/presupuesto/ver?id=<?php echo $nota['presupuesto_id']; ?>">
                        Ver Presupuesto #<?php echo $nota['presupuesto_id']; ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Detalle de items -->
        <div class="seccion-formulario">
            <h3>Items Entregados</h3>
            <div class="tabla-contenedor">
                <table class="tabla-datos">
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
                            <td>Bs. <?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></td>
                            <td>Bs. <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: 600;">Total:</td>
                            <td style="font-weight: 600;">
                                Bs. <?php echo number_format($nota['total'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
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

.info-observaciones {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.info-observaciones a {
    color: #1a1a2e;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>