<?php if ($_SESSION['usuario_rol'] == 1): ?>
<div id="areaAdminUsuarios">
    <div class="modulo-usuario">
        <div class="modulo-header">
            <h2>Gestión de Usuarios</h2>
            <button id="btnNuevoUsuario" class="btn-primario">Nuevo Usuario</button>
        </div>

        <div class="modulo-body">
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
                <tbody id="cuerpoTablaUsuarios"></tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="modalUsuario" class="modal<?php echo (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 2) ? ' modo-perfil' : ''; ?>" style="display: none;">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3 id="tituloModalUsuario">Nuevo Usuario</h3>
            <button type="button" id="btnCerrarModalUsuario" class="btn-cerrar-modal">&times;</button>
        </div>

        <form id="formularioUsuario">
            <input type="hidden" id="usuarioId" name="id" value="">

            <div class="grupo-formulario">
                <label for="nombreUsuario">Nombre</label>
                <input type="text" id="nombreUsuario" name="nombre" placeholder="Ingrese el nombre completo" required>
            </div>

            <div class="grupo-formulario">
                <label for="correoUsuario">Correo Electrónico</label>
                <input type="email" id="correoUsuario" name="correo" placeholder="ejemplo@correo.com" required>
            </div>

            <div id="grupoClave" class="grupo-formulario">
                <label for="claveUsuario">Contraseña</label>
                <input type="password" id="claveUsuario" name="clave" placeholder="Mínimo 6 caracteres">
            </div>

            <div id="grupoCambiarClave" class="grupo-formulario" style="display: none;">
                <label>
                    <input type="checkbox" id="checkCambiarClave" name="cambiar_clave" value="1">
                    ¿Desea modificar la contraseña actual?
                </label>
                <input type="password" id="nuevaClaveUsuario" name="nueva_clave" placeholder="Ingrese la nueva contraseña" style="margin-top: 8px; display: none;">
            </div>

<?php if ($_SESSION['usuario_rol'] == 1): ?>
            <div id="contenedorRol" class="grupo-formulario">
                <label for="rolUsuario">Rol</label>
                <select id="rolUsuario" name="rol_id" required>
                    <option value="">Seleccione un rol</option>
                </select>
            </div>

            <div id="contenedorEstado" class="grupo-formulario">
                <label for="estadoUsuario">Estado</label>
                <select id="estadoUsuario" name="activo">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
<?php endif; ?>

            <div id="mensajeErrorUsuario" class="mensaje-error" style="display: none;"></div>

            <div class="modal-footer">
                <button type="button" id="btnCancelarUsuario" class="btn-secundario">Cancelar</button>
                <button type="submit" id="btnGuardarUsuario" class="btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ---- Admin: tabla de usuarios ---- */
#areaAdminUsuarios {
    background: #ffffff;
    border-radius: 8px;
    padding: 20px 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

#areaAdminUsuarios .modulo-header {
    margin-bottom: 18px;
}

/* ---- Modal flotante (Admin) ---- */
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
    border-radius: 8px;
    width: 90%;
    max-width: 520px;
    padding: 28px;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.btn-cerrar-modal {
    background: none;
    border: none;
    font-size: 26px;
    cursor: pointer;
    color: #888;
    padding: 0;
    line-height: 1;
}

.btn-cerrar-modal:hover {
    color: #333;
}

.grupo-formulario {
    margin-bottom: 16px;
}

.grupo-formulario label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
    color: #333;
}

.grupo-formulario input[type="text"],
.grupo-formulario input[type="email"],
.grupo-formulario input[type="password"],
.grupo-formulario select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
    transition: border-color 0.2s;
}

.grupo-formulario input:focus,
.grupo-formulario select:focus {
    border-color: #4a90d9;
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 144, 217, 0.15);
}

.mensaje-error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px 14px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
    border: 1px solid #f5c6cb;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-primario {
    background: #4a90d9;
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primario:hover {
    background: #357abd;
}

.btn-secundario {
    background: #e0e0e0;
    color: #333;
    border: none;
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-secundario:hover {
    background: #ccc;
}

.estado-activo {
    background: #d4edda;
    color: #155724;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    display: inline-block;
}

.estado-inactivo {
    background: #f8d7da;
    color: #721c24;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    display: inline-block;
}

/* ---- Vendor: tarjeta de perfil estatica ---- */
#modalUsuario.modo-perfil {
    display: flex !important;
    justify-content: center;
    align-items: center;
    min-height: 75vh;
    width: 100%;
    position: relative !important;
    background: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    top: auto !important;
    left: auto !important;
    height: auto !important;
    z-index: auto !important;
}

#modalUsuario.modo-perfil .modal-contenido {
    max-width: 480px;
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
    padding: 0;
}

#modalUsuario.modo-perfil .modal-header {
    background: #1a1a2e;
    color: #fff;
    text-align: center;
    margin-bottom: 0;
    display: block;
    padding: 28px 24px;
    border-bottom: none;
}

#modalUsuario.modo-perfil .modal-header h3 {
    color: #fff;
    font-size: 22px;
}

#modalUsuario.modo-perfil .modal-contenido .grupo-formulario {
    padding: 0 28px;
}

#modalUsuario.modo-perfil .modal-contenido .grupo-formulario:first-of-type {
    margin-top: 24px;
}

#modalUsuario.modo-perfil .modal-footer {
    padding: 0 28px 28px;
    justify-content: center;
    margin-top: 0;
}

#modalUsuario.modo-perfil .btn-primario {
    width: 100%;
    padding: 12px 22px;
    font-size: 15px;
}

#modalUsuario.modo-perfil .modal-header span,
#modalUsuario.modo-perfil .btn-secundario,
#modalUsuario.modo-perfil .grupo-formulario:nth-of-type(4),
#modalUsuario.modo-perfil .grupo-formulario:nth-of-type(5) {
    display: none !important;
}

.btn-link-password {
    background: none;
    border: none;
    color: #4a90d9;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 0 16px;
    display: inline-block;
    text-decoration: underline;
}

.btn-link-password:hover {
    color: #357abd;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/usuario.js"></script>
