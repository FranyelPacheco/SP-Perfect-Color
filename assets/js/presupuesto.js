// Archivo: presupuesto.js
// Manejo de la vista de presupuestos

document.addEventListener('DOMContentLoaded', function() {
    var busquedaPresupuestos = document.getElementById('busquedaPresupuestos');
    var filtroEstado = document.getElementById('filtroEstadoPresupuesto');

    cargarPresupuestos();

    function cargarPresupuestos() {
        fetch('/SP%20Perfect%20Color/presupuesto/listarAjax')
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
                        render: function(data, type, row) {
                            if (data == null) return '';
                            var usd = parseFloat(data) || 0;
                            var tasa = parseFloat((row && row.tasa_bcv) ? row.tasa_bcv : (window.TASA_CAMBIO_HOY || 0));
                            var html = '<strong>$ ' + formatearMoneda(usd) + '</strong>';
                            if (tasa > 0) {
                                html += '<div class="text-muted small">Bs. ' + formatearMoneda(usd * tasa) + '</div>';
                            }
                            return html;
                        }
                    },
                    {
                        data: 'estado',
                        render: function(data, type) {
                            if (!data) return '';
                            var isEspera = (data === 'presupuesto' || data === 'pendiente');
                            var texto = isEspera ? 'En Espera' : (data.charAt(0).toUpperCase() + data.slice(1));
                            if (type === 'filter' || type === 'sort') {
                                return texto;
                            }
                            var cls = isEspera ? 'estado-presupuesto' : 'estado-' + data;
                            var icon = isEspera ? 'bi-clock-fill' : (data === 'aprobado' ? 'bi-check-circle-fill' : 'bi-x-circle-fill');
                            return '<span class="' + cls + '"><i class="bi ' + icon + ' me-1"></i>' + texto + '</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var html = '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/presupuesto/ver?id=' + row.id_presupuesto + '" class="btn btn-sm btn-info text-white" title="Ver Detalle" aria-label="Ver Detalle" data-bs-toggle="tooltip"><i class="bi bi-eye-fill"></i></a>';
                            if (row.estado === 'pendiente' || row.estado === 'presupuesto' || (row.id_estado_venta && parseInt(row.id_estado_venta) === 1)) {
                                html +=
                                    '<button class="btn btn-sm btn-success btn-aprobar-presupuesto" data-id="' + row.id_presupuesto + '" title="Aprobar / Procesar Entrega" aria-label="Aprobar / Procesar Entrega" data-bs-toggle="tooltip"><i class="bi bi-check-lg"></i></button>' +
                                    '<button class="btn btn-sm btn-danger btn-rechazar-presupuesto" data-id="' + row.id_presupuesto + '" title="Rechazar" aria-label="Rechazar" data-bs-toggle="tooltip"><i class="bi bi-x-lg"></i></button>';
                            }
                            html += '<button class="btn btn-sm btn-outline-danger btn-eliminar-presupuesto" data-id="' + row.id_presupuesto + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>';
                            html += '</div>';
                            return html;
                        }
                    }
                ]
            });
        }

        var table = $('#tablaPresupuestos').DataTable();
        table.clear();

        if (Array.isArray(presupuestos)) {
            presupuestos.forEach(function(presupuesto) {
                table.row.add(presupuesto);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    $(document).on('click', '.btn-aprobar-presupuesto', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) {
            window.location.href = '/SP%20Perfect%20Color/notaEntrega/crearDesdePresupuesto?id=' + id;
        }
    });

    $(document).on('click', '.btn-rechazar-presupuesto', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) {
            cambiarEstado(id, 'rechazado');
        }
    });

    $(document).on('click', '.btn-eliminar-presupuesto', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) {
            confirmarConModal('Eliminar', 'Esta seguro de eliminar este presupuesto?', function() {
                var fd = new FormData();
                fd.append('id', id);
                fetch('/SP%20Perfect%20Color/presupuesto/eliminar', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.estado === 'exito') {
                        mostrarNotificacion(res.mensaje, 'exito');
                        cargarPresupuestos();
                    } else {
                        mostrarNotificacion(res.mensaje, 'error');
                    }
                })
                .catch(function() {
                    mostrarNotificacion('Error de conexion', 'error');
                });
            });
        }
    });

    if (busquedaPresupuestos) {
        busquedaPresupuestos.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaPresupuestos')) {
                $('#tablaPresupuestos').DataTable().search(this.value).draw();
            }
        });
    }

    $(document).on('click', '.btn-filtro-presupuesto', function(e) {
        e.preventDefault();
        $('.btn-filtro-presupuesto').removeClass('btn-primary active').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary active');
        var filtro = $(this).attr('data-filtro') || '';
        if ($.fn.DataTable.isDataTable('#tablaPresupuestos')) {
            var table = $('#tablaPresupuestos').DataTable();
            table.column(5).search(filtro).draw();
        }
    });

    if (filtroEstado) {
        filtroEstado.addEventListener('change', function() {
            if ($.fn.DataTable.isDataTable('#tablaPresupuestos')) {
                var table = $('#tablaPresupuestos').DataTable();
                if (this.value === '') {
                    table.column(5).search('').draw();
                } else {
                    table.column(5).search(this.value).draw();
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

            fetch('/SP%20Perfect%20Color/presupuesto/cambiarEstado', {
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
