<!-- Archivo: proveedorListView.php -->
<!-- Vista para la gestion de proveedores -->

<div class="modulo-proveedor">
    <div class="modulo-header">
        <h2>Gestion de Proveedores</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaProveedores" placeholder="Buscar por RIF o nombre de empresa..." class="input-busqueda">
            <?php if ($_SESSION['usuario_rol'] == 1): ?>
            <button id="btnNuevoProveedor" class="btn-primario">Nuevo Proveedor</button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaProveedores" class="tabla-datos">
                <thead>
                    <tr>
                        <th>RIF</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Telefono</th>
                        <th>Rubros</th>
                        <?php if ($_SESSION['usuario_rol'] == 1): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaProveedores">
                    <!-- Se llena dinamicamente con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar proveedor -->
<?php if ($_SESSION['usuario_rol'] == 1): ?>
<div id="modalProveedor" class="modal" style="display: none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3 id="tituloModalProveedor">Nuevo Proveedor</h3>
            <button type="button" id="btnCerrarModalProveedor" class="btn-cerrar-modal">&times;</button>
        </div>
        <form id="formularioProveedor">
            <input type="hidden" id="proveedorId" name="id" value="">
            
            <div class="grupo-formulario">
                <label for="rifProveedor">RIF</label>
                <input type="text" id="rifProveedor" name="rif" required 
                       placeholder="Ej: J-123456789" maxlength="12">
            </div>
            
            <div class="grupo-formulario">
                <label for="nombreEmpresaProveedor">Nombre de la Empresa</label>
                <input type="text" id="nombreEmpresaProveedor" name="nombre_empresa" required 
                       placeholder="Ingrese el nombre de la empresa">
            </div>
            
            <div class="grupo-formulario">
                <label for="contactoProveedor">Persona de Contacto</label>
                <input type="text" id="contactoProveedor" name="contacto" 
                       placeholder="Ingrese el nombre de la persona de contacto">
            </div>
            
            <div class="grupo-formulario">
                <label for="telefonoProveedor">Telefono</label>
                <input type="text" id="telefonoProveedor" name="telefono" 
                       placeholder="Ingrese el telefono (11 digitos)" maxlength="11">
            </div>
            
            <div class="grupo-formulario">
                <label for="correoProveedor">Correo Electronico</label>
                <input type="email" id="correoProveedor" name="correo" 
                       placeholder="Ingrese el correo electronico">
            </div>
            
            <div class="grupo-formulario">
                <label for="direccionProveedor">Direccion</label>
                <input type="text" id="direccionProveedor" name="direccion" 
                       placeholder="Ingrese la direccion">
            </div>
            
            <div class="grupo-formulario">
                <label for="rubrosProveedor">Rubros que suministra</label>
                <textarea id="rubrosProveedor" name="rubros" rows="2" 
                          placeholder="Ej: Tintes, Quimicos, Pinturas"></textarea>
            </div>
            
            <div id="mensajeErrorProveedor" class="mensaje-error" style="display: none;"></div>
            
            <div class="modal-footer">
                <button type="button" id="btnCancelarProveedor" class="btn-secundario">Cancelar</button>
                <button type="submit" id="btnGuardarProveedor" class="btn-primario">Guardar</button>
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
</style>

<script src="/SP%20Perfect%20Color/assets/js/proveedor.js"></script>