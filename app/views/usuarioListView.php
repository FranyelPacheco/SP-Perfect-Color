<?php if ($_SESSION['usuario_rol'] == 1): ?>
<div id="areaAdminUsuarios" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 mb-0">Gestion de Usuarios</h2>
            <button id="btnNuevoUsuario" class="btn btn-success"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
        </div>

        <div class="table-responsive">
            <table id="tablaUsuarios" class="table table-hover table-striped mb-0">
                <thead class="table-dark">
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

<div id="modalUsuario" class="modal d-none<?php echo (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 2) ? ' modo-perfil' : ''; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white text-center border-bottom-0">
                <h5 class="modal-title text-white" id="tituloModalUsuario">Nuevo Usuario</h5>
                <button type="button" id="btnCerrarModalUsuario" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formularioUsuario">
                <div class="modal-body">
                    <input type="hidden" id="usuarioId" name="id" value="">

                    <div class="mb-3">
                        <label for="nombreUsuario" class="form-label">Nombre</label>
                        <input type="text" id="nombreUsuario" name="nombre" class="form-control" placeholder="Ingrese el nombre completo" required>
                    </div>

                    <div class="mb-3">
                        <label for="correoUsuario" class="form-label">Correo Electronico</label>
                        <input type="email" id="correoUsuario" name="correo" class="form-control" placeholder="ejemplo@correo.com" required>
                    </div>

                    <button type="button" id="btnToggleClave" class="btn btn-primary btn-sm mt-2 mb-3 d-none">
                        <i class="bi bi-key me-1"></i>Cambiar Contraseña
                    </button>

                    <div id="grupoClave" class="mb-3">
                        <label for="claveUsuario" class="form-label">Contrasena</label>
                        <input type="password" id="claveUsuario" name="clave" class="form-control" placeholder="Minimo 6 caracteres">
                    </div>

                    <div id="grupoCambiarClave" class="mb-3" style="display: none;">
                        <div class="form-check mb-2">
                            <input type="checkbox" id="checkCambiarClave" name="cambiar_clave" value="1" class="form-check-input">
                            <label for="checkCambiarClave" class="form-check-label">Desea modificar la contrasena actual?</label>
                        </div>
                        <input type="password" id="nuevaClaveUsuario" name="nueva_clave" class="form-control" placeholder="Ingrese la nueva contrasena" style="display: none;">
                    </div>

<?php if ($_SESSION['usuario_rol'] == 1): ?>
                    <div id="contenedorRol" class="mb-3">
                        <label for="rolUsuario" class="form-label">Rol</label>
                        <select id="rolUsuario" name="rol_id" class="form-select" required>
                            <option value="">Seleccione un rol</option>
                        </select>
                    </div>

                    <div id="contenedorEstado" class="mb-3">
                        <label for="estadoUsuario" class="form-label">Estado</label>
                        <select id="estadoUsuario" name="activo" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
<?php endif; ?>

                    <div id="mensajeErrorUsuario" class="alert alert-danger d-none"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btnCancelarUsuario" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarUsuario" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/usuario.js"></script>
