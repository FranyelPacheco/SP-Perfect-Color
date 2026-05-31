// Archivo: cuentaCobrar.js
// Manejo de la vista de cuentas por cobrar

const DATATABLES_SPANISH = {
    "emptyTable": "No hay informacion",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
};

document.addEventListener('DOMContentLoaded', function() {
    var busquedaCuentas = document.getElementById('busquedaCuentas');

    cargarCuentas();

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
                language: DATATABLES_SPANISH,
                columns: [
                    { data: 'cliente_nombre' },
                    { data: 'cliente_cedula' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            return row.nota_id ? 'NE #' + row.nota_id : '-';
                        }
                    },
                    {
                        data: 'monto_total',
                        render: function(data) {
                            if (data == null) return '';
                            return 'Bs. ' + formatearMoneda(data);
                        }
                    },
                    {
                        data: 'saldo_pendiente',
                        render: function(data) {
                            if (data == null) return '';
                            var cls = parseFloat(data) > 0 ? 'saldo-pendiente-positivo' : 'saldo-pendiente-cero';
                            return '<span class="' + cls + '">Bs. ' + formatearMoneda(data) + '</span>';
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
                                '<a href="/SP%20Perfect%20Color/cuentaCobrar/ver?id=' + row.id + '" class="btn btn-sm btn-info" title="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>' +
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
