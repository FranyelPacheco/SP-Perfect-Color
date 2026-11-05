// Archivo: tasaCambio.js
// Manejo de la interfaz y operaciones de tasa de cambio (sin opción de eliminación)

$(function() {
    const $formTasa = $('#formularioTasaCambio');
    const $modal = $('#modalTasaCambio');
    const modalInstancia = $modal.length ? new bootstrap.Modal($modal[0]) : null;
    const $modalTitulo = $('#modalTasaTitulo');
    const $textoBotonGuardar = $('#textoBotonGuardar');
    const $inputId = $('#idTasaCambio');
    const $inputFecha = $('#fechaTasa');
    const $feedbackFechaTasa = $('#feedbackFechaTasa');
    const $inputTasa = $('#valorTasa');
    const $mensajeError = $('#mensajeErrorTasa');
    const $btnGuardarTasa = $('#btnGuardarTasa');
    let temporizadorFecha;

    $inputTasa.on('keydown', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) {
            e.preventDefault();
        }
    });
    $inputTasa.on('input', function() {
        // Comas a puntos, solo números y un único punto decimal
        let val = this.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
        const puntos = val.split('.');
        if (puntos.length > 2) {
            val = puntos[0] + '.' + puntos.slice(1).join('');
        }
        // Máximo 6 dígitos enteros y 2 decimales
        const partes = val.split('.');
        if (partes[0].length > 6) {
            partes[0] = partes[0].substring(0, 6);
        }
        if (partes.length > 1 && partes[1].length > 2) {
            partes[1] = partes[1].substring(0, 2);
        }
        this.value = partes.length > 1 ? partes[0] + '.' + partes[1] : partes[0];
    });

    cargarTasas();

    function limpiarEstadoFecha() {
        $inputFecha.removeClass('is-invalid is-valid');
        $feedbackFechaTasa.addClass('d-none').removeClass('d-block');
        $btnGuardarTasa.prop('disabled', false);
    }

    function verificarFechaEnTiempoReal() {
        clearTimeout(temporizadorFecha);
        const fecha = ($inputFecha.val() || '').trim();
        if (!fecha) {
            limpiarEstadoFecha();
            return;
        }
        const idActual = ($inputId.val() || '').trim();

        temporizadorFecha = setTimeout(function() {
            api.get('tasaCambio/verificarFechaAjax', { fecha: fecha, id: idActual })
                .then(function(res) {
                    if (res.estado !== 'exito' || !res.datos) return;
                    if (res.datos.existe) {
                        $inputFecha.addClass('is-invalid').removeClass('is-valid');
                        $feedbackFechaTasa
                            .text('Ya existe una tasa registrada para la fecha ' + (res.datos.fecha_formateada || fecha))
                            .removeClass('d-none').addClass('d-block');
                        $btnGuardarTasa.prop('disabled', true);
                    } else {
                        $inputFecha.removeClass('is-invalid').addClass('is-valid');
                        $feedbackFechaTasa.addClass('d-none').removeClass('d-block');
                        $btnGuardarTasa.prop('disabled', false);
                    }
                })
                .catch(function(err) {
                    console.error('[TasaCambio] Error al verificar fecha:', err);
                });
        }, 200);
    }

    $inputFecha.on('change input', verificarFechaEnTiempoReal);

    $('#btnNuevaTasa').on('click', function() {
        limpiarFormulario();
        verificarFechaEnTiempoReal();
    });

    $formTasa.on('submit', function(e) {
        e.preventDefault();
        guardarTasa();
    });

    function limpiarMensaje() {
        $mensajeError.text('').addClass('d-none');
    }

    function limpiarFormulario() {
        if ($formTasa.length) $formTasa[0].reset();
        $inputId.val('');
        limpiarEstadoFecha();
        $inputFecha.val(new Date().toISOString().split('T')[0]);
        $modalTitulo.text('Registrar Tasa de Cambio');
        $textoBotonGuardar.text('Guardar Tasa');
        limpiarMensaje();
    }

    function cargarTasas() {
        api.get('tasaCambio/listarAjax')
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
                            const partes = data.split('-');
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
                            const num = parseFloat(data);
                            return '<strong>Bs. ' + num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>';
                        }
                    },
                    {
                        data: 'created_at',
                        render: function(data) {
                            if (!data) return '-';
                            const d = new Date(data.replace(' ', 'T'));
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
                                '<button type="button" class="btn btn-sm btn-outline-primary btn-editar-tasa" data-id="' + row.id_tasa_cambio + '" title="Editar Tasa" aria-label="Editar Tasa" data-bs-toggle="tooltip">' +
                                '<i class="bi bi-pencil"></i>' +
                                '</button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        const table = $('#tablaTasas').DataTable();
        table.clear();
        $.each(tasas, function(i, t) {
            table.row.add(t);
        });
        table.draw();
    }

    // Evento de edición (cargar datos en modal)
    $(document).on('click', '.btn-editar-tasa', function(e) {
        e.preventDefault();
        const id = $(this).attr('data-id');
        if (!id) return;

        limpiarEstadoFecha();
        limpiarMensaje();

        api.get('tasaCambio/obtener', { id: id })
            .then(function(res) {
                if (res.estado === 'exito' && res.datos) {
                    const t = res.datos;
                    $inputId.val(t.id_tasa_cambio);
                    $inputFecha.val(t.fecha);
                    $inputTasa.val(parseFloat(t.tasa));
                    $modalTitulo.text('Editar Tasa de Cambio (' + t.fecha + ')');
                    $textoBotonGuardar.text('Actualizar Tasa');
                    if (modalInstancia) modalInstancia.show();
                } else {
                    mostrarNotificacion(res.mensaje || 'Error al obtener la tasa', 'error');
                }
            })
            .catch(function(err) {
                console.error('[TasaCambio] Error:', err);
                mostrarNotificacion('No se pudo conectar con el servidor. Intente de nuevo.', 'error');
            });
    });

    // Guardar / Actualizar
    function guardarTasa() {
        const id = ($inputId.val() || '').trim();
        const fecha = ($inputFecha.val() || '').trim();
        const tasa = ($inputTasa.val() || '').trim();

        limpiarMensaje();

        if (!fecha) {
            mostrarErrorEnModal('Debe ingresar la fecha de vigencia.');
            return;
        }

        const tasaNum = parseFloat(tasa.replace(',', '.'));
        if (isNaN(tasaNum) || tasaNum <= 0) {
            mostrarErrorEnModal('El valor de la tasa debe ser un número mayor a 0 (ej. 45.50).');
            return;
        }

        if (tasaNum > 999999.99) {
            mostrarErrorEnModal('El valor de la tasa no puede superar 999.999,99.');
            return;
        }

        const datos = { fecha: fecha, tasa: tasaNum.toFixed(2) };
        const esEdicion = (id && parseInt(id) > 0);
        if (esEdicion) datos.id_tasa_cambio = id;

        function ejecutarEnvio() {
            $btnGuardarTasa.prop('disabled', true);

            api.post(esEdicion ? 'tasaCambio/actualizar' : 'tasaCambio/guardar', datos)
            .then(function(res) {
                $btnGuardarTasa.prop('disabled', false);
                if (res.estado === 'exito') {
                    if (modalInstancia) modalInstancia.hide();
                    mostrarNotificacion(res.mensaje, 'exito');
                    cargarTasas();
                    // Si la fecha editada o creada es hoy, recargar la página tras 1s para actualizar las tarjetas de resumen
                    if (fecha === new Date().toISOString().split('T')[0]) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1200);
                    }
                } else {
                    mostrarErrorEnModal(res.mensaje || 'No fue posible guardar la tasa.');
                }
            })
            .catch(function(err) {
                $btnGuardarTasa.prop('disabled', false);
                console.error('[TasaCambio] Error al guardar:', err);
                mostrarErrorEnModal('No se pudo conectar con el servidor. Intente de nuevo.');
            });
        }

        if (esEdicion) {
            confirmarConModal('Actualizar Tasa', '¿Está seguro de actualizar la tasa para la fecha ' + escapeHtml(fecha) + '? Esta modificación actualizará los cálculos asociados.', ejecutarEnvio, 'btn-success');
        } else {
            ejecutarEnvio();
        }
    }

    function mostrarErrorEnModal(texto) {
        $mensajeError.text(texto).removeClass('d-none');
    }

    // Buscador manual de la tabla
    vincularBusqueda('#busquedaTasas', '#tablaTasas');
});
