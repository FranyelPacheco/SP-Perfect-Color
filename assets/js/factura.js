// Archivo: factura.js
// Manejo de la lista de facturas

document.addEventListener('DOMContentLoaded', function() {
    var tablaFacturas = document.getElementById('cuerpoTablaFacturas');
    var busquedaFacturas = document.getElementById('busquedaFacturas');

    cargarFacturas();

    var temporizadorBusqueda;
    if (busquedaFacturas) {
        busquedaFacturas.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                buscarFacturas(busquedaFacturas.value.trim());
            }, 300);
        });
    }

    function cargarFacturas() {
        fetch('/SP%20Perfect%20Color/factura/listarAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarFacturas(resultado.datos.facturas);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar facturas:', error);
            });
    }

    function buscarFacturas(termino) {
        fetch('/SP%20Perfect%20Color/factura/buscarAjax?termino=' + encodeURIComponent(termino))
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarFacturas(resultado.datos.facturas);
                }
            })
            .catch(function(error) {
                console.error('Error al buscar facturas:', error);
            });
    }

    function mostrarFacturas(facturas) {
        tablaFacturas.innerHTML = '';

        if (facturas.length === 0) {
            tablaFacturas.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay facturas registradas</td></tr>';
            return;
        }

        facturas.forEach(function(factura) {
            var fila = document.createElement('tr');
            
            fila.innerHTML = 
                '<td style="font-weight: 600;">' + factura.numero_factura + '</td>' +
                '<td>' + factura.fecha + '</td>' +
                '<td>' + factura.cliente_nombre + '</td>' +
                '<td>' + factura.metodo_pago + '</td>' +
                '<td style="font-weight: 600;">Bs. ' + formatearMoneda(factura.total) + '</td>' +
                '<td><span class="estado-' + factura.estado + '">' + factura.estado.charAt(0).toUpperCase() + factura.estado.slice(1) + '</span></td>' +
                '<td class="acciones"><a href="/SP%20Perfect%20Color/factura/ver?id=' + factura.id + '" class="btn-primario">Ver</a></td>';
            
            tablaFacturas.appendChild(fila);
        });
    }
});