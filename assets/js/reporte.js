$(function() {
    const $tipoReporte = $('#tipoReporte');
    const $fechaDesde = $('#fechaDesde');
    const $fechaHasta = $('#fechaHasta');
    const $encabezado = $('#encabezadoReporte');
    const $cuerpoReporte = $('#cuerpoReporte');
    const $resumen = $('#resumenReporte');
    const $btnPDF = $('#btnPDF');
    const $btnExcel = $('#btnExcel');

    const ENCABEZADOS = {
        ventas: ['Fecha', 'Cliente', 'Total ($)', 'Método', 'Acción'],
        compras: ['Fecha', 'N° Compra', 'Proveedor', 'RIF', 'Total ($)', 'Condición', 'Estado Pago', 'Acción'],
        ambas: ['Fecha', 'Tipo', 'N° Doc', 'Tercero', 'Cédula / RIF', 'Condición', 'Ingreso ($)', 'Egreso ($)'],
        carteraCxc: ['Cliente', 'Cédula', 'Monto Total', 'Saldo Pendiente', 'Vencimiento', 'Estado']
    };

    const LABELS = {
        ventas: ['Total Ventas', 'Monto Total'],
        compras: ['Total Compras', 'Total Gasto Insumos'],
        ambas: ['Total Operaciones', 'Total Ingresos'],
        carteraCxc: ['Total Cuentas', 'Saldo Pendiente']
    };

    const TOTAL_KEYS = {
        ventas: 'total',
        compras: 'total',
        ambas: 'total_ingresos',
        carteraCxc: 'total_saldo'
    };

    // Qué filtros muestra cada tipo de reporte
    const GRUPOS_POR_TIPO = {
        ventas: ['#grupoCliente', '#grupoCondicion', '#grupoMetodoPago'],
        compras: ['#grupoProveedor', '#grupoCondicion'],
        ambas: [],
        carteraCxc: ['#grupoCliente', '#grupoEstadoCxc']
    };
    const TODOS_LOS_GRUPOS = ['#grupoCliente', '#grupoProveedor', '#grupoCondicion', '#grupoMetodoPago', '#grupoEstadoCxc'];

    const hoyISO = function() { return new Date().toISOString().split('T')[0]; };

    // 1. Cambia los encabezados de la tabla al seleccionar otro tipo de reporte
    function cambiarEncabezado() {
        const tipo = $tipoReporte.val();
        const cols = ENCABEZADOS[tipo] || ['Fecha', 'Cliente', 'Total', 'Metodo', 'Accion'];
        $encabezado.empty();
        $.each(cols, function(i, col) {
            $encabezado.append($('<th></th>').text(col));
        });

        // Limpiar datos anteriores al cambiar de tipo
        $cuerpoReporte.html('<tr><td colspan="' + cols.length + '" class="text-center text-muted">Seleccione un reporte y presione Generar</td></tr>');
        $resumen.hide();
        $btnPDF.hide();
        $btnExcel.hide();

        // Toggle filter groups según el tipo seleccionado
        const visibles = GRUPOS_POR_TIPO[tipo] || [];
        $.each(TODOS_LOS_GRUPOS, function(i, grupo) {
            $(grupo).css('display', visibles.indexOf(grupo) !== -1 ? 'block' : 'none');
        });

        // Ajustar fechaDesde según el tipo
        const hoy = new Date();
        const anio = hoy.getFullYear();
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const dia = String(hoy.getDate()).padStart(2, '0');
        $fechaDesde.val(anio + '-' + mes + '-' + (tipo === 'ventas' || tipo === 'compras' ? dia : '01'));
    }

    $tipoReporte.on('change', cambiarEncabezado);
    // Ejecutar al cargar para sincronizar fechaDesde y filtros
    cambiarEncabezado();

    $('#btnGenerarReporte').on('click', generarReporte);

    function validarRangoFechas(desde, hasta) {
        if (!desde || !hasta) {
            mostrarNotificacion('Debe seleccionar ambas fechas', 'error');
            return false;
        }
        const hoy = hoyISO();
        if (desde > hoy || hasta > hoy) {
            mostrarNotificacion('Las fechas no pueden ser posteriores a la fecha actual (' + hoy + ')', 'error');
            if (desde > hoy) $fechaDesde.val(hoy);
            if (hasta > hoy) $fechaHasta.val(hoy);
            return false;
        }
        if (desde > hasta) {
            mostrarNotificacion('La fecha "Desde" no puede ser mayor que "Hasta"', 'error');
            return false;
        }
        return true;
    }

    $fechaDesde.add($fechaHasta).on('change', function() {
        const hoy = hoyISO();
        if (this.value > hoy) {
            mostrarNotificacion('No se permiten fechas futuras', 'error');
            this.value = hoy;
        }
    });

    // Parámetros de consulta comunes a la vista previa y a las exportaciones
    function parametrosReporte(tipo, desde, hasta) {
        const params = { desde: desde, hasta: hasta };
        const idCliente = $('#filtroCliente').val();
        const idProveedor = $('#filtroProveedor').val();
        const condicion = $('#filtroCondicion').val();
        const idTipoPago = $('#filtroMetodoPago').val();
        const estadoCxc = $('#filtroEstadoCxc').val();

        if (idCliente && (tipo === 'ventas' || tipo === 'carteraCxc')) params.id_cliente = idCliente;
        if (idProveedor && tipo === 'compras') params.id_proveedor = idProveedor;
        if (condicion && (tipo === 'ventas' || tipo === 'compras')) params.condicion = condicion;
        if (idTipoPago && tipo === 'ventas') params.id_tipo_pago = idTipoPago;
        if (estadoCxc && tipo === 'carteraCxc') params.estado_cxc = estadoCxc;
        return params;
    }

    // 2. Obtiene los datos del reporte vía AJAX
    function generarReporte() {
        const tipo = $tipoReporte.val();
        const desde = $fechaDesde.val();
        const hasta = $fechaHasta.val();

        if (!validarRangoFechas(desde, hasta)) {
            return;
        }

        api.get('reporte/' + tipo + 'Ajax', parametrosReporte(tipo, desde, hasta))
            .then(function(res) {
                if (res.estado === 'exito') {
                    mostrarResultados(tipo, res.datos);
                } else {
                    mostrarNotificacion(res.mensaje, 'error');
                }
            })
            .catch(function() { mostrarNotificacion('No se pudo conectar con el servidor. Intente de nuevo.', 'error'); });
    }

    // 3. Formatea un número a moneda local ($ 1.000,00)
    function formatearMonedaLocal(num) {
        if (num == null) return '0,00';
        const n = parseFloat(num);
        if (isNaN(n)) return '0,00';
        return n.toFixed(2).replace('.', ',');
    }

    function filaVacia(columnas, mensaje) {
        $cuerpoReporte.html('<tr><td colspan="' + columnas + '" class="text-center text-muted">' + mensaje + '</td></tr>');
    }

    // 4. Muestra los resultados en la tabla y el resumen
    function mostrarResultados(tipo, datos) {
        const labels = LABELS[tipo] || ['Total Registros', 'Monto Total'];
        $('#labelResumen1').text(labels[0]);
        $('#labelResumen2').text(labels[1]);

        $resumen.css('display', 'flex');
        $('#totalRegistros').text(datos.cantidad || 0);
        $('#montoTotal').text('$ ' + formatearMonedaLocal(datos[TOTAL_KEYS[tipo] || 'total'] || 0));

        if (tipo === 'ambas') {
            $('#colResumenEgresos, #colResumenBalance').css('display', 'block');
            $('#montoEgresos').text('$ ' + formatearMonedaLocal(datos.total_egresos || 0));
            const balance = parseFloat(datos.balance_neto || 0);
            $('#montoBalance')
                .text('$ ' + formatearMonedaLocal(balance))
                .attr('class', 'fs-3 fw-bold ' + (balance >= 0 ? 'text-success' : 'text-danger'));
        } else {
            $('#colResumenEgresos, #colResumenBalance').css('display', 'none');
        }

        $cuerpoReporte.empty();

        if (tipo === 'ventas') {
            const items = datos.ventas || [];
            if (items.length === 0) {
                filaVacia(5, 'No hay ventas en este rango');
            } else {
                $.each(items, function(i, v) {
                    $('<tr></tr>').html(
                        '<td>' + (v.fecha ? v.fecha.substring(0, 10) : '-') + '</td>' +
                        '<td>' + escapeHtml(v.cliente_nombre || '-') + '<br><small class="text-muted">' + escapeHtml(v.cliente_cedula || '') + '</small></td>' +
                        '<td class="fw-bold text-primary">$ ' + formatearMonedaLocal(v.total) + '</td>' +
                        '<td>' + escapeHtml(v.tipo_pago_nombre || '-') + '</td>' +
                        '<td><a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + (v.id_nota_entrega || v.id_venta) + '" class="btn btn-sm btn-outline-primary" title="Ver Nota"><i class="bi bi-eye"></i></a></td>'
                    ).appendTo($cuerpoReporte);
                });
            }

        } else if (tipo === 'compras') {
            const items = datos.compras || [];
            if (items.length === 0) {
                filaVacia(8, 'No hay compras registradas en este rango');
            } else {
                $.each(items, function(i, c) {
                    const badgeCond = c.condicion_pago === 'Contado' ? 'success' : 'warning';
                    $('<tr></tr>').html(
                        '<td>' + (c.fecha ? c.fecha.substring(0, 10) : '-') + '</td>' +
                        '<td><strong>#' + (c.id_compra || '-') + '</strong></td>' +
                        '<td>' + escapeHtml(c.proveedor_nombre || '-') + '</td>' +
                        '<td>' + escapeHtml(c.proveedor_rif || '-') + '</td>' +
                        '<td class="fw-bold text-danger">$ ' + formatearMonedaLocal(c.total) + '</td>' +
                        '<td><span class="badge bg-' + badgeCond + '">' + escapeHtml(c.condicion_pago || '-') + '</span></td>' +
                        '<td>' + (c.situacion_pago ? c.situacion_pago.charAt(0).toUpperCase() + c.situacion_pago.slice(1) : '-') + '</td>' +
                        '<td><a href="/SP%20Perfect%20Color/notaEntregaCompra/ver?id=' + c.id_compra + '" class="btn btn-sm btn-outline-secondary" title="Ver Recepción"><i class="bi bi-eye"></i></a></td>'
                    ).appendTo($cuerpoReporte);
                });
            }

        } else if (tipo === 'ambas') {
            const items = datos.items || [];
            if (items.length === 0) {
                filaVacia(8, 'No hay operaciones en este rango');
            } else {
                $.each(items, function(i, row) {
                    const esVenta = (row.tipo_transaccion === 'Venta');
                    const badgeTipo = esVenta ? '<span class="badge bg-success">Venta</span>' : '<span class="badge bg-danger">Compra</span>';
                    const ingresoFmt = parseFloat(row.monto_ingreso) > 0 ? '<span class="text-success fw-bold">$ ' + formatearMonedaLocal(row.monto_ingreso) + '</span>' : '-';
                    const egresoFmt = parseFloat(row.monto_egreso) > 0 ? '<span class="text-danger fw-bold">$ ' + formatearMonedaLocal(row.monto_egreso) + '</span>' : '-';

                    $('<tr></tr>').html(
                        '<td>' + row.fecha + '</td>' +
                        '<td>' + badgeTipo + '</td>' +
                        '<td>#' + row.doc_numero + '</td>' +
                        '<td>' + escapeHtml(row.tercero_nombre || '-') + '</td>' +
                        '<td>' + escapeHtml(row.tercero_doc || '-') + '</td>' +
                        '<td>' + escapeHtml(row.condicion || '-') + '</td>' +
                        '<td>' + ingresoFmt + '</td>' +
                        '<td>' + egresoFmt + '</td>'
                    ).appendTo($cuerpoReporte);
                });
            }

        } else if (tipo === 'carteraCxc') {
            const items = datos.cuentas || [];
            if (items.length === 0) {
                filaVacia(6, 'No hay cuentas pendientes en este rango');
            } else {
                $.each(items, function(i, c) {
                    const badge = c.estado === 'pagado' ? 'success' : 'warning';
                    $('<tr></tr>').html(
                        '<td>' + escapeHtml(c.cliente_nombre || '-') + '</td>' +
                        '<td>' + escapeHtml(c.cliente_cedula || '-') + '</td>' +
                        '<td>$ ' + formatearMonedaLocal(c.monto_total) + '</td>' +
                        '<td><strong>$ ' + formatearMonedaLocal(c.saldo_pendiente) + '</strong></td>' +
                        '<td>' + (c.fecha_vencimiento || '-') + '</td>' +
                        '<td><span class="badge bg-' + badge + '">' + c.estado + '</span></td>'
                    ).appendTo($cuerpoReporte);
                });
            }
        }

        // Mostrar botones de exportación
        $btnPDF.css('display', 'block');
        $btnExcel.css('display', 'block');
    }

    // 5 y 6. Exporta el reporte actual a PDF o Excel (descarga de archivo, no AJAX)
    function exportar(accion) {
        const tipo = $tipoReporte.val();
        const desde = $fechaDesde.val();
        const hasta = $fechaHasta.val();
        if (!validarRangoFechas(desde, hasta)) return;
        const params = $.extend({ tipo: tipo }, parametrosReporte(tipo, desde, hasta));
        window.location.href = '/SP%20Perfect%20Color/reporte/' + accion + '?' + $.param(params);
    }

    $btnPDF.on('click', function() { exportar('exportarPdfAjax'); });
    $btnExcel.on('click', function() { exportar('exportarExcelAjax'); });
});
