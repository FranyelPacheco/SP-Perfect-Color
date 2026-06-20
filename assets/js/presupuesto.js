// Archivo: presupuesto.js
// Manejo de la vista de presupuestos


document.addEventListener('DOMContentLoaded', function() {
    var busquedaPresupuestos = document.getElementById('busquedaPresupuestos');
    var filtroEstado = document.getElementById('filtroEstadoPresupuesto');

    cargarPresupuestos();

    function cargarPresupuestos() {
        fetch('presupuesto/listarAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarPresupuestos(resultado.datos.presupuestos);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar presupuestos:', error);
            });
    }

    function mostrarPresupuestos(presupuestos) {
        if (!$.fn.DataTable.isDataTable('#tablaPresupuestos')) {
            $('#tablaPresupuestos').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    {
                        data: 'id_presupuesto',
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
                            var cap = data.charAt(0).toUpperCase() + data.slice(1);
                            return '<span class="estado-' + data + '">' + cap + '</span>';
                        }
                    },
                    { data: 'usuario_nombre' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var html = '<div class="d-flex gap-2">' +
                                '<a href="presupuesto/ver?id=' + row.id_presupuesto + '" class="btn btn-sm btn-info" title="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>';
                            if (row.estado === 'pendiente') {
                                html +=
                                    '<button class="btn btn-sm btn-success btn-aprobar-presupuesto" data-id="' + row.id_presupuesto + '"><i class="bi bi-check-lg me-1"></i>Aprobar</button>' +
                                    '<button class="btn btn-sm btn-danger btn-rechazar-presupuesto" data-id="' + row.id_presupuesto + '"><i class="bi bi-x-lg me-1"></i>Rechazar</button>';
                            }
                            html += '<button class="btn btn-sm btn-outline-danger btn-eliminar-presupuesto" data-id="' + row.id_presupuesto + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
                            html += '</div>';
                            return html;
                        }
                    }
                ]
            });
        }

        var table = $('#tablaPresupuestos').DataTable();
        table.clear();

        presupuestos.forEach(function(presupuesto) {
            table.row.add(presupuesto);
        });

        table.draw();
    }

    document.getElementById('tablaPresupuestos').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-aprobar-presupuesto');
        if (btn) { cambiarEstado(parseInt(btn.dataset.id), 'aprobado'); return; }
        btn = e.target.closest('.btn-rechazar-presupuesto');
        if (btn) { cambiarEstado(parseInt(btn.dataset.id), 'rechazado'); return; }
        btn = e.target.closest('.btn-eliminar-presupuesto');
        if (btn) {
            confirmarConModal('Eliminar', 'Esta seguro de eliminar este presupuesto?', function() {
                var fd = new FormData();
                fd.append('id', btn.dataset.id);
                fetch('presupuesto/eliminar', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarPresupuestos(); }
                    else { mostrarNotificacion(res.mensaje, 'error'); }
                })
                .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
            });
            return;
        }
    });

    if (busquedaPresupuestos) {
        busquedaPresupuestos.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaPresupuestos')) {
                $('#tablaPresupuestos').DataTable().search(this.value).draw();
            }
        });
    }

    if (filtroEstado) {
        filtroEstado.addEventListener('change', function() {
            if ($.fn.DataTable.isDataTable('#tablaPresupuestos')) {
                var table = $('#tablaPresupuestos').DataTable();
                if (this.value === '') {
                    table.column(5).search('').draw();
                } else {
                    table.column(5).search('^' + this.value + '$', true).draw();
                }
            }
        });
    }

    function cambiarEstado(id, estado) {
        var mensaje = estado === 'aprobado'
            ? 'Esta seguro de aprobar este presupuesto?'
            : 'Esta seguro de rechazar este presupuesto?';

        confirmarConModal('Confirmar', mensaje, function() {
            var formData = new FormData();
            formData.append('id', id);
            formData.append('estado', estado);

            fetch('presupuesto/cambiarEstado', {
            method: 'POST',
            body: formData
        })
        .then(function(respuesta) { return respuesta.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion(resultado.mensaje, 'exito');
                cargarPresupuestos();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexion', 'error');
        });
        });
    }
});
