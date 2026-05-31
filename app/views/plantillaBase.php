<!-- Archivo: plantillaBase.php -->
<!-- Plantilla base HTML con Bootstrap 5 -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Perfect Color - Sistema de Gestion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css">
</head>
<body>
    <div class="d-flex min-vh-100">
        <!-- Offcanvas sidebar (mobile) -->
        <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="offcanvasSidebar">
            <div class="offcanvas-header border-bottom border-secondary">
                <h5 class="offcanvas-title">SP Perfect Color</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column">
                <nav class="mt-2">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/dashboard" class="nav-link text-white-50">Inicio</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/cliente" class="nav-link text-white-50">Clientes</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/proveedor" class="nav-link text-white-50">Proveedores</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/inventario" class="nav-link text-white-50">Inventario</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/presupuesto" class="nav-link text-white-50">Presupuestos</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/notaEntrega" class="nav-link text-white-50">Notas de Entrega</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/cuentaCobrar" class="nav-link text-white-50">Cuentas por Cobrar</a></li>
                        <li class="nav-item"><a href="/SP%20Perfect%20Color/cuentaPagar" class="nav-link text-white-50">Cuentas por Pagar</a></li>
                    </ul>
                </nav>
                <div class="mt-auto p-3 border-top border-secondary small">
                    <?php if (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], [1, 2])): ?>
                        <a href="/SP%20Perfect%20Color/usuario" class="text-white text-decoration-none d-block mb-2"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></a>
                    <?php else: ?>
                        <span class="text-white-50 d-block mb-2"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></span>
                    <?php endif; ?>
                    <a href="/SP%20Perfect%20Color/login/salir" class="text-danger text-decoration-none small">Cerrar Sesion</a>
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <nav class="bg-dark text-white d-none d-lg-flex flex-column" style="width: 250px; flex-shrink: 0;">
            <div class="p-3 border-bottom border-secondary">
                <h5 class="mb-0 fs-5">SP Perfect Color</h5>
            </div>
            <ul class="nav flex-column mt-2">
                <li class="nav-item"><a href="/SP%20Perfect%20Color/dashboard" class="nav-link text-white-50">Inicio</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/cliente" class="nav-link text-white-50">Clientes</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/proveedor" class="nav-link text-white-50">Proveedores</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/inventario" class="nav-link text-white-50">Inventario</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/presupuesto" class="nav-link text-white-50">Presupuestos</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/notaEntrega" class="nav-link text-white-50">Notas de Entrega</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/cuentaCobrar" class="nav-link text-white-50">Cuentas por Cobrar</a></li>
                <li class="nav-item"><a href="/SP%20Perfect%20Color/cuentaPagar" class="nav-link text-white-50">Cuentas por Pagar</a></li>
            </ul>
            <div class="mt-auto p-3 border-top border-secondary small">
                <?php if (isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], [1, 2])): ?>
                    <a href="/SP%20Perfect%20Color/usuario" class="text-white text-decoration-none d-block mb-2"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></a>
                <?php else: ?>
                    <span class="text-white-50 d-block mb-2"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></span>
                <?php endif; ?>
                <a href="/SP%20Perfect%20Color/login/salir" class="text-danger text-decoration-none small">Cerrar Sesion</a>
            </div>
        </nav>

        <!-- Main content -->
        <main class="flex-grow-1 bg-light">
            <!-- Mobile top navbar -->
            <nav class="navbar navbar-dark bg-dark d-lg-none px-3 py-2">
                <div class="d-flex align-items-center w-100">
                    <button class="navbar-toggler border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <span class="navbar-brand mb-0 fs-6">SP Perfect Color</span>
                </div>
            </nav>

            <div class="container-fluid p-3 p-lg-4">
                <?php if (isset($contenidoVista)) include $contenidoVista; ?>
            </div>
        </main>
    </div>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/SP%20Perfect%20Color/assets/js/utilidades.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });
            $(document).on('draw.dt', function() {
                [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function(el) {
                    if (!bootstrap.Tooltip.getInstance(el)) { new bootstrap.Tooltip(el); }
                });
            });
        });
    </script>
</body>
</html>
