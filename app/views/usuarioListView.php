<div class="row justify-content-center">
    <div class="col-12<?php echo $_SESSION['usuario_rol'] == 1 ? ' col-lg-10' : ' col-md-8 col-lg-6'; ?>">

        <?php if ($_SESSION['usuario_rol'] == 1): ?>
        <div id="areaAdminUsuarios" class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4 class="mb-0 toolbar-title"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Usuarios</h4>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <button id="btnNuevoUsuario" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tablaUsuarios" class="table table-hover table-striped mb-0">
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

        <?php if ($_SESSION['usuario_rol'] == 2): ?>
        <div id="perfilVendedor" class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 80px; height: 80px; font-size: 2rem; font-weight: 600;">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)); ?>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($_SESSION['usuario_correo'] ?? 'Correo no disponible'); ?></p>
                <span class="badge bg-secondary fs-6 mb-3">Vendedor</span>

                <hr class="my-4">

                <div class="text-start mb-3">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Nombre Completo</label>
                    <p class="fw-medium fs-5 mb-0"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></p>
                </div>
                <div class="text-start mb-3">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Correo Electronico</label>
                    <p class="fw-medium fs-5 mb-0"><?php echo htmlspecialchars($_SESSION['usuario_correo'] ?? 'No disponible'); ?></p>
                </div>
                <div class="text-start mb-4">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Rol</label>
                    <p class="fw-medium fs-5 mb-0">Vendedor</p>
                </div>

                <button id="btnEditarPerfil" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-pencil-square me-2"></i>Editar Perfil
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<div id="modalUsuario" class="modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white border-bottom-0">
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

                    <div id="grupoClave" class="mb-3">
                        <label for="claveUsuario" class="form-label">Contrasena</label>
                        <input type="password" id="claveUsuario" name="clave" class="form-control" placeholder="Minimo 6 caracteres">
                    </div>

                    <div id="grupoCambiarClave" class="mb-3" style="display:none">
                        <div class="form-check">
                            <input type="checkbox" id="chkCambiarClave" class="form-check-input">
                            <label for="chkCambiarClave" class="form-check-label">Cambiar ContraseÃ±a</label>
                        </div>
                        <div id="grupoNuevaClave" class="mt-2" style="display:none">
                            <label for="nuevaClaveUsuario" class="form-label">Nueva Contrasena</label>
                            <input type="password" id="nuevaClaveUsuario" name="nueva_clave" class="form-control" placeholder="Minimo 6 caracteres">
                        </div>
                    </div>

<?php if ($_SESSION['usuario_rol'] == 1): ?>
                    <div id="contenedorRol" class="mb-3">
                        <label for="rolUsuario" class="form-label">Rol</label>
                        <select id="rolUsuario" name="id_rol" class="form-select" required>
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

                <div class="modal-footer border-top-0">
                    <button type="button" id="btnCancelarUsuario" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarUsuario" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/usuario.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/usuario.js'); ?>"></script>
