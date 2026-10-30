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

    // Referencias bancarias: solo los últimos 5 dígitos del comprobante
    document.addEventListener('DOMContentLoaded', function () {
        ['referenciaPago', 'referenciaPagoInicial', 'referenciaInicialCompra'].forEach(function (id) {
            const campo = document.getElementById(id);
            if (!campo) return;
            campo.maxLength = 5;
            campo.inputMode = 'numeric';
            campo.autocomplete = 'off';
            campo.pattern = '\\d{5}';
            campo.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 5);
            });
        });
    });

    // Al terminar de expandir o plegar el sidebar cambia el ancho útil: reajustar las tablas
    document.querySelector('.sidebar')?.addEventListener('transitionend', function (e) {
        if (e.propertyName === 'width' && window.jQuery && $.fn.dataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
        }
    });

    // Validación nativa de formularios: en lugar del globo del navegador, el campo vibra en rojo y muestra el aviso debajo
    let primerInvalido = null;

    function textoAviso(el) {
        return el.validity.valueMissing ? (el.type === 'checkbox' ? 'Debes marcar esta opción' : 'Este campo es obligatorio') : el.validationMessage;
    }

    function quitarAviso(el) {
        el.classList.remove('is-invalid', 'campo-vibra');
        const anclaje = el.closest('.input-group') || el;
        const aviso = anclaje.nextElementSibling;
        if (aviso && aviso.classList.contains('aviso-campo')) aviso.remove();
    }

    function marcarInvalido(el) {
        const anclaje = el.closest('.input-group') || el;
        quitarAviso(el);
        el.classList.add('is-invalid');
        void el.offsetWidth;
        el.classList.add('campo-vibra');
        const aviso = document.createElement('div');
        aviso.className = 'aviso-campo';
        aviso.setAttribute('role', 'alert');
        aviso.innerHTML = '<i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span></span>';
        aviso.lastChild.textContent = textoAviso(el);
        anclaje.insertAdjacentElement('afterend', aviso);
        (el.closest('.input-group') || el).classList.add('campo-vibra');
        setTimeout(() => (el.closest('.input-group') || el).classList.remove('campo-vibra'), 500);
    }

    document.addEventListener('invalid', function (e) {
        e.preventDefault();
        marcarInvalido(e.target);
        if (!primerInvalido) {
            primerInvalido = e.target;
            e.target.focus();
            setTimeout(() => { primerInvalido = null; }, 0);
        }
    }, true);

    ['input', 'change'].forEach(function (evento) {
        document.addEventListener(evento, function (e) {
            if (e.target.classList?.contains('is-invalid') && e.target.validity?.valid) quitarAviso(e.target);
        });
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

// Gráfico de barras en SVG plano: crece desde la base al entrar en pantalla, resalta la barra bajo el cursor
// y se maneja con teclado (flechas, Inicio y Fin). Sin librerías.
(function () {
    const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
    const NS = 'http://www.w3.org/2000/svg';
    const r2 = (v) => Math.round(v * 100) / 100;

    function rutaBarra(x, y, w, h, radio) {
        if (w <= 0 || h <= 0) return '';
        const r = Math.max(0, Math.min(radio, w / 2, h));
        return 'M' + r2(x) + ' ' + r2(y + h) + ' L' + r2(x) + ' ' + r2(y + r) + ' A' + r + ' ' + r + ' 0 0 1 ' + r2(x + r) + ' ' + r2(y) +
            ' L' + r2(x + w - r) + ' ' + r2(y) + ' A' + r + ' ' + r + ' 0 0 1 ' + r2(x + w) + ' ' + r2(y + r) + ' L' + r2(x + w) + ' ' + r2(y + h) + ' Z';
    }

    function crearGraficoBarras(raiz, opciones) {
        const valores = opciones.valores;
        const etiquetas = opciones.etiquetas;
        const nombre = opciones.nombre || '';
        const formato = opciones.formato || String;
        const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const idGrad = 'gbGrad' + Math.random().toString(36).slice(2, 7);
        let activo = valores.length - 1;
        let interactuando = false;
        let entro = false;
        let barras = [];

        raiz.innerHTML = '<div class="gb-plot" tabindex="0" role="slider"><svg class="gb-svg" aria-hidden="true"></svg><div class="gb-valores" aria-hidden="true"></div>' +
            '<div class="gb-tip" aria-hidden="true"><div class="gb-tip-etiqueta"></div><div class="gb-tip-fila"><i class="gb-punto"></i><span class="gb-tip-nombre"></span><b class="gb-tip-valor"></b></div></div></div>' +
            '<div class="gb-eje" aria-hidden="true"></div>';
        const plot = raiz.querySelector('.gb-plot');
        const svg = raiz.querySelector('.gb-svg');
        const capaValores = raiz.querySelector('.gb-valores');
        const tip = raiz.querySelector('.gb-tip');
        const eje = raiz.querySelector('.gb-eje');
        raiz.querySelector('.gb-tip-nombre').textContent = nombre;
        plot.setAttribute('aria-label', raiz.getAttribute('aria-label') || nombre);
        plot.setAttribute('aria-valuemin', 1);
        plot.setAttribute('aria-valuemax', valores.length);

        const maximo = Math.max(0, ...valores);
        const sinDatos = maximo === 0;

        function dibujar() {
            const ancho = plot.clientWidth;
            const alto = plot.clientHeight;
            if (!ancho || !alto) return;
            svg.setAttribute('width', ancho);
            svg.setAttribute('height', alto);
            svg.setAttribute('viewBox', '0 0 ' + ancho + ' ' + alto);
            const sala = 26;
            const banda = ancho / valores.length;
            const grosor = Math.min(banda * 0.62, 84);
            const cero = alto - 1;
            const tope = (maximo || 1);
            let html = '<defs><linearGradient id="' + idGrad + '" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#3B82F6"/><stop offset="100%" stop-color="#1D4ED8"/></linearGradient></defs>';
            barras = valores.map(function (v, i) {
                const centro = i * banda + banda / 2;
                const altoBarra = v > 0 ? Math.max(3, (v / tope) * (alto - sala - 2)) : 0;
                html += '<path class="gb-barra" data-i="' + i + '" d="' + rutaBarra(centro - grosor / 2, cero - altoBarra, grosor, altoBarra, 7) + '" fill="url(#' + idGrad + ')"/>';
                return { centro: centro, punta: cero - altoBarra, v: v };
            });
            html += '<line x1="0" x2="' + ancho + '" y1="' + cero + '" y2="' + cero + '" stroke="#e2e8f0" stroke-width="1"/>';
            svg.innerHTML = html;
            svg.querySelectorAll('.gb-barra').forEach(function (b) {
                b.style.transformBox = 'fill-box';
                b.style.transformOrigin = '50% 100%';
                if (!entro && !reducir) b.style.opacity = 0;
            });
            capaValores.innerHTML = barras.map(function (b, i) {
                return '<span class="gb-valor" style="left:' + r2((b.centro / ancho) * 100) + '%;top:' + r2(b.punta) + 'px">' + (b.v > 0 ? formato(b.v) : '') + '</span>';
            }).join('');
            if (!entro && !reducir) capaValores.style.opacity = 0;
            eje.innerHTML = etiquetas.map(function (e, i) {
                return '<span style="left:' + r2(((i * banda + banda / 2) / ancho) * 100) + '%">' + e + '</span>';
            }).join('');
            actualizar();
        }

        function actualizar() {
            const b = barras[activo];
            if (!b) return;
            svg.querySelectorAll('.gb-barra').forEach(function (el, i) {
                el.classList.toggle('atenuada', interactuando && i !== activo);
            });
            const ancho = plot.clientWidth;
            const volteo = b.centro > ancho / 2;
            tip.classList.toggle('izq', volteo);
            tip.style.transform = 'translate3d(' + r2(b.centro) + 'px,' + r2(Math.max(24, b.punta)) + 'px,0)';
            tip.classList.toggle('abierto', interactuando);
            capaValores.querySelectorAll('.gb-valor').forEach(function (el, i) { el.style.visibility = interactuando && i === activo ? 'hidden' : ''; });
            raiz.querySelector('.gb-tip-etiqueta').textContent = etiquetas[activo];
            raiz.querySelector('.gb-tip-valor').textContent = formato(b.v);
            plot.setAttribute('aria-valuenow', activo + 1);
            plot.setAttribute('aria-valuetext', etiquetas[activo] + ': ' + formato(b.v));
        }

        function elegir(i) {
            const n = Math.min(Math.max(i, 0), valores.length - 1);
            if (n === activo) return;
            activo = n;
            actualizar();
        }

        function entrar() {
            if (entro) return;
            entro = true;
            capaValores.style.transition = 'opacity 0.4s ease 0.5s';
            capaValores.style.opacity = 1;
            svg.querySelectorAll('.gb-barra').forEach(function (b, i) {
                b.style.opacity = 1;
                if (reducir) return;
                b.animate([{ transform: 'scaleY(0)' }, { transform: 'scaleY(1)' }], { duration: 650, delay: i * 70, easing: EASE, fill: 'backwards' });
            });
        }

        plot.addEventListener('pointermove', function (e) {
            const caja = plot.getBoundingClientRect();
            interactuando = true;
            elegir(Math.floor(((e.clientX - caja.left) / caja.width) * valores.length));
            actualizar();
        });
        plot.addEventListener('pointerleave', function () { interactuando = false; actualizar(); });
        plot.addEventListener('focus', function () { interactuando = true; actualizar(); });
        plot.addEventListener('blur', function () { interactuando = false; actualizar(); });
        plot.addEventListener('keydown', function (e) {
            const pasos = { ArrowLeft: activo - 1, ArrowDown: activo - 1, ArrowRight: activo + 1, ArrowUp: activo + 1, Home: 0, End: valores.length - 1 };
            if (!(e.key in pasos)) return;
            e.preventDefault();
            interactuando = true;
            elegir(pasos[e.key]);
            actualizar();
        });

        dibujar();
        if ('ResizeObserver' in window) new ResizeObserver(dibujar).observe(plot);
        if ('IntersectionObserver' in window && !reducir) {
            const obs = new IntersectionObserver(function (es) {
                if (es[0].isIntersecting) { obs.disconnect(); entrar(); }
            }, { threshold: 0.35 });
            obs.observe(raiz);
        } else {
            entrar();
        }
        if (sinDatos) {
            const aviso = document.createElement('p');
            aviso.className = 'gb-vacio';
            aviso.textContent = 'Aún no hay ingresos registrados en estos días.';
            raiz.appendChild(aviso);
        }
    }

    // Ingresos diarios de los últimos 7 días (los días sin pagos cuentan 0)
    window.inicializarGraficoIngresos = function (datos) {
        const raiz = document.getElementById('graficoIngresos');
        if (!raiz) return;
        const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        const dos = (n) => String(n).padStart(2, '0');
        const fechas = [], valores = [], etiquetas = [];
        for (let i = 6; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            fechas.push(d.getFullYear() + '-' + dos(d.getMonth() + 1) + '-' + dos(d.getDate()));
            valores.push(0);
            etiquetas.push(i === 0 ? 'Hoy' : dias[d.getDay()] + ' ' + dos(d.getDate()));
        }
        (datos || []).forEach(function (fila) {
            const idx = fechas.indexOf(fila.fecha);
            if (idx !== -1) valores[idx] = parseFloat(fila.total) || 0;
        });
        crearGraficoBarras(raiz, {
            valores: valores,
            etiquetas: etiquetas,
            nombre: 'Ingresos',
            formato: (v) => '$ ' + v.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        });
    };
})();
