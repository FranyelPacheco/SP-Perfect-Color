// Archivo: presupuesto.js
// Manejo de la lista de presupuestos

document.addEventListener('DOMContentLoaded', function() {
    var tablaPresupuestos = document.getElementById('cuerpoTablaPresupuestos');
    var busquedaPresupuestos = document.getElementById('busquedaPresupuestos');
    var filtroEstado = document.getElementById('filtroEstadoPresupuesto');

    // Cargar lista de presupuestos al iniciar
    cargarPresupuestos();

    // Evento para buscar presupuestos
    var temporizadorBusqueda;
    if (busquedaPresupuestos) {
        busquedaPresupuestos.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                buscarPresupuestos();
            }, 300);
        });
    }

    // Evento para filtrar por estado
    if (filtroEstado) {
        filtroEstado.addEventListener('change', function() {
            buscarPresupuestos();
        });
    }

    // Cargar todos los presupuestos
    function cargarPresupuestos() {
        fetch('presupuesto/listarAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarPresupuestos(resultado.datos.presupuestos);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar presupuestos:', error);
            });
    }

    // Buscar presupuestos por filtros
    function buscarPresupuestos() {
        var termino = busquedaPresupuestos ? busquedaPresupuestos.value.trim() : '';
        var estado = filtroEstado ? filtroEstado.value : '';
        
        fetch('presupuesto/buscarAjax?termino=' + encodeURIComponent(termino) + '&estado=' + encodeURIComponent(estado))
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarPresupuestos(resultado.datos.presupuestos);
                }
            })
            .catch(function(error) {
                console.error('Error al buscar presupuestos:', error);
            });
    }

    // Mostrar presupuestos en la tabla
    function mostrarPresupuestos(presupuestos) {
        tablaPresupuestos.innerHTML = '';

        if (presupuestos.length === 0) {
            tablaPresupuestos.innerHTML = '<tr><td colspan="8" style="text-align: center;">No hay presupuestos registrados</td></tr>';
            return;
        }

        presupuestos.forEach(function(presupuesto) {
            var fila = document.createElement('tr');
            
            // ID
            var celdaId = document.createElement('td');
            celdaId.textContent = '#' + presupuesto.id;
            fila.appendChild(celdaId);
            
            // Fecha
            var celdaFecha = document.createElement('td');
            celdaFecha.textContent = presupuesto.fecha;
            fila.appendChild(celdaFecha);
            
            // Cliente
            var celdaCliente = document.createElement('td');
            celdaCliente.textContent = presupuesto.cliente_nombre;
            fila.appendChild(celdaCliente);
            
            // Cedula
            var celdaCedula = document.createElement('td');
            celdaCedula.textContent = presupuesto.cliente_cedula;
            fila.appendChild(celdaCedula);
            
            // Total
            var celdaTotal = document.createElement('td');
            celdaTotal.textContent = formatearMoneda(presupuesto.total);
            celdaTotal.style.fontWeight = '600';
            fila.appendChild(celdaTotal);
            
            // Estado
            var celdaEstado = document.createElement('td');
            var spanEstado = document.createElement('span');
            spanEstado.className = 'estado-' + presupuesto.estado;
            spanEstado.textContent = presupuesto.estado.charAt(0).toUpperCase() + presupuesto.estado.slice(1);
            celdaEstado.appendChild(spanEstado);
            fila.appendChild(celdaEstado);
            
            // Vendedor
            var celdaVendedor = document.createElement('td');
            celdaVendedor.textContent = presupuesto.usuario_nombre;
            fila.appendChild(celdaVendedor);
            
            // Acciones
            var celdaAcciones = document.createElement('td');
            celdaAcciones.className = 'acciones';
            
            // Boton ver
            var btnVer = document.createElement('a');
            btnVer.href = 'presupuesto/ver?id=' + presupuesto.id;
            btnVer.className = 'btn-primario';
            btnVer.textContent = 'Ver';
            celdaAcciones.appendChild(btnVer);
            
            // Boton aprobar si esta pendiente
            if (presupuesto.estado === 'pendiente') {
                var btnAprobar = document.createElement('button');
                btnAprobar.className = 'btn-exito';
                btnAprobar.textContent = 'Aprobar';
                btnAprobar.addEventListener('click', function() {
                    cambiarEstado(presupuesto.id, 'aprobado');
                });
                celdaAcciones.appendChild(btnAprobar);
                
                var btnRechazar = document.createElement('button');
                btnRechazar.className = 'btn-peligro';
                btnRechazar.textContent = 'Rechazar';
                btnRechazar.addEventListener('click', function() {
                    cambiarEstado(presupuesto.id, 'rechazado');
                });
                celdaAcciones.appendChild(btnRechazar);
            }
            
            fila.appendChild(celdaAcciones);
            
            tablaPresupuestos.appendChild(fila);
        });
    }

    // Cambiar estado de un presupuesto
    function cambiarEstado(id, estado) {
        var mensaje = estado === 'aprobado' 
            ? 'Esta seguro de aprobar este presupuesto?' 
            : 'Esta seguro de rechazar este presupuesto?';
        
        if (!confirm(mensaje)) {
            return;
        }
        
        var formData = new FormData();
        formData.append('id', id);
        formData.append('estado', estado);
        
        fetch('presupuesto/cambiarEstado', {
            method: 'POST',
            body: formData
        })
        .then(function(respuesta) { return respuesta.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion(resultado.mensaje, 'exito');
                cargarPresupuestos();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexion', 'error');
        });
    }
});