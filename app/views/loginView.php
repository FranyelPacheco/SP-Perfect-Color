<!-- Archivo: loginView.php -->
<!-- Vista del formulario de inicio de sesion -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Perfect Color - Iniciar Sesion</title>
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css">
</head>
<body class="pagina-login">
    <div class="contenedor-login">
        <div class="login-header">
            <h1>SP Perfect Color</h1>
            <p>Sistema de Gestion Administrativa</p>
        </div>
        
        <form id="formularioLogin" class="formulario-login">
            <div class="grupo-formulario">
                <label for="correo">Correo Electronico</label>
                <input type="email" id="correo" name="correo" required 
                       placeholder="Ingrese su correo">
            </div>
            
            <div class="grupo-formulario">
                <label for="clave">Clave</label>
                <input type="password" id="clave" name="clave" required 
                       placeholder="Ingrese su clave">
            </div>
            
            <div id="mensajeError" class="mensaje-error" style="display: none;"></div>
            
            <button type="submit" class="btn-primario btn-completo">Iniciar Sesion</button>
        </form>
    </div>
    
    <script src="/SP%20Perfect%20Color/assets/js/login.js"></script>
</body>
</html>