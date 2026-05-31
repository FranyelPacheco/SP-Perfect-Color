// Archivo: notaEntrega.js
// Manejo de la vista de notas de entrega

const DATATABLES_SPANISH = {
    "emptyTable": "No hay informacion",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
};

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
                language: DATATABLES_SPANISH,
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
                            return 'Bs. ' + formatearMoneda(data);
                        }
                    },
                    { data: 'usuario_nombre' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + row.id + '" class="btn btn-sm btn-info" title="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>' +
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
