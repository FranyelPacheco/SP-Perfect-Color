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
    btnNuevoCliente.addEventListener('click', function() {
        formularioCliente.reset();
        clienteId.value = '';
        mensajeError.classList.add('d-none');
        tituloModal.textContent = 'Nuevo Cliente';
        bootstrap.Modal.getOrCreateInstance(modalCliente).show();
    });

    // Eventos para cerrar modal
    btnCerrarModal.addEventListener('click', function() {
        bootstrap.Modal.getInstance(modalCliente).hide();
    });
    btnCancelar.addEventListener('click', function() {
        bootstrap.Modal.getInstance(modalCliente).hide();
    });

    // Evento para enviar formulario
    formularioCliente.addEventListener('submit', async function(evento) {
        evento.preventDefault();
        await guardarCliente();
    });

    // Limpiar formulario cuando el modal se cierra
    modalCliente.addEventListener('hidden.bs.modal', function () {
        formularioCliente.reset();
        mensajeError.classList.add('d-none');
    });

    // Permitir solo numeros en cedula
    cedulaCliente.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Permitir solo numeros en telefono
    telefonoCliente.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Cargar lista de clientes via API
    async function cargarClientes() {
        try {
            const respuesta = await fetch('cliente/listarAjax');
            const resultado = await respuesta.json();
            if (resultado.estado === 'exito') {
                mostrarClientes(resultado.datos.clientes);
            }
        } catch (error) {
            console.error('Error al cargar clientes:', error);
        }
    }

    // Renderizar tabla usando API DataTables
    function mostrarClientes(clientes) {
        if (!$.fn.DataTable.isDataTable('#tablaClientes')) {
            $('#tablaClientes').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH
            });
        }

        var table = $('#tablaClientes').DataTable();
        table.clear();

        clientes.forEach(function(cliente) {
            var acciones = '<div class="d-flex gap-2">' +
                '<button class="btn btn-sm btn-warning btn-editar-cliente" data-id="' + cliente.id_cliente + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                '<button class="btn btn-sm btn-danger btn-eliminar-cliente" data-id="' + cliente.id_cliente + '" data-nombre="' + (cliente.nombres + ' ' + cliente.apellidos).replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                '</div>';

            table.row.add([
                cliente.cedula,
                cliente.nombres,
                cliente.apellidos,
                cliente.telefonos || '-',
                cliente.correo || '-',
                acciones
            ]);
        });

        table.draw();
    }

    // Delegated events for action buttons
    document.getElementById('tablaClientes').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-editar-cliente');
        if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
        btn = e.target.closest('.btn-eliminar-cliente');
        if (btn) { eliminarCliente(parseInt(btn.dataset.id), btn.dataset.nombre); return; }
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
            const respuesta = await fetch('cliente/obtener?id=' + id);
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
            }
        } catch (error) {
            console.error('Error al obtener cliente:', error);
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

        const url = esEdicion ? 'cliente/actualizar' : 'cliente/guardar';
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
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(resultado.mensaje, 'exito');
                } else {
                    alert(resultado.mensaje);
                }
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
        if (!confirm('Esta seguro de eliminar al cliente ' + nombre + '?')) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        try {
            const respuesta = await fetch('cliente/eliminar', { method: 'POST', body: formData });
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                cargarClientes();
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(resultado.mensaje, 'exito');
                } else {
                    alert(resultado.mensaje);
                }
            } else {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(resultado.mensaje, 'error');
                } else {
                    alert(resultado.mensaje);
                }
            }
        } catch (error) {
            console.error('Error al eliminar cliente:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error de conexion', 'error');
            }
        }
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
    }
});
