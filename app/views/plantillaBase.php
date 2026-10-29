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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="/SP%20Perfect%20Color/assets/css/estiloUI.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/estiloUI.css'); ?>">
    <script>
        // Mientras haya peticiones en curso la vista muestra el esqueleto; se retira cuando todo termina de cargar
        (function () {
            var raiz = document.documentElement, pendientes = 0, espera, listo = false;
            raiz.classList.add('js-cargando');
            function terminar() {
                if (listo) return;
                listo = true;
                raiz.classList.remove('js-cargando');
                document.querySelectorAll('.skeleton-page').forEach(function (el) { el.remove(); });
                var c = document.querySelector('main > .container-fluid');
                if (c) c.classList.remove('cargando');
            }
            function revisar() {
                clearTimeout(espera);
                if (pendientes === 0 && document.readyState === 'complete') {
                    espera = setTimeout(function () { if (pendientes === 0) terminar(); }, 250);
                }
            }
            var fetchOriginal = window.fetch;
            if (fetchOriginal) {
                window.fetch = function () {
                    pendientes++;
                    return fetchOriginal.apply(this, arguments).finally(function () { pendientes--; revisar(); });
                };
            }
            var enviarOriginal = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function () {
                pendientes++;
                this.addEventListener('loadend', function () { pendientes--; revisar(); }, { once: true });
                return enviarOriginal.apply(this, arguments);
            };
            window.addEventListener('load', revisar);
            setTimeout(terminar, 8000);
        })();
    </script>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <script src="/SP%20Perfect%20Color/assets/js/csrf.js"></script>
