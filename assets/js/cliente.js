// Archivo: cliente.js
// Manejo de la vista de gestion de clientes

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const busquedaClientes = document.getElementById('busquedaClientes');
    const btnNuevoCliente = document.getElementById('btnNuevoCliente');
    const modalCliente = document.getElementById('modalCliente');
    const btnCerrarModal = document.getElementById('btnCerrarModalCliente');
    const btnCancelar = document.getElementById('btnCancelarCliente');
    const formularioCliente = document.getElementById('formularioCliente');
    const tituloModal = document.getElementById('tituloModalCliente');
    const clienteId = document.getElementById('clienteId');
    const tipoDocCliente = document.getElementById('tipoDocCliente');
    const cedulaCliente = document.getElementById('cedulaCliente');
    const cedulaCompletaCliente = document.getElementById('cedulaCompletaCliente');
    const feedbackCedulaCliente = document.getElementById('feedbackCedulaCliente');
    const nombresCliente = document.getElementById('nombresCliente');
    const apellidosCliente = document.getElementById('apellidosCliente');
    const operadoraCliente = document.getElementById('operadoraCliente');
    const telefonoNumeroCliente = document.getElementById('telefonoNumeroCliente');
    const telefonoCliente = document.getElementById('telefonoCliente');

    // Elementos segundo teléfono
    const btnToggleTel2Cliente = document.getElementById('btnToggleTel2Cliente');
    const btnQuitarTel2Cliente = document.getElementById('btnQuitarTel2Cliente');
    const seccionTel2Cliente = document.getElementById('seccionTel2Cliente');
    const operadoraCliente2 = document.getElementById('operadoraCliente2');
    const telefonoNumeroCliente2 = document.getElementById('telefonoNumeroCliente2');
    const telefonoCliente2 = document.getElementById('telefonoCliente2');

    const correoCliente = document.getElementById('correoCliente');
    const direccionCliente = document.getElementById('direccionCliente');
    const mensajeError = document.getElementById('mensajeErrorCliente');
    const btnGuardarCliente = document.getElementById('btnGuardarCliente');

    // Cargar lista de clientes al iniciar
    cargarClientes();

    // Evento para abrir modal de nuevo cliente
    if (btnNuevoCliente) {
        btnNuevoCliente.addEventListener('click', function() {
            formularioCliente.reset();
            clienteId.value = '';
            limpiarEstadoCedula();
            resetearSegundoTelefono();
            mensajeError.classList.add('d-none');
            tituloModal.textContent = 'Nuevo Cliente';
            bootstrap.Modal.getOrCreateInstance(modalCliente).show();
        });
    }

    // Toggle segundo teléfono
    if (btnToggleTel2Cliente) {
        btnToggleTel2Cliente.addEventListener('click', function() {
            if (seccionTel2Cliente) seccionTel2Cliente.classList.remove('d-none');
            this.classList.add('d-none');
        });
    }

    if (btnQuitarTel2Cliente) {
        btnQuitarTel2Cliente.addEventListener('click', function() {
            resetearSegundoTelefono();
        });
    }

    function resetearSegundoTelefono() {
        if (seccionTel2Cliente) seccionTel2Cliente.classList.add('d-none');
        if (btnToggleTel2Cliente) btnToggleTel2Cliente.classList.remove('d-none');
        if (telefonoNumeroCliente2) telefonoNumeroCliente2.value = '';
        if (telefonoCliente2) telefonoCliente2.value = '';
    }

    // Eventos para cerrar modal
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalCliente).hide();
        });
    }
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalCliente).hide();
        });
    }

    // Evento para enviar formulario
    if (formularioCliente) {
        formularioCliente.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            await guardarCliente();
        });
    }

    // Limpiar formulario cuando el modal se cierra
    if (modalCliente) {
        modalCliente.addEventListener('hidden.bs.modal', function () {
            formularioCliente.reset();
            limpiarEstadoCedula();
            resetearSegundoTelefono();
            mensajeError.classList.add('d-none');
        });
    }

    function limpiarEstadoCedula() {
        if (cedulaCliente) {
            cedulaCliente.classList.remove('is-invalid', 'is-valid');
            cedulaCliente.removeAttribute('title');
        }
        if (feedbackCedulaCliente) {
            feedbackCedulaCliente.classList.add('d-none');
            feedbackCedulaCliente.removeAttribute('title');
        }
        if (btnGuardarCliente) {
            btnGuardarCliente.disabled = false;
        }
    }

    // Verificacion en tiempo real de cedula existente
    var temporizadorCedula;
    function verificarCedulaEnTiempoReal() {
        clearTimeout(temporizadorCedula);
        const num = cedulaCliente.value.trim();
        const tipo = tipoDocCliente ? tipoDocCliente.value : 'V';
        if (!num || num.length < 5) {
            limpiarEstadoCedula();
            return;
        }

        const cedulaCompleta = tipo + '-' + num;
        const id = clienteId.value || '';

        temporizadorCedula = setTimeout(async function() {
            try {
                const resp = await fetch('/SP%20Perfect%20Color/cliente/verificarCedulaAjax?cedula=' + encodeURIComponent(cedulaCompleta) + '&id=' + encodeURIComponent(id));
                const res = await resp.json();
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        cedulaCliente.classList.add('is-invalid');
                        cedulaCliente.classList.remove('is-valid');
                        cedulaCliente.setAttribute('title', 'Cliente ya registrado');
                        if (feedbackCedulaCliente) {
                            feedbackCedulaCliente.textContent = 'Cliente ya registrado';
                            feedbackCedulaCliente.setAttribute('title', 'Cliente ya registrado');
                            feedbackCedulaCliente.classList.remove('d-none');
                        }
                        if (btnGuardarCliente) btnGuardarCliente.disabled = true;
                    } else {
                        cedulaCliente.classList.remove('is-invalid');
                        cedulaCliente.classList.add('is-valid');
                        cedulaCliente.removeAttribute('title');
                        if (feedbackCedulaCliente) {
                            feedbackCedulaCliente.classList.add('d-none');
                            feedbackCedulaCliente.removeAttribute('title');
                        }
                        if (btnGuardarCliente) btnGuardarCliente.disabled = false;
                    }
                }
            } catch (e) {
                console.error('Error al verificar cedula:', e);
            }
        }, 300);
    }

    if (cedulaCliente) {
        cedulaCliente.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            verificarCedulaEnTiempoReal();
        });
    }
    if (tipoDocCliente) {
        tipoDocCliente.addEventListener('change', verificarCedulaEnTiempoReal);
    }

    // Restringir caracteres especiales en nombres y apellidos (solo letras y espacios)
    function restringirSoloLetras(input) {
        if (!input) return;
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
        });
    }
    restringirSoloLetras(nombresCliente);
    restringirSoloLetras(apellidosCliente);

    // Permitir solo numeros en telefono principal
    if (telefonoNumeroCliente) {
        telefonoNumeroCliente.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Permitir solo numeros en segundo telefono
    if (telefonoNumeroCliente2) {
        telefonoNumeroCliente2.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Cargar lista de clientes via API
    async function cargarClientes() {
        try {
            const respuesta = await fetch('/SP%20Perfect%20Color/cliente/listarAjax');
            const resultado = await respuesta.json();
            if (resultado.estado === 'exito') {
                mostrarClientes(resultado.datos.clientes);
            }
        } catch (error) {
            console.error('Error al cargar clientes:', error);
        }
    }

    // Renderizar tabla usando API DataTables con columns explicitas
    function mostrarClientes(clientes) {
        if (!$.fn.DataTable.isDataTable('#tablaClientes')) {
            $('#tablaClientes').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'cedula' },
                    { data: 'nombres' },
                    { data: 'apellidos' },
                    {
                        data: 'telefonos',
                        render: function(d) { return d || '-'; }
                    },
                    {
                        data: 'correo',
                        render: function(d) { return d || '-'; }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (!row) return '';
                            var nomCompleto = ((row.nombres || '') + ' ' + (row.apellidos || '')).replace(/"/g, '&quot;');
                            return '<div class="d-flex gap-2">' +
                                '<button class="btn btn-sm btn-warning btn-editar-cliente" data-id="' + row.id_cliente + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                                '<button class="btn btn-sm btn-danger btn-eliminar-cliente" data-id="' + row.id_cliente + '" data-nombre="' + nomCompleto + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaClientes').DataTable();
        table.clear();

        if (Array.isArray(clientes)) {
            clientes.forEach(function(cliente) {
                table.row.add(cliente);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    $(document).on('click', '.btn-editar-cliente', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) {
            abrirModalEditar(id);
        }
    });

    $(document).on('click', '.btn-eliminar-cliente', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        var nombre = $(this).attr('data-nombre') || 'este cliente';
        if (!isNaN(id)) {
            eliminarCliente(id, nombre);
        }
    });

    // Enlazar busqueda manual a DataTables
    if (busquedaClientes) {
        busquedaClientes.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaClientes')) {
                $('#tablaClientes').DataTable().search(this.value).draw();
            }
        });
    }

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('/SP%20Perfect%20Color/cliente/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const c = resultado.datos;
                formularioCliente.reset();
                limpiarEstadoCedula();
                mensajeError.classList.add('d-none');
                clienteId.value = c.id_cliente;

                // Parsear cedula (V-12345678)
                const partesCedula = (c.cedula || '').split('-');
                if (partesCedula.length === 2 && tipoDocCliente) {
                    tipoDocCliente.value = partesCedula[0];
                    cedulaCliente.value = partesCedula[1];
                } else {
                    cedulaCliente.value = c.cedula || '';
                }

                nombresCliente.value = c.nombres;
                apellidosCliente.value = c.apellidos;

                // Parsear teléfonos (pueden venir separados por coma)
                resetearSegundoTelefono();
                const arrayTels = (c.telefonos || '').split(',').map(function(t) { return t.trim(); });
                if (arrayTels.length > 0 && arrayTels[0]) {
                    const telRaw1 = arrayTels[0].replace(/[^0-9]/g, '');
                    if (telRaw1.length === 11 && operadoraCliente && telefonoNumeroCliente) {
                        operadoraCliente.value = telRaw1.substring(0, 4);
                        telefonoNumeroCliente.value = telRaw1.substring(4);
                    } else if (telefonoNumeroCliente) {
                        telefonoNumeroCliente.value = telRaw1;
                    }
                }
                if (arrayTels.length > 1 && arrayTels[1]) {
                    const telRaw2 = arrayTels[1].replace(/[^0-9]/g, '');
                    if (telRaw2.length === 11 && operadoraCliente2 && telefonoNumeroCliente2) {
                        operadoraCliente2.value = telRaw2.substring(0, 4);
                        telefonoNumeroCliente2.value = telRaw2.substring(4);
                        if (seccionTel2Cliente) seccionTel2Cliente.classList.remove('d-none');
                        if (btnToggleTel2Cliente) btnToggleTel2Cliente.classList.add('d-none');
                    }
                }

                correoCliente.value = c.correo || '';
                direccionCliente.value = c.direccion || '';
                tituloModal.textContent = 'Editar Cliente';
                bootstrap.Modal.getOrCreateInstance(modalCliente).show();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener cliente:', error);
            mostrarNotificacion('Error al cargar los datos del cliente', 'error');
        }
    }

    // Guarda o actualiza un cliente
    async function guardarCliente() {
        const id = clienteId.value;
        const esEdicion = id !== '';

        const cedulaNum = cedulaCliente.value.trim();
        const tipoDoc = tipoDocCliente ? tipoDocCliente.value : 'V';
        if (!cedulaNum) {
            mostrarError('La cedula es obligatoria');
            cedulaCliente.focus();
            return;
        }

        const cedulaFinal = tipoDoc + '-' + cedulaNum;
        if (cedulaCompletaCliente) {
            cedulaCompletaCliente.value = cedulaFinal;
        }

        const nombreVal = nombresCliente.value.trim();
        if (!nombreVal) {
            mostrarError('El nombre es obligatorio');
            nombresCliente.focus();
            return;
        }
        if (nombreVal.length > 40) {
            mostrarError('El nombre no puede exceder los 40 caracteres');
            nombresCliente.focus();
            return;
        }
        const soloLetrasRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/;
        if (!soloLetrasRegex.test(nombreVal)) {
            mostrarError('El nombre solo puede contener letras y espacios (sin caracteres especiales)');
            nombresCliente.focus();
            return;
        }

        const apellidoVal = apellidosCliente.value.trim();
        if (!apellidoVal) {
            mostrarError('El apellido es obligatorio');
            apellidosCliente.focus();
            return;
        }
        if (apellidoVal.length > 40) {
            mostrarError('El apellido no puede exceder los 40 caracteres');
            apellidosCliente.focus();
            return;
        }
        if (!soloLetrasRegex.test(apellidoVal)) {
            mostrarError('El apellido solo puede contener letras y espacios (sin caracteres especiales)');
            apellidosCliente.focus();
            return;
        }

        const correoVal = correoCliente.value.trim();
        if (!correoVal) {
            mostrarError('El correo electronico es obligatorio');
            correoCliente.focus();
            return;
        }
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(correoVal)) {
            mostrarError('El correo electronico no es valido');
            correoCliente.focus();
            return;
        }

        // Armar telefono 1 (obligatorio)
        const telNum = telefonoNumeroCliente ? telefonoNumeroCliente.value.trim() : '';
        const op = operadoraCliente ? operadoraCliente.value : '';
        if (!telNum) {
            mostrarError('El teléfono principal es obligatorio');
            telefonoNumeroCliente.focus();
            return;
        }
        if (telNum.length !== 7) {
            mostrarError('El número de teléfono principal debe tener exactamente 7 dígitos');
            telefonoNumeroCliente.focus();
            return;
        }
        const tel1Completo = op + telNum;
        if (telefonoCliente) {
            telefonoCliente.value = tel1Completo;
        }

        // Armar telefono 2 si tiene numero
        const telNum2 = telefonoNumeroCliente2 ? telefonoNumeroCliente2.value.trim() : '';
        const op2 = operadoraCliente2 ? operadoraCliente2.value : '';
        let tel2Completo = '';
        if (telNum2) {
            if (telNum2.length !== 7) {
                mostrarError('El segundo número de teléfono debe tener exactamente 7 dígitos');
                telefonoNumeroCliente2.focus();
                return;
            }
            tel2Completo = op2 + telNum2;
            if (tel1Completo === tel2Completo) {
                mostrarError('El segundo teléfono no puede ser igual al teléfono principal');
                telefonoNumeroCliente2.focus();
                return;
            }
        }

        const url = esEdicion ? '/SP%20Perfect%20Color/cliente/actualizar' : '/SP%20Perfect%20Color/cliente/guardar';
        const formData = new FormData(formularioCliente);
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
            const respuesta = await fetch(url, { method: 'POST', body: formData });
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                bootstrap.Modal.getInstance(modalCliente).hide();
                cargarClientes();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar cliente:', error);
            mostrarError('Error de conexion al guardar el cliente');
        }
    }

    // Elimina un cliente
    async function eliminarCliente(id, nombre) {
        confirmarConModal('Eliminar', 'Esta seguro de eliminar al cliente ' + nombre + '?', function() {
            const formData = new FormData();
            formData.append('id', id);
            fetch('/SP%20Perfect%20Color/cliente/eliminar', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
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
                    mostrarNotificacion('Error de conexion al eliminar el cliente', 'error');
                });
        });
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
    }
});
