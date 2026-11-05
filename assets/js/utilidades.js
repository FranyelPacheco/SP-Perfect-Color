// Archivo: utilidades.js
// Funciones utilitarias comunes para todas las vistas

window.DATATABLES_SPANISH = {
    "emptyTable": "Aún no hay registros para mostrar",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
    "infoFiltered": "(filtrado de _MAX_ registros totales)",
    "lengthMenu": "Mostrar _MENU_ registros",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente <i class=\"bi bi-chevron-right\"></i>", "previous": "<i class=\"bi bi-chevron-left\"></i> Anterior" }
};

// Valores por defecto de todas las DataTables: idioma, columnas plegables y prioridad a la primera columna y a Acciones
if (window.jQuery && $.fn.dataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
        language: window.DATATABLES_SPANISH,
        autoWidth: false,
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: -1 }
        ]
    });

    // Los datos llegan por AJAX después de crear la tabla: recalcular qué columnas caben tras cada dibujado
    let recalculando = false;
    $(document).on('draw.dt', function (e, settings) {
        const tabla = new $.fn.dataTable.Api(settings);
        if (recalculando || !tabla.responsive) return;
        recalculando = true;
        requestAnimationFrame(function () {
            tabla.columns.adjust().responsive.recalc();
            recalculando = false;
        });
    });
}

const BASE_URL = '/SP%20Perfect%20Color/';

function escapeHtml(texto) {
    return String(texto ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function debounce(fn, ms = 300) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}

// Token CSRF en toda petición $.ajax no-GET al mismo origen
$.ajaxPrefilter(function (opciones, originales, xhr) {
    const token = $('meta[name="csrf-token"]').attr('content');
    const tipo = (opciones.type || 'GET').toUpperCase();
    if (token && !opciones.crossDomain && tipo !== 'GET' && tipo !== 'HEAD') xhr.setRequestHeader('X-CSRF-Token', token);
});

// Petición JSON: resuelve también con el cuerpo JSON de respuestas 4xx/5xx y expone abort()
function peticionJson(opciones) {
    const xhr = $.ajax($.extend({ dataType: 'json' }, opciones));
    const promesa = new Promise(function (ok, fallo) {
        xhr.done(ok).fail(function (x, estado) {
            if (x.responseJSON) return ok(x.responseJSON);
            const error = new Error(estado);
            error.name = estado === 'abort' ? 'AbortError' : 'Error';
            fallo(error);
        });
    });
    promesa.abort = function () { xhr.abort(); };
    return promesa;
}

// Peticiones JSON a la app: api.get('cliente/obtener', {id: 1}), api.post('cliente/guardar', formDataOObjeto)
const api = {
    get(ruta, params = {}, opciones = {}) {
        return peticionJson($.extend({ url: BASE_URL + ruta, data: params }, opciones));
    },
    post(ruta, datos = {}) {
        const config = { url: BASE_URL + ruta, type: 'POST', data: datos };
        if (datos instanceof FormData) { config.processData = false; config.contentType = false; }
        return peticionJson(config);
    }
};

// Toast tipo SwipeToast (React Bits) en JS plano: sube desde abajo, mecha que se consume, pausa al pasar el cursor y se descarta deslizando hacia abajo o con Escape. Estilos en estiloUI.css
const TOAST_TIPOS = {
    exito: { icono: 'bi-check-circle-fill', mecha: '#22c55e' },
    error: { icono: 'bi-exclamation-octagon-fill', mecha: '#f87171' },
    info: { icono: 'bi-info-circle-fill', mecha: '#60a5fa' }
};

function mostrarNotificacion(mensaje, tipo, descripcion) {
    const t = TOAST_TIPOS[tipo] ? tipo : 'info';
    const duracion = t === 'error' ? 5500 : 3500;
    let $contenedor = $('#toastContenedor');
    if (!$contenedor.length) {
        $contenedor = $('<div id="toastContenedor" class="toast-contenedor"></div>').appendTo('body');
    }

    const $raiz = $('<div class="swipe-toast"></div>').attr('data-tipo', t).html('<div class="swipe-toast__gate"><div class="swipe-toast__lift">' +
        '<div class="swipe-toast__card" role="' + (t === 'error' ? 'alert' : 'status') + '" tabindex="0">' +
        '<i class="bi ' + TOAST_TIPOS[t].icono + ' swipe-toast__icon" aria-hidden="true"></i>' +
        '<span class="swipe-toast__body"><span class="swipe-toast__title"></span><span class="swipe-toast__desc"></span></span>' +
        '<button type="button" class="swipe-toast__close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>' +
        '<i class="swipe-toast__fuse" aria-hidden="true"></i></div></div></div>');
    $raiz.find('.swipe-toast__title').text(mensaje);
    const $desc = $raiz.find('.swipe-toast__desc');
    if (descripcion) $desc.text(descripcion); else $desc.remove();
    $contenedor.append($raiz);

    const $tarjeta = $raiz.find('.swipe-toast__card');
    const tarjeta = $tarjeta[0];
    const mecha = $raiz.find('.swipe-toast__fuse')[0];
    const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let cerrado = false;
    let arrastre = null;
    let pausado = false;

    const anim = mecha.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: duracion, easing: 'linear', fill: 'forwards' });
    const sincronizar = () => { if (pausado || arrastre) anim.pause(); else if (!cerrado) anim.play(); };

    const quitar = () => {
        $raiz.attr('data-phase', 'gone');
        setTimeout(() => $raiz.remove(), 220);
    };
    const cerrar = (deslizado) => {
        if (cerrado) return;
        cerrado = true;
        anim.pause();
        if (deslizado) { quitar(); return; }
        $raiz.attr('data-phase', 'closing');
        setTimeout(quitar, 280);
    };
    anim.onfinish = () => cerrar(false);

    $tarjeta.on('pointerenter', e => { if (e.originalEvent.pointerType === 'mouse') { pausado = true; sincronizar(); } });
    $tarjeta.on('pointerleave', e => { if (e.originalEvent.pointerType === 'mouse') { pausado = false; sincronizar(); } });
    $tarjeta.on('focus', () => { pausado = true; sincronizar(); });
    $tarjeta.on('blur', () => { pausado = false; sincronizar(); });
    $tarjeta.on('keydown', e => { if (e.key === 'Escape') cerrar(false); });
    $raiz.find('.swipe-toast__close').on('click', () => cerrar(false));

    $tarjeta.on('pointerdown', e => {
        const ev = e.originalEvent;
        if (ev.button !== 0 || cerrado || $(ev.target).closest('button').length) return;
        tarjeta.setPointerCapture(ev.pointerId);
        arrastre = { id: ev.pointerId, y0: ev.clientY, dy: 0, hist: [[performance.now(), 0]] };
        $tarjeta.css('transition', 'none');
        sincronizar();
    });
    $tarjeta.on('pointermove', e => {
        const ev = e.originalEvent;
        if (!arrastre || arrastre.id !== ev.pointerId) return;
        const raw = ev.clientY - arrastre.y0;
        arrastre.dy = raw >= 0 ? raw : -(24 * Math.abs(raw)) / (24 + 0.55 * Math.abs(raw)) * 0.55;
        $tarjeta.css('transform', 'translateY(' + arrastre.dy + 'px)');
        arrastre.hist.push([performance.now(), arrastre.dy]);
        if (arrastre.hist.length > 4) arrastre.hist.shift();
    });
    const soltar = e => {
        if (!arrastre || arrastre.id !== e.originalEvent.pointerId) return;
        const { dy, hist } = arrastre;
        arrastre = null;
        const [t0, y0] = hist[0];
        const [t1, y1] = hist[hist.length - 1];
        const v = hist.length > 1 && performance.now() - t1 < 100 ? (y1 - y0) / Math.max(1, t1 - t0) : 0;
        if (dy > 0 && (v > 0.11 || (dy >= 40 && v >= 0))) {
            cerrado = true;
            anim.pause();
            $tarjeta.css({
                transition: 'transform 0.25s cubic-bezier(0.23,1,0.32,1), opacity 0.2s ease',
                transform: 'translateY(' + (dy + tarjeta.offsetHeight) + 'px)',
                opacity: '0'
            });
            setTimeout(quitar, 200);
            return;
        }
        $tarjeta.css({
            transition: reducir ? 'transform 0.2s ease' : 'transform 0.5s cubic-bezier(0.34,1.4,0.64,1)',
            transform: 'translateY(0)'
        });
        sincronizar();
    };
    $tarjeta.on('pointerup pointercancel', soltar);

    requestAnimationFrame(() => requestAnimationFrame(() => { $raiz.attr('data-mounted', 'true'); }));
}

// Enlaza un input de búsqueda con el filtro global de una DataTable
function vincularBusqueda(input, selectorTabla) {
    $(input).on('keyup', function () {
        if ($.fn.DataTable.isDataTable(selectorTabla)) {
            $(selectorTabla).DataTable().search(this.value).draw();
        }
    });
}

// Delegación de clic para botones de tabla: evita el submit/enlace por defecto y cierra el tooltip antes de ejecutar
function alHacerClick(selector, fn) {
    $(document).on('click', selector, function(e) {
        e.preventDefault();
        bootstrap.Tooltip.getInstance(this)?.hide();
        fn.call(this, e);
    });
}

