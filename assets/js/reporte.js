document.addEventListener('DOMContentLoaded', function() {
    var tipoReporte = document.getElementById('tipoReporte');
    var fechaDesde = document.getElementById('fechaDesde');
    var fechaHasta = document.getElementById('fechaHasta');
    var btnGenerar = document.getElementById('btnGenerarReporte');
    var cuerpoReporte = document.getElementById('cuerpoReporte');
    var resumen = document.getElementById('resumenReporte');
    var totalRegistros = document.getElementById('totalRegistros');
    var montoTotal = document.getElementById('montoTotal');
    var desglose = document.getElementById('desglosePago');
    var cuerpoTipo = document.getElementById('cuerpoDesgloseTipo');
    var cuerpoMetodo = document.getElementById('cuerpoDesgloseMetodo');
    var tituloTipo = document.getElementById('tituloDesgloseTipo');

    if (btnGenerar) {
        btnGenerar.addEventListener('click', generarReporte);
    }

    function generarReporte() {
        var tipo = tipoReporte.value;
        var desde = fechaDesde.value;
        var hasta = fechaHasta.value;

        if (!desde || !hasta) {
            mostrarNotificacion('Debe seleccionar ambas fechas', 'error');
            return;
        }

        var urls = {
            ventas: '/SP%20Perfect%20Color/reporte/ventasAjax?desde=' + desde + '&hasta=' + hasta,
            ingresos: '/SP%20Perfect%20Color/reporte/ingresosAjax?desde=' + desde + '&hasta=' + hasta,
            egresos: '/SP%20Perfect%20Color/reporte/egresosAjax?desde=' + desde + '&hasta=' + hasta
        };

        fetch(urls[tipo])
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

    function formatearMonedaLocal(num) {
        if (num == null) return '0,00';
        var n = parseFloat(num);
        if (isNaN(n)) return '0,00';
        return n.toFixed(2).replace('.', ',');
    }

    function mostrarResultados(tipo, datos) {
        resumen.style.display = 'flex';
        totalRegistros.textContent = datos.cantidad || 0;
        montoTotal.textContent = '$ ' + formatearMonedaLocal(datos.total || 0);

        cuerpoReporte.innerHTML = '';
        var items = [];

        if (tipo === 'ventas') {
            items = datos.ventas || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay ventas en este rango</td></tr>';
            } else {
                items.forEach(function(v) {
                    var fila = document.createElement('tr');
                    fila.innerHTML =
                        '<td>' + v.fecha + '</td>' +
                        '<td>' + (v.cliente_nombre || '-') + '<br><small class="text-muted">' + (v.cliente_cedula || '') + '</small></td>' +
                        '<td>$ ' + formatearMonedaLocal(v.total) + '</td>' +
                        '<td>' + (v.metodo_pago || '-') + '</td>' +
                        '<td><a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + v.id + '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a></td>';
                    cuerpoReporte.appendChild(fila);
                });
            }
            // Desglose por tipo de pago y metodo
            desglose.style.display = 'flex';
            if (cuerpoTipo) {
                cuerpoTipo.innerHTML = '';
                var tipos = datos.por_tipo_pago || [];
                if (tipos.length === 0) {
                    cuerpoTipo.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>';
                } else {
                    tipos.forEach(function(t) {
                        var label = t.tipo === 'contado' ? 'Contado' : (t.tipo === 'credito' ? 'Credito' : t.tipo);
                        cuerpoTipo.innerHTML += '<tr><td>' + label + '</td><td>' + t.cantidad + '</td><td>$ ' + formatearMonedaLocal(t.total) + '</td></tr>';
                    });
                }
            }
            if (cuerpoMetodo) {
                cuerpoMetodo.innerHTML = '';
                var metodos = datos.por_metodo_pago || [];
                if (metodos.length === 0) {
                    cuerpoMetodo.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin datos de contado</td></tr>';
                } else {
                    metodos.forEach(function(m) {
                        cuerpoMetodo.innerHTML += '<tr><td>' + (m.metodo === 'sin_asignar' ? 'Sin asignar' : m.metodo) + '</td><td>' + m.cantidad + '</td><td>$ ' + formatearMonedaLocal(m.total) + '</td></tr>';
                    });
                }
            }

        } else if (tipo === 'ingresos') {
            (datos.pagos || []).forEach(function(p) {
                items.push(p);
                var fila = document.createElement('tr');
                fila.innerHTML =
                    '<td>' + p.fecha + '</td>' +
                    '<td>' + (p.cliente_nombre || 'Cliente #' + p.cliente_id) + '</td>' +
                    '<td>$ ' + formatearMonedaLocal(p.monto) + '</td>' +
                    '<td>' + p.metodo_pago + '</td>' +
                    '<td><small class="text-muted">Pago registrado</small></td>';
                cuerpoReporte.appendChild(fila);
            });
            (datos.directos || []).forEach(function(d) {
                items.push(d);
                var fila = document.createElement('tr');
                fila.innerHTML =
                    '<td>' + d.fecha + '</td>' +
                    '<td>' + (d.cliente_nombre || '-') + '</td>' +
                    '<td>$ ' + formatearMonedaLocal(d.monto) + '</td>' +
                    '<td>' + (d.metodo_pago || 'Efectivo') + '</td>' +
                    '<td><small class="text-muted">Contado directo</small></td>';
                cuerpoReporte.appendChild(fila);
            });
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay ingresos en este rango</td></tr>';
            }
            // Desglose solo por metodo
            desglose.style.display = 'flex';
            if (cuerpoTipo) {
                if (tituloTipo) tituloTipo.innerHTML = '<i class="bi bi-tag me-1 text-primary"></i>Por Tipo';
                cuerpoTipo.innerHTML = '';
                var contadoTotal = 0;
                var contadoCant = 0;
                (datos.directos || []).forEach(function(d) { contadoTotal += parseFloat(d.monto || 0); contadoCant++; });
                var credTotal = 0;
                var credCant = 0;
                (datos.pagos || []).forEach(function(p) { credTotal += parseFloat(p.monto || 0); credCant++; });
                if (contadoCant === 0 && credCant === 0) {
                    cuerpoTipo.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>';
                } else {
                    if (contadoCant > 0) cuerpoTipo.innerHTML += '<tr><td>Contado directo</td><td>' + contadoCant + '</td><td>$ ' + formatearMonedaLocal(contadoTotal) + '</td></tr>';
                    if (credCant > 0) cuerpoTipo.innerHTML += '<tr><td>Credito (cobrado)</td><td>' + credCant + '</td><td>$ ' + formatearMonedaLocal(credTotal) + '</td></tr>';
                }
            }
            if (cuerpoMetodo) {
                cuerpoMetodo.innerHTML = '';
                var metodos = datos.por_metodo_pago || [];
                if (metodos.length === 0) {
                    cuerpoMetodo.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>';
                } else {
                    metodos.forEach(function(m) {
                        cuerpoMetodo.innerHTML += '<tr><td>' + m.metodo + '</td><td>' + m.cantidad + '</td><td>$ ' + formatearMonedaLocal(m.total) + '</td></tr>';
                    });
                }
            }

        } else if (tipo === 'egresos') {
            items = datos.egresos || [];
            if (items.length === 0) {
                cuerpoReporte.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay egresos en este rango</td></tr>';
            } else {
                items.forEach(function(e) {
                    var fila = document.createElement('tr');
                    fila.innerHTML =
                        '<td>' + e.fecha + '</td>' +
                        '<td>' + (e.proveedor_nombre || 'Proveedor #' + e.proveedor_id) + '</td>' +
                        '<td>$ ' + formatearMonedaLocal(e.monto) + '</td>' +
                        '<td>' + e.metodo_pago + '</td>' +
                        '<td><small class="text-muted">Pago realizado</small></td>';
                    cuerpoReporte.appendChild(fila);
                });
            }
            // Desglose solo por metodo
            desglose.style.display = 'flex';
            if (cuerpoTipo) {
                if (tituloTipo) tituloTipo.innerHTML = '<i class="bi bi-tag me-1 text-primary"></i>Por Tipo';
                cuerpoTipo.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Egresos agrupados por metodo</td></tr>';
            }
            if (cuerpoMetodo) {
                cuerpoMetodo.innerHTML = '';
                var metodos = datos.por_metodo_pago || [];
                if (metodos.length === 0) {
                    cuerpoMetodo.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>';
                } else {
                    metodos.forEach(function(m) {
                        cuerpoMetodo.innerHTML += '<tr><td>' + m.metodo + '</td><td>' + m.cantidad + '</td><td>$ ' + formatearMonedaLocal(m.total) + '</td></tr>';
                    });
                }
            }
        }
    }
});
