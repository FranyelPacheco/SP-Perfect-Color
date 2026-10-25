<?php
// VISTA: plantillaBase.php
// OBJETIVO: Plantilla base HTML con sidebar, navbar y layout principal
$ctrlLower = strtolower($controlador ?? '');
$isVentasActive = in_array($ctrlLower, ['presupuesto', 'notaentrega', 'cuentacobrar'], true);
$isComprasActive = in_array($ctrlLower, ['notaentregacompra', 'cuentapagar'], true);
$isConfigPagoActive = in_array($ctrlLower, ['banco', 'tipopago'], true);

$tasaCambioGlobal = 0.0;
try {
    $tasaModelGlobal = new \App\Models\TasaCambioModel();
    $tasaCambioGlobal = $tasaModelGlobal->obtenerTasaVigenteValor();
} catch (\Throwable $e) {
    $tasaCambioGlobal = 0.0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SP Perfect Color - Sistema de Gestión'; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Sistema de gestión administrativa para SP Perfect Color'; ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="SP Perfect Color">
    <meta name="generator" content="SP Perfect Color">
    <link rel="canonical" href="<?php echo 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'); ?>">
    <link rel="icon" type="image/webp" href="/SP%20Perfect%20Color/assets/images/logo.webp">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SP Perfect Color - Sistema de Gestión'; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Sistema de gestión administrativa para SP Perfect Color'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SP Perfect Color">
    <meta property="og:locale" content="es_VE">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SP Perfect Color - Sistema de Gestión'; ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Sistema de gestión administrativa para SP Perfect Color'; ?>">

    <!-- JSON-LD Structured Data -->
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
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloBase.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estiloBase.css'); ?>">
    <script>
        window.TASA_CAMBIO_HOY = <?php echo json_encode((float)$tasaCambioGlobal); ?>;
        window.formatearBs = function(montoUsd, tasa) {
            var t = (typeof tasa === 'number' && tasa > 0) ? tasa : (window.TASA_CAMBIO_HOY || 0);
            var m = parseFloat(montoUsd) || 0;
            var bs = m * t;
            return 'Bs. ' + bs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
    </script>
    <style>
        /* Regla de máxima prioridad para garantizar texto blanco en aside móvil */
        #offcanvasSidebar,
        #offcanvasSidebar .offcanvas-body,
        #offcanvasSidebar nav {
            background: linear-gradient(135deg, #0F172A 0%, #1D4ED8 100%) !important;
        }
        #offcanvasSidebar a,
        #offcanvasSidebar a.nav-link,
        #offcanvasSidebar .nav-link,
        #offcanvasSidebar .nav-link span,
        #offcanvasSidebar .nav-link i,
        #offcanvasSidebar .dropdown-submenu a,
        #offcanvasSidebar .sidebar-user a,
        #offcanvasSidebar .sidebar-user span {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }
        #offcanvasSidebar a.nav-link:hover,
        #offcanvasSidebar a.nav-link:focus,
        #offcanvasSidebar a.nav-link.active {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            background: rgba(255, 255, 255, 0.15) !important;
        }
    </style>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script src="/SP%20Perfect%20Color/assets/js/csrf.js"></script>
</head>
<body>
    <div class="d-flex" style="height:100vh;">
        <!-- Offcanvas sidebar (mobile) -->
        <div class="offcanvas offcanvas-start sidebar-offcanvas text-white" tabindex="-1" id="offcanvasSidebar">
            <div class="offcanvas-header border-bottom border-white border-opacity-10">
                <div class="d-flex align-items-center gap-2">
                    <img src="/SP%20Perfect%20Color/assets/images/logo.webp" alt="SP Perfect Color" height="36">
                    <div>
                        <h5 class="offcanvas-title fw-bold">SP Perfect Color</h5>
                        <small class="text-white-50" style="font-size:0.7rem;">Sistema de Gestión</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column">
                <nav class="mt-2">
                    <?php $claseMenu = ''; require __DIR__ . '/parciales/menuLateral.php'; ?>
                </nav>
                <div class="sidebar-user">
                    <a href="/SP%20Perfect%20Color/perfil" class="user-name" title="Ver mi perfil"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></a>
                    <a href="/SP%20Perfect%20Color/login/salir" class="logout-link"><i class="bi bi-box-arrow-left me-1"></i>Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <nav class="sidebar d-none d-lg-flex">
            <div class="sidebar-brand">
                <img src="/SP%20Perfect%20Color/assets/images/logo.webp" alt="SP Perfect Color" class="sidebar-logo">
                <div class="sidebar-brand-text">
                    <h5>SP Perfect Color</h5>
                    <small>Sistema de Gestión</small>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" title="Colapsar sidebar" aria-label="Colapsar sidebar"><i class="bi bi-list"></i></button>
            </div>
            <?php $claseMenu = 'mt-1'; require __DIR__ . '/parciales/menuLateral.php'; ?>
            <div class="sidebar-user">
                <a href="/SP%20Perfect%20Color/perfil" class="user-name" title="Ver mi perfil"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></a>
                <a href="/SP%20Perfect%20Color/login/salir" class="logout-link"><i class="bi bi-box-arrow-left me-1"></i>Cerrar Sesión</a>
            </div>
        </nav>

        <!-- Main content -->
        <main class="flex-grow-1" style="background: #f4f6f9;">
            <!-- Mobile top navbar -->
            <nav class="navbar navbar-dark top-navbar d-lg-none px-3 py-2">
                <div class="d-flex align-items-center w-100">
                    <button class="navbar-toggler border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <img src="/SP%20Perfect%20Color/assets/images/logo.webp" alt="SP Perfect Color" height="28" class="me-2">
                    <span class="navbar-brand mb-0 fs-6 fw-bold">SP Perfect Color</span>
                </div>
            </nav>

            <div class="container-fluid p-3 p-lg-4">
                <?php if (isset($contenidoVista)) include $contenidoVista; ?>
            </div>
        </main>
    </div>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/SP%20Perfect%20Color/assets/js/utilidades.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/utilidades.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            var sidebarToggle = document.getElementById('sidebarToggle');
            var sidebar = document.querySelector('.sidebar');
            var sidebarBrand = document.querySelector('.sidebar-brand');
            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
            }
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            // Click brand area to expand when collapsed
            if (sidebarBrand && sidebar) {
                sidebarBrand.addEventListener('click', function(e) {
                    if (e.target.closest('.sidebar-toggle')) return;
                    if (sidebar.classList.contains('collapsed')) {
                        toggleSidebar();
                    }
                });
            }

            // Submenús desplegables (clic o teclado); aria-expanded y Escape para cerrar
            function cerrarSubmenus(excepto) {
                document.querySelectorAll('.dropdown-hover.open').forEach(function(el) {
                    if (el !== excepto) {
                        el.classList.remove('open');
                        el.querySelector(':scope > .nav-link').setAttribute('aria-expanded', 'false');
                    }
                });
            }
            document.querySelectorAll('.dropdown-hover > .nav-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var abierto = this.parentElement.classList.toggle('open');
                    this.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                });
            });
            document.addEventListener('click', function(e) {
                var padre = e.target.closest('.dropdown-hover');
                cerrarSubmenus(padre);
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') cerrarSubmenus(null);
            });

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });
            $(document).on('draw.dt', function() {
                [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function(el) {
                    if (!bootstrap.Tooltip.getInstance(el)) { new bootstrap.Tooltip(el); }
                });
            });
        });
    </script>

    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmacionTitulo">Confirmar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modalConfirmacionCuerpo"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="modalConfirmacionBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
