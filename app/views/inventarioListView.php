<!-- Archivo: inventarioListView.php -->
<!-- Vista para la gestion de inventario -->

<div class="modulo-inventario">
    <div class="modulo-header">
        <h2>Gestion de Inventario</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaInsumos" placeholder="Buscar por codigo, nombre o categoria..." class="input-busqueda">
            <?php if ($_SESSION['usuario_rol'] == 1): ?>
            <button id="btnNuevoInsumo" class="btn-primario">Nuevo Insumo</button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Alertas de stock bajo -->
    <div id="alertasStockBajo" class="alertas-stock" style="display: none;">
        <h3>Alertas de Stock Bajo</h3>
        <div id="contenidoAlertas"></div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaInsumos" class="tabla-datos">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Categoria</th>
                        <th>Stock</th>
                        <th>P. Venta</th>
                        <th>Proveedor</th>
                        <?php if ($_SESSION['usuario_rol'] == 1): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaInsumos">
                    <!-- Se llena dinamicamente con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar insumo -->
<?php if ($_SESSION['usuario_rol'] == 1): ?>
<div id="modalInsumo" class="modal" style="display: none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3 id="tituloModalInsumo">Nuevo Insumo</h3>
            <button type="button" id="btnCerrarModalInsumo" class="btn-cerrar-modal">&times;</button>
        </div>
        <form id="formularioInsumo">
            <input type="hidden" id="insumoId" name="id" value="">
            
            <div class="fila-formulario">
                <div class="grupo-formulario mitad">
                    <label for="codigoInsumo">Codigo</label>
                    <input type="text" id="codigoInsumo" name="codigo" required 
                           placeholder="Codigo unico del insumo">
                </div>
                
                <div class="grupo-formulario mitad">
                    <label for="categoriaInsumo">Categoria</label>
                    <select id="categoriaInsumo" name="categoria">
                        <option value="">Seleccione una categoria</option>
                        <option value="Tintes">Tintes</option>
                        <option value="Quimicos">Quimicos</option>
                        <option value="Pintura Automotriz">Pintura Automotriz</option>
                        <option value="Brillo Directo">Brillo Directo</option>
                        <option value="Acrilico">Acrilico</option>
                        <option value="Fondos Anticorrosivos">Fondos Anticorrosivos</option>
                        <option value="Esmalte">Esmalte</option>
                        <option value="Sintetico">Sintetico</option>
                        <option value="Transparentes">Transparentes</option>
                        <option value="Masillas">Masillas</option>
                        <option value="Lijas">Lijas</option>
                        <option value="Thiner">Thiner</option>
                        <option value="Disolvente">Disolvente</option>
                        <option value="Spray">Spray</option>
                        <option value="Pulitura">Pulitura</option>
                        <option value="Mopas">Mopas</option>
                        <option value="Brochas">Brochas</option>
                        <option value="Herramientas">Herramientas</option>
                        <option value="Ferreteria">Ferreteria</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
            </div>
            
            <div class="grupo-formulario">
                <label for="nombreInsumo">Nombre del Insumo</label>
                <input type="text" id="nombreInsumo" name="nombre" required 
                       placeholder="Ingrese el nombre del insumo">
            </div>
            
            <div class="fila-formulario">
                <div class="grupo-formulario mitad">
                    <label for="marcaInsumo">Marca</label>
                    <input type="text" id="marcaInsumo" name="marca" 
                           placeholder="Ingrese la marca">
                </div>
                
                <div class="grupo-formulario mitad">
                    <label for="unidadMedidaInsumo">Unidad de Medida</label>
                    <select id="unidadMedidaInsumo" name="unidad_medida">
                        <option value="">Seleccione...</option>
                        <option value="Unidad">Unidad</option>
                        <option value="Litro">Litro</option>
                        <option value="Galon">Galon</option>
                        <option value="Kilogramo">Kilogramo</option>
                        <option value="Gramo">Gramo</option>
                        <option value="Metro">Metro</option>
                        <option value="Caja">Caja</option>
                        <option value="Paquete">Paquete</option>
                    </select>
                </div>
            </div>
            
            <div class="fila-formulario">
                <div class="grupo-formulario tercera">
                    <label for="stockActualInsumo">Stock Actual</label>
                    <input type="number" id="stockActualInsumo" name="stock_actual" 
                           step="0.01" min="0" value="0">
                </div>
                
                <div class="grupo-formulario tercera">
                    <label for="stockMinimoInsumo">Stock Minimo</label>
                    <input type="number" id="stockMinimoInsumo" name="stock_minimo" 
                           step="0.01" min="0" value="5">
                </div>
                
                <div class="grupo-formulario tercera">
                    <label for="fechaVencimientoInsumo">Fecha Venc.</label>
                    <input type="date" id="fechaVencimientoInsumo" name="fecha_vencimiento">
                </div>
            </div>
            
            <div class="fila-formulario">
                <div class="grupo-formulario mitad">
                    <label for="precioVentaInsumo">Precio de Venta (Bs.)</label>
                    <input type="number" id="precioVentaInsumo" name="precio_venta" 
                           step="0.01" min="0" value="0" required>
                </div>
                
                <div class="grupo-formulario mitad">
                    <label for="precioCompraInsumo">Precio de Compra (Bs.)</label>
                    <input type="number" id="precioCompraInsumo" name="precio_compra" 
                           step="0.01" min="0" value="0">
                </div>
            </div>
            
            <div class="grupo-formulario">
                <label for="proveedorInsumo">Proveedor</label>
                <select id="proveedorInsumo" name="proveedor_id">
                    <option value="">Seleccione un proveedor...</option>
                </select>
            </div>
            
            <div id="mensajeErrorInsumo" class="mensaje-error" style="display: none;"></div>
            
            <div class="modal-footer">
                <button type="button" id="btnCancelarInsumo" class="btn-secundario">Cancelar</button>
                <button type="submit" id="btnGuardarInsumo" class="btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.modulo-acciones {
    display: flex;
    gap: 10px;
    align-items: center;
}

.input-busqueda {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 300px;
}

.input-busqueda:focus {
    border-color: #1a1a2e;
    outline: none;
}

.fila-formulario {
    display: flex;
    gap: 15px;
}

.fila-formulario .mitad {
    flex: 1;
}

.fila-formulario .tercera {
    flex: 1;
}

.alertas-stock {
    background: #fff3e0;
    border: 1px solid #ff9800;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.alertas-stock h3 {
    color: #e65100;
    margin: 0 0 10px 0;
    font-size: 16px;
}

.alerta-item {
    padding: 8px 0;
    border-bottom: 1px solid #ffe0b2;
    color: #bf360c;
}

.alerta-item:last-child {
    border-bottom: none;
}

.stock-bajo {
    color: #f44336;
    font-weight: 600;
}

.stock-normal {
    color: #4caf50;
    font-weight: 600;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/inventario.js"></script>