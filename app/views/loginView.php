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
                <h1>SP Perfect Color</h1>
                <p class="mb-0">Sistema de Gestión Administrativa</p>
            </div>
            <div class="card-body">
                <form id="formularioLogin" novalidate>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" id="correo" name="correo" class="form-control" required autocomplete="email" placeholder="usuario@ejemplo.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="clave" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" id="clave" name="clave" class="form-control" required autocomplete="current-password" placeholder="Ingrese su contraseña">
                            <button type="button" id="toggleClave" class="input-group-text password-toggle" tabindex="-1">
                                <i class="bi bi-eye-slash-fill" id="toggleIcono"></i>
                            </button>
                        </div>
                    </div>

                    <div id="mensajeError" class="alert alert-danger d-none"></div>

                    <button type="submit" id="btnLogin" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                        <span id="btnText">Iniciar Sesión</span>
                        <div id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </button>
                </form>
            </div>
            <div class="card-footer text-center border-0 py-3">
                <small class="text-muted">&copy; <?php echo date('Y'); ?> SP Perfect Color</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/SP%20Perfect%20Color/assets/js/login.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/login.js'); ?>"></script>
</body>
</html>