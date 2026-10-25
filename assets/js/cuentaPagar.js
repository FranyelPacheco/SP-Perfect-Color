// Archivo: cuentaPagar.js
// Manejo de la vista de cuentas por pagar


document.addEventListener('DOMContentLoaded', function() {
    var busquedaCuentas = document.getElementById('busquedaCuentasPagar');

    cargarCuentas();

    function cargarCuentas() {
        fetch('/SP%20Perfect%20Color/cuentaPagar/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarCuentas(resultado.datos.cuentas);
                }
            });
    }

    function mostrarCuentas(cuentas) {
        if (!$.fn.DataTable.isDataTable('#tablaCuentasPagar')) {
            var $t = $('#tablaCuentasPagar');
            $t.DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'proveedor_nombre' },
                    { data: 'proveedor_rif' },
                    {
                        data: 'monto_total',
                        render: function(data, type, row) {
                            if (data == null) return '';
                            var usd = parseFloat(data) || 0;
                            var tasa = parseFloat((row && row.tasa_bcv) ? row.tasa_bcv : (window.TASA_CAMBIO_HOY || 0));
                            var html = '$ ' + formatearMoneda(usd);
                            if (tasa > 0) {
                                html += '<div class="text-muted small">Bs. ' + formatearMoneda(usd * tasa) + '</div>';
                            }
                            return html;
                        }
                    },
                    {
                        data: 'saldo_pendiente',
                        render: function(data) {
                            if (data == null) return '';
                            var usd = parseFloat(data) || 0;
                            var cls = usd > 0 ? 'saldo-pendiente-positivo' : 'saldo-pendiente-cero';
                            var tasaHoy = parseFloat(window.TASA_CAMBIO_HOY || 0);
                            var html = '<span class="' + cls + '">$ ' + formatearMoneda(usd) + '</span>';
                            if (usd > 0 && tasaHoy > 0) {
                                html += '<div class="text-muted small">Bs. ' + formatearMoneda(usd * tasaHoy) + '</div>';
                            }
                            return html;
                        }
                    },
                    {
                        data: 'fecha_vencimiento',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'estado',
                        render: function(data) {
                            if (!data) return '';
                            var cap = data.charAt(0).toUpperCase() + data.slice(1);
                            return '<span class="estado-' + data + '">' + cap + '</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var estaPagado = (row.estado === 'pagado' || row.situacion === 'pagado' || parseFloat(row.saldo_pendiente || 0) <= 0);
                            var btnEliminar = estaPagado
                                ? '<button class="btn btn-sm btn-outline-danger btn-eliminar-cxp" data-id="' + row.id_cuenta_pagar + '" title="Eliminar cuenta saldada" aria-label="Eliminar cuenta saldada" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>'
                                : '<button class="btn btn-sm btn-outline-secondary" disabled title="No se puede eliminar una cuenta pendiente de pago" aria-label="No se puede eliminar una cuenta pendiente de pago" data-bs-toggle="tooltip"><i class="bi bi-lock-fill"></i></button>';
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/cuentaPagar/ver?id=' + row.id_cuenta_pagar + '" class="btn btn-sm btn-info" title="Ver" aria-label="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye-fill"></i></a>' +
                                btnEliminar +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaCuentasPagar').DataTable();
        table.clear();

        cuentas.forEach(function(cuenta) {
            table.row.add(cuenta);
        });

        table.draw();
    }

    $(document).on('click', '.btn-eliminar-cxp', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = $(this).attr('data-id') || $(this).data('id');
        confirmarConModal('Eliminar', 'Esta seguro de eliminar esta cuenta por pagar?', function() {
            var fd = new FormData();
            fd.append('id', id);
            fetch('/SP%20Perfect%20Color/cuentaPagar/eliminar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarCuentas(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
        });
    });

    // Enlazar busqueda manual a DataTables
    if (busquedaCuentas) {
        busquedaCuentas.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaCuentasPagar')) {
                $('#tablaCuentasPagar').DataTable().search(this.value).draw();
            }
        });
    }
});
