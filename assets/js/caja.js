// Archivo: caja.js
// Manejo de la vista de caja

document.addEventListener('DOMContentLoaded', function() {
    var contenidoEstadoCaja = document.getElementById('contenidoEstadoCaja');
    var accionesCaja = document.getElementById('accionesCaja');

    // Cargar estado de caja al iniciar
    cargarEstadoCaja();

    // Cargar estado actual de la caja
    function cargarEstadoCaja() {
        fetch('/SP%20Perfect%20Color/caja/estadoAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarEstadoCaja(resultado.datos);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar estado de caja:', error);
            });
    }

    // Mostrar el estado de caja y los botones correspondientes
    function mostrarEstadoCaja(datos) {
        if (datos.caja_abierta) {
            // Caja abierta: mostrar resumen y boton cerrar
            var resumen = datos.resumen;
            var caja = datos.caja;
            
            contenidoEstadoCaja.innerHTML = 
                '<p style="color: #2e7d32; font-weight: 600;">Caja Abierta desde: ' + caja.fecha_apertura + '</p>' +
                '<p>Monto Inicial: <strong>Bs. ' + formatearMoneda(caja.monto_inicial) + '</strong></p>' +
                '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 15px;">' +
                    '<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center;">' +
                        '<div style="font-size: 18px; font-weight: 600;">Bs. ' + formatearMoneda(resumen.efectivo) + '</div>' +
                        '<div style="font-size: 11px; color: #666;">Efectivo</div>' +
                    '</div>' +
                    '<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center;">' +
                        '<div style="font-size: 18px; font-weight: 600;">Bs. ' + formatearMoneda(resumen.punto_venta) + '</div>' +
                        '<div style="font-size: 11px; color: #666;">Punto de Venta</div>' +
                    '</div>' +
                    '<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center;">' +
                        '<div style="font-size: 18px; font-weight: 600;">Bs. ' + formatearMoneda(resumen.pago_movil) + '</div>' +
                        '<div style="font-size: 11px; color: #666;">Pago Movil</div>' +
                    '</div>' +
                    '<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center;">' +
                        '<div style="font-size: 18px; font-weight: 600;">Bs. ' + formatearMoneda(resumen.credito) + '</div>' +
                        '<div style="font-size: 11px; color: #666;">Credito</div>' +
                    '</div>' +
                '</div>' +
                '<div style="margin-top: 15px; padding: 10px; background: #e8f5e9; border-radius: 4px; text-align: center;">' +
                    '<span style="font-weight: 600;">Total: Bs. ' + formatearMoneda(resumen.total_general) + '</span>' +
                    ' (' + resumen.cantidad_facturas + ' facturas)' +
                '</div>';
            
            accionesCaja.innerHTML = 
                '<button type="button" class="btn-peligro" onclick="confirmarCerrarCaja()" style="padding: 10px 24px;">Cerrar Caja</button>' +
                '<a href="/SP%20Perfect%20Color/factura/nueva" class="btn-primario" style="padding: 10px 24px;">Nueva Factura</a>';
        } else {
            // Caja cerrada: mostrar formulario para abrir
            contenidoEstadoCaja.innerHTML = 
                '<p style="color: #c62828; font-weight: 600;">Caja Cerrada</p>' +
                '<p>Debe abrir la caja para poder facturar.</p>' +
                '<form id="formularioAbrirCaja" style="margin-top: 15px;">' +
                    '<div class="grupo-formulario">' +
                        '<label for="montoInicial">Monto Inicial (Bs.)</label>' +
                        '<input type="number" id="montoInicial" name="monto_inicial" step="0.01" min="0" value="0" required>' +
                    '</div>' +
                    '<button type="submit" class="btn-primario">Abrir Caja</button>' +
                '</form>';
            
            accionesCaja.innerHTML = '';
            
            // Evento para abrir caja
            document.getElementById('formularioAbrirCaja').addEventListener('submit', function(evento) {
                evento.preventDefault();
                abrirCaja();
            });
        }
    }

    // Abre la caja
    function abrirCaja() {
        var montoInicial = document.getElementById('montoInicial').value;
        
        var formData = new FormData();
        formData.append('monto_inicial', montoInicial);
        
        fetch('/SP%20Perfect%20Color/caja/abrirCaja', {
            method: 'POST',
            body: formData
        })
        .then(function(respuesta) { return respuesta.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion(resultado.mensaje, 'exito');
                location.reload();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexion', 'error');
        });
    }

    // Confirma y cierra la caja
    window.confirmarCerrarCaja = function() {
        if (!confirm('Esta seguro de cerrar la caja? Esta accion no se puede deshacer.')) {
            return;
        }
        
        var formData = new FormData();
        
        fetch('/SP%20Perfect%20Color/caja/cerrarCaja', {
            method: 'POST',
            body: formData
        })
        .then(function(respuesta) { return respuesta.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion('Caja cerrada exitosamente. Total: Bs. ' + formatearMoneda(resultado.datos.resumen.total_general), 'exito');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexion', 'error');
        });
    };
});