// Archivo: cliente.js
// Manejo de la vista de gestion de clientes

$(function() {
    const $modalCliente = $('#modalCliente');
    const $formularioCliente = $('#formularioCliente');
    const $tituloModal = $('#tituloModalCliente');
    const $clienteId = $('#clienteId');
    const $tipoDocCliente = $('#tipoDocCliente');
    const $cedulaCliente = $('#cedulaCliente');
    const $cedulaCompletaCliente = $('#cedulaCompletaCliente');
    const $feedbackCedulaCliente = $('#feedbackCedulaCliente');
    const $nombresCliente = $('#nombresCliente');
    const $apellidosCliente = $('#apellidosCliente');
    const $operadoraCliente = $('#operadoraCliente');
    const $telefonoNumeroCliente = $('#telefonoNumeroCliente');
    const $telefonoCliente = $('#telefonoCliente');

    // Elementos segundo teléfono
    const $btnToggleTel2Cliente = $('#btnToggleTel2Cliente');
    const $seccionTel2Cliente = $('#seccionTel2Cliente');
    const $operadoraCliente2 = $('#operadoraCliente2');
    const $telefonoNumeroCliente2 = $('#telefonoNumeroCliente2');
    const $telefonoCliente2 = $('#telefonoCliente2');

    const $correoCliente = $('#correoCliente');
    const $feedbackCorreoCliente = $('#feedbackCorreoCliente');
    const $direccionCliente = $('#direccionCliente');
    const $mensajeError = $('#mensajeErrorCliente');
    const $btnGuardarCliente = $('#btnGuardarCliente');

    const REGEX_EMAIL = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const REGEX_SOLO_LETRAS = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/;

    // Cargar lista de clientes al iniciar
    cargarClientes();

    // Evento para abrir modal de nuevo cliente
    $('#btnNuevoCliente').on('click', function() {
        $formularioCliente[0].reset();
        $clienteId.val('');
        limpiarEstadoCedula();
        bloquearCedula(false);
        limpiarEstadoCorreo();
        resetearSegundoTelefono();
        $mensajeError.addClass('d-none');
        $tituloModal.text('Nuevo Cliente');
        bootstrap.Modal.getOrCreateInstance($modalCliente[0]).show();
    });

    // Toggle segundo teléfono
    $btnToggleTel2Cliente.on('click', function() {
        $seccionTel2Cliente.removeClass('d-none');
        $(this).addClass('d-none');
    });

    $('#btnQuitarTel2Cliente').on('click', resetearSegundoTelefono);

    function resetearSegundoTelefono() {
        $seccionTel2Cliente.addClass('d-none');
        $btnToggleTel2Cliente.removeClass('d-none');
        $telefonoNumeroCliente2.val('');
        $telefonoCliente2.val('');
    }

    // Eventos para cerrar modal
    $('#btnCerrarModalCliente, #btnCancelarCliente').on('click', function() {
        bootstrap.Modal.getInstance($modalCliente[0]).hide();
    });

    // Evento para enviar formulario
    $formularioCliente.on('submit', async function(evento) {
        evento.preventDefault();
        await guardarCliente();
    });

    // Limpiar formulario cuando el modal se cierra
    $modalCliente.on('hidden.bs.modal', function () {
        $formularioCliente[0].reset();
        limpiarEstadoCedula();
        limpiarEstadoCorreo();
        resetearSegundoTelefono();
        $mensajeError.addClass('d-none');
    });

    function asignarOperadora($select, valor) {
        if (!$select.find('option').filter(function () { return this.value === valor; }).length) {
            $select.append(new Option(valor, valor));
        }
        $select.val(valor);
    }

    function bloquearCedula(bloquear) {
        $cedulaCliente.prop('disabled', bloquear);
        if (bloquear) $cedulaCliente.attr('title', 'La cédula no se puede modificar');
        else $cedulaCliente.removeAttr('title');
        $tipoDocCliente.prop('disabled', bloquear);
    }

    function actualizarBotonGuardarCliente() {
        $btnGuardarCliente.prop('disabled', $cedulaCliente.hasClass('is-invalid') || $correoCliente.hasClass('is-invalid'));
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
        actualizarBotonGuardarCliente();
    }

    function limpiarEstadoCedula() {
        marcarCampo($cedulaCliente, $feedbackCedulaCliente, null);
    }

    function limpiarEstadoCorreo() {
        marcarCampo($correoCliente, $feedbackCorreoCliente, null);
    }

    // Verificacion en tiempo real de cedula existente
    let temporizadorCedula;
    function verificarCedulaEnTiempoReal() {
        clearTimeout(temporizadorCedula);
        const num = $cedulaCliente.val().trim();
        const tipo = $tipoDocCliente.val() || 'V';
        if (!num || num.length < 5) {
            limpiarEstadoCedula();
            return;
        }

        const cedulaCompleta = tipo + '-' + num;
        const id = $clienteId.val() || '';

        temporizadorCedula = setTimeout(async function() {
            try {
                const res = await api.get('cliente/verificarCedulaAjax', { cedula: cedulaCompleta, id: id });
                if (res.estado === 'exito') {
                    if (res.datos.existe) marcarCampo($cedulaCliente, $feedbackCedulaCliente, 'invalid', 'Cliente ya registrado');
                    else marcarCampo($cedulaCliente, $feedbackCedulaCliente, 'valid');
                }
            } catch (e) {
                console.error('Error al verificar cédula:', e);
            }
        }, 300);
    }

    // Verificacion en tiempo real de correo existente
    let temporizadorCorreo;
    function verificarCorreoEnTiempoReal() {
        clearTimeout(temporizadorCorreo);
        const correo = $correoCliente.val().trim();
        if (!correo) {
            limpiarEstadoCorreo();
            return;
        }

        if (!REGEX_EMAIL.test(correo)) {
            marcarCampo($correoCliente, $feedbackCorreoCliente, 'invalid', 'Formato de correo inválido (ej: usuario@correo.com)', true);
            $correoCliente.attr('title', 'Formato de correo inválido');
            $feedbackCorreoCliente.attr('title', 'Formato de correo inválido');
            return;
        }

        const id = $clienteId.val() || '';

        temporizadorCorreo = setTimeout(async function() {
            try {
                const res = await api.get('cliente/verificarCorreoAjax', { correo: correo, id: id });
                if (res.estado === 'exito') {
                    if (res.datos.existe) marcarCampo($correoCliente, $feedbackCorreoCliente, 'invalid', 'Correo ya registrado', true);
                    else marcarCampo($correoCliente, $feedbackCorreoCliente, 'valid');
                }
            } catch (e) {
                console.error('Error al verificar correo:', e);
            }
        }, 300);
    }

    $cedulaCliente.on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        verificarCedulaEnTiempoReal();
    });
    $tipoDocCliente.on('change', verificarCedulaEnTiempoReal);
    $correoCliente.on('input blur', verificarCorreoEnTiempoReal);

    // Restringir caracteres especiales y números en nombres y apellidos (estrictamente solo letras y espacios)
    restringirSoloLetras($nombresCliente);
    restringirSoloLetras($apellidosCliente);

    // Permitir solo numeros en telefono principal y secundario
    $telefonoNumeroCliente.add($telefonoNumeroCliente2).on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Cargar lista de clientes via API
    async function cargarClientes() {
        try {
            const resultado = await api.get('cliente/listarAjax');
            if (resultado.estado === 'exito') {
                mostrarClientes(resultado.datos.clientes);
            }
        } catch (error) {
            console.error('No se pudieron cargar los clientes. Recargue la página:', error);
        }
    }

    // Renderizar tabla usando API DataTables con columns explicitas
    function mostrarClientes(clientes) {
        if (!$.fn.DataTable.isDataTable('#tablaClientes')) {
            $('#tablaClientes').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'cedula', render: $.fn.dataTable.render.text() },
                    { data: 'nombres', render: $.fn.dataTable.render.text() },
                    { data: 'apellidos', render: $.fn.dataTable.render.text() },
                    {
                        data: 'telefonos',
                        render: function(d) { return escapeHtml(d) || '-'; }
                    },
                    {
                        data: 'correo',
                        render: function(d) { return escapeHtml(d) || '-'; }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (!row) return '';
                            const nomCompleto = escapeHtml((row.nombres || '') + ' ' + (row.apellidos || ''));
                            return '<div class="d-flex gap-2">' +
                                '<button class="btn btn-sm btn-warning btn-editar-cliente" data-id="' + row.id_cliente + '" title="Editar" aria-label="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                                '<button class="btn btn-sm btn-danger btn-eliminar-cliente" data-id="' + row.id_cliente + '" data-nombre="' + nomCompleto + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        const table = $('#tablaClientes').DataTable();
        table.clear();

        if (Array.isArray(clientes)) {
            $.each(clientes, function(i, cliente) {
                table.row.add(cliente);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    alHacerClick('.btn-editar-cliente', function() {
        const id = parseInt($(this).attr('data-id'));
        if (!isNaN(id)) {
            abrirModalEditar(id);
        }
    });

    alHacerClick('.btn-eliminar-cliente', function() {
        const id = parseInt($(this).attr('data-id'));
        const nombre = $(this).attr('data-nombre') || 'este cliente';
        if (!isNaN(id)) {
            eliminarCliente(id, nombre);
        }
    });

    // Enlazar busqueda manual a DataTables
    vincularBusqueda('#busquedaClientes', '#tablaClientes');

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const resultado = await api.get('cliente/obtener', { id: id });

            if (resultado.estado === 'exito') {
                const c = resultado.datos;
                $formularioCliente[0].reset();
                limpiarEstadoCedula();
                limpiarEstadoCorreo();
                $mensajeError.addClass('d-none');
                $clienteId.val(c.id_cliente);

                // Parsear cedula (V-12345678)
                const partesCedula = (c.cedula || '').split('-');
                if (partesCedula.length === 2 && $tipoDocCliente.length) {
                    $tipoDocCliente.val(partesCedula[0]);
                    $cedulaCliente.val(partesCedula[1]);
                } else {
                    $cedulaCliente.val(c.cedula || '');
                }
                bloquearCedula(true);

                $nombresCliente.val(c.nombres);
                $apellidosCliente.val(c.apellidos);

                // Parsear teléfonos (pueden venir separados por coma)
                resetearSegundoTelefono();
                const arrayTels = (c.telefonos || '').split(',').map(function(t) { return t.trim(); });
                if (arrayTels[0]) {
                    const telRaw1 = arrayTels[0].replace(/[^0-9]/g, '');
                    if (telRaw1.length === 11 && $operadoraCliente.length) {
                        asignarOperadora($operadoraCliente, telRaw1.substring(0, 4));
                        $telefonoNumeroCliente.val(telRaw1.substring(4));
                    } else {
                        $telefonoNumeroCliente.val(telRaw1);
                    }
                }
                if (arrayTels[1]) {
                    const telRaw2 = arrayTels[1].replace(/[^0-9]/g, '');
                    if (telRaw2.length === 11 && $operadoraCliente2.length) {
                        asignarOperadora($operadoraCliente2, telRaw2.substring(0, 4));
                        $telefonoNumeroCliente2.val(telRaw2.substring(4));
                        $seccionTel2Cliente.removeClass('d-none');
                        $btnToggleTel2Cliente.addClass('d-none');
                    }
                }

                $correoCliente.val(c.correo || '');
                $direccionCliente.val(c.direccion || '');
                $tituloModal.text('Editar Cliente');
                bootstrap.Modal.getOrCreateInstance($modalCliente[0]).show();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener cliente:', error);
            mostrarNotificacion('No se pudieron cargar los datos del cliente. Actualice la página e intente de nuevo.', 'error');
        }
    }

    // Guarda o actualiza un cliente
    async function guardarCliente() {
        const id = $clienteId.val();
        const esEdicion = id !== '';

        const cedulaNum = $cedulaCliente.val().trim();
        const tipoDoc = $tipoDocCliente.val() || 'V';
        if (!cedulaNum) {
            mostrarError('La cédula es obligatoria');
            $cedulaCliente.trigger('focus');
            return;
        }

        const cedulaFinal = tipoDoc + '-' + cedulaNum;
        $cedulaCompletaCliente.val(cedulaFinal);

        const nombreVal = $nombresCliente.val().trim();
        if (!nombreVal) {
            mostrarError('El nombre es obligatorio');
            $nombresCliente.trigger('focus');
            return;
        }
        if (nombreVal.length > 40) {
            mostrarError('El nombre no puede exceder los 40 caracteres');
            $nombresCliente.trigger('focus');
            return;
        }
        if (!REGEX_SOLO_LETRAS.test(nombreVal)) {
            mostrarError('El nombre solo puede contener letras y espacios (sin caracteres especiales)');
            $nombresCliente.trigger('focus');
            return;
        }

        const apellidoVal = $apellidosCliente.val().trim();
        if (!apellidoVal) {
            mostrarError('El apellido es obligatorio');
            $apellidosCliente.trigger('focus');
            return;
        }
        if (apellidoVal.length > 40) {
            mostrarError('El apellido no puede exceder los 40 caracteres');
            $apellidosCliente.trigger('focus');
            return;
        }
        if (!REGEX_SOLO_LETRAS.test(apellidoVal)) {
            mostrarError('El apellido solo puede contener letras y espacios (sin caracteres especiales)');
            $apellidosCliente.trigger('focus');
            return;
        }

        const correoVal = $correoCliente.val().trim();
        if (!correoVal) {
            mostrarError('El correo electrónico es obligatorio');
            $correoCliente.trigger('focus');
            return;
        }
        if (!REGEX_EMAIL.test(correoVal)) {
            mostrarError('El correo electrónico no es válido');
            $correoCliente.trigger('focus');
            return;
        }
        if ($correoCliente.hasClass('is-invalid')) {
            mostrarError('El correo electrónico es inválido o ya se encuentra registrado');
            $correoCliente.trigger('focus');
            return;
        }

        // Armar telefono 1 (obligatorio)
        const telNum = $telefonoNumeroCliente.val().trim();
        const op = $operadoraCliente.val() || '';
        if (!telNum) {
            mostrarError('El teléfono principal es obligatorio');
            $telefonoNumeroCliente.trigger('focus');
            return;
        }
        if (telNum.length !== 7) {
            mostrarError('El número de teléfono principal debe tener exactamente 7 dígitos');
            $telefonoNumeroCliente.trigger('focus');
            return;
        }
        const tel1Completo = op + telNum;
        $telefonoCliente.val(tel1Completo);

        // Armar telefono 2 si tiene numero
        const telNum2 = ($telefonoNumeroCliente2.val() || '').trim();
        const op2 = $operadoraCliente2.val() || '';
        let tel2Completo = '';
        if (telNum2) {
            if (telNum2.length !== 7) {
                mostrarError('El segundo número de teléfono debe tener exactamente 7 dígitos');
                $telefonoNumeroCliente2.trigger('focus');
                return;
            }
            tel2Completo = op2 + telNum2;
            if (tel1Completo === tel2Completo) {
                mostrarError('El segundo teléfono no puede ser igual al teléfono principal');
                $telefonoNumeroCliente2.trigger('focus');
                return;
            }
        }

        const formData = new FormData($formularioCliente[0]);
        formData.set('cedula', cedulaFinal);
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
            const resultado = await api.post(esEdicion ? 'cliente/actualizar' : 'cliente/guardar', formData);

            if (resultado.estado === 'exito') {
                bootstrap.Modal.getInstance($modalCliente[0]).hide();
                cargarClientes();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar cliente:', error);
            mostrarError('No se pudo guardar el cliente. Revise su conexión e intente de nuevo.');
        }
    }

    // Elimina un cliente
    function eliminarCliente(id, nombre) {
        confirmarConModal('Eliminar', '¿Está seguro de eliminar al cliente ' + escapeHtml(nombre) + '?', function() {
            api.post('cliente/eliminar', { id: id })
                .then(function(resultado) {
                    if (resultado.estado === 'exito') {
                        cargarClientes();
                        mostrarNotificacion(resultado.mensaje, 'exito');
                    } else {
                        mostrarNotificacion(resultado.mensaje, 'error');
                    }
                })
                .catch(function(error) {
                    console.error('Error al eliminar cliente:', error);
                    mostrarNotificacion('No se pudo eliminar el cliente. Revise su conexión e intente de nuevo.', 'error');
                });
        });
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        $mensajeError.text(mensaje).removeClass('d-none');
    }
});
