<!-- Archivo: cuentaPagarVerView.php -->
<!-- Vista para ver el detalle de una cuenta por pagar -->

<div class="modulo-cuenta-pagar">
    <div class="modulo-header">
        <h2>Cuenta por Pagar #<?php echo $cuenta['id']; ?></h2>
        <a href="/SP%20Perfect%20Color/cuentaPagar" class="btn-secundario">Volver a la lista</a>
    </div>
    
    <div class="modulo-body">
        <div class="seccion-formulario">
            <h3>Informacion de la Cuenta</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Proveedor:</span>
                    <span class="info-valor"><?php echo $cuenta['proveedor_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">RIF:</span>
                    <span class="info-valor"><?php echo $cuenta['proveedor_rif']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Telefono:</span>
                    <span class="info-valor"><?php echo $cuenta['proveedor_telefono'] ?? '-'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Monto Total:</span>
                    <span class="info-valor">Bs. <?php echo number_format($cuenta['monto_total'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Saldo Pendiente:</span>
                    <span class="info-valor" style="font-weight: 600; font-size: 18px; color: <?php echo $cuenta['saldo_pendiente'] > 0 ? '#c62828' : '#2e7d32'; ?>;">
                        Bs. <?php echo number_format($cuenta['saldo_pendiente'], 2, ',', '.'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Vencimiento:</span>
                    <span class="info-valor"><?php echo $cuenta['fecha_vencimiento'] ?? '-'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Pagos realizados -->
        <div class="seccion-formulario">
            <h3>Pagos Realizados</h3>
            <div class="tabla-contenedor">
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Metodo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td><?php echo $pago['fecha']; ?></td>
                            <td>Bs. <?php echo number_format($pago['monto'], 2, ',', '.'); ?></td>
                            <td><?php echo $pago['metodo_pago']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pagos)): ?>
                        <tr><td colspan="3" style="text-align: center;">No hay pagos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Registrar pago (solo admin) -->
        <?php if ($cuenta['saldo_pendiente'] > 0 && $_SESSION['usuario_rol'] == 1): ?>
        <div class="seccion-formulario">
            <h3>Registrar Pago</h3>
            <form id="formularioPago">
                <input type="hidden" id="cuentaId" value="<?php echo $cuenta['id']; ?>">
                
                <div style="display: flex; gap: 15px; align-items: flex-end;">
                    <div class="grupo-formulario" style="flex: 1;">
                        <label for="montoPago">Monto (Bs.)</label>
                        <input type="number" id="montoPago" name="monto" step="0.01" min="0.01" 
                               max="<?php echo $cuenta['saldo_pendiente']; ?>" 
                               value="<?php echo $cuenta['saldo_pendiente']; ?>" required>
                    </div>
                    
                    <div class="grupo-formulario" style="flex: 1;">
                        <label for="metodoPagoCuenta">Metodo de Pago</label>
                        <select id="metodoPagoCuenta" name="metodo_pago">
                            <option value="Transferencia">Transferencia</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Pago Movil">Pago Movil</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario">
                        <button type="submit" class="btn-exito" style="padding: 10px 24px; white-space: nowrap;">
                            Registrar Pago
                        </button>
                    </div>
                </div>
                
                <div id="mensajeErrorPago" class="mensaje-error" style="display: none; margin-top: 10px;"></div>
            </form>
        </div>
        <?php endif; ?>
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

<script>
document.getElementById('formularioPago').addEventListener('submit', function(evento) {
    evento.preventDefault();
    
    var cuentaId = document.getElementById('cuentaId').value;
    var monto = document.getElementById('montoPago').value;
    var metodoPago = document.getElementById('metodoPagoCuenta').value;
    var mensajeError = document.getElementById('mensajeErrorPago');
    
    if (!monto || parseFloat(monto) <= 0) {
        mensajeError.textContent = 'Ingrese un monto valido';
        mensajeError.style.display = 'block';
        return;
    }
    
    var formData = new FormData();
    formData.append('cuenta_id', cuentaId);
    formData.append('monto', monto);
    formData.append('metodo_pago', metodoPago);
    
    fetch('/SP%20Perfect%20Color/cuentaPagar/registrarPago', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(resultado) {
        if (resultado.estado === 'exito') {
            location.reload();
        } else {
            mensajeError.textContent = resultado.mensaje;
            mensajeError.style.display = 'block';
        }
    });
});
</script>