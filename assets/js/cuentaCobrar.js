// Archivo: cuentaCobrar.js
// Manejo de la vista de cuentas por cobrar


document.addEventListener('DOMContentLoaded', function() {
    var busquedaCuentas = document.getElementById('busquedaCuentas');

    cargarCuentas();

    $(document).on('click', '.btn-eliminar-cxc', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = $(this).attr('data-id') || $(this).data('id');
        confirmarConModal('Eliminar', 'Esta seguro de eliminar esta cuenta por cobrar?', function() {
            var fd = new FormData();
            fd.append('id', id);
            fetch('/SP%20Perfect%20Color/cuentaCobrar/eliminar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarCuentas(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
        });
    });

    if (busquedaCuentas) {
        busquedaCuentas.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaCuentas')) {
                $('#tablaCuentas').DataTable().search(this.value).draw();
            }
        });
    }

    function cargarCuentas() {
        fetch('/SP%20Perfect%20Color/cuentaCobrar/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarCuentas(resultado.datos.cuentas);
                }
            });
    }

    function mostrarCuentas(cuentas) {
        if (!$.fn.DataTable.isDataTable('#tablaCuentas')) {
            $('#tablaCuentas').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'cliente_nombre' },
                    { data: 'cliente_cedula' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            return row.id_nota_entrega ? 'NE #' + row.id_nota_entrega : '-';
                        }
                    },
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
                        render: function(data, type, row) {
                            if (!data) return '-';
                            var vencida = row.estado === 'pendiente' && new Date(data) < new Date();
                            var badge = vencida ? ' <span class="estado-moroso">Vencida</span>' : '';
                            return data + badge;
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
                                ? '<button class="btn btn-sm btn-outline-danger btn-eliminar-cxc" data-id="' + row.id_cuenta_cobrar + '" title="Eliminar cuenta saldada" aria-label="Eliminar cuenta saldada" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>'
                                : '<button class="btn btn-sm btn-outline-secondary" disabled title="No se puede eliminar una cuenta pendiente de cobro" aria-label="No se puede eliminar una cuenta pendiente de cobro" data-bs-toggle="tooltip"><i class="bi bi-lock-fill"></i></button>';
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/cuentaCobrar/ver?id=' + row.id_cuenta_cobrar + '" class="btn btn-sm btn-info text-white" title="Ver" aria-label="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye-fill"></i></a>' +
                                btnEliminar +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaCuentas').DataTable();
        table.clear();

        cuentas.forEach(function(cuenta) {
            table.row.add(cuenta);
        });

        table.draw();
    }
});
