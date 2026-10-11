// Archivo: tasaCambio.js
// Manejo de la interfaz y operaciones de tasa de cambio (sin opción de eliminación)

document.addEventListener('DOMContentLoaded', function() {
    var busquedaTasas = document.getElementById('busquedaTasas');
    var formTasa = document.getElementById('formularioTasaCambio');
    var modalElement = document.getElementById('modalTasaCambio');
    var modalInstancia = modalElement ? new bootstrap.Modal(modalElement) : null;
    var modalTitulo = document.getElementById('modalTasaTitulo');
    var textoBotonGuardar = document.getElementById('textoBotonGuardar');
    var inputId = document.getElementById('idTasaCambio');
    var inputFecha = document.getElementById('fechaTasa');
    var inputTasa = document.getElementById('valorTasa');
    var mensajeError = document.getElementById('mensajeErrorTasa');
    var btnNuevaTasa = document.getElementById('btnNuevaTasa');

    cargarTasas();

    if (btnNuevaTasa) {
        btnNuevaTasa.addEventListener('click', function() {
            limpiarFormulario();
        });
    }

    if (formTasa) {
        formTasa.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarTasa();
        });
    }

    function limpiarFormulario() {
        if (formTasa) formTasa.reset();
        if (inputId) inputId.value = '';
        if (inputFecha) {
            var hoy = new Date().toISOString().split('T')[0];
            inputFecha.value = hoy;
        }
        if (modalTitulo) modalTitulo.textContent = 'Registrar Tasa de Cambio';
        if (textoBotonGuardar) textoBotonGuardar.textContent = 'Guardar Tasa';
        if (mensajeError) {
            mensajeError.textContent = '';
            mensajeError.classList.add('d-none');
        }
    }

    function cargarTasas() {
        fetch('/SP%20Perfect%20Color/tasaCambio/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarTasas(resultado.datos.tasas || []);
                } else {
                    console.error('[TasaCambio] Error al listar:', resultado.mensaje);
                }
            })
            .catch(function(err) {
                console.error('[TasaCambio] Error de red:', err);
            });
    }

    function mostrarTasas(tasas) {
        if (!$.fn.DataTable.isDataTable('#tablaTasas')) {
            $('#tablaTasas').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH || {},
                order: [[0, 'desc']],
                columns: [
                    {
                        data: 'fecha',
                        render: function(data) {
                            if (!data) return '-';
                            var partes = data.split('-');
                            if (partes.length === 3) {
                                return partes[2] + '/' + partes[1] + '/' + partes[0];
                            }
                            return data;
                        }
                    },
                    {
                        data: 'tasa',
                        render: function(data) {
                            if (data == null) return '-';
                            var num = parseFloat(data);
                            return '<strong>Bs. ' + num.toLocaleString('es-VE', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) + '</strong>';
                        }
                    },
                    {
                        data: 'created_at',
                        render: function(data) {
                            if (!data) return '-';
                            var d = new Date(data.replace(' ', 'T'));
                            if (isNaN(d.getTime())) return data;
                            return d.toLocaleString('es-VE', { dateStyle: 'short', timeStyle: 'short' });
                        }
                    },
                    {
                        data: 'estado',
                        render: function(data) {
                            if (parseInt(data) === 1) {
                                return '<span class="badge bg-success">Activa</span>';
                            }
                            return '<span class="badge bg-secondary">Inactiva</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            if (!row) return '';
                            // Solo botón de edición. Cero botones de eliminación según regla de negocio.
                            return '<div class="d-flex gap-2">' +
                                '<button type="button" class="btn btn-sm btn-outline-primary btn-editar-tasa" data-id="' + row.id_tasa_cambio + '" title="Editar Tasa">' +
                                '<i class="bi bi-pencil me-1"></i>Editar' +
                                '</button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaTasas').DataTable();
        table.clear();
        tasas.forEach(function(t) {
            table.row.add(t);
        });
        table.draw();
    }

    // Evento de edición (cargar datos en modal)
    $(document).on('click', '.btn-editar-tasa', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-id') || $(this).data('id');
        if (!id) return;

        if (mensajeError) {
            mensajeError.textContent = '';
            mensajeError.classList.add('d-none');
        }

        fetch('/SP%20Perfect%20Color/tasaCambio/obtener?id=' + encodeURIComponent(id))
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito' && res.datos) {
                    var t = res.datos;
                    if (inputId) inputId.value = t.id_tasa_cambio;
                    if (inputFecha) inputFecha.value = t.fecha;
                    if (inputTasa) inputTasa.value = parseFloat(t.tasa);
                    if (modalTitulo) modalTitulo.textContent = 'Editar Tasa de Cambio (' + t.fecha + ')';
                    if (textoBotonGuardar) textoBotonGuardar.textContent = 'Actualizar Tasa';

                    if (modalInstancia) {
                        modalInstancia.show();
                    }
                } else {
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion(res.mensaje || 'Error al obtener la tasa', 'error');
                    }
                }
            })
            .catch(function(err) {
                console.error('[TasaCambio] Error:', err);
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Error de conexión con el servidor', 'error');
                }
            });
    });

    // Guardar / Actualizar
    function guardarTasa() {
        var id = inputId ? inputId.value.trim() : '';
        var fecha = inputFecha ? inputFecha.value.trim() : '';
        var tasa = inputTasa ? inputTasa.value.trim() : '';

        if (mensajeError) {
            mensajeError.textContent = '';
            mensajeError.classList.add('d-none');
        }

        if (!fecha) {
            mostrarErrorEnModal('Debe ingresar la fecha de vigencia.');
            return;
        }

        var tasaNum = parseFloat(tasa.replace(',', '.'));
        if (isNaN(tasaNum) || tasaNum <= 0) {
            mostrarErrorEnModal('El valor de la tasa debe ser un número mayor a 0 (ej. 45.5000).');
            return;
        }

        var fd = new FormData();
        fd.append('fecha', fecha);
        fd.append('tasa', tasaNum);

        var url = '/SP%20Perfect%20Color/tasaCambio/guardar';
        if (id && parseInt(id) > 0) {
            url = '/SP%20Perfect%20Color/tasaCambio/actualizar';
            fd.append('id_tasa_cambio', id);
        }

        fetch(url, {
            method: 'POST',
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.estado === 'exito') {
                if (modalInstancia) modalInstancia.hide();
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(res.mensaje, 'exito');
                }
                cargarTasas();
                // Si la fecha editada o creada es hoy, recargar la página tras 1s para actualizar las tarjetas de resumen
                var hoy = new Date().toISOString().split('T')[0];
                if (fecha === hoy) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                }
            } else {
                mostrarErrorEnModal(res.mensaje || 'No fue posible guardar la tasa.');
            }
        })
        .catch(function(err) {
            console.error('[TasaCambio] Error al guardar:', err);
            mostrarErrorEnModal('Error de comunicación con el servidor.');
        });
    }

    function mostrarErrorEnModal(texto) {
        if (mensajeError) {
            mensajeError.textContent = texto;
            mensajeError.classList.remove('d-none');
        }
    }

    // Buscador manual de la tabla
    if (busquedaTasas) {
        busquedaTasas.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaTasas')) {
                $('#tablaTasas').DataTable().search(this.value).draw();
            }
        });
    }
});
