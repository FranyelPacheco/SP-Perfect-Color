<!-- Archivo: facturaFormView.php -->
<!-- Vista para crear una nueva factura -->

<div class="modulo-factura">
    <div class="modulo-header">
        <h2>Nueva Factura</h2>
        <a href="/SP%20Perfect%20Color/factura" class="btn-secundario">Volver a la lista</a>
    </div>
    
    <div class="modulo-body">
        <form id="formularioFactura">
            <input type="hidden" id="notaEntregaId" name="nota_entrega_id" value="<?php echo isset($nota) ? $nota['id'] : ''; ?>">
            
            <!-- Seleccion de cliente -->
            <div class="seccion-formulario">
                <h3>Datos del Cliente</h3>
                <div class="grupo-formulario">
                    <label for="clienteFactura">Cliente</label>
                    <select id="clienteFactura" name="cliente_id" required>
                        <option value="">Cargando clientes...</option>
                    </select>
                </div>
            </div>
            
            <!-- Metodo de pago -->
            <div class="seccion-formulario">
                <h3>Metodo de Pago</h3>
                <div class="grupo-formulario">
                    <label for="metodoPago">Seleccione el metodo de pago</label>
                    <select id="metodoPago" name="metodo_pago" required>
                        <option value="">Seleccione...</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Punto de Venta">Punto de Venta</option>
                        <option value="Pago Movil">Pago Movil</option>
                        <option value="Credito">Credito</option>
                    </select>
                </div>
            </div>
            
            <!-- Items de la factura -->
            <div class="seccion-formulario">
                <h3>Items a Facturar</h3>
                
                <?php if (isset($nota)): ?>
                <!-- Si viene de una nota de entrega, mostrar items precargados -->
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
                        <tbody id="cuerpoTablaItems">
                            <?php 
                            $detalleNota = $this->notaEntregaModel->obtenerDetalle($nota['id']);
                            $totalPrecargado = 0;
                            foreach ($detalleNota as $item): 
                                $totalPrecargado += $item['subtotal'];
                            ?>
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
                                <td id="totalFactura" style="font-weight: 600;">Bs. <?php echo number_format($totalPrecargado, 2, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <!-- Si es factura directa, mostrar buscador de insumos -->
                <div class="grupo-formulario">
                    <label for="busquedaInsumoFactura">Buscar Insumo</label>
                    <input type="text" id="busquedaInsumoFactura" 
                           placeholder="Buscar por nombre o codigo...">
                </div>
                
                <div id="listaInsumosDisponibles" class="lista-insumos">
                    <div class="insumo-item" style="justify-content: center; color: #999;">
                        Cargando insumos disponibles...
                    </div>
                </div>
                
                <div class="tabla-contenedor" style="margin-top: 15px;">
                    <table id="tablaItemsFactura" class="tabla-datos">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaItems">
                            <tr id="filaVacia">
                                <td colspan="6" style="text-align: center;">No hay items agregados</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right; font-weight: 600;">Total:</td>
                                <td id="totalFactura" style="font-weight: 600;">Bs. 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <div id="mensajeErrorFactura" class="mensaje-error" style="display: none;"></div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <a href="/SP%20Perfect%20Color/factura" class="btn-secundario">Cancelar</a>
                <button type="submit" class="btn-primario" style="padding: 12px 30px; font-size: 16px;">
                    Crear Factura
                </button>
            </div>
        </form>
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

.seccion-formulario .grupo-formulario {
    margin-bottom: 15px;
}

.seccion-formulario .grupo-formulario label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.seccion-formulario .grupo-formulario select,
.seccion-formulario .grupo-formulario input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: 'Segoe UI', Arial, sans-serif;
    transition: border-color 0.3s;
}

.seccion-formulario .grupo-formulario select:focus,
.seccion-formulario .grupo-formulario input:focus {
    border-color: #1a1a2e;
    outline: none;
}

.lista-insumos {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 10px;
}

.insumo-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
}

.insumo-item:hover {
    background: #f5f5f5;
}

.insumo-info {
    flex: 1;
}

.insumo-nombre {
    font-weight: 600;
    display: block;
    font-size: 13px;
}

.insumo-detalle {
    font-size: 11px;
    color: #666;
    margin-top: 2px;
}

.insumo-precio {
    font-weight: 600;
    color: #2e7d32;
    margin-right: 15px;
    font-size: 13px;
    white-space: nowrap;
}

.cantidad-input {
    width: 70px;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 13px;
}

.precio-input {
    width: 90px;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: right;
    font-size: 13px;
}
</style>

<?php if (!isset($nota)): ?>
<script src="/SP%20Perfect%20Color/assets/js/facturaForm.js"></script>
<?php else: ?>
<script>
// Guardar factura desde nota de entrega
document.getElementById('formularioFactura').addEventListener('submit', function(evento) {
    evento.preventDefault();
    
    var clienteId = document.getElementById('clienteFactura').value;
    var metodoPago = document.getElementById('metodoPago').value;
    var notaEntregaId = document.getElementById('notaEntregaId').value;
    var mensajeError = document.getElementById('mensajeErrorFactura');
    
    if (!clienteId) {
        mensajeError.textContent = 'Debe seleccionar un cliente';
        mensajeError.style.display = 'block';
        return;
    }
    
    if (!metodoPago) {
        mensajeError.textContent = 'Debe seleccionar un metodo de pago';
        mensajeError.style.display = 'block';
        return;
    }
    
    // Cargar items desde la nota de entrega
    fetch('/SP%20Perfect%20Color/notaEntrega/obtenerDetalleAjax?id=' + notaEntregaId)
        .then(function(r) { return r.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                var items = resultado.datos.detalle.map(function(item) {
                    return {
                        insumo_id: item.insumo_id,
                        cantidad: item.cantidad,
                        precio_unitario: item.precio_unitario
                    };
                });
                
                var formData = new FormData();
                formData.append('cliente_id', clienteId);
                formData.append('metodo_pago', metodoPago);
                formData.append('nota_entrega_id', notaEntregaId);
                formData.append('items', JSON.stringify(items));
                
                fetch('/SP%20Perfect%20Color/factura/guardar', {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.estado === 'exito') {
                        mostrarNotificacion('Factura ' + res.datos.numero_factura + ' creada exitosamente', 'exito');
                        setTimeout(function() {
                            window.location.href = '/SP%20Perfect%20Color/factura';
                        }, 2000);
                    } else {
                        mensajeError.textContent = res.mensaje;
                        mensajeError.style.display = 'block';
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
        });
});

// Cargar clientes
fetch('/SP%20Perfect%20Color/factura/obtenerClientesAjax')
    .then(function(r) { return r.json(); })
    .then(function(resultado) {
        if (resultado.estado === 'exito') {
            var select = document.getElementById('clienteFactura');
            select.innerHTML = '<option value="">Seleccione un cliente...</option>';
            resultado.datos.clientes.forEach(function(cliente) {
                var opcion = document.createElement('option');
                opcion.value = cliente.id;
                opcion.textContent = cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos;
                // Si viene de nota, seleccionar el cliente de la nota
                if (cliente.id == <?php echo isset($nota) ? $nota['cliente_id'] : 0; ?>) {
                    opcion.selected = true;
                }
                select.appendChild(opcion);
            });
        }
    });
</script>
<?php endif; ?>