// Restringe un input a letras y espacios (teclado, pegado y autocompletado)
function restringirSoloLetras(input) {
    const $input = $(input);
    if (!$input.length) return;

    $input.on('keydown', function(e) {
        if (['Backspace', 'Tab', 'Enter', 'Escape', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) || e.ctrlKey || e.metaKey || e.altKey) {
            return;
        }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(e.key)) {
            e.preventDefault();
        }
    });

    $input.on('beforeinput', function(e) {
        const dato = e.originalEvent.data;
        if (dato && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]*$/.test(dato)) {
            e.preventDefault();
        }
    });

    $input.on('input', function() {
        const cursorPos = this.selectionStart;
        const limpio = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
        if (this.value !== limpio) {
            this.value = limpio;
            if (cursorPos !== null) {
                this.setSelectionRange(cursorPos - 1, cursorPos - 1);
            }
        }
    });

    $input.on('paste', function(e) {
        const textoPegado = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        if (textoPegado && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(textoPegado)) {
            e.preventDefault();
            document.execCommand('insertText', false, textoPegado.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, ''));
        }
    });
}

// Validación en vivo de campos únicos contra validacion/unico.
// Marca is-invalid/is-valid, muestra el mensaje en feedback y deshabilita el botón si el formulario tiene errores.
function validarUnico({ input, feedback, entidad, campo, getValor, getId, minLen = 1, formato, msgFormato, boton }) {
    const $input = $(input);
    let peticion;
    const marcar = (estado, mensaje = '') => {
        $input.toggleClass('is-invalid', estado === 'invalid').toggleClass('is-valid', estado === 'valid');
        if (feedback) $(feedback).text(mensaje);
        if (boton) $(boton).prop('disabled', $(boton).closest('form, .modal-content').find('.is-invalid').length > 0);
    };
    const verificar = debounce(async () => {
        const valor = (getValor ? getValor() : $input.val()).trim();
        if (valor.length < minLen) return marcar(null);
        if (formato && !formato.test(valor)) return marcar('invalid', msgFormato || 'Formato inválido');
        peticion?.abort();
        try {
            peticion = api.get('validacion/unico', { entidad, campo, valor, id: getId ? (getId() ?? '') : '' });
            const r = await peticion;
            const d = r.datos || {};
            if (d.existe) marcar('invalid', d.mensaje);
            else if (d.inactivo_id) marcar('valid', 'Existe deshabilitado: al guardar se reactivará');
            else marcar('valid');
        } catch (e) {
            if (e.name !== 'AbortError') marcar(null);
        }
    });
    $input.on('input blur', verificar);
    return { limpiar: () => marcar(null), revalidar: verificar };
}

function confirmarConModal(titulo, mensaje, callback, claseBoton) {
    claseBoton = claseBoton || 'btn-danger';
    const $modal = $('#modalConfirmacion');
    if (!$modal.length) return;
    $('#modalConfirmacionTitulo').text(titulo);
    $('#modalConfirmacionCuerpo').html(mensaje);
    const $btn = $('#modalConfirmacionBtn').attr('class', 'btn ' + claseBoton);
    const $nuevoBtn = $btn.clone();
    $btn.replaceWith($nuevoBtn);
    $nuevoBtn.on('click', function() {
        bootstrap.Modal.getInstance($modal[0]).hide();
        if (typeof callback === 'function') {
            callback();
        }
    });
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
}

// Formatea un numero como moneda (formato venezolano)
function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-VE', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(cantidad);
}

// Animacion de contador para los valores del dashboard con temporizacion real y desaceleracion suave (ease-out)
function animarContador(elemento, valorFinal, duracion) {
    const $el = $(elemento);
    if (!$el.length) return;
    const esMoneda = $el.attr('data-moneda') === '1';

    if (!duracion || duracion <= 0 || valorFinal <= 0) {
        $el.text(esMoneda ? '$ ' + formatearMoneda(valorFinal) : Math.round(valorFinal));
        return;
    }

    let inicioTiempo = null;
    let ultimoEntero = -1;

    function frame(ahora) {
        if (!inicioTiempo) inicioTiempo = ahora;
        const progreso = Math.min((ahora - inicioTiempo) / duracion, 1);
        // Desaceleracion cubica (ease-out-cubic): arranca con energia y frena suavemente
        const factor = 1 - Math.pow(1 - progreso, 3);
        const actual = valorFinal * factor;

        if (esMoneda) {
            $el.text('$ ' + formatearMoneda(actual));
        } else {
            let entero = Math.floor(actual);
            if (progreso === 1) entero = Math.round(valorFinal);
            if (entero !== ultimoEntero) {
                $el.text(entero);
                ultimoEntero = entero;
            }
        }

        if (progreso < 1) {
            requestAnimationFrame(frame);
        } else {
            $el.text(esMoneda ? '$ ' + formatearMoneda(valorFinal) : Math.round(valorFinal));
        }
    }

    requestAnimationFrame(frame);
}

// Inicializar contadores al cargar la pagina
$(function() {
    $('.stat-value[data-valor]').each(function(i) {
        const el = this;
        const valorFinal = parseFloat($(el).attr('data-valor')) || 0;
        // Arranca junto con la entrada escalonada de cada tarjeta
        setTimeout(function() { animarContador(el, valorFinal, 1200); }, 150 + i * 50);
    });
});
