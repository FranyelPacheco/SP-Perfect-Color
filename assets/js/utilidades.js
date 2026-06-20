// Archivo: utilidades.js
// Funciones utilitarias comunes para todas las vistas

window.DATATABLES_SPANISH = {
    "emptyTable": "No hay informacion",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
};

// Muestra un mensaje temporal en pantalla (toast)
function mostrarNotificacion(mensaje, tipo) {
    // Crear elemento de notificacion
    const notificacion = document.createElement('div');
    notificacion.textContent = mensaje;
    notificacion.style.position = 'fixed';
    notificacion.style.bottom = '20px';
    notificacion.style.right = '20px';
    notificacion.style.padding = '12px 24px';
    notificacion.style.borderRadius = '4px';
    notificacion.style.color = '#fff';
    notificacion.style.zIndex = '9999';
    notificacion.style.transition = 'opacity 0.3s';
    
    // Definir color segun tipo
    if (tipo === 'exito') {
        notificacion.style.background = '#4caf50';
    } else if (tipo === 'error') {
        notificacion.style.background = '#f44336';
    } else {
        notificacion.style.background = '#2196f3';
    }
    
    document.body.appendChild(notificacion);
    
    // Eliminar la notificacion despues de 3 segundos
    setTimeout(function() {
        notificacion.style.opacity = '0';
        setTimeout(function() {
            notificacion.remove();
        }, 300);
    }, 3000);
}

// Formatea un numero como moneda (formato venezolano)
function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-VE', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(cantidad);
}

// Confirma una accion antes de ejecutarla
function confirmarAccion(mensaje, funcionCallback) {
    if (confirm(mensaje || 'Esta seguro de realizar esta accion?')) {
        funcionCallback();
    }
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
        if (actual >= valorFinal) {
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
        animarContador(el, valorFinal, 800);
    });
});