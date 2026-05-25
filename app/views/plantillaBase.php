<!-- Archivo: plantillaBase.php -->
<!-- Plantilla base HTML que comparten todas las vistas del sistema -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Perfect Color - Sistema de Gestion</title>
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css">

</head>
<body>
    <div class="contenedor-principal">
        <!-- Barra lateral de navegacion -->
        <aside class="barra-lateral">
            <div class="logo-empresa">
                <h1>SP Perfect Color</h1>
            </div>
              <nav class="menu-navegacion">
                  <ul>
                      <li><a href="/SP%20Perfect%20Color/dashboard">Inicio</a></li>
                      
                      <li><a href="/SP%20Perfect%20Color/cliente">Clientes</a></li>
                      <li><a href="/SP%20Perfect%20Color/proveedor">Proveedores</a></li>
                      <li><a href="/SP%20Perfect%20Color/inventario">Inventario</a></li>
                      <li><a href="/SP%20Perfect%20Color/presupuesto">Presupuestos</a></li>
                      <li><a href="/SP%20Perfect%20Color/notaEntrega">Notas de Entrega</a></li>
                      <li><a href="/SP%20Perfect%20Color/cuentaCobrar">Cuentas por Cobrar</a></li>
                      <li><a href="/SP%20Perfect%20Color/cuentaPagar">Cuentas por Pagar</a></li>
                  </ul>
              </nav>
              <section class="info-usuario">
                  <div class="usuario-info">
                      <?php if (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], [1, 2])): ?>
                          <span class="usuario-nombre"><a href="/SP%20Perfect%20Color/usuario"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></a></span>
                      <?php else: ?>
                          <span class="usuario-nombre"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></span>
                      <?php endif; ?>
                  </div>
                  <a href="/SP%20Perfect%20Color/login/salir" class="btn-cerrar-sesion">Cerrar Sesión</a>
              </section>
        </aside>
        
        <!-- Contenido principal donde se cargan las vistas -->
        <main class="contenido-principal">
            <?php 
            if (isset($contenidoVista)) {
                include $contenidoVista;
            }
            ?>
        </main>
    </div>
    
    <!-- Scripts comunes -->
    <script src="/SP%20Perfect%20Color/assets/js/utilidades.js"></script>
</body>
</html>