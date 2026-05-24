<!-- Archivo: facturaVerView.php -->
<!-- Vista para ver el detalle de una factura -->

<div class="modulo-factura">
    <div class="modulo-header">
        <h2>Factura <?php echo $factura['numero_factura']; ?></h2>
        <a href="/SP%20Perfect%20Color/factura" class="btn-secundario">Volver a la lista</a>
    </div>
    
    <div class="modulo-body">
        <div class="seccion-formulario">
            <h3>Informacion de la Factura</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Numero:</span>
                    <span class="info-valor" style="font-weight: 600;"><?php echo $factura['numero_factura']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha:</span>
                    <span class="info-valor"><?php echo $factura['fecha']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cliente:</span>
                    <span class="info-valor"><?php echo $factura['cliente_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cedula:</span>
                    <span class="info-valor"><?php echo $factura['cliente_cedula']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Vendedor:</span>
                    <span class="info-valor"><?php echo $factura['usuario_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Metodo de Pago:</span>
                    <span class="info-valor"><?php echo $factura['metodo_pago']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-valor">
                        <span class="estado-<?php echo $factura['estado']; ?>">
                            <?php echo ucfirst($factura['estado']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total:</span>
                    <span class="info-valor" style="font-weight: 600; font-size: 18px;">
                        Bs. <?php echo number_format($factura['total'], 2, ',', '.'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Detalle de items -->
        <div class="seccion-formulario">
            <h3>Items Facturados</h3>
            <div class="tabla-contenedor">
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Insumo</th>
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
                            <td><?php echo number_format($item['cantidad'], 2, ',', '.'); ?></td>
                            <td>Bs. <?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></td>
                            <td>Bs. <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: 600;">Total:</td>
                            <td style="font-weight: 600;">
                                Bs. <?php echo number_format($factura['total'], 2, ',', '.'); ?>
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
</style>