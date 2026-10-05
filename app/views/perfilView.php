<?php
// VISTA: perfilView.php
// OBJETIVO: Vista de perfil personal y cambio seguro de contraseña para cualquier usuario autenticado
?>
<div class="row g-4 justify-content-center">
    <!-- Columna Izquierda: Tarjeta Informativa de Usuario -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="card-body p-0">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm"
                     style="width: 86px; height: 86px; font-size: 2.2rem; font-weight: 700; background-color: #1E3A5F !important;">
                    <?php echo strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)); ?>
                </div>
                <h4 class="mb-1 fw-bold"><?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?></h4>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($usuario['correo'] ?? ''); ?></p>
                <span class="badge bg-primary px-3 py-2 fs-6 mb-3" style="background-color: #1E3A5F !important;">
                    <i class="bi bi-shield-lock-fill me-1"></i><?php echo htmlspecialchars($usuario['rol_nombre'] ?? 'Usuario'); ?>
                </span>

                <hr class="my-3">

                <div class="text-start">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Módulos Asignados</label>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php 
                        $modulos = !empty($usuario['rol_modulos']) ? explode(',', $usuario['rol_modulos']) : [];
                        if ((int)($usuario['id_rol'] ?? 0) === 1): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-all me-1"></i>Acceso Total (Administrador)</span>
                        <?php elseif (!empty($modulos)): 
                            foreach ($modulos as $mod): ?>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst(trim($mod))); ?></span>
                            <?php endforeach; 
                        else: ?>
                            <span class="text-muted small">Sin módulos asignados</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-start mt-3">
                    <label class="text-muted small text-uppercase fw-semibold mb-1">Estado de Cuenta</label>
                    <div>
                        <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Activa</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Formularios de Edición y Cambio de Contraseña -->
    <div class="col-12 col-lg-8">
        <!-- Formulario 1: Datos Personales -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-semibold text-primary"><i class="bi bi-person-gear me-2"></i>Mis Datos Personales</h5>
            </div>
            <div class="card-body p-4">
                <form id="formEditarPerfil">
                    <div id="alertaDatosPerfil" class="alert d-none mb-3"></div>

                    <div class="mb-3">
                        <label for="perfilNombre" class="form-label fw-semibold">Nombre Completo</label>
                        <input type="text" class="form-control" id="perfilNombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="perfilCorreo" class="form-label fw-semibold">Correo Electrónico</label>
                        <input type="email" class="form-control" id="perfilCorreo" name="correo" value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" required>
                    </div>

                    <div class="text-end">
                        <button type="submit" id="btnGuardarDatos" class="btn btn-primary px-4">
                            <span class="btn-texto"><i class="bi bi-check2-circle me-1"></i>Guardar Cambios</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formulario 2: Cambio Seguro de Contraseña -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-semibold text-primary"><i class="bi bi-key-fill me-2"></i>Cambiar Contraseña</h5>
            </div>
            <div class="card-body p-4">
                <form id="formCambiarClave">
                    <div id="alertaClavePerfil" class="alert d-none mb-3"></div>

                    <div class="mb-3">
                        <label for="claveActual" class="form-label fw-semibold">Contraseña Actual</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="claveActual" name="clave_actual" placeholder="Ingrese su contraseña actual" required>
                            <button type="button" class="input-group-text btn-toggle-pass" data-target="claveActual"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="nuevaClave" class="form-label fw-semibold">Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="nuevaClave" name="nueva_clave" placeholder="Mínimo 6 caracteres" minlength="6" required>
                                <button type="button" class="input-group-text btn-toggle-pass" data-target="nuevaClave"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirmarClave" class="form-label fw-semibold">Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmarClave" name="confirmar_clave" placeholder="Repita la nueva contraseña" minlength="6" required>
                                <button type="button" class="input-group-text btn-toggle-pass" data-target="confirmarClave"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" id="btnGuardarClave" class="btn btn-warning px-4 text-dark fw-semibold">
                            <span class="btn-texto"><i class="bi bi-shield-lock me-1"></i>Actualizar Contraseña</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/perfil.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/perfil.js'); ?>"></script>
