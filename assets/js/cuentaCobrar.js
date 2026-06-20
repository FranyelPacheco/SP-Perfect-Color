// Archivo: cuentaCobrar.js
// Manejo de la vista de cuentas por cobrar


document.addEventListener('DOMContentLoaded', function() {
    var busquedaCuentas = document.getElementById('busquedaCuentas');

    cargarCuentas();

    document.getElementById('tablaCuentas').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-eliminar-cxc');
        if (btn) {
            confirmarConModal('Eliminar', 'Esta seguro de eliminar esta cuenta por cobrar?', function() {
            var fd = new FormData();
            fd.append('id', btn.dataset.id);
            fetch('/SP%20Perfect%20Color/cuentaCobrar/eliminar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarCuentas(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
            });
            return;
        }
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
                        render: function(data) {
                            if (data == null) return '';
                            return '$ ' + formatearMoneda(data);
                        }
                    },
                    {
                        data: 'saldo_pendiente',
                        render: function(data) {
                            if (data == null) return '';
                            var cls = parseFloat(data) > 0 ? 'saldo-pendiente-positivo' : 'saldo-pendiente-cero';
                            return '<span class="' + cls + '">$ ' + formatearMoneda(data) + '</span>';
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
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/cuentaCobrar/ver?id=' + row.id_cuenta_cobrar + '" class="btn btn-sm btn-info" title="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>' +
                                '<button class="btn btn-sm btn-outline-danger btn-eliminar-cxc" data-id="' + row.id_cuenta_cobrar + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
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
