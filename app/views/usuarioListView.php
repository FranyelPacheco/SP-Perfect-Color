<?php
// VISTA: usuarioListView.php
// OBJETIVO: Gestión de usuarios y roles (administradores/delegados) y perfil personal (otros roles)
?>
<script>
    var SESSION_USER_ROL = <?php echo json_encode($_SESSION['usuario_rol'] ?? 0); ?>;
    var SESSION_USER_ID = <?php echo json_encode($_SESSION['id_usuario'] ?? 0); ?>;
    var PUEDE_GESTIONAR = <?php echo json_encode($puedeGestionar ?? false); ?>;
</script>
<div class="row justify-content-center">
    <div class="col-12<?php echo $puedeGestionar ? ' col-lg-11' : ' col-md-8 col-lg-6'; ?>">

        <?php if ($puedeGestionar): ?>
        <!-- Pestañas de navegación entre Usuarios y Roles -->
        <ul class="nav nav-pills mb-3 gap-2" id="pillsUsuarioTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="pills-usuarios-tab" data-bs-toggle="pill" data-bs-target="#pills-usuarios" type="button" role="tab">
                    <i class="bi bi-people-fill me-2"></i>Usuarios
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="pills-roles-tab" data-bs-toggle="pill" data-bs-target="#pills-roles" type="button" role="tab">
                    <i class="bi bi-shield-lock-fill me-2"></i>Roles y Permisos
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pillsUsuarioTabContent">
            <!-- PESTAÑA 1: USUARIOS -->
            <div class="tab-pane fade show active" id="pills-usuarios" role="tabpanel">
                <div id="areaAdminUsuarios" class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h4 class="mb-0 toolbar-title"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Usuarios Registrados</h4>
                            <div class="d-flex gap-2 align-items-center flex-wrap toolbar-actions">
                                <button id="btnNuevoUsuario" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo Usuario</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="tablaUsuarios" class="table table-hover table-striped mb-0 w-100">
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
            </div>

            <!-- PESTAÑA 2: ROLES Y PERMISOS -->
            <div class="tab-pane fade" id="pills-roles" role="tabpanel">
                <div id="areaAdminRoles" class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h4 class="mb-0 toolbar-title"><i class="bi bi-shield-check me-2 text-primary"></i>Roles del Sistema</h4>
                                <small class="text-muted">Configure los roles y asigne a qué módulos tiene acceso cada uno</small>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap toolbar-actions">
                                <button id="btnNuevoRol" class="btn btn-success"><i class="bi bi-plus-lg me-2"></i>Nuevo Rol</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="tablaRoles" class="table table-hover table-striped mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre del Rol</th>
                                        <th>Módulos con Acceso</th>
                                        <th>Usuarios</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaRoles"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$puedeGestionar): ?>
        <!-- Vista de Perfil para usuarios sin privilegios administrativos -->
        <div id="perfilVendedor" class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm"
                     style="width: 80px; height: 80px; font-size: 2rem; font-weight: 600;">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)); ?>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($_SESSION['usuario_correo'] ?? 'Correo no disponible'); ?></p>
                <span class="badge bg-secondary fs-6 mb-3"><?php echo htmlspecialchars($_SESSION['usuario_rol_nombre'] ?? 'Usuario'); ?></span>

                <hr class="my-4">

                <div class="text-start mb-3">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Nombre Completo</label>
                    <p class="fw-medium fs-5 mb-0"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></p>
                </div>
                <div class="text-start mb-3">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Correo Electrónico</label>
                    <p class="fw-medium fs-5 mb-0"><?php echo htmlspecialchars($_SESSION['usuario_correo'] ?? 'No disponible'); ?></p>
                </div>
                <div class="text-start mb-4">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Rol</label>
                    <p class="fw-medium fs-5 mb-0"><?php echo htmlspecialchars($_SESSION['usuario_rol_nombre'] ?? 'Usuario'); ?></p>
                </div>

                <button id="btnEditarPerfil" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-pencil-square me-2"></i>Editar Mi Perfil
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- MODAL PARA USUARIO (CREAR/EDITAR) -->
<div id="modalUsuario" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-bottom-0">
                <h5 class="modal-title text-white" id="tituloModalUsuario">Nuevo Usuario</h5>
                <button type="button" id="btnCerrarModalUsuario" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formularioUsuario">
                <div class="modal-body p-4">
                    <input type="hidden" id="usuarioId" name="id" value="">

                    <div class="mb-3">
                        <label for="nombreUsuario" class="form-label fw-semibold">Nombre Completo</label>
                        <input type="text" id="nombreUsuario" name="nombre" class="form-control" placeholder="Ingrese el nombre completo" required>
                    </div>

                    <div class="mb-3">
                        <label for="correoUsuario" class="form-label fw-semibold">Correo Electrónico</label>
                        <input type="email" id="correoUsuario" name="correo" class="form-control" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div id="grupoClave" class="mb-3">
                        <label for="claveUsuario" class="form-label fw-semibold">Contraseña</label>
                        <input type="password" id="claveUsuario" name="clave" class="form-control" placeholder="Mínimo 6 caracteres">
                    </div>

                    <div id="grupoCambiarClave" class="mb-3" style="display:none">
                        <div class="form-check form-switch">
                            <input type="checkbox" id="chkCambiarClave" class="form-check-input">
                            <label for="chkCambiarClave" class="form-check-label fw-semibold">Cambiar Contraseña</label>
                        </div>
                        <div id="grupoNuevaClave" class="mt-2" style="display:none">
                            <label for="nuevaClaveUsuario" class="form-label small text-muted">Nueva Contraseña</label>
                            <input type="password" id="nuevaClaveUsuario" name="nueva_clave" class="form-control" placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>

                    <?php if ($puedeGestionar): ?>
                    <div id="contenedorRol" class="mb-3">
                        <label for="rolUsuario" class="form-label fw-semibold">Rol Asignado</label>
                        <select id="rolUsuario" name="id_rol" class="form-select" required>
                            <option value="">Seleccione un rol activo</option>
                        </select>
                    </div>

                    <div id="contenedorEstado" class="mb-3">
                        <label for="estadoUsuario" class="form-label fw-semibold">Estado de la Cuenta</label>
                        <select id="estadoUsuario" name="activo" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div id="mensajeErrorUsuario" class="alert alert-danger d-none"></div>
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" id="btnCancelarUsuario" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarUsuario" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($puedeGestionar): ?>
<!-- MODAL PARA ROL (CREAR/EDITAR) -->
<div id="modalRol" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white border-bottom-0">
                <h5 class="modal-title text-white" id="tituloModalRol">Nuevo Rol</h5>
                <button type="button" id="btnCerrarModalRol" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="formularioRol">
                <div class="modal-body p-4">
                    <input type="hidden" id="rolId" name="id" value="">

                    <div class="row mb-3">
                        <div class="col-md-8 col-12 mb-3 mb-md-0">
                            <label for="nombreRol" class="form-label fw-semibold">Nombre del Rol</label>
                            <input type="text" id="nombreRol" name="nombre" class="form-control" placeholder="Ej: Supervisor, Almacenista, Auditor" required>
                        </div>
                        <div class="col-md-4 col-12" id="contenedorEstadoRol">
                            <label for="estadoRol" class="form-label fw-semibold">Estado</label>
                            <select id="estadoRol" name="activo" class="form-select">
                                <option value="1">Habilitado (Activo)</option>
                                <option value="0">Deshabilitado (Inactivo)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Módulos Autorizados</span>
                            <span class="small text-muted fw-normal">Seleccione los módulos a los que este rol podrá entrar</span>
                        </label>
                        <div class="p-3 bg-light rounded border">
                            <div class="row g-2" id="contenedorModulosCheckboxes">
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="cliente" id="mod_cliente">
                                        <label class="form-check-label" for="mod_cliente"><i class="bi bi-people-fill text-primary me-1"></i> Clientes</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="proveedor" id="mod_proveedor">
                                        <label class="form-check-label" for="mod_proveedor"><i class="bi bi-truck text-primary me-1"></i> Proveedores</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="inventario" id="mod_inventario">
                                        <label class="form-check-label" for="mod_inventario"><i class="bi bi-box-seam-fill text-primary me-1"></i> Inventario</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="presupuesto" id="mod_presupuesto">
                                        <label class="form-check-label" for="mod_presupuesto"><i class="bi bi-file-earmark-text-fill text-primary me-1"></i> Presupuestos</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="notaEntrega" id="mod_notaEntrega">
                                        <label class="form-check-label" for="mod_notaEntrega"><i class="bi bi-receipt-cutoff text-primary me-1"></i> Notas de Entrega</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="cuentaCobrar" id="mod_cuentaCobrar">
                                        <label class="form-check-label" for="mod_cuentaCobrar"><i class="bi bi-cash-coin text-primary me-1"></i> Cuentas por Cobrar</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="cuentaPagar" id="mod_cuentaPagar">
                                        <label class="form-check-label" for="mod_cuentaPagar"><i class="bi bi-credit-card-2-back-fill text-primary me-1"></i> Cuentas por Pagar</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="banco" id="mod_banco">
                                        <label class="form-check-label" for="mod_banco"><i class="bi bi-bank text-primary me-1"></i> Bancos</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="tipoPago" id="mod_tipoPago">
                                        <label class="form-check-label" for="mod_tipoPago"><i class="bi bi-credit-card text-primary me-1"></i> Tipos de Pago</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="reporte" id="mod_reporte">
                                        <label class="form-check-label" for="mod_reporte"><i class="bi bi-bar-chart-fill text-primary me-1"></i> Reportes</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <hr class="my-2">
                                    <div class="form-check">
                                        <input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="usuario" id="mod_usuario">
                                        <label class="form-check-label fw-bold text-danger" for="mod_usuario">
                                            <i class="bi bi-shield-lock-fill me-1"></i> Usuarios y Roles 
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 font-monospace" style="font-size: 0.72rem;">Acceso Administrativo</span>
                                        </label>
                                        <div class="text-muted small ps-4">Permite crear, modificar y deshabilitar usuarios y roles del sistema.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="mensajeErrorRol" class="alert alert-danger d-none"></div>
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" id="btnCancelarRol" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarRol" class="btn btn-primary">Guardar Rol</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/SP%20Perfect%20Color/assets/js/usuario.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/usuario.js'); ?>"></script>