</head>
<body>
    <div class="d-flex" style="height:100vh;height:100dvh;">
        <!-- Offcanvas sidebar (mobile) -->
        <div class="offcanvas offcanvas-start sidebar-offcanvas text-white" tabindex="-1" id="offcanvasSidebar">
            <div class="offcanvas-header border-bottom border-white border-opacity-10">
                <div class="d-flex align-items-center gap-2">
                    <img src="/SP%20Perfect%20Color/assets/images/logo-recortado.webp" alt="SP Perfect Color" height="48">
                    <div>
                        <h5 class="offcanvas-title fw-bold">SP Perfect Color</h5>
                        <small class="text-white-50" style="font-size:0.7rem;">Sistema de Gestión</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            </div>
            <div class="offcanvas-body p-0 d-flex flex-column">
                <nav class="mt-2">
                    <?php $claseMenu = ''; require __DIR__ . '/parciales/menuLateral.php'; ?>
                </nav>
                <div class="sidebar-user">
                    <a href="/SP%20Perfect%20Color/perfil" class="user-name" title="Ver mi perfil"><?php if (!empty($_SESSION['usuario_foto'])): ?><img src="/SP%20Perfect%20Color/assets/uploads/avatares/<?php echo rawurlencode($_SESSION['usuario_foto']); ?>" alt="" onerror="this.outerHTML='&lt;i class=&quot;bi bi-person-circle&quot;&gt;&lt;/i&gt;'" class="user-avatar me-1"><?php else: ?><i class="bi bi-person-circle me-1"></i><?php endif; ?><span class="user-nombre"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span></a>
                    <a href="/SP%20Perfect%20Color/login/salir" class="logout-link"><i class="bi bi-box-arrow-left me-1"></i>Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <!-- Desktop sidebar: plegado por defecto, se expande al pasar el cursor -->
        <nav class="sidebar d-none d-lg-block" aria-label="Menú principal">
            <div class="sidebar-panel" id="sidebarPanel" data-open="false">
                <script>try { if (sessionStorage.getItem('sbAbierto') === '1') { var sp = document.getElementById('sidebarPanel'); sp.dataset.open = 'true'; sp.parentElement.classList.add('sin-transicion'); } } catch (e) {}</script>
                <div class="sidebar-brand">
                    <img src="/SP%20Perfect%20Color/assets/images/logo-recortado.webp" alt="SP Perfect Color" class="sidebar-logo">
                    <div class="sidebar-brand-text">
                        <h5>SP Perfect Color</h5>
                        <small>Sistema de Gestión</small>
                    </div>
                </div>
                <?php $claseMenu = 'sidebar-nav'; require __DIR__ . '/parciales/menuLateral.php'; ?>
                <div class="sidebar-user">
                    <a href="/SP%20Perfect%20Color/perfil" class="user-name" title="Ver mi perfil"><?php if (!empty($_SESSION['usuario_foto'])): ?><img src="/SP%20Perfect%20Color/assets/uploads/avatares/<?php echo rawurlencode($_SESSION['usuario_foto']); ?>" alt="" onerror="this.outerHTML='&lt;i class=&quot;bi bi-person-circle&quot;&gt;&lt;/i&gt;'" class="user-avatar"><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?><span class="user-nombre"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span></a>
                    <a href="/SP%20Perfect%20Color/login/salir" class="logout-link" title="Cerrar sesión"><i class="bi bi-box-arrow-left"></i><span>Cerrar Sesión</span></a>
                </div>
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
                    <img src="/SP%20Perfect%20Color/assets/images/logo-recortado.webp" alt="SP Perfect Color" height="36" class="me-2">
                    <span class="navbar-brand mb-0 fs-6 fw-bold">SP Perfect Color</span>
                </div>
            </nav>

            <div class="container-fluid p-3 p-lg-4 cargando">
                <?php require __DIR__ . '/parciales/skeleton.php'; ?>
                <?php if (isset($contenidoVista)) include $contenidoVista; ?>
            </div>
        </main>
    </div>
    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/SP%20Perfect%20Color/assets/js/utilidades.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/utilidades.js'); ?>"></script>
    <script src="/SP%20Perfect%20Color/assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/ui.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar: se expande con el cursor o el foco y se pliega al salir
            var panel = document.getElementById('sidebarPanel');
            if (panel) {
                var abrir = function() { panel.dataset.open = 'true'; };
                var cerrar = function() {
                    panel.dataset.open = 'false';
                    try { sessionStorage.removeItem('sbAbierto'); } catch (e) {}
                };
                requestAnimationFrame(function() { requestAnimationFrame(function() { panel.parentElement.classList.remove('sin-transicion'); }); });
                if (panel.dataset.open === 'true') {
                    // Abierto por la navegación anterior: solo se mantiene mientras el cursor siga encima
                    var revisarCursor = function(e) {
                        document.removeEventListener('mousemove', revisarCursor);
                        if (!panel.contains(e.target)) cerrar();
                    };
                    document.addEventListener('mousemove', revisarCursor);
                    setTimeout(function() { if (!panel.matches(':hover')) cerrar(); }, 1500);
                }
                panel.addEventListener('click', function(e) {
                    if (e.target.closest('a[href]:not([href="#"])')) { try { sessionStorage.setItem('sbAbierto', '1'); } catch (er) {} }
                });
                panel.addEventListener('mouseenter', abrir);
                panel.addEventListener('mouseleave', cerrar);
                panel.addEventListener('focusin', abrir);
                panel.addEventListener('focusout', function(e) {
                    if (!panel.contains(e.relatedTarget)) cerrar();
                });
            }
            document.querySelectorAll('.dropdown-hover').forEach(function(item) {
                if (item.querySelector('.dropdown-submenu a.active')) {
                    item.classList.add('open');
                    item.querySelector(':scope > .nav-link').setAttribute('aria-expanded', 'true');
                }
            });

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
                if (e.target.closest('.sidebar-panel')) return;
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

    <template id="plantillaSkeleton"><?php require __DIR__ . '/parciales/skeleton.php'; ?></template>

    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmacionTitulo">Confirmar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modalConfirmacionCuerpo"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="button" class="btn btn-danger" id="modalConfirmacionBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
