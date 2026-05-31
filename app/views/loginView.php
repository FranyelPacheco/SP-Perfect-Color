<!-- Archivo: loginView.php -->
<!-- Vista del formulario de inicio de sesion con Bootstrap 5 -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Perfect Color - Iniciar Sesion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center min-vh-100" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
    <div class="card shadow-lg" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h1 class="h3 mb-1">SP Perfect Color</h1>
                <p class="text-muted small">Sistema de Gestion Administrativa</p>
            </div>

            <form id="formularioLogin">
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo Electronico</label>
                    <input type="email" id="correo" name="correo" class="form-control" required placeholder="Ingrese su correo">
                </div>

                <div class="mb-3">
                    <label for="clave" class="form-label">Clave</label>
                    <input type="password" id="clave" name="clave" class="form-control" required placeholder="Ingrese su clave">
                </div>

                <div id="mensajeError" class="alert alert-danger d-none"></div>

                <button type="submit" class="btn btn-primary w-100">Iniciar Sesion</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/SP%20Perfect%20Color/assets/js/login.js"></script>
</body>
</html>
