// Archivo: proveedor.js
// Manejo de la vista de gestion de proveedores

var rubrosDisponibles = [];

$(function() {
    const $modalProveedor = $('#modalProveedor');
    const $formularioProveedor = $('#formularioProveedor');
    const $tituloModal = $('#tituloModalProveedor');
    const $proveedorId = $('#proveedorId');
    const $tipoRifProveedor = $('#tipoRifProveedor');
    const $rifProveedor = $('#rifProveedor');
    const $rifCompletoProveedor = $('#rifCompletoProveedor');
    const $feedbackRifProveedor = $('#feedbackRifProveedor');
    const $nombreEmpresaProveedor = $('#nombreEmpresaProveedor');
    const $operadoraProveedor = $('#operadoraProveedor');
    const $telefonoNumeroProveedor = $('#telefonoNumeroProveedor');
    const $telefonoProveedor = $('#telefonoProveedor');

    // Elementos segundo teléfono
    const $btnToggleTel2Proveedor = $('#btnToggleTel2Proveedor');
    const $seccionTel2Proveedor = $('#seccionTel2Proveedor');
    const $operadoraProveedor2 = $('#operadoraProveedor2');
    const $telefonoNumeroProveedor2 = $('#telefonoNumeroProveedor2');
    const $telefonoProveedor2 = $('#telefonoProveedor2');

    const $correoProveedor = $('#correoProveedor');
    const $feedbackCorreoProveedor = $('#feedbackCorreoProveedor');
    const $direccionProveedor = $('#direccionProveedor');
    const $rubrosContainer = $('#rubrosContainer');
    const $mensajeError = $('#mensajeErrorProveedor');
    const $btnGuardarProveedor = $formularioProveedor.find('button[type="submit"]');

    const REGEX_EMAIL = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const esAdmin = $('#btnNuevoProveedor').length > 0;

    // Cargar rubros disponibles
    api.get('inventario/listarRubrosAjax')
        .then(function(res) {
            if (res.estado === 'exito') {
                rubrosDisponibles = res.datos.rubros;
            }
        })
        .catch(function(e) {
            console.error('Error al cargar rubros:', e);
        });

    cargarProveedores();

    function crearItemRubro(valor) {
        const $select = $('<select name="rubros[]" class="form-select"></select>')
            .append('<option value="">Seleccione un rubro...</option>');
        $.each(rubrosDisponibles, function(i, r) {
            $select.append($('<option></option>').val(r.id_rubro).text(r.nombre));
        });
        if (valor) $select.val(valor);
        return $('<div class="input-group mb-2 rubro-item"></div>')
            .append($select)
            .append('<button class="btn btn-outline-danger btn-remove-rubro" type="button"><i class="bi bi-x"></i></button>');
    }

    function resetRubrosContainer(valores) {
        $rubrosContainer.empty();
        if (valores && valores.length) {
            $.each(valores, function(i, v) { $rubrosContainer.append(crearItemRubro(v)); });
        } else {
            $rubrosContainer.append(crearItemRubro(''));
        }
    }

    function resetearSegundoTelefonoProveedor() {
        $seccionTel2Proveedor.addClass('d-none');
        $btnToggleTel2Proveedor.removeClass('d-none');
        $telefonoNumeroProveedor2.val('');
        $telefonoProveedor2.val('');
    }

    $btnToggleTel2Proveedor.on('click', function() {
        $seccionTel2Proveedor.removeClass('d-none');
        $(this).addClass('d-none');
    });

    $('#btnQuitarTel2Proveedor').on('click', resetearSegundoTelefonoProveedor);

    $('#btnNuevoProveedor').on('click', function() {
        $tituloModal.text('Nuevo Proveedor');
        $proveedorId.val('');
        $rifProveedor.val('');
        limpiarEstadoRif();
        bloquearRif(false);
        limpiarEstadoCorreoProveedor();
        $nombreEmpresaProveedor.val('');
        $telefonoProveedor.val('');
        resetearSegundoTelefonoProveedor();
        $correoProveedor.val('');
        $direccionProveedor.val('');
        resetRubrosContainer();
        $mensajeError.addClass('d-none');
        bootstrap.Modal.getOrCreateInstance($modalProveedor[0]).show();
    });

    $('#btnCerrarModalProveedor, #btnCancelarProveedor').on('click', function() {
        bootstrap.Modal.getInstance($modalProveedor[0]).hide();
    });

    $modalProveedor.on('hidden.bs.modal', function() {
        $formularioProveedor[0].reset();
        limpiarEstadoRif();
        limpiarEstadoCorreoProveedor();
        resetearSegundoTelefonoProveedor();
        resetRubrosContainer();
        $mensajeError.addClass('d-none');
    });

    $('#btnAgregarRubro').on('click', function() {
        $rubrosContainer.append(crearItemRubro(''));
    });

    $rubrosContainer.on('click', '.btn-remove-rubro', function() {
        if ($rubrosContainer.find('.rubro-item').length > 1) {
            $(this).closest('.rubro-item').remove();
        }
    });

    $formularioProveedor.on('submit', async function(evento) {
        evento.preventDefault();
        await guardarProveedor();
    });

    function asignarOperadora($select, valor) {
        if (!$select.find('option').filter(function () { return this.value === valor; }).length) {
            $select.append(new Option(valor, valor));
        }
        $select.val(valor);
    }

    function bloquearRif(bloquear) {
        $rifProveedor.prop('disabled', bloquear);
        if (bloquear) $rifProveedor.attr('title', 'El RIF no se puede modificar');
        else $rifProveedor.removeAttr('title');
        $tipoRifProveedor.prop('disabled', bloquear);
    }

    function actualizarBotonGuardarProveedor() {
        $btnGuardarProveedor.prop('disabled', $rifProveedor.hasClass('is-invalid') || $correoProveedor.hasClass('is-invalid'));
    }

    // Marca un campo como inválido/válido y muestra u oculta su mensaje; estado null lo deja limpio
    function marcarCampo($campo, $fb, estado, mensaje, conIcono) {
        $campo.toggleClass('is-invalid', estado === 'invalid').toggleClass('is-valid', estado === 'valid');
        if (estado === 'invalid') {
            $campo.attr('title', mensaje);
            if (conIcono) $fb.html('<i class="bi bi-exclamation-circle-fill me-1"></i>' + escapeHtml(mensaje));
            else $fb.text(mensaje);
            $fb.attr('title', mensaje).removeClass('d-none');
        } else {
            $campo.removeAttr('title');
            $fb.addClass('d-none').removeAttr('title');
        }
        actualizarBotonGuardarProveedor();
    }

    function limpiarEstadoRif() {
        marcarCampo($rifProveedor, $feedbackRifProveedor, null);
    }

    function limpiarEstadoCorreoProveedor() {
        marcarCampo($correoProveedor, $feedbackCorreoProveedor, null);
    }

    let temporizadorRif;
    function verificarRifEnTiempoReal() {
        clearTimeout(temporizadorRif);
        const num = $rifProveedor.val().trim();
        const tipo = $tipoRifProveedor.val() || 'J';
        if (!num || num.length < 3) {
            limpiarEstadoRif();
            return;
        }

        const rifCompleto = tipo + '-' + num;
        const id = $proveedorId.val() || '';

        temporizadorRif = setTimeout(async function() {
            try {
                const res = await api.get('proveedor/verificarRifAjax', { rif: rifCompleto, id: id });
                if (res.estado === 'exito') {
                    if (res.datos.existe) marcarCampo($rifProveedor, $feedbackRifProveedor, 'invalid', 'Proveedor ya registrado');
                    else marcarCampo($rifProveedor, $feedbackRifProveedor, 'valid');
                }
            } catch (e) {
                console.error('Error al verificar RIF:', e);
            }
        }, 300);
    }

    let temporizadorCorreoProveedor;
    function verificarCorreoEnTiempoReal() {
        clearTimeout(temporizadorCorreoProveedor);
        const correo = $correoProveedor.val().trim();
        if (!correo) {
            limpiarEstadoCorreoProveedor();
            return;
        }

        if (!REGEX_EMAIL.test(correo)) {
            marcarCampo($correoProveedor, $feedbackCorreoProveedor, 'invalid', 'Formato de correo inválido (ej: proveedor@correo.com)', true);
            $correoProveedor.attr('title', 'Formato de correo inválido');
            $feedbackCorreoProveedor.attr('title', 'Formato de correo inválido');
            return;
        }

        const id = $proveedorId.val() || '';

        temporizadorCorreoProveedor = setTimeout(async function() {
            try {
                const res = await api.get('proveedor/verificarCorreoAjax', { correo: correo, id: id });
                if (res.estado === 'exito') {
                    if (res.datos.existe) marcarCampo($correoProveedor, $feedbackCorreoProveedor, 'invalid', 'Correo ya registrado', true);
                    else marcarCampo($correoProveedor, $feedbackCorreoProveedor, 'valid');
                }
            } catch (e) {
                console.error('Error al verificar correo:', e);
            }
        }, 300);
    }

    $rifProveedor.on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        verificarRifEnTiempoReal();
    });
    $tipoRifProveedor.on('change', verificarRifEnTiempoReal);
    $correoProveedor.on('input blur', verificarCorreoEnTiempoReal);

    $telefonoNumeroProveedor.add($telefonoNumeroProveedor2).on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    async function cargarProveedores() {
        try {
            const resultado = await api.get('proveedor/listarAjax');

            if (resultado.estado === 'exito') {
                mostrarProveedores(resultado.datos.proveedores);
            }
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
        }
    }

    function mostrarProveedores(proveedores) {
        if (!$.fn.DataTable.isDataTable('#tablaProveedores')) {
            const columnsDef = [
                { data: 'rif', render: $.fn.dataTable.render.text() },
                { data: 'nombre_empresa' },
                {
                    data: 'telefonos',
                    render: function(d) { return escapeHtml(d) || '-'; }
                },
                {
                    data: 'correo',
                    render: function(d) { return escapeHtml(d) || '-'; }
                },
                {
                    data: 'rubros',
                    render: function(d) { return escapeHtml(d) || '-'; }
                }
            ];

            if (esAdmin) {
                columnsDef.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        if (!row) return '';
                        const nom = escapeHtml(row.nombre_empresa);
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-proveedor" data-id="' + row.id_proveedor + '" title="Editar" aria-label="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="' + row.id_proveedor + '" data-nombre="' + nom + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
                            '</div>';
                    }
                });
            }

            $('#tablaProveedores').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: columnsDef
            });
        }

        const table = $('#tablaProveedores').DataTable();
        table.clear();

        if (Array.isArray(proveedores)) {
            $.each(proveedores, function(i, proveedor) {
                table.row.add(proveedor);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    alHacerClick('.btn-editar-proveedor', function() {
        const id = parseInt($(this).attr('data-id'));
        if (!isNaN(id)) {
            abrirModalEditar(id);
        }
    });

    alHacerClick('.btn-eliminar-proveedor', function() {
        const id = parseInt($(this).attr('data-id'));
        const nombre = $(this).attr('data-nombre') || 'este proveedor';
        if (!isNaN(id)) {
            eliminarProveedor(id, nombre);
        }
    });

    async function abrirModalEditar(id) {
        try {
            const resultado = await api.get('proveedor/obtener', { id: id });

            if (resultado.estado === 'exito') {
                const proveedor = resultado.datos;

                $tituloModal.text('Editar Proveedor');
                $proveedorId.val(proveedor.id_proveedor);
                limpiarEstadoRif();
                limpiarEstadoCorreoProveedor();

                // Parsear RIF (J-12345678)
                const partesRif = (proveedor.rif || '').split('-');
                if (partesRif.length === 2 && $tipoRifProveedor.length) {
                    $tipoRifProveedor.val(partesRif[0]);
                    $rifProveedor.val(partesRif[1]);
                } else {
                    $rifProveedor.val(proveedor.rif);
                }
                bloquearRif(true);

                $nombreEmpresaProveedor.val(proveedor.nombre_empresa);

                // Parsear teléfonos (pueden venir separados por coma)
                resetearSegundoTelefonoProveedor();
                const arrayTels = (proveedor.telefonos || '').split(',').map(function(t) { return t.trim(); });
                if (arrayTels[0]) {
                    const telRaw1 = arrayTels[0].replace(/[^0-9]/g, '');
                    if (telRaw1.length === 11 && $operadoraProveedor.length) {
                        asignarOperadora($operadoraProveedor, telRaw1.substring(0, 4));
                        $telefonoNumeroProveedor.val(telRaw1.substring(4));
                    } else {
                        $telefonoNumeroProveedor.val(telRaw1);
                    }
                }
                if (arrayTels[1]) {
                    const telRaw2 = arrayTels[1].replace(/[^0-9]/g, '');
                    if (telRaw2.length === 11 && $operadoraProveedor2.length) {
                        asignarOperadora($operadoraProveedor2, telRaw2.substring(0, 4));
                        $telefonoNumeroProveedor2.val(telRaw2.substring(4));
                        $seccionTel2Proveedor.removeClass('d-none');
                        $btnToggleTel2Proveedor.addClass('d-none');
                    }
                }

                $correoProveedor.val(proveedor.correo || '');
                $direccionProveedor.val(proveedor.direccion || '');
                const rubrosArray = proveedor.rubros_id ? proveedor.rubros_id.split(',').filter(Boolean).map(Number) : [];
                resetRubrosContainer(rubrosArray.length ? rubrosArray : ['']);
                $mensajeError.addClass('d-none');

                bootstrap.Modal.getOrCreateInstance($modalProveedor[0]).show();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener proveedor:', error);
            mostrarNotificacion('No se pudieron cargar los datos del proveedor. Actualice la página e intente de nuevo.', 'error');
        }
    }

    async function guardarProveedor() {
        const id = $proveedorId.val();
        const esEdicion = id !== '';

        const rifNum = $rifProveedor.val().trim();
        const tipoRif = $tipoRifProveedor.val() || 'J';
        if (!rifNum) {
            mostrarError('El RIF es obligatorio');
            $rifProveedor.trigger('focus');
            return;
        }

        const rifFinal = tipoRif + '-' + rifNum;
        $rifCompletoProveedor.val(rifFinal);

        const nombreEmpresaVal = $nombreEmpresaProveedor.val().trim();
        if (!nombreEmpresaVal) {
            mostrarError('El nombre de la empresa es obligatorio');
            $nombreEmpresaProveedor.trigger('focus');
            return;
        }
        if (nombreEmpresaVal.length < 3 || nombreEmpresaVal.length > 60) {
            mostrarError('El nombre de la empresa debe tener entre 3 y 60 caracteres');
            $nombreEmpresaProveedor.trigger('focus');
            return;
        }
        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,\-&]+$/.test(nombreEmpresaVal)) {
            mostrarError('El nombre de la empresa contiene caracteres no permitidos (solo letras, números y signos . , - &)');
            $nombreEmpresaProveedor.trigger('focus');
            return;
        }

        const correoVal = $correoProveedor.val().trim();
        if (!correoVal) {
            mostrarError('El correo electrónico es obligatorio');
            $correoProveedor.trigger('focus');
            return;
        }
        if (!REGEX_EMAIL.test(correoVal)) {
            mostrarError('El correo electrónico no tiene un formato válido');
            $correoProveedor.trigger('focus');
            return;
        }
        if ($correoProveedor.hasClass('is-invalid')) {
            mostrarError('El correo electrónico es inválido o ya se encuentra registrado');
            $correoProveedor.trigger('focus');
            return;
        }

        // Armar telefono 1 (obligatorio)
        const telNum = $telefonoNumeroProveedor.val().trim();
        const op = $operadoraProveedor.val() || '';
        if (!telNum) {
            mostrarError('El teléfono principal es obligatorio');
            $telefonoNumeroProveedor.trigger('focus');
            return;
        }
        if (telNum.length !== 7) {
            mostrarError('El número de teléfono debe tener exactamente 7 dígitos');
            $telefonoNumeroProveedor.trigger('focus');
            return;
        }
        const tel1Completo = op + telNum;
        $telefonoProveedor.val(tel1Completo);

        // Telefono 2
        const telNum2 = ($telefonoNumeroProveedor2.val() || '').trim();
        const op2 = $operadoraProveedor2.val() || '';
        let tel2Completo = '';
        if (telNum2) {
            if (telNum2.length !== 7) {
                mostrarError('El segundo número de teléfono debe tener exactamente 7 dígitos');
                $telefonoNumeroProveedor2.trigger('focus');
                return;
            }
            tel2Completo = op2 + telNum2;
            if (tel1Completo === tel2Completo) {
                mostrarError('El segundo teléfono no puede ser igual al teléfono principal');
                $telefonoNumeroProveedor2.trigger('focus');
                return;
            }
        }

        const rubroSeleccionado = $rubrosContainer.find('select[name="rubros[]"]').filter(function() { return !!this.value; }).length > 0;
        if (!rubroSeleccionado) {
            mostrarError('Debe seleccionar al menos un rubro');
            return;
        }

        const formData = new FormData($formularioProveedor[0]);
        formData.set('rif', rifFinal);
        formData.set('telefono', tel1Completo);
        if (tel2Completo) {
            formData.set('telefono2', tel2Completo);
        } else {
            formData.delete('telefono2');
        }

        if (esEdicion) {
            formData.set('id', id);
        }

        try {
            const resultado = await api.post(esEdicion ? 'proveedor/actualizar' : 'proveedor/guardar', formData);

            if (resultado.estado === 'exito') {
                bootstrap.Modal.getInstance($modalProveedor[0]).hide();
                cargarProveedores();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar proveedor:', error);
            mostrarError('No se pudo guardar el proveedor. Revise su conexión e intente de nuevo.');
        }
    }

    function eliminarProveedor(id, nombre) {
        confirmarConModal('Eliminar', '¿Está seguro de eliminar al proveedor ' + escapeHtml(nombre) + '?', function() {
            api.post('proveedor/eliminar', { id: id })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    cargarProveedores();
                    mostrarNotificacion(resultado.mensaje, 'exito');
                } else {
                    mostrarNotificacion(resultado.mensaje, 'error');
                }
            })
            .catch(function(error) {
                console.error('Error al eliminar proveedor:', error);
                mostrarNotificacion('No se pudo eliminar el proveedor. Revise su conexión e intente de nuevo.', 'error');
            });
        });
    }

    vincularBusqueda('#busquedaProveedores', '#tablaProveedores');

    function mostrarError(mensaje) {
        $mensajeError.text(mensaje).removeClass('d-none');
    }
});
