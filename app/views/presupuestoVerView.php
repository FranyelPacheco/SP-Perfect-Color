<!-- Archivo: presupuestoVerView.php -->
<!-- Vista para ver el detalle de un presupuesto -->

<div class="modulo-presupuesto">
    <div class="modulo-header">
        <h2>Presupuesto #<?php echo $presupuesto['id']; ?></h2>
        <a href="../presupuesto" class="btn-secundario">Volver a la lista</a>
    </div>
    
    <div class="modulo-body">
        <!-- Datos del presupuesto -->
        <div class="seccion-formulario">
            <h3>Informacion del Presupuesto</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Fecha:</span>
                    <span class="info-valor"><?php echo $presupuesto['fecha']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cliente:</span>
                    <span class="info-valor"><?php echo $presupuesto['cliente_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cedula:</span>
                    <span class="info-valor"><?php echo $presupuesto['cliente_cedula']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Vendedor:</span>
                    <span class="info-valor"><?php echo $presupuesto['usuario_nombre']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-valor">
                        <span class="estado-<?php echo $presupuesto['estado']; ?>">
                            <?php echo ucfirst($presupuesto['estado']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total:</span>
                    <span class="info-valor" style="font-weight: 600; font-size: 18px;">
                        Bs. <?php echo number_format($presupuesto['total'], 2, ',', '.'); ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($presupuesto['observaciones'])): ?>
            <div class="info-observaciones">
                <span class="info-label">Observaciones:</span>
                <p><?php echo $presupuesto['observaciones']; ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Detalle de items -->
        <div class="seccion-formulario">
            <h3>Items del Presupuesto</h3>
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
                                Bs. <?php echo number_format($presupuesto['total'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Botones de accion -->
        <?php if ($presupuesto['estado'] == 'pendiente'): ?>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button class="btn-peligro" onclick="cambiarEstado(<?php echo $presupuesto['id']; ?>, 'rechazado')">
                Rechazar
            </button>
            <button class="btn-exito" onclick="cambiarEstado(<?php echo $presupuesto['id']; ?>, 'aprobado')">
                Aprobar
            </button>
        </div>
        <?php endif; ?>
        
        <?php if ($presupuesto['estado'] == 'aprobado'): ?>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <a href="../notaEntrega/crearDesdePresupuesto?id=<?php echo $presupuesto['id']; ?>" 
               class="btn-primario" style="padding: 12px 30px; font-size: 16px;">
                Crear Nota de Entrega
            </a>
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

.info-observaciones {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.info-observaciones p {
    margin: 8px 0 0 0;
    color: #555;
}
</style>

<script>
function cambiarEstado(id, estado) {
    var mensaje = estado === 'aprobado' 
        ? 'Esta seguro de aprobar este presupuesto?' 
        : 'Esta seguro de rechazar este presupuesto?';
    
    if (!confirm(mensaje)) {
        return;
    }
    
    var formData = new FormData();
    formData.append('id', id);
    formData.append('estado', estado);
    
    fetch('../presupuesto/cambiarEstado', {
        method: 'POST',
        body: formData
    })
    .then(function(respuesta) { return respuesta.json(); })
    .then(function(resultado) {
        if (resultado.estado === 'exito') {
            location.reload();
        } else {
            alert(resultado.mensaje);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('Error de conexion');
    });
}
</script>