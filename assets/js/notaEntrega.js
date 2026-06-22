// Archivo: notaEntrega.js
// Manejo de la vista de notas de entrega


document.addEventListener('DOMContentLoaded', function() {
    var busquedaNotas = document.getElementById('busquedaNotas');

    cargarNotas();

    function cargarNotas() {
        fetch('/SP%20Perfect%20Color/notaEntrega/listarAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarNotas(resultado.datos.notas);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar notas:', error);
            });
    }

    function mostrarNotas(notas) {
        if (!$.fn.DataTable.isDataTable('#tablaNotas')) {
            $('#tablaNotas').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    {
                        data: 'id',
                        render: function(data) {
                            if (data == null) return '';
                            return '#' + data;
                        }
                    },
                    { data: 'fecha' },
                    { data: 'cliente_nombre' },
                    { data: 'cliente_cedula' },
                    {
                        data: 'total',
                        render: function(data) {
                            if (data == null) return '';
                            return '$ ' + formatearMoneda(data);
                        }
                    },
                    {
                        data: 'estado',
                        render: function(data) {
                            if (!data) return '';
                            return '<span class="estado-entregado">Entregado</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var cp = row.condicion_pago ? row.condicion_pago.charAt(0).toUpperCase() + row.condicion_pago.slice(1) : '-';
                            var tp = row.tipo_pago_nombre || '-';
                            return cp + '<br><small class="text-muted">' + tp + '</small>';
                        }
                    },
                    { data: 'usuario_nombre' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + row.id_nota_entrega + '" class="btn btn-sm btn-info" title="Ver"><i class="bi bi-eye"></i></a>' +
                                '<a href="/SP%20Perfect%20Color/notaEntrega/editar?id=' + row.id_nota_entrega + '" class="btn btn-sm btn-primary" title="Editar Items"><i class="bi bi-pencil"></i></a>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaNotas').DataTable();
        table.clear();

        notas.forEach(function(nota) {
            table.row.add(nota);
        });

        table.draw();
    }

    if (busquedaNotas) {
        busquedaNotas.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaNotas')) {
                $('#tablaNotas').DataTable().search(this.value).draw();
            }
        });
    }
});
