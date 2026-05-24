// Archivo: notaEntrega.js
// Manejo de la lista de notas de entrega

document.addEventListener('DOMContentLoaded', function() {
    var tablaNotas = document.getElementById('cuerpoTablaNotas');
    var busquedaNotas = document.getElementById('busquedaNotas');

    // Cargar lista de notas al iniciar
    cargarNotas();

    // Evento para buscar notas mientras se escribe
    var temporizadorBusqueda;
    if (busquedaNotas) {
        busquedaNotas.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                buscarNotas(busquedaNotas.value.trim());
            }, 300);
        });
    }

    // Cargar todas las notas de entrega
    function cargarNotas() {
        fetch('/SP%20Perfect%20Color/notaEntrega/listarAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarNotas(resultado.datos.notas);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar notas:', error);
            });
    }

    // Buscar notas por termino
    function buscarNotas(termino) {
        fetch('/SP%20Perfect%20Color/notaEntrega/buscarAjax?termino=' + encodeURIComponent(termino))
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarNotas(resultado.datos.notas);
                }
            })
            .catch(function(error) {
                console.error('Error al buscar notas:', error);
            });
    }

    // Mostrar notas en la tabla
    function mostrarNotas(notas) {
        tablaNotas.innerHTML = '';

        if (notas.length === 0) {
            tablaNotas.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay notas de entrega registradas</td></tr>';
            return;
        }

        notas.forEach(function(nota) {
            var fila = document.createElement('tr');
            
            var celdaId = document.createElement('td');
            celdaId.textContent = '#' + nota.id;
            fila.appendChild(celdaId);
            
            var celdaFecha = document.createElement('td');
            celdaFecha.textContent = nota.fecha;
            fila.appendChild(celdaFecha);
            
            var celdaCliente = document.createElement('td');
            celdaCliente.textContent = nota.cliente_nombre;
            fila.appendChild(celdaCliente);
            
            var celdaCedula = document.createElement('td');
            celdaCedula.textContent = nota.cliente_cedula;
            fila.appendChild(celdaCedula);
            
            var celdaTotal = document.createElement('td');
            celdaTotal.textContent = formatearMoneda(nota.total);
            celdaTotal.style.fontWeight = '600';
            fila.appendChild(celdaTotal);
            
            var celdaVendedor = document.createElement('td');
            celdaVendedor.textContent = nota.usuario_nombre;
            fila.appendChild(celdaVendedor);
            
            var celdaAcciones = document.createElement('td');
            celdaAcciones.className = 'acciones';
            
            var btnVer = document.createElement('a');
            btnVer.href = '/SP%20Perfect%20Color/notaEntrega/ver?id=' + nota.id;
            btnVer.className = 'btn-primario';
            btnVer.textContent = 'Ver';
            celdaAcciones.appendChild(btnVer);
            
            fila.appendChild(celdaAcciones);
            
            tablaNotas.appendChild(fila);
        });
    }
});