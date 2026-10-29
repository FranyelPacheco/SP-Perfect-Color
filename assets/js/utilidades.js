// Archivo: utilidades.js
// Funciones utilitarias comunes para todas las vistas

window.DATATABLES_SPANISH = {
    "emptyTable": "No hay registros para mostrar",
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

    // Al plegar el sidebar cambia el ancho útil sin evento resize
    $(document).on('click', '#sidebarToggle', function () {
        setTimeout(function () {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
        }, 350);
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

// Peticiones JSON a la app: api.get('cliente/obtener', {id: 1}), api.post('cliente/guardar', formDataOObjeto)
const api = {
    async get(ruta, params = {}, opciones = {}) {
        const query = new URLSearchParams(params).toString();
        const resp = await fetch(BASE_URL + ruta + (query ? '?' + query : ''), opciones);
        return resp.json();
    },
    async post(ruta, datos = {}) {
        const body = datos instanceof FormData ? datos : new URLSearchParams(datos);
        const resp = await fetch(BASE_URL + ruta, { method: 'POST', body });
        return resp.json();
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
    let contenedor = document.getElementById('toastContenedor');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'toastContenedor';
        contenedor.className = 'toast-contenedor';
        document.body.appendChild(contenedor);
    }

    const raiz = document.createElement('div');
    raiz.className = 'swipe-toast';
    raiz.dataset.tipo = t;
    raiz.innerHTML = '<div class="swipe-toast__gate"><div class="swipe-toast__lift">' +
        '<div class="swipe-toast__card" role="' + (t === 'error' ? 'alert' : 'status') + '" tabindex="0">' +
        '<i class="bi ' + TOAST_TIPOS[t].icono + ' swipe-toast__icon" aria-hidden="true"></i>' +
        '<span class="swipe-toast__body"><span class="swipe-toast__title"></span><span class="swipe-toast__desc"></span></span>' +
        '<button type="button" class="swipe-toast__close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>' +
        '<i class="swipe-toast__fuse" aria-hidden="true"></i></div></div></div>';
    raiz.querySelector('.swipe-toast__title').textContent = mensaje;
    const desc = raiz.querySelector('.swipe-toast__desc');
    if (descripcion) desc.textContent = descripcion; else desc.remove();
    contenedor.appendChild(raiz);

    const tarjeta = raiz.querySelector('.swipe-toast__card');
    const mecha = raiz.querySelector('.swipe-toast__fuse');
    const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let cerrado = false;
    let arrastre = null;
    let pausado = false;

    const anim = mecha.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: duracion, easing: 'linear', fill: 'forwards' });
    const sincronizar = () => { if (pausado || arrastre) anim.pause(); else if (!cerrado) anim.play(); };

    const quitar = () => {
        raiz.dataset.phase = 'gone';
        setTimeout(() => raiz.remove(), 220);
    };
    const cerrar = (deslizado) => {
        if (cerrado) return;
        cerrado = true;
        anim.pause();
        if (deslizado) { quitar(); return; }
        raiz.dataset.phase = 'closing';
        setTimeout(quitar, 280);
    };
    anim.onfinish = () => cerrar(false);

    tarjeta.addEventListener('pointerenter', e => { if (e.pointerType === 'mouse') { pausado = true; sincronizar(); } });
    tarjeta.addEventListener('pointerleave', e => { if (e.pointerType === 'mouse') { pausado = false; sincronizar(); } });
    tarjeta.addEventListener('focus', () => { pausado = true; sincronizar(); });
    tarjeta.addEventListener('blur', () => { pausado = false; sincronizar(); });
    tarjeta.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(false); });
    raiz.querySelector('.swipe-toast__close').addEventListener('click', () => cerrar(false));

    tarjeta.addEventListener('pointerdown', e => {
        if (e.button !== 0 || cerrado || e.target.closest('button')) return;
        tarjeta.setPointerCapture(e.pointerId);
        arrastre = { id: e.pointerId, y0: e.clientY, dy: 0, hist: [[performance.now(), 0]] };
        tarjeta.style.transition = 'none';
        sincronizar();
    });
    tarjeta.addEventListener('pointermove', e => {
        if (!arrastre || arrastre.id !== e.pointerId) return;
        const raw = e.clientY - arrastre.y0;
        arrastre.dy = raw >= 0 ? raw : -(24 * Math.abs(raw)) / (24 + 0.55 * Math.abs(raw)) * 0.55;
        tarjeta.style.transform = 'translateY(' + arrastre.dy + 'px)';
        arrastre.hist.push([performance.now(), arrastre.dy]);
        if (arrastre.hist.length > 4) arrastre.hist.shift();
    });
    const soltar = e => {
        if (!arrastre || arrastre.id !== e.pointerId) return;
        const { dy, hist } = arrastre;
        arrastre = null;
        const [t0, y0] = hist[0];
        const [t1, y1] = hist[hist.length - 1];
        const v = hist.length > 1 && performance.now() - t1 < 100 ? (y1 - y0) / Math.max(1, t1 - t0) : 0;
        if (dy > 0 && (v > 0.11 || (dy >= 40 && v >= 0))) {
            cerrado = true;
            anim.pause();
            tarjeta.style.transition = 'transform 0.25s cubic-bezier(0.23,1,0.32,1), opacity 0.2s ease';
            tarjeta.style.transform = 'translateY(' + (dy + tarjeta.offsetHeight) + 'px)';
            tarjeta.style.opacity = '0';
            setTimeout(quitar, 200);
            return;
        }
        tarjeta.style.transition = reducir ? 'transform 0.2s ease' : 'transform 0.5s cubic-bezier(0.34,1.4,0.64,1)';
        tarjeta.style.transform = 'translateY(0)';
        sincronizar();
    };
    tarjeta.addEventListener('pointerup', soltar);
    tarjeta.addEventListener('pointercancel', soltar);

    requestAnimationFrame(() => requestAnimationFrame(() => { raiz.dataset.mounted = 'true'; }));
}

