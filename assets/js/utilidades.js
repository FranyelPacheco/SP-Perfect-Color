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