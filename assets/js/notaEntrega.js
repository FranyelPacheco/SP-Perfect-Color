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
                            var label = data === 'en_espera' ? 'En espera' : data.charAt(0).toUpperCase() + data.slice(1);
                            return '<span class="estado-' + data + '">' + label + '</span>';
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
                            var html = '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/notaEntrega/ver?id=' + row.id_nota_entrega + '" class="btn btn-sm btn-info" title="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>';
                            if (row.estado === 'pendiente') {
                                html += '<button class="btn btn-sm btn-warning btn-espera-nota" data-id="' + row.id_nota_entrega + '" title="Poner en Espera"><i class="bi bi-pause-circle"></i></button>';
                            } else if (row.estado === 'en_espera') {
                                html += '<a href="/SP%20Perfect%20Color/notaEntrega/editar?id=' + row.id_nota_entrega + '" class="btn btn-sm btn-primary" title="Editar Items"><i class="bi bi-pencil"></i></a>';
                                html += '<button class="btn btn-sm btn-success btn-entregar-nota" data-id="' + row.id_nota_entrega + '" title="Marcar como Entregado"><i class="bi bi-check-lg"></i></button>';
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

    document.getElementById('tablaNotas').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-espera-nota');
        if (btn) {
            if (!confirm('Poner esta nota de entrega en espera?')) return;
            var fd = new FormData();
            fd.append('id', btn.dataset.id);
            fd.append('estado', 'en_espera');
            fetch('/SP%20Perfect%20Color/notaEntrega/cambiarEstado', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarNotas(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
            return;
        }
        btn = e.target.closest('.btn-entregar-nota');
        if (btn) {
            if (!confirm('Marcar esta nota como entregada?')) return;
            var fd = new FormData();
            fd.append('id', btn.dataset.id);
            fd.append('estado', 'entregado');
            fetch('/SP%20Perfect%20Color/notaEntrega/cambiarEstado', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarNotas(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
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
