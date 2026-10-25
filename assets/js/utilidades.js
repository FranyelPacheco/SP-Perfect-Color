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
    "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
};

const BASE_URL = '/SP%20Perfect%20Color/';

function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
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

// Toast accesible; los estilos viven en estiloBase.css (.toast-app)
function mostrarNotificacion(mensaje, tipo) {
    let contenedor = document.getElementById('toastContenedor');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'toastContenedor';
        contenedor.className = 'toast-contenedor';
        contenedor.setAttribute('role', 'status');
        contenedor.setAttribute('aria-live', 'polite');
        document.body.appendChild(contenedor);
    }
    const toast = document.createElement('div');
    toast.className = 'toast-app toast-' + (tipo === 'exito' || tipo === 'error' ? tipo : 'info');
    toast.textContent = mensaje;
    contenedor.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('toast-salida');
        setTimeout(() => toast.remove(), 300);
    }, tipo === 'error' ? 5000 : 3000);
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
    confirmarConModal('Confirmar', mensaje || 'Esta seguro de realizar esta accion?', funcionCallback);
}

// Formatea un numero como moneda (formato venezolano)
function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-VE', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(cantidad);
}

// Envia una peticion AJAX generica
async function enviarPeticion(url, metodo, datos) {
    try {
        const opciones = {
            method: metodo,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (datos && metodo !== 'GET') {
            opciones.body = JSON.stringify(datos);
        }

        const respuesta = await fetch(url, opciones);
        return await respuesta.json();
    } catch (error) {
        return {
            estado: 'error',
            mensaje: 'Error de conexion con el servidor'
        };
    }
}

// Animacion de contador para los valores del dashboard
function animarContador(elemento, valorFinal, duracion) {
    var esMoneda = elemento.getAttribute('data-moneda') === '1';
    var inicio = 0;
    var paso = duracion / 60;
    var incremento = valorFinal / paso;
    var actual = 0;

    function actualizar() {
        actual += incremento;
        if (!(actual < valorFinal)) {
            actual = valorFinal;
            if (esMoneda) {
                elemento.textContent = '$ ' + formatearMoneda(valorFinal);
            } else {
                elemento.textContent = Math.round(valorFinal);
            }
            return;
        }
        if (esMoneda) {
            elemento.textContent = '$ ' + formatearMoneda(actual);
        } else {
            elemento.textContent = Math.round(actual);
        }
        requestAnimationFrame(actualizar);
    }

    actualizar();
}

// Inicializa la grafica de ingresos diarios con Chart.js
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
                backgroundColor: 'rgba(29, 78, 216, 0.7)',
                borderColor: 'rgba(29, 78, 216, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
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
    valores.forEach(function(el) {
        var valorFinal = parseFloat(el.getAttribute('data-valor')) || 0;
        animarContador(el, valorFinal, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 600);
    });
});