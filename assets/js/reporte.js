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
    var montoEgresos = document.getElementById('montoEgresos');
    var montoBalance = document.getElementById('montoBalance');
    var colResumenEgresos = document.getElementById('colResumenEgresos');
    var colResumenBalance = document.getElementById('colResumenBalance');

    var ENCABEZADOS = {
        ventas: ['Fecha', 'Cliente', 'Total ($)', 'Método', 'Acción'],
        compras: ['Fecha', 'N° Compra', 'Proveedor', 'RIF', 'Total ($)', 'Condición', 'Estado Pago', 'Acción'],
        ambas: ['Fecha', 'Tipo', 'N° Doc', 'Tercero', 'Cédula / RIF', 'Condición', 'Ingreso ($)', 'Egreso ($)'],
        carteraCxc: ['Cliente', 'Cédula', 'Monto Total', 'Saldo Pendiente', 'Vencimiento', 'Estado']
    };

    var LABELS = {
        ventas: ['Total Ventas', 'Monto Total'],
        compras: ['Total Compras', 'Total Gasto Insumos'],
        ambas: ['Total Operaciones', 'Total Ingresos'],
        carteraCxc: ['Total Cuentas', 'Saldo Pendiente']
    };

    var TOTAL_KEYS = {
        ventas: 'total',
        compras: 'total',
        ambas: 'total_ingresos',
        carteraCxc: 'total_saldo'
    };

    // 1. Cambia los encabezados de la tabla al seleccionar otro tipo de reporte
    function cambiarEncabezado() {
        var tipo = tipoReporte.value;
        var cols = ENCABEZADOS[tipo] || ['Fecha', 'Cliente', 'Total', 'Metodo', 'Accion'];
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

        // Toggle filter groups según el tipo seleccionado
        var grupoCliente = document.getElementById('grupoCliente');
        var grupoProveedor = document.getElementById('grupoProveedor');
        var grupoCondicion = document.getElementById('grupoCondicion');
        var grupoMetodo = document.getElementById('grupoMetodoPago');
        var grupoEstado = document.getElementById('grupoEstadoCxc');

        if (tipo === 'ventas') {
            if (grupoCliente) grupoCliente.style.display = 'block';
            if (grupoProveedor) grupoProveedor.style.display = 'none';
            if (grupoCondicion) grupoCondicion.style.display = 'block';
            if (grupoMetodo) grupoMetodo.style.display = 'block';
            if (grupoEstado) grupoEstado.style.display = 'none';
        } else if (tipo === 'compras') {
            if (grupoCliente) grupoCliente.style.display = 'none';
            if (grupoProveedor) grupoProveedor.style.display = 'block';
            if (grupoCondicion) grupoCondicion.style.display = 'block';
            if (grupoMetodo) grupoMetodo.style.display = 'none';
            if (grupoEstado) grupoEstado.style.display = 'none';
        } else if (tipo === 'ambas') {
            if (grupoCliente) grupoCliente.style.display = 'none';
            if (grupoProveedor) grupoProveedor.style.display = 'none';
            if (grupoCondicion) grupoCondicion.style.display = 'none';
            if (grupoMetodo) grupoMetodo.style.display = 'none';
            if (grupoEstado) grupoEstado.style.display = 'none';
        } else if (tipo === 'carteraCxc') {
            if (grupoCliente) grupoCliente.style.display = 'block';
            if (grupoProveedor) grupoProveedor.style.display = 'none';
            if (grupoCondicion) grupoCondicion.style.display = 'none';
            if (grupoMetodo) grupoMetodo.style.display = 'none';
            if (grupoEstado) grupoEstado.style.display = 'block';
        }

        // Ajustar fechaDesde según el tipo
        var hoy = new Date();
        var anio = hoy.getFullYear();
        var mes = String(hoy.getMonth() + 1).padStart(2, '0');
        var dia = String(hoy.getDate()).padStart(2, '0');
        if (tipo === 'ventas' || tipo === 'compras') {
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

    function validarRangoFechas(desde, hasta) {
        if (!desde || !hasta) {
            mostrarNotificacion('Debe seleccionar ambas fechas', 'error');
            return false;
        }
        var hoy = new Date().toISOString().split('T')[0];
        if (desde > hoy || hasta > hoy) {
            mostrarNotificacion('Las fechas no pueden ser posteriores a la fecha actual (' + hoy + ')', 'error');
            if (desde > hoy) fechaDesde.value = hoy;
            if (hasta > hoy) fechaHasta.value = hoy;
            return false;
        }
        if (desde > hasta) {
            mostrarNotificacion('La fecha "Desde" no puede ser mayor que "Hasta"', 'error');
            return false;
        }
        return true;
    }

    [fechaDesde, fechaHasta].forEach(function(inp) {
        if (inp) {
            inp.addEventListener('change', function() {
                var hoy = new Date().toISOString().split('T')[0];
                if (this.value > hoy) {
                    mostrarNotificacion('No se permiten fechas futuras', 'error');
                    this.value = hoy;
                }
            });
        }
    });

    // 2. Obtiene los datos del reporte vía AJAX
    function generarReporte() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;

        if (!validarRangoFechas(desde, hasta)) {
            return;
        }

        var url = '/SP%20Perfect%20Color/reporte/' + tipo + 'Ajax?desde=' + desde + '&hasta=' + hasta;
        var idCliente = document.getElementById('filtroCliente') ? document.getElementById('filtroCliente').value : '';
        var idProveedor = document.getElementById('filtroProveedor') ? document.getElementById('filtroProveedor').value : '';
        var condicion = document.getElementById('filtroCondicion') ? document.getElementById('filtroCondicion').value : '';
        var idTipoPago = document.getElementById('filtroMetodoPago') ? document.getElementById('filtroMetodoPago').value : '';
        var estadoCxc = document.getElementById('filtroEstadoCxc') ? document.getElementById('filtroEstadoCxc').value : '';

        if (idCliente && (tipo === 'ventas' || tipo === 'carteraCxc')) url += '&id_cliente=' + idCliente;
        if (idProveedor && tipo === 'compras') url += '&id_proveedor=' + idProveedor;
        if (condicion && (tipo === 'ventas' || tipo === 'compras')) url += '&condicion=' + condicion;
        if (idTipoPago && tipo === 'ventas') url += '&id_tipo_pago=' + idTipoPago;
        if (estadoCxc && tipo === 'carteraCxc') url += '&estado_cxc=' + estadoCxc;

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

    // 3. Formatea un número a moneda local ($ 1.000,00)
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

        if (tipo === 'ambas') {
            if (colResumenEgresos) colResumenEgresos.style.display = 'block';
            if (colResumenBalance) colResumenBalance.style.display = 'block';
            if (montoEgresos) montoEgresos.textContent = '$ ' + formatearMonedaLocal(datos.total_egresos || 0);
            var balance = parseFloat(datos.balance_neto || 0);
            if (montoBalance) {
                montoBalance.textContent = '$ ' + formatearMonedaLocal(balance);
                montoBalance.className = 'fs-3 fw-bold ' + (balance >= 0 ? 'text-success' : 'text-danger');
            }
        } else {
            if (colResumenEgresos) colResumenEgresos.style.display = 'none';
            if (colResumenBalance) colResumenBalance.style.display = 'none';
        }

        cuerpoReporte.innerHTML = '';

        if (tipo === 'ventas') {
            var items = datos.ventas || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay ventas en este rango</td></tr>';
            } else {
                items.forEach(function(v) {
                    var fila = document.createElement('tr');
                    fila.innerHTML =
                        '<td>' + (v.fecha ? v.fecha.substring(0, 10) : '-') + '</td>' +
                        '<td>' + (v.cliente_nombre || '-') + '<br><small class="text-muted">' + (v.cliente_cedula || '') + '</small></td>' +
                        '<td class="fw-bold text-primary">$ ' + formatearMonedaLocal(v.total) + '</td>' +
                        '<td>' + (v.tipo_pago_nombre || '-') + '</td>' +
                        '<td><a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + (v.id_nota_entrega || v.id_venta) + '" class="btn btn-sm btn-outline-primary" title="Ver Nota"><i class="bi bi-eye"></i></a></td>';
                    cuerpoReporte.appendChild(fila);
                });
            }

        } else if (tipo === 'compras') {
            var items = datos.compras || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay compras registradas en este rango</td></tr>';
            } else {
                items.forEach(function(c) {
                    var fila = document.createElement('tr');
                    var badgeCond = c.condicion_pago === 'Contado' ? 'success' : 'warning';
                    fila.innerHTML =
                        '<td>' + (c.fecha ? c.fecha.substring(0, 10) : '-') + '</td>' +
                        '<td><strong>#' + (c.id_nota_entrega || '-') + '</strong></td>' +
                        '<td>' + (c.proveedor_nombre || '-') + '</td>' +
                        '<td>' + (c.proveedor_rif || '-') + '</td>' +
                        '<td class="fw-bold text-danger">$ ' + formatearMonedaLocal(c.total) + '</td>' +
                        '<td><span class="badge bg-' + badgeCond + '">' + (c.condicion_pago || '-') + '</span></td>' +
                        '<td>' + (c.situacion_pago ? c.situacion_pago.charAt(0).toUpperCase() + c.situacion_pago.slice(1) : '-') + '</td>' +
                        '<td><a href="/SP%20Perfect%20Color/notaEntregaCompra/ver?id=' + c.id_nota_entrega + '" class="btn btn-sm btn-outline-secondary" title="Ver Recepción"><i class="bi bi-eye"></i></a></td>';
                    cuerpoReporte.appendChild(fila);
                });
            }

        } else if (tipo === 'ambas') {
            var items = datos.items || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay operaciones en este rango</td></tr>';
            } else {
                items.forEach(function(row) {
                    var fila = document.createElement('tr');
                    var esVenta = (row.tipo_transaccion === 'Venta');
                    var badgeTipo = esVenta ? '<span class="badge bg-success">Venta</span>' : '<span class="badge bg-danger">Compra</span>';
                    var ingresoFmt = parseFloat(row.monto_ingreso) > 0 ? '<span class="text-success fw-bold">$ ' + formatearMonedaLocal(row.monto_ingreso) + '</span>' : '-';
                    var egresoFmt = parseFloat(row.monto_egreso) > 0 ? '<span class="text-danger fw-bold">$ ' + formatearMonedaLocal(row.monto_egreso) + '</span>' : '-';

                    fila.innerHTML =
                        '<td>' + row.fecha + '</td>' +
                        '<td>' + badgeTipo + '</td>' +
                        '<td>#' + row.doc_numero + '</td>' +
                        '<td>' + (row.tercero_nombre || '-') + '</td>' +
                        '<td>' + (row.tercero_doc || '-') + '</td>' +
                        '<td>' + (row.condicion || '-') + '</td>' +
                        '<td>' + ingresoFmt + '</td>' +
                        '<td>' + egresoFmt + '</td>';
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
                    var badge = c.estado === 'pagado' ? 'success' : 'warning';
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

        // Mostrar botones de exportación
        document.getElementById('btnPDF').style.display = 'block';
        document.getElementById('btnExcel').style.display = 'block';
    }

    // 5. Exporta el reporte actual a PDF
    document.getElementById('btnPDF').addEventListener('click', function() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;
        if (!validarRangoFechas(desde, hasta)) return;
        var url = '/SP%20Perfect%20Color/reporte/exportarPdfAjax?tipo=' + tipo + '&desde=' + desde + '&hasta=' + hasta;
        var idCliente = document.getElementById('filtroCliente') ? document.getElementById('filtroCliente').value : '';
        var idProveedor = document.getElementById('filtroProveedor') ? document.getElementById('filtroProveedor').value : '';
        var condicion = document.getElementById('filtroCondicion') ? document.getElementById('filtroCondicion').value : '';
        var idTipoPago = document.getElementById('filtroMetodoPago') ? document.getElementById('filtroMetodoPago').value : '';
        var estadoCxc = document.getElementById('filtroEstadoCxc') ? document.getElementById('filtroEstadoCxc').value : '';

        if (idCliente && (tipo === 'ventas' || tipo === 'carteraCxc')) url += '&id_cliente=' + idCliente;
        if (idProveedor && tipo === 'compras') url += '&id_proveedor=' + idProveedor;
        if (condicion && (tipo === 'ventas' || tipo === 'compras')) url += '&condicion=' + condicion;
        if (idTipoPago && tipo === 'ventas') url += '&id_tipo_pago=' + idTipoPago;
        if (estadoCxc && tipo === 'carteraCxc') url += '&estado_cxc=' + estadoCxc;

        window.location.href = url;
    });

    // 6. Exporta el reporte actual a Excel
    document.getElementById('btnExcel').addEventListener('click', function() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;
        if (!validarRangoFechas(desde, hasta)) return;
        var url = '/SP%20Perfect%20Color/reporte/exportarExcelAjax?tipo=' + tipo + '&desde=' + desde + '&hasta=' + hasta;
        var idCliente = document.getElementById('filtroCliente') ? document.getElementById('filtroCliente').value : '';
        var idProveedor = document.getElementById('filtroProveedor') ? document.getElementById('filtroProveedor').value : '';
        var condicion = document.getElementById('filtroCondicion') ? document.getElementById('filtroCondicion').value : '';
        var idTipoPago = document.getElementById('filtroMetodoPago') ? document.getElementById('filtroMetodoPago').value : '';
        var estadoCxc = document.getElementById('filtroEstadoCxc') ? document.getElementById('filtroEstadoCxc').value : '';

        if (idCliente && (tipo === 'ventas' || tipo === 'carteraCxc')) url += '&id_cliente=' + idCliente;
        if (idProveedor && tipo === 'compras') url += '&id_proveedor=' + idProveedor;
        if (condicion && (tipo === 'ventas' || tipo === 'compras')) url += '&condicion=' + condicion;
        if (idTipoPago && tipo === 'ventas') url += '&id_tipo_pago=' + idTipoPago;
        if (estadoCxc && tipo === 'carteraCxc') url += '&estado_cxc=' + estadoCxc;

        window.location.href = url;
    });
});