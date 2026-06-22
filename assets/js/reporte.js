document.addEventListener('DOMContentLoaded', function() {
    var tipoReporte = document.getElementById('tipoReporte');
    var fechaDesde = document.getElementById('fechaDesde');
    var fechaHasta = document.getElementById('fechaHasta');
    var btnGenerar = document.getElementById('btnGenerarReporte');
    var encabezado = document.getElementById('encabezadoReporte');
    var cuerpoReporte = document.getElementById('cuerpoReporte');
    var resumen = document.getElementById('resumenReporte');
    var totalRegistros = document.getElementById('totalRegistros');
    var montoTotal = document.getElementById('montoTotal');
    var labelResumen1 = document.getElementById('labelResumen1');
    var labelResumen2 = document.getElementById('labelResumen2');

    var ENCABEZADOS = {
        ventas: ['Fecha', 'Cliente', 'Total', 'Metodo', 'Detalle'],
        carteraCxc: ['Cliente', 'Cedula', 'Monto Total', 'Saldo Pendiente', 'Vencimiento', 'Estado']
    };

    var LABELS = {
        ventas: ['Total Registros', 'Monto Total'],
        carteraCxc: ['Total Cuentas', 'Saldo Pendiente']
    };

    var TOTAL_KEYS = {
        ventas: 'total',
        carteraCxc: 'total_saldo'
    };

    // 1. Cambia los encabezados de la tabla al seleccionar otro tipo de reporte
  function cambiarEncabezado() {
    var tipo = tipoReporte.value;
    var cols = ENCABEZADOS[tipo] || ['Fecha', 'Cliente', 'Total', 'Metodo', 'Detalle'];
    encabezado.innerHTML = '';
    cols.forEach(function(col) {
        var th = document.createElement('th');
        th.textContent = col;
        encabezado.appendChild(th);
    });
    // Limpiar datos anteriores al cambiar de tipo
    cuerpoReporte.innerHTML = '<tr><td colspan="' + cols.length + '" class="text-center text-muted">Seleccione un reporte y presione Generar</td></tr>';
    resumen.style.display = 'none';
    document.getElementById('btnPDF').style.display = 'none';
    document.getElementById('btnExcel').style.display = 'none';
    // Toggle filter groups segun tipo
    var grupoCondicion = document.getElementById('grupoCondicion');
    var grupoMetodo = document.getElementById('grupoMetodoPago');
    var grupoEstado = document.getElementById('grupoEstadoCxc');
    if (tipo === 'carteraCxc') {
        if (grupoCondicion) grupoCondicion.style.display = 'none';
        if (grupoMetodo) grupoMetodo.style.display = 'none';
        if (grupoEstado) grupoEstado.style.display = 'block';
    } else {
        if (grupoCondicion) grupoCondicion.style.display = 'block';
        if (grupoMetodo) grupoMetodo.style.display = 'block';
        if (grupoEstado) grupoEstado.style.display = 'none';
    }
    // Ajustar fechaDesde segun el tipo (formato local, evita UTC offset)
    var hoy = new Date();
    var anio = hoy.getFullYear();
    var mes = String(hoy.getMonth() + 1).padStart(2, '0');
    var dia = String(hoy.getDate()).padStart(2, '0');
    if (tipo === 'ventas') {
        fechaDesde.value = anio + '-' + mes + '-' + dia;
    } else {
        fechaDesde.value = anio + '-' + mes + '-01';
    }
}

    if (tipoReporte) {
        tipoReporte.addEventListener('change', cambiarEncabezado);
    }
    // Ejecutar al cargar para sincronizar fechaDesde y filtros
    cambiarEncabezado();

    if (btnGenerar) {
        btnGenerar.addEventListener('click', generarReporte);
    }

    // 2. Obtiene los datos del reporte via AJAX
    function generarReporte() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;

        if (!desde || !hasta) {
            mostrarNotificacion('Debe seleccionar ambas fechas', 'error');
            return;
        }

        var url = '/SP%20Perfect%20Color/reporte/' + tipo + 'Ajax?desde=' + desde + '&hasta=' + hasta;
        var idCliente = document.getElementById('filtroCliente').value;
        var condicion = document.getElementById('filtroCondicion').value;
        var idTipoPago = document.getElementById('filtroMetodoPago').value;
        var estadoCxc = document.getElementById('filtroEstadoCxc').value;
        if (idCliente) url += '&id_cliente=' + idCliente;
        if (condicion) url += '&condicion=' + condicion;
        if (idTipoPago) url += '&id_tipo_pago=' + idTipoPago;
        if (estadoCxc) url += '&estado_cxc=' + estadoCxc;

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    mostrarResultados(tipo, res.datos);
                } else {
                    mostrarNotificacion(res.mensaje, 'error');
                }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
    }

    // 3. Formatea un numero a moneda local ($ 1.000,00)
    function formatearMonedaLocal(num) {
        if (num == null) return '0,00';
        var n = parseFloat(num);
        if (isNaN(n)) return '0,00';
        return n.toFixed(2).replace('.', ',');
    }

    // 4. Muestra los resultados en la tabla y el resumen
    function mostrarResultados(tipo, datos) {
        var labels = LABELS[tipo] || ['Total Registros', 'Monto Total'];
        labelResumen1.textContent = labels[0];
        labelResumen2.textContent = labels[1];

        resumen.style.display = 'flex';
        var keyTotal = TOTAL_KEYS[tipo] || 'total';
        totalRegistros.textContent = datos.cantidad || 0;
        montoTotal.textContent = '$ ' + formatearMonedaLocal(datos[keyTotal] || 0);

        cuerpoReporte.innerHTML = '';

        if (tipo === 'ventas') {
            var items = datos.ventas || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay ventas en este rango</td></tr>';
            } else {
                items.forEach(function(v) {
                    var fila = document.createElement('tr');
                    fila.innerHTML =
                        '<td>' + v.fecha + '</td>' +
                        '<td>' + (v.cliente_nombre || '-') + '<br><small class="text-muted">' + (v.cliente_cedula || '') + '</small></td>' +
                        '<td>$ ' + formatearMonedaLocal(v.total) + '</td>' +
                        '<td>' + (v.tipo_pago_nombre || '-') + '</td>' +
                        '<td><a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + v.id_nota_entrega + '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a></td>';
                    cuerpoReporte.appendChild(fila);
                });
            }

        } else if (tipo === 'carteraCxc') {
            var items = datos.cuentas || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay cuentas pendientes en este rango</td></tr>';
            } else {
                items.forEach(function(c) {
                    var fila = document.createElement('tr');
                    var badge = c.estado === 'pagado' ? 'success' : (c.estado === 'moroso' ? 'danger' : 'warning');
                    fila.innerHTML =
                        '<td>' + (c.cliente_nombre || '-') + '</td>' +
                        '<td>' + (c.cliente_cedula || '-') + '</td>' +
                        '<td>$ ' + formatearMonedaLocal(c.monto_total) + '</td>' +
                        '<td><strong>$ ' + formatearMonedaLocal(c.saldo_pendiente) + '</strong></td>' +
                        '<td>' + (c.fecha_vencimiento || '-') + '</td>' +
                        '<td><span class="badge bg-' + badge + '">' + c.estado + '</span></td>';
                    cuerpoReporte.appendChild(fila);
                });
            }
        }

        // Mostrar botones de exportacion
        document.getElementById('btnPDF').style.display = 'block';
        document.getElementById('btnExcel').style.display = 'block';
    }

    // 5. Exporta el reporte actual a PDF
    document.getElementById('btnPDF').addEventListener('click', function() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;
        var url = '/SP%20Perfect%20Color/reporte/exportarPdfAjax?tipo=' + tipo + '&desde=' + desde + '&hasta=' + hasta;
        var idCliente = document.getElementById('filtroCliente').value;
        var condicion = document.getElementById('filtroCondicion').value;
        var idTipoPago = document.getElementById('filtroMetodoPago').value;
        var estadoCxc = document.getElementById('filtroEstadoCxc').value;
        if (idCliente) url += '&id_cliente=' + idCliente;
        if (condicion) url += '&condicion=' + condicion;
        if (idTipoPago) url += '&id_tipo_pago=' + idTipoPago;
        if (estadoCxc) url += '&estado_cxc=' + estadoCxc;
        window.location.href = url;
    });

    // 6. Exporta el reporte actual a Excel
    document.getElementById('btnExcel').addEventListener('click', function() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;
        var url = '/SP%20Perfect%20Color/reporte/exportarExcelAjax?tipo=' + tipo + '&desde=' + desde + '&hasta=' + hasta;
        var idCliente = document.getElementById('filtroCliente').value;
        var condicion = document.getElementById('filtroCondicion').value;
        var idTipoPago = document.getElementById('filtroMetodoPago').value;
        var estadoCxc = document.getElementById('filtroEstadoCxc').value;
        if (idCliente) url += '&id_cliente=' + idCliente;
        if (condicion) url += '&condicion=' + condicion;
        if (idTipoPago) url += '&id_tipo_pago=' + idTipoPago;
        if (estadoCxc) url += '&estado_cxc=' + estadoCxc;
        window.location.href = url;
    });
});