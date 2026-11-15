<?php
// VISTA: error404View.php
// OBJETIVO: Página 404 con diseño independiente para rutas no encontradas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - SP Perfect Color</title>
    <meta name="description" content="La página solicitada no está disponible - SP Perfect Color.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="SP Perfect Color">
    <link rel="icon" type="image/webp" href="<?php echo url(); ?>assets/images/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script src="<?php echo url(); ?>assets/js/csrf.js"></script>
</head>
<body class="error-404-page">
    <div class="error-card">
        <h1>404</h1>
        <p>La página solicitada no fue encontrada.</p>
        <a href="<?php echo url(); ?>dashboard" class="btn">Volver al Inicio</a>
    </div>
</body>
</html>
