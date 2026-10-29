// Archivo: ui.js
// Skeleton de carga al cambiar de módulo y resaltado de filas encontradas en las DataTables

(function () {
    const BASE = '/SP%20Perfect%20Color/';
    const SKELETON = '<div class="skeleton-page" aria-hidden="true">' +
        '<div class="sk sk-titulo"></div>' +
        '<div class="sk-tarjeta">' +
        '<div class="sk-barra"><div class="sk sk-input"></div><div class="sk sk-boton"></div></div>' +
        Array.from({ length: 7 }, () => '<div class="sk-fila"><div class="sk sk-c1"></div><div class="sk sk-c2"></div><div class="sk sk-c3"></div><div class="sk sk-c4"></div></div>').join('') +
        '</div></div>';

    function mostrarSkeleton() {
        const contenedor = document.querySelector('main > .container-fluid');
        if (!contenedor || contenedor.querySelector(':scope > .skeleton-page')) return;
        contenedor.classList.add('cargando');
        contenedor.insertAdjacentHTML('beforeend', SKELETON);
    }

    function quitarSkeleton() {
        document.querySelectorAll('.skeleton-page').forEach(el => el.remove());
        document.querySelector('main > .container-fluid')?.classList.remove('cargando');
    }

    document.addEventListener('click', function (e) {
        const enlace = e.target.closest('a[href]');
        if (!enlace || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || enlace.target === '_blank' || enlace.hasAttribute('download')) return;
        const destino = new URL(enlace.href, location.href);
        if (destino.origin !== location.origin || !destino.pathname.startsWith(BASE) || destino.pathname.includes('/salir')) return;
        if (destino.pathname === location.pathname && destino.search === location.search) return;
        if (enlace.getAttribute('href') === '#' || enlace.matches('[data-bs-toggle]')) return;
        mostrarSkeleton();
    });
    window.addEventListener('pageshow', quitarSkeleton);

    // Con una búsqueda activa, las filas que coinciden quedan resaltadas
    if (window.jQuery && $.fn.dataTable) {
        $(document).on('draw.dt', function (e, settings) {
            const tabla = new $.fn.dataTable.Api(settings);
            const hayBusqueda = (tabla.search() || '').trim() !== '';
            $(tabla.table().body()).find('tr').toggleClass('fila-encontrada', hayBusqueda);
        });
    }
})();
