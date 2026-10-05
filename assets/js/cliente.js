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
    const cedulaCliente = document.getElementById('cedulaCliente');
    const nombresCliente = document.getElementById('nombresCliente');
    const apellidosCliente = document.getElementById('apellidosCliente');
    const telefonoCliente = document.getElementById('telefonoCliente');
    const correoCliente = document.getElementById('correoCliente');
    const direccionCliente = document.getElementById('direccionCliente');
    const mensajeError = document.getElementById('mensajeErrorCliente');

    // Cargar lista de clientes al iniciar
    cargarClientes();

    // Evento para abrir modal de nuevo cliente
    if (btnNuevoCliente) {
        btnNuevoCliente.addEventListener('click', function() {
            formularioCliente.reset();
            clienteId.value = '';
            mensajeError.classList.add('d-none');
            tituloModal.textContent = 'Nuevo Cliente';
            bootstrap.Modal.getOrCreateInstance(modalCliente).show();
        });
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
            mensajeError.classList.add('d-none');
        });
    }

    // Permitir solo numeros en cedula
    if (cedulaCliente) {
        cedulaCliente.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Permitir solo numeros en telefono
    if (telefonoCliente) {
        telefonoCliente.addEventListener('input', function() {
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
                                '<button class="btn btn-sm btn-danger btn-eliminar-cliente" data-id="' + row.id_cliente + '" data-nombre="' + nomCompleto + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
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
                mensajeError.classList.add('d-none');
                clienteId.value = c.id_cliente;
                cedulaCliente.value = c.cedula;
                nombresCliente.value = c.nombres;
                apellidosCliente.value = c.apellidos;
                telefonoCliente.value = c.telefonos || '';
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

        if (!cedulaCliente.value.trim()) {
            mostrarError('La cedula es obligatoria');
            return;
        }
        if (!nombresCliente.value.trim()) {
            mostrarError('El nombre es obligatorio');
            return;
        }
        if (!apellidosCliente.value.trim()) {
            mostrarError('El apellido es obligatorio');
            return;
        }

        const url = esEdicion ? '/SP%20Perfect%20Color/cliente/actualizar' : '/SP%20Perfect%20Color/cliente/guardar';
        const formData = new FormData(formularioCliente);
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
