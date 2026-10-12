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
                        data: 'id_nota_entrega',
                        render: function(data, type, row) {
                            var val = data || (row ? (row.id || row.id_venta) : '');
                            return val ? '#' + val : '';
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
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var cp = row.condicion_pago ? row.condicion_pago.charAt(0).toUpperCase() + row.condicion_pago.slice(1) : '-';
                            var tp = row.tipo_pago_nombre || '-';
                            return cp + '<br><small class="text-muted">' + tp + '</small>';
                        }
                    },
                    {
                        data: 'estado_nombre',
                        render: function(data, type, row) {
                            var idEst = parseInt(row.id_estado_venta || 0);
                            if (idEst === 1) {
                                return '<span class="badge bg-warning text-dark"><i class="bi bi-clock-fill me-1"></i>En Espera</span>';
                            } else if (idEst === 2) {
                                return '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Aprobada</span>';
                            } else if (idEst === 3) {
                                return '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Rechazada</span>';
                            }
                            return '<span class="badge bg-secondary">' + (data || '-') + '</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var idEst = parseInt(row.id_estado_venta || 0);
                            var html = '<div class="d-flex gap-2">';
                            if (idEst === 1) {
                                html += '<a href="/SP%20Perfect%20Color/notaEntrega/crearDesdePresupuesto?id=' + (row.id_venta || row.id_nota_entrega) + '" class="btn btn-sm btn-success" title="Aprobar y Cobrar Entrega"><i class="bi bi-cash-coin me-1"></i>Cobrar / Aprobar</a>' +
                                        '<a href="/SP%20Perfect%20Color/presupuesto/ver?id=' + (row.id_venta || row.id_nota_entrega) + '" class="btn btn-sm btn-info text-white" title="Ver Detalle"><i class="bi bi-eye-fill"></i></a>';
                            } else if (idEst === 2) {
                                html += '<a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + (row.id_nota_entrega || row.id_venta) + '" class="btn btn-sm btn-info text-white" title="Ver Comprobante"><i class="bi bi-eye-fill"></i></a>' +
                                        '<a href="/SP%20Perfect%20Color/notaEntrega/pdf?id=' + (row.id_nota_entrega || row.id_venta) + '" target="_blank" class="btn btn-sm btn-primary" title="Comprobante PDF"><i class="bi bi-file-earmark-pdf-fill"></i></a>';
                            } else {
                                html += '<a href="/SP%20Perfect%20Color/presupuesto/ver?id=' + (row.id_venta || row.id_nota_entrega) + '" class="btn btn-sm btn-secondary text-white" title="Ver Presupuesto"><i class="bi bi-eye-fill"></i></a>';
                            }
                            html += '</div>';
                            return html;
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

    // Filtros rápidos
    $(document).on('click', '.btn-filtro-nota', function() {
        $('.btn-filtro-nota').removeClass('btn-primary active').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary active');
        var filtro = $(this).attr('data-filtro') || '';
        if ($.fn.DataTable.isDataTable('#tablaNotas')) {
            $('#tablaNotas').DataTable().column(6).search(filtro).draw();
        }
    });

    if (busquedaNotas) {
        busquedaNotas.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaNotas')) {
                $('#tablaNotas').DataTable().search(this.value).draw();
            }
        });
    }
});
