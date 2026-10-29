// Archivo: ui.js
// Skeleton de carga al cambiar de módulo y resaltado de filas encontradas en las DataTables

(function () {
    const BASE = '/SP%20Perfect%20Color/';
    function mostrarSkeleton() {
        const contenedor = document.querySelector('main > .container-fluid');
        if (!contenedor || contenedor.querySelector(':scope > .skeleton-page')) return;
        contenedor.classList.add('cargando');
        const plantilla = document.getElementById('plantillaSkeleton');
        if (plantilla) contenedor.appendChild(plantilla.content.cloneNode(true));
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

    // Al terminar de expandir o plegar el sidebar cambia el ancho útil: reajustar las tablas
    document.querySelector('.sidebar')?.addEventListener('transitionend', function (e) {
        if (e.propertyName === 'width' && window.jQuery && $.fn.dataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
        }
    });

    // Con una búsqueda activa, las filas que coinciden quedan resaltadas
    if (window.jQuery && $.fn.dataTable) {
        const COLUMNA_NUMERICA = /total|monto|saldo|precio|p\. venta|stock|tasa|cantidad/i;
        const COLUMNA_CENTRADA = /^(estado|activo|tipo|condici|presentaci|situaci|rol)/i;
        const COLUMNA_COMPACTA = /fecha|c[eé]dula|rif|c[oó]digo|^id$|^n°|tel[eé]fono|vencimiento|documento/i;

        // Alinea cada columna según lo que contiene: cifras a la derecha, estados al centro y datos cortos sin saltos
        function alinearColumnas(tabla) {
            const encabezados = tabla.columns().header().toArray();
            const filas = $(tabla.table().body()).find('tr:not(.child)');
            encabezados.forEach(function (th, i) {
                if (i === encabezados.length - 1) return;
                const texto = th.textContent.trim();
                const clase = COLUMNA_NUMERICA.test(texto) ? 'text-end td-compacta' : COLUMNA_CENTRADA.test(texto) ? 'text-center' : COLUMNA_COMPACTA.test(texto) ? 'td-compacta' : '';
                if (!clase) return;
                $(th).addClass(clase.replace('td-compacta', '').trim());
                filas.each(function () { $(this).children().eq(i).addClass(clase); });
            });
        }

        $(document).on('draw.dt', function (e, settings) {
            const tabla = new $.fn.dataTable.Api(settings);
            const hayBusqueda = (tabla.search() || '').trim() !== '';
            $(tabla.table().body()).find('tr').toggleClass('fila-encontrada', hayBusqueda);
            alinearColumnas(tabla);
            clearTimeout(tabla.settings()[0]._reajuste);
            tabla.settings()[0]._reajuste = setTimeout(function () { tabla.columns.adjust().responsive.recalc(); }, 300);
        });
    }
})();
