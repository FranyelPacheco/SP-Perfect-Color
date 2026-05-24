<!-- Archivo: usuarioListView.php -->
<!-- Vista para la gestion de usuarios -->

<div class="modulo-usuario">
    <div class="modulo-header">
        <h2>Gestion de Usuarios</h2>
        <button id="btnNuevoUsuario" class="btn-primario">Nuevo Usuario</button>
    </div>
    
    <div class="modulo-body">
        <!-- Tabla de usuarios -->
        <div class="tabla-contenedor">
            <table id="tablaUsuarios" class="tabla-datos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaUsuarios">
                    <!-- Se llena dinamicamente con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div id="modalUsuario" class="modal" style="display: none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3 id="tituloModalUsuario">Nuevo Usuario</h3>
            <button type="button" id="btnCerrarModalUsuario" class="btn-cerrar-modal">&times;</button>
        </div>
        <form id="formularioUsuario">
            <input type="hidden" id="usuarioId" name="id" value="">
            
            <div class="grupo-formulario">
                <label for="nombreUsuario">Nombre</label>
                <input type="text" id="nombreUsuario" name="nombre" required 
                       placeholder="Ingrese el nombre completo">
            </div>
            
            <div class="grupo-formulario">
                <label for="correoUsuario">Correo Electronico</label>
                <input type="email" id="correoUsuario" name="correo" required 
                       placeholder="Ingrese el correo electronico">
            </div>
            
            <div id="grupoClave" class="grupo-formulario">
                <label for="claveUsuario">Clave</label>
                <input type="password" id="claveUsuario" name="clave" 
                       placeholder="Ingrese la clave (minimo 6 caracteres)">
            </div>
            
            <div id="grupoCambiarClave" class="grupo-formulario" style="display: none;">
                <label>
                    <input type="checkbox" id="checkCambiarClave" name="cambiar_clave" value="1">
                    Cambiar clave
                </label>
                <input type="password" id="nuevaClaveUsuario" name="nueva_clave" 
                       placeholder="Ingrese la nueva clave" style="margin-top: 8px; display: none;">
            </div>
            
            <div class="grupo-formulario">
                <label for="rolUsuario">Rol</label>
                <select id="rolUsuario" name="rol_id" required>
                    <option value="">Seleccione un rol</option>
                </select>
            </div>
            
            <div class="grupo-formulario">
                <label for="estadoUsuario">Estado</label>
                <select id="estadoUsuario" name="activo">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            
            <div id="mensajeErrorUsuario" class="mensaje-error" style="display: none;"></div>
            
            <div class="modal-footer">
                <button type="button" id="btnCancelarUsuario" class="btn-secundario">Cancelar</button>
                <button type="submit" id="btnGuardarUsuario" class="btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Estilos del modal -->
<style>
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

.estado-activo {
    color: #4caf50;
    font-weight: 600;
}

.estado-inactivo {
    color: #f44336;
    font-weight: 600;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/usuario.js"></script>