// Enlaza un input de búsqueda con el filtro global de una DataTable
function vincularBusqueda(input, selectorTabla) {
    input?.addEventListener('keyup', function () {
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
    if (!input) return;

    input.addEventListener('keydown', function(e) {
        if (['Backspace', 'Tab', 'Enter', 'Escape', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) || e.ctrlKey || e.metaKey || e.altKey) {
            return;
        }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(e.key)) {
            e.preventDefault();
        }
    });

    input.addEventListener('beforeinput', function(e) {
        if (e.data && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]*$/.test(e.data)) {
            e.preventDefault();
        }
    });

    input.addEventListener('input', function() {
        var cursorPos = this.selectionStart;
        var limpio = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
        if (this.value !== limpio) {
            this.value = limpio;
            if (cursorPos !== null) {
                this.setSelectionRange(cursorPos - 1, cursorPos - 1);
            }
        }
    });

    input.addEventListener('paste', function(e) {
        var textoPegado = (e.clipboardData || window.clipboardData).getData('text');
        if (textoPegado && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(textoPegado)) {
            e.preventDefault();
            var limpio = textoPegado.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            document.execCommand('insertText', false, limpio);
        }
    });
}

// Validación en vivo de campos únicos contra validacion/unico.
// Marca is-invalid/is-valid, muestra el mensaje en feedback y deshabilita el botón si el formulario tiene errores.
function validarUnico({ input, feedback, entidad, campo, getValor, getId, minLen = 1, formato, msgFormato, boton }) {
    let controlador;
    const marcar = (estado, mensaje = '') => {
        input.classList.toggle('is-invalid', estado === 'invalid');
        input.classList.toggle('is-valid', estado === 'valid');
        if (feedback) feedback.textContent = mensaje;
        if (boton) boton.disabled = !!boton.closest('form, .modal-content')?.querySelector('.is-invalid');
    };
    const verificar = debounce(async () => {
        const valor = (getValor ? getValor() : input.value).trim();
        if (valor.length < minLen) return marcar(null);
        if (formato && !formato.test(valor)) return marcar('invalid', msgFormato || 'Formato inválido');
        controlador?.abort();
        controlador = new AbortController();
        try {
            const r = await api.get('validacion/unico', { entidad, campo, valor, id: getId ? (getId() ?? '') : '' }, { signal: controlador.signal });
            const d = r.datos || {};
            if (d.existe) marcar('invalid', d.mensaje);
            else if (d.inactivo_id) marcar('valid', 'Existe deshabilitado: al guardar se reactivará');
            else marcar('valid');
        } catch (e) {
            if (e.name !== 'AbortError') marcar(null);
        }
    });
    input.addEventListener('input', verificar);
    input.addEventListener('blur', verificar);
    return { limpiar: () => marcar(null), revalidar: verificar };
}

