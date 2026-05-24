// Archivo: cuentaPagar.js
// Manejo de la lista de cuentas por pagar

document.addEventListener('DOMContentLoaded', function() {
    var tablaCuentas = document.getElementById('cuerpoTablaCuentasPagar');
    var busquedaCuentas = document.getElementById('busquedaCuentasPagar');

    cargarCuentas();

    var temporizadorBusqueda;
    if (busquedaCuentas) {
        busquedaCuentas.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                buscarCuentas(busquedaCuentas.value.trim());
            }, 300);
        });
    }

    function cargarCuentas() {
        fetch('/SP%20Perfect%20Color/cuentaPagar/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarCuentas(resultado.datos.cuentas);
                }
            });
    }

    function buscarCuentas(termino) {
        fetch('/SP%20Perfect%20Color/cuentaPagar/buscarAjax?termino=' + encodeURIComponent(termino))
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarCuentas(resultado.datos.cuentas);
                }
            });
    }

    function mostrarCuentas(cuentas) {
        tablaCuentas.innerHTML = '';

        if (cuentas.length === 0) {
            tablaCuentas.innerHTML = '<tr><td colspan="7" style="text-align: center;">No hay cuentas por pagar</td></tr>';
            return;
        }

        cuentas.forEach(function(cuenta) {
            var fila = document.createElement('tr');
            
            fila.innerHTML = 
                '<td>' + cuenta.proveedor_nombre + '</td>' +
                '<td>' + cuenta.proveedor_rif + '</td>' +
                '<td>Bs. ' + formatearMoneda(cuenta.monto_total) + '</td>' +
                '<td class="' + (parseFloat(cuenta.saldo_pendiente) > 0 ? 'saldo-pendiente-positivo' : 'saldo-pendiente-cero') + '">Bs. ' + formatearMoneda(cuenta.saldo_pendiente) + '</td>' +
                '<td>' + (cuenta.fecha_vencimiento || '-') + '</td>' +
                '<td><span class="estado-' + cuenta.estado + '">' + cuenta.estado.charAt(0).toUpperCase() + cuenta.estado.slice(1) + '</span></td>' +
                '<td class="acciones"><a href="/SP%20Perfect%20Color/cuentaPagar/ver?id=' + cuenta.id + '" class="btn-primario">Ver</a></td>';
            
            tablaCuentas.appendChild(fila);
        });
    }
});