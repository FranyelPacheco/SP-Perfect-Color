<?php
// VISTA: loginView.php
// OBJETIVO: Formulario de inicio de sesión con diseño independiente
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Perfect Color - Iniciar Sesión</title>
    <meta name="description" content="Inicio de sesión al sistema de gestión administrativa SP Perfect Color.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="SP Perfect Color">
    <link rel="canonical" href="<?php echo 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/SP%20Perfect%20Color/login'; ?>">
    <link rel="icon" type="image/webp" href="/SP%20Perfect%20Color/assets/images/logo.webp">

    <meta property="og:title" content="SP Perfect Color - Iniciar Sesión">
    <meta property="og:description" content="Inicio de sesión al sistema de gestión administrativa SP Perfect Color.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SP Perfect Color">
    <meta property="og:locale" content="es_VE">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="SP Perfect Color - Iniciar Sesión">
    <meta name="twitter:description" content="Inicio de sesión al sistema de gestión administrativa SP Perfect Color.">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "SP Perfect Color",
        "url": "<?php echo 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/SP%20Perfect%20Color'; ?>",
        "description": "Sistema de gestión administrativa para SP Perfect Color",
        "foundingDate": "2025"
    }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estiloBase.css'); ?>">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/login.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/login.css'); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script src="/SP%20Perfect%20Color/assets/js/csrf.js"></script>
</head>
<body class="auth-page">
    <main class="auth-layout">
        <aside class="auth-brand" aria-hidden="false">
            <div class="auth-brand-inner">
                <div class="auth-logo-badge">
                    <img src="/SP%20Perfect%20Color/assets/images/logo-recortado.webp" alt="SP Perfect Color">
                </div>
                <h1>SP Perfect Color</h1>
                <p class="auth-lema">Sistema de gestión interno</p>
            </div>
        </aside>

        <section class="auth-form-lado">
            <div class="auth-form-caja">
                <div class="auth-logo-movil">
                    <img src="/SP%20Perfect%20Color/assets/images/logo-recortado.webp" alt="SP Perfect Color">
                    <span>SP Perfect Color</span>
                </div>
                <h2>Iniciar sesión</h2>
                <p class="auth-sub">Ingresa con tu cuenta.</p>

                <form id="formularioLogin" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="correo" name="correo" class="form-control" required autocomplete="email" placeholder="usuario@ejemplo.com">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="clave" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="clave" name="clave" class="form-control" required maxlength="20" autocomplete="current-password" placeholder="Ingresa tu contraseña">
                            <button type="button" id="toggleClave" class="input-group-text password-toggle" aria-label="Mostrar u ocultar contraseña">
                                <i class="bi bi-eye-slash-fill" id="toggleIcono"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-3">
                        <a href="#" class="auth-link" data-bs-toggle="modal" data-bs-target="#modalRecuperarClave">Recuperar contraseña</a>
                    </div>

                    <div id="mensajeError" class="alert alert-danger d-none" role="alert"></div>

                    <button type="submit" id="btnLogin" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        <span id="btnText">Iniciar sesión</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </button>
                </form>
            </div>
        </section>
    </main>

    <!-- MODAL PARA RECUPERAR CONTRASEÑA -->
    <div class="modal fade" id="modalRecuperarClave" tabindex="-1" aria-labelledby="modalRecuperarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fs-6 text-white" id="modalRecuperarLabel"><i class="bi bi-shield-lock-fill me-2"></i>Recuperar Contraseña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Ingrese el correo electrónico registrado en su cuenta. Le enviaremos un enlace seguro para restablecer su clave.</p>
                    <form id="formRecuperarClave" novalidate>
                        <div class="mb-3">
                            <label for="correoRecuperar" class="form-label fw-semibold">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" id="correoRecuperar" name="correo" class="form-control" placeholder="correo@ejemplo.com" required>
                            </div>
                        </div>

                        <div id="alertaRecuperar" class="alert d-none mb-3"></div>

                        <button type="submit" id="btnEnviarRecuperar" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                            <span class="btn-texto">Enviar Instrucciones</span>
                            <div class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Enviando...</span>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/SP%20Perfect%20Color/assets/js/login.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/login.js'); ?>"></script>
</body>
</html>