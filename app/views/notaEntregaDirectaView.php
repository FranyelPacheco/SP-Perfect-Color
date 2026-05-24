<!-- Archivo: notaEntregaDirectaView.php -->
<!-- Vista para crear nota de entrega directa (sin presupuesto) -->

<div class="modulo-nota-entrega">
    <div class="modulo-header">
        <h2>Nueva Nota de Entrega</h2>
        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn-secundario">Volver a la lista</a>
    </div>
    
    <div class="modulo-body">
        <form id="formularioNotaEntrega">
            <input type="hidden" id="presupuestoId" name="presupuesto_id" value="">
            
            <!-- Seleccion de cliente -->
            <div class="seccion-formulario">
                <h3>Datos del Cliente</h3>
                <div class="grupo-formulario">
                    <label for="clienteNota">Cliente</label>
                    <select id="clienteNota" name="cliente_id" required>
                        <option value="">Cargando clientes...</option>
                    </select>
                </div>
            </div>
            
            <!-- Busqueda y seleccion de insumos -->
            <div class="seccion-formulario">
                <h3>Agregar Insumos</h3>
                <div class="grupo-formulario">
                    <label for="busquedaInsumoNota">Buscar Insumo</label>
                    <input type="text" id="busquedaInsumoNota" 
                           placeholder="Buscar por nombre o codigo...">
                </div>
                
                <div id="listaInsumosDisponibles" class="lista-insumos">
                    <div class="insumo-item" style="justify-content: center; color: #999;">
                        Cargando insumos disponibles...
                    </div>
                </div>
            </div>
            
            <!-- Tabla de items agregados -->
            <div class="seccion-formulario">
                <h3>Items a Entregar</h3>
                <div class="tabla-contenedor">
                    <table id="tablaItemsNota" class="tabla-datos">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Insumo</th>
                                <th>Stock</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaItems">
                            <tr id="filaVacia">
                                <td colspan="7" style="text-align: center;">No hay items agregados</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align: right; font-weight: 600;">Total:</td>
                                <td id="totalNota" style="font-weight: 600;">Bs. 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div id="mensajeErrorNota" class="mensaje-error" style="display: none;"></div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <a href="/SP%20Perfect%20Color/notaEntrega" class="btn-secundario">Cancelar</a>
                <button type="submit" class="btn-primario" style="padding: 12px 30px; font-size: 16px;">
                    Crear Nota de Entrega
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

<script src="/SP%20Perfect%20Color/assets/js/notaEntregaForm.js"></script>