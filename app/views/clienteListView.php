<!-- Archivo: clienteListView.php -->
<!-- Vista para la gestion de clientes -->

<div class="modulo-cliente">
    <div class="modulo-header">
        <h2>Gestion de Clientes</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaClientes" placeholder="Buscar por cedula, nombre o apellido..." class="input-busqueda">
            <button id="btnNuevoCliente" class="btn-primario">Nuevo Cliente</button>
        </div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaClientes" class="tabla-datos">
                <thead>
                    <tr>
                        <th>Cedula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Telefono</th>
                        <th>Correo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaClientes">
                    <!-- Se llena dinamicamente con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar cliente -->
<div id="modalCliente" class="modal" style="display: none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3 id="tituloModalCliente">Nuevo Cliente</h3>
            <button type="button" id="btnCerrarModalCliente" class="btn-cerrar-modal">&times;</button>
        </div>
        <form id="formularioCliente">
            <input type="hidden" id="clienteId" name="id" value="">
            
            <div class="grupo-formulario">
                <label for="cedulaCliente">Cedula</label>
                <input type="text" id="cedulaCliente" name="cedula" required 
                       placeholder="Ingrese la cedula (7-8 digitos)" maxlength="8">
            </div>
            
            <div class="grupo-formulario">
                <label for="nombresCliente">Nombres</label>
                <input type="text" id="nombresCliente" name="nombres" required 
                       placeholder="Ingrese los nombres">
            </div>
            
            <div class="grupo-formulario">
                <label for="apellidosCliente">Apellidos</label>
                <input type="text" id="apellidosCliente" name="apellidos" required 
                       placeholder="Ingrese los apellidos">
            </div>
            
            <div class="grupo-formulario">
                <label for="telefonoCliente">Telefono</label>
                <input type="text" id="telefonoCliente" name="telefono" 
                       placeholder="Ingrese el telefono (11 digitos)" maxlength="11">
            </div>
            
            <div class="grupo-formulario">
                <label for="correoCliente">Correo Electronico</label>
                <input type="email" id="correoCliente" name="correo" 
                       placeholder="Ingrese el correo electronico">
            </div>
            
            <div class="grupo-formulario">
                <label for="direccionCliente">Direccion</label>
                <textarea id="direccionCliente" name="direccion" rows="2" 
                          placeholder="Ingrese la direccion"></textarea>
            </div>
            
            <div id="mensajeErrorCliente" class="mensaje-error" style="display: none;"></div>
            
            <div class="modal-footer">
                <button type="button" id="btnCancelarCliente" class="btn-secundario">Cancelar</button>
                <button type="submit" id="btnGuardarCliente" class="btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

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

/* Estilos del modal (si no estan en estiloBase.css) */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-contenido {
    background: #fff;
    border-radius: 6px;
    width: 90%;
    max-width: 500px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.btn-cerrar-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.btn-cerrar-modal:hover {
    color: #000;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/cliente.js"></script>