<?php
// VISTA: restablecerClaveView.php
// OBJETIVO: Formulario para ingresar nueva contraseña tras validación de token seguro
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Perfect Color - Restablecer Contraseña</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/webp" href="/SP%20Perfect%20Color/assets/images/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css">
</head>
<body class="login-page">
    <div class="login-bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="login-card">
        <div class="card shadow">
            <div class="card-header text-center border-0">
                <div class="login-logo-wrapper">
                    <img src="/SP%20Perfect%20Color/assets/images/logo.webp" alt="SP Perfect Color" class="login-logo">
                </div>
                <h1 style="font-size: 1.5rem;">Restablecer Contraseña</h1>
                <p class="mb-0">Ingrese su nueva clave de acceso</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errorToken)): ?>
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($errorToken); ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/SP%20Perfect%20Color/login" class="btn btn-primary w-100">Volver al Inicio de Sesión</a>
                    </div>
                <?php else: ?>
                    <form id="formRestablecerClave" novalidate>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">

                        <div class="mb-3">
                            <label for="nuevaClave" class="form-label fw-semibold">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" id="nuevaClave" name="nueva_clave" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                                <button type="button" class="input-group-text btn-toggle-pass" data-target="nuevaClave"><i class="bi bi-eye-slash-fill"></i></button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirmarClave" class="form-label fw-semibold">Confirmar Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-check-fill"></i></span>
                                <input type="password" id="confirmarClave" name="confirmar_clave" class="form-control" required minlength="6" placeholder="Repita la contraseña">
                                <button type="button" class="input-group-text btn-toggle-pass" data-target="confirmarClave"><i class="bi bi-eye-slash-fill"></i></button>
                            </div>
                        </div>

                        <div id="alertaRestablecer" class="alert d-none mb-3"></div>

                        <button type="submit" id="btnRestablecer" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                            <span class="btn-texto">Guardar Nueva Contraseña</span>
                            <div class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center border-0 py-3">
                <small class="text-muted"><a href="/SP%20Perfect%20Color/login" class="text-decoration-none">Regresar al inicio de sesión</a></small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.btn-toggle-pass').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = document.getElementById(this.getAttribute('data-target'));
                    var icon = this.querySelector('i');
                    if (target) {
                        if (target.type === 'password') {
                            target.type = 'text';
                            if (icon) { icon.classList.remove('bi-eye-slash-fill'); icon.classList.add('bi-eye-fill'); }
                        } else {
                            target.type = 'password';
                            if (icon) { icon.classList.remove('bi-eye-fill'); icon.classList.add('bi-eye-slash-fill'); }
                        }
                    }
                });
            });

            var form = document.getElementById('formRestablecerClave');
            if (form) {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    var alerta = document.getElementById('alertaRestablecer');
                    var btn = document.getElementById('btnRestablecer');
                    var spinner = btn.querySelector('.spinner-border');
                    var texto = btn.querySelector('.btn-texto');

                    alerta.classList.add('d-none');

                    var c1 = document.getElementById('nuevaClave').value;
                    var c2 = document.getElementById('confirmarClave').value;

                    if (!c1 || !c2) {
                        alerta.className = 'alert alert-danger';
                        alerta.textContent = 'Por favor complete todos los campos.';
                        alerta.classList.remove('d-none');
                        return;
                    }

                    if (c1.length < 6) {
                        alerta.className = 'alert alert-danger';
                        alerta.textContent = 'La nueva contraseña debe tener al menos 6 caracteres.';
                        alerta.classList.remove('d-none');
                        return;
                    }

                    if (c1 !== c2) {
                        alerta.className = 'alert alert-danger';
                        alerta.textContent = 'Las contraseñas no coinciden.';
                        alerta.classList.remove('d-none');
                        return;
                    }

                    btn.disabled = true;
                    spinner.classList.remove('d-none');

                    try {
                        var fd = new FormData(form);
                        var res = await fetch('/SP%20Perfect%20Color/login/guardarNuevaClave', {
                            method: 'POST',
                            body: fd,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        var json = await res.json();

                        if (json.estado === 'exito') {
                            alerta.className = 'alert alert-success';
                            alerta.textContent = json.mensaje;
                            alerta.classList.remove('d-none');
                            setTimeout(function () {
                                window.location.href = json.datos && json.datos.redirect ? json.datos.redirect : '/SP%20Perfect%20Color/login';
                            }, 2000);
                        } else {
                            alerta.className = 'alert alert-danger';
                            alerta.textContent = json.mensaje || 'Error al restablecer la contraseña.';
                            alerta.classList.remove('d-none');
                            btn.disabled = false;
                            spinner.classList.add('d-none');
                        }
                    } catch (err) {
                        alerta.className = 'alert alert-danger';
                        alerta.textContent = 'Error de conexión con el servidor.';
                        alerta.classList.remove('d-none');
                        btn.disabled = false;
                        spinner.classList.add('d-none');
                    }
                });
            }
        });
    </script>
</body>
</html>
