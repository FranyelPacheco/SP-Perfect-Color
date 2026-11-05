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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estiloBase.css'); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script src="/SP%20Perfect%20Color/assets/js/csrf.js"></script>
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
                    <img src="/SP%20Perfect%20Color/assets/images/logo-recortado.webp" alt="SP Perfect Color" class="login-logo">
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
                                <input type="password" id="nuevaClave" name="nueva_clave" class="form-control" required minlength="6" maxlength="20" placeholder="Mínimo 6 caracteres, máx. 20">
                                <button type="button" class="input-group-text btn-toggle-pass" data-target="nuevaClave"><i class="bi bi-eye-slash-fill"></i></button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirmarClave" class="form-label fw-semibold">Confirmar Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-check-fill"></i></span>
                                <input type="password" id="confirmarClave" name="confirmar_clave" class="form-control" required minlength="6" maxlength="20" placeholder="Repita la contraseña">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $.ajaxPrefilter(function (opciones, originales, xhr) {
            var token = $('meta[name="csrf-token"]').attr('content');
            if (token && !opciones.crossDomain && (opciones.type || 'GET').toUpperCase() !== 'GET') xhr.setRequestHeader('X-CSRF-Token', token);
        });

        $(function () {
            $('.btn-toggle-pass').on('click', function () {
                var $campo = $('#' + $(this).attr('data-target'));
                if (!$campo.length) return;
                var mostrar = $campo.attr('type') === 'password';
                $campo.attr('type', mostrar ? 'text' : 'password');
                $(this).find('i').toggleClass('bi-eye-fill', mostrar).toggleClass('bi-eye-slash-fill', !mostrar);
            });

            $('#formRestablecerClave').on('submit', function (e) {
                e.preventDefault();
                var form = this;
                var $alerta = $('#alertaRestablecer').addClass('d-none');
                var $btn = $('#btnRestablecer');
                var $spinner = $btn.find('.spinner-border');
                var avisar = function (tipo, texto) {
                    $alerta.attr('class', 'alert alert-' + tipo).text(texto).removeClass('d-none');
                };
                var liberar = function () {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                };

                var c1 = $('#nuevaClave').val();
                var c2 = $('#confirmarClave').val();

                if (!c1 || !c2) return avisar('danger', 'Por favor complete todos los campos.');
                if (c1.length < 6) return avisar('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
                if (c1 !== c2) return avisar('danger', 'Las contraseñas no coinciden.');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                $.ajax({ url: '/SP%20Perfect%20Color/login/guardarNuevaClave', type: 'POST', data: new FormData(form), processData: false, contentType: false, dataType: 'json' })
                    .done(function (json) {
                        if (json.estado === 'exito') {
                            avisar('success', json.mensaje);
                            setTimeout(function () {
                                window.location.href = json.datos && json.datos.redirect ? json.datos.redirect : '/SP%20Perfect%20Color/login';
                            }, 2000);
                        } else {
                            avisar('danger', json.mensaje || 'Error al restablecer la contraseña.');
                            liberar();
                        }
                    })
                    .fail(function (x) {
                        var json = x.responseJSON;
                        avisar('danger', (json && json.mensaje) || 'Error de conexión con el servidor.');
                        liberar();
                    });
            });
        });
    </script>
</body>
</html>
