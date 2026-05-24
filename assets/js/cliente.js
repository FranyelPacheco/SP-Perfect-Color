// Archivo: cliente.js
// Manejo de la vista de gestion de clientes

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const tablaClientes = document.getElementById('cuerpoTablaClientes');
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

    // Evento para buscar clientes mientras se escribe
    let temporizadorBusqueda;
    busquedaClientes.addEventListener('keyup', function() {
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(function() {
            buscarClientes(busquedaClientes.value.trim());
        }, 300);
    });

    // Evento para abrir modal de nuevo cliente
    btnNuevoCliente.addEventListener('click', function() {
        abrirModalCrear();
    });

    // Eventos para cerrar modal
    btnCerrarModal.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);

    // Evento para enviar formulario
    formularioCliente.addEventListener('submit', async function(evento) {
        evento.preventDefault();
        await guardarCliente();
    });

    // Cierra el modal al hacer clic fuera del contenido
    modalCliente.addEventListener('click', function(evento) {
        if (evento.target === modalCliente) {
            cerrarModal();
        }
    });

    // Permitir solo numeros en el campo de cedula
    cedulaCliente.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Permitir solo numeros en el campo de telefono
    telefonoCliente.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Funcion para cargar la lista de clientes
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

    // Funcion para buscar clientes
    async function buscarClientes(termino) {
        try {
            const respuesta = await fetch('cliente/buscarAjax?termino=' + encodeURIComponent(termino));
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarClientes(resultado.datos.clientes);
            }
        } catch (error) {
            console.error('Error al buscar clientes:', error);
        }
    }

    // Muestra los clientes en la tabla
    function mostrarClientes(clientes) {
        tablaClientes.innerHTML = '';

        if (clientes.length === 0) {
            tablaClientes.innerHTML = '<tr><td colspan="6" style="text-align: center;">No hay clientes registrados</td></tr>';
            return;
        }

        clientes.forEach(function(cliente) {
            const fila = document.createElement('tr');
            
            // Cedula
            const celdaCedula = document.createElement('td');
            celdaCedula.textContent = cliente.cedula;
            fila.appendChild(celdaCedula);
            
            // Nombres
            const celdaNombres = document.createElement('td');
            celdaNombres.textContent = cliente.nombres;
            fila.appendChild(celdaNombres);
            
            // Apellidos
            const celdaApellidos = document.createElement('td');
            celdaApellidos.textContent = cliente.apellidos;
            fila.appendChild(celdaApellidos);
            
            // Telefono
            const celdaTelefono = document.createElement('td');
            celdaTelefono.textContent = cliente.telefono || '-';
            fila.appendChild(celdaTelefono);
            
            // Correo
            const celdaCorreo = document.createElement('td');
            celdaCorreo.textContent = cliente.correo || '-';
            fila.appendChild(celdaCorreo);
            
            // Acciones
            const celdaAcciones = document.createElement('td');
            celdaAcciones.className = 'acciones';
            
            // Boton editar
            const btnEditar = document.createElement('button');
            btnEditar.className = 'btn-primario';
            btnEditar.textContent = 'Editar';
            btnEditar.addEventListener('click', function() {
                abrirModalEditar(cliente.id);
            });
            celdaAcciones.appendChild(btnEditar);
            
            // Boton eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.className = 'btn-peligro';
            btnEliminar.textContent = 'Eliminar';
            btnEliminar.addEventListener('click', function() {
                eliminarCliente(cliente.id, cliente.nombres + ' ' + cliente.apellidos);
            });
            celdaAcciones.appendChild(btnEliminar);
            
            fila.appendChild(celdaAcciones);
            
            tablaClientes.appendChild(fila);
        });
    }

    // Abre el modal en modo creacion
    function abrirModalCrear() {
        tituloModal.textContent = 'Nuevo Cliente';
        clienteId.value = '';
        cedulaCliente.value = '';
        cedulaCliente.disabled = false;
        nombresCliente.value = '';
        apellidosCliente.value = '';
        telefonoCliente.value = '';
        correoCliente.value = '';
        direccionCliente.value = '';
        mensajeError.style.display = 'none';
        
        modalCliente.style.display = 'flex';
    }

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('cliente/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const cliente = resultado.datos;
                
                tituloModal.textContent = 'Editar Cliente';
                clienteId.value = cliente.id;
                cedulaCliente.value = cliente.cedula;
                cedulaCliente.disabled = true;
                nombresCliente.value = cliente.nombres;
                apellidosCliente.value = cliente.apellidos;
                telefonoCliente.value = cliente.telefono || '';
                correoCliente.value = cliente.correo || '';
                direccionCliente.value = cliente.direccion || '';
                mensajeError.style.display = 'none';
                
                modalCliente.style.display = 'flex';
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener cliente:', error);
            mostrarNotificacion('Error al cargar los datos del cliente', 'error');
        }
    }

    // Cierra el modal
    function cerrarModal() {
        modalCliente.style.display = 'none';
        formularioCliente.reset();
        mensajeError.style.display = 'none';
    }

    // Guarda o actualiza un cliente
    async function guardarCliente() {
        const id = clienteId.value;
        const esEdicion = id !== '';
        
        // Validar campos
        if (!cedulaCliente.value.trim()) {
            mostrarError('La cedula es obligatoria');
            return;
        }
        
        if (cedulaCliente.value.trim().length < 7) {
            mostrarError('La cedula debe tener entre 7 y 8 digitos');
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
        
        if (telefonoCliente.value.trim() && telefonoCliente.value.trim().length !== 11) {
            mostrarError('El telefono debe tener 11 digitos');
            return;
        }
        
        // Determinar URL
        const url = esEdicion ? 'cliente/actualizar' : 'cliente/guardar';
        
        // Preparar datos
        const formData = new FormData(formularioCliente);
        
        // Si es edicion, agregar el ID y la cedula (aunque este deshabilitada)
        if (esEdicion) {
            formData.set('id', id);
            formData.set('cedula', cedulaCliente.value);
        }
        
        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cerrarModal();
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
        if (!confirm('Esta seguro de eliminar al cliente ' + nombre + '?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        
        try {
            const respuesta = await fetch('cliente/eliminar', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cargarClientes();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar cliente:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.style.display = 'block';
    }
});