function confirmarConModal(titulo, mensaje, callback, claseBoton) {
    claseBoton = claseBoton || 'btn-danger';
    var modal = document.getElementById('modalConfirmacion');
    if (!modal) return;
    document.getElementById('modalConfirmacionTitulo').textContent = titulo;
    document.getElementById('modalConfirmacionCuerpo').innerHTML = mensaje;
    var btn = document.getElementById('modalConfirmacionBtn');
    btn.className = 'btn ' + claseBoton;
    var nuevoBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(nuevoBtn, btn);
    nuevoBtn.addEventListener('click', function() {
        bootstrap.Modal.getInstance(modal).hide();
        if (typeof callback === 'function') {
            callback();
        }
    });
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function confirmarAccion(mensaje, funcionCallback) {
    confirmarConModal('Confirmar', mensaje || '¿Está seguro de realizar esta acción?', funcionCallback);
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
    if (!elemento) return;
    var esMoneda = elemento.getAttribute('data-moneda') === '1';

    if (!duracion || duracion <= 0 || valorFinal <= 0) {
        elemento.textContent = esMoneda ? '$ ' + formatearMoneda(valorFinal) : Math.round(valorFinal);
        return;
    }

    var inicioTiempo = null;
    var ultimoEntero = -1;

    function frame(ahora) {
        if (!inicioTiempo) inicioTiempo = ahora;
        var transcurrido = ahora - inicioTiempo;
        var progreso = Math.min(transcurrido / duracion, 1);
        // Desaceleracion cubica (ease-out-cubic): arranca con energia y frena suavemente
        var factor = 1 - Math.pow(1 - progreso, 3);
        var actual = valorFinal * factor;

        if (esMoneda) {
            elemento.textContent = '$ ' + formatearMoneda(actual);
        } else {
            var entero = Math.floor(actual);
            if (progreso === 1) entero = Math.round(valorFinal);
            if (entero !== ultimoEntero) {
                elemento.textContent = entero;
                ultimoEntero = entero;
            }
        }

        if (progreso < 1) {
            requestAnimationFrame(frame);
        } else {
            elemento.textContent = esMoneda ? '$ ' + formatearMoneda(valorFinal) : Math.round(valorFinal);
        }
    }

    requestAnimationFrame(frame);
}

// Inicializa la grafica de ingresos diarios con Chart.js evitando saltos de diseño (CLS)
function inicializarGraficoIngresos(datos) {
    var canvas = document.getElementById('graficoIngresos');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');

    var fechas = [];
    var valores = [];

    // Rellenar los ultimos 7 dias con 0 por defecto
    for (var i = 6; i >= 0; i--) {
        var d = new Date();
        d.setDate(d.getDate() - i);
        var fechaStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        fechas.push(fechaStr);
        valores.push(0);
    }

    // Llenar con datos reales
    if (datos && datos.length) {
        for (var j = 0; j < datos.length; j++) {
            var idx = fechas.indexOf(datos[j].fecha);
            if (idx !== -1) {
                valores[idx] = parseFloat(datos[j].total) || 0;
            }
        }
    }

    // Formatear fechas para mostrar
    var etiquetas = fechas.map(function(f) {
        var partes = f.split('-');
        var d = new Date(partes[0], partes[1] - 1, partes[2]);
        var diasSem = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
        return diasSem[d.getDay()] + ' ' + partes[2];
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Ingresos ($)',
                data: valores,
                backgroundColor: 'rgba(29, 78, 216, 0.75)',
                borderColor: 'rgba(29, 78, 216, 1)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 750,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '$ ' + value.toFixed(2); }
                    }
                }
            }
        }
    });
}

// Inicializar contadores al cargar la pagina
document.addEventListener('DOMContentLoaded', function() {
    var valores = document.querySelectorAll('.stat-value[data-valor]');
    valores.forEach(function(el, i) {
        var valorFinal = parseFloat(el.getAttribute('data-valor')) || 0;
        // Arranca junto con la entrada escalonada de cada tarjeta
        setTimeout(function() { animarContador(el, valorFinal, 1200); }, 150 + i * 50);
    });
});