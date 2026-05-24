// Archivo: usuario.js
// Manejo de la vista de gestion de usuarios

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const tablaUsuarios = document.getElementById('cuerpoTablaUsuarios');
    const btnNuevoUsuario = document.getElementById('btnNuevoUsuario');
    const modalUsuario = document.getElementById('modalUsuario');
    const btnCerrarModal = document.getElementById('btnCerrarModalUsuario');
    const btnCancelar = document.getElementById('btnCancelarUsuario');
    const formularioUsuario = document.getElementById('formularioUsuario');
    const tituloModal = document.getElementById('tituloModalUsuario');
    const usuarioId = document.getElementById('usuarioId');
    const nombreUsuario = document.getElementById('nombreUsuario');
    const correoUsuario = document.getElementById('correoUsuario');
    const claveUsuario = document.getElementById('claveUsuario');
    const grupoClave = document.getElementById('grupoClave');
    const grupoCambiarClave = document.getElementById('grupoCambiarClave');
    const checkCambiarClave = document.getElementById('checkCambiarClave');
    const nuevaClaveUsuario = document.getElementById('nuevaClaveUsuario');
    const rolUsuario = document.getElementById('rolUsuario');
    const estadoUsuario = document.getElementById('estadoUsuario');
    const mensajeError = document.getElementById('mensajeErrorUsuario');
    let rolesGlobal = [];

    // Cargar lista de usuarios al iniciar
    cargarUsuarios();
    cargarRoles();

    // Evento para abrir modal de nuevo usuario
    btnNuevoUsuario.addEventListener('click', function() {
        abrirModalCrear();
    });

    // Evento para cerrar modal
    btnCerrarModal.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);

    // Evento para mostrar/ocultar campo de nueva clave
    checkCambiarClave.addEventListener('change', function() {
        if (this.checked) {
            nuevaClaveUsuario.style.display = 'block';
        } else {
            nuevaClaveUsuario.style.display = 'none';
            nuevaClaveUsuario.value = '';
        }
    });

    // Evento para enviar formulario
    formularioUsuario.addEventListener('submit', async function(evento) {
        evento.preventDefault();
        await guardarUsuario();
    });

    // Cierra el modal al hacer clic fuera del contenido
    modalUsuario.addEventListener('click', function(evento) {
        if (evento.target === modalUsuario) {
            cerrarModal();
        }
    });

    // Funcion para cargar la lista de usuarios desde el servidor
    async function cargarUsuarios() {
        try {
            const respuesta = await fetch('usuario/listarAjax');
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarUsuarios(resultado.datos.usuarios);
            }
        } catch (error) {
            console.error('Error al cargar usuarios:', error);
        }
    }

    // Funcion para cargar los roles en el select
    async function cargarRoles() {
        try {
            const respuesta = await fetch('usuario/listarAjax');
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito' && resultado.datos.roles) {
                rolesGlobal = resultado.datos.roles;
                llenarSelectRoles();
            }
        } catch (error) {
            console.error('Error al cargar roles:', error);
        }
    }

    // Llena el select de roles con las opciones disponibles
    function llenarSelectRoles() {
        rolUsuario.innerHTML = '<option value="">Seleccione un rol</option>';
        rolesGlobal.forEach(function(rol) {
            const opcion = document.createElement('option');
            opcion.value = rol.id;
            opcion.textContent = rol.nombre;
            rolUsuario.appendChild(opcion);
        });
    }

    // Muestra los usuarios en la tabla
    function mostrarUsuarios(usuarios) {
        tablaUsuarios.innerHTML = '';

        if (usuarios.length === 0) {
            tablaUsuarios.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hay usuarios registrados</td></tr>';
            return;
        }

        usuarios.forEach(function(usuario) {
            const fila = document.createElement('tr');
            
            // Nombre
            const celdaNombre = document.createElement('td');
            celdaNombre.textContent = usuario.nombre;
            fila.appendChild(celdaNombre);
            
            // Correo
            const celdaCorreo = document.createElement('td');
            celdaCorreo.textContent = usuario.correo;
            fila.appendChild(celdaCorreo);
            
            // Rol
            const celdaRol = document.createElement('td');
            celdaRol.textContent = usuario.rol_nombre;
            fila.appendChild(celdaRol);
            
            // Estado
            const celdaEstado = document.createElement('td');
            const spanEstado = document.createElement('span');
            if (usuario.activo == 1) {
                spanEstado.className = 'estado-activo';
                spanEstado.textContent = 'Activo';
            } else {
                spanEstado.className = 'estado-inactivo';
                spanEstado.textContent = 'Inactivo';
            }
            celdaEstado.appendChild(spanEstado);
            fila.appendChild(celdaEstado);
            
            // Acciones
            const celdaAcciones = document.createElement('td');
            celdaAcciones.className = 'acciones';
            
            // Boton editar
            const btnEditar = document.createElement('button');
            btnEditar.className = 'btn-primario';
            btnEditar.textContent = 'Editar';
            btnEditar.addEventListener('click', function() {
                abrirModalEditar(usuario.id);
            });
            celdaAcciones.appendChild(btnEditar);
            
            // Boton activar/desactivar
            if (usuario.activo == 1) {
                const btnDesactivar = document.createElement('button');
                btnDesactivar.className = 'btn-secundario';
                btnDesactivar.textContent = 'Desactivar';
                btnDesactivar.addEventListener('click', function() {
                    cambiarEstadoUsuario(usuario.id, 0);
                });
                celdaAcciones.appendChild(btnDesactivar);
            } else {
                const btnActivar = document.createElement('button');
                btnActivar.className = 'btn-exito';
                btnActivar.textContent = 'Activar';
                btnActivar.addEventListener('click', function() {
                    cambiarEstadoUsuario(usuario.id, 1);
                });
                celdaAcciones.appendChild(btnActivar);
            }
            
            // Boton eliminar
            const btnEliminar = document.createElement('button');
            btnEliminar.className = 'btn-peligro';
            btnEliminar.textContent = 'Eliminar';
            btnEliminar.addEventListener('click', function() {
                eliminarUsuario(usuario.id, usuario.nombre);
            });
            celdaAcciones.appendChild(btnEliminar);
            
            fila.appendChild(celdaAcciones);
            
            tablaUsuarios.appendChild(fila);
        });
    }

    // Abre el modal en modo creacion
    function abrirModalCrear() {
        tituloModal.textContent = 'Nuevo Usuario';
        usuarioId.value = '';
        nombreUsuario.value = '';
        correoUsuario.value = '';
        claveUsuario.value = '';
        claveUsuario.required = true;
        grupoClave.style.display = 'block';
        grupoCambiarClave.style.display = 'none';
        checkCambiarClave.checked = false;
        nuevaClaveUsuario.style.display = 'none';
        nuevaClaveUsuario.value = '';
        rolUsuario.value = '';
        estadoUsuario.value = '1';
        estadoUsuario.parentElement.style.display = 'none';
        mensajeError.style.display = 'none';
        
        modalUsuario.style.display = 'flex';
    }

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('usuario/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const usuario = resultado.datos;
                
                tituloModal.textContent = 'Editar Usuario';
                usuarioId.value = usuario.id;
                nombreUsuario.value = usuario.nombre;
                correoUsuario.value = usuario.correo;
                claveUsuario.value = '';
                claveUsuario.required = false;
                grupoClave.style.display = 'none';
                grupoCambiarClave.style.display = 'block';
                checkCambiarClave.checked = false;
                nuevaClaveUsuario.style.display = 'none';
                nuevaClaveUsuario.value = '';
                rolUsuario.value = usuario.rol_id;
                estadoUsuario.value = usuario.activo;
                estadoUsuario.parentElement.style.display = 'block';
                mensajeError.style.display = 'none';
                
                modalUsuario.style.display = 'flex';
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener usuario:', error);
            mostrarNotificacion('Error al cargar los datos del usuario', 'error');
        }
    }

    // Cierra el modal
    function cerrarModal() {
        modalUsuario.style.display = 'none';
        formularioUsuario.reset();
        mensajeError.style.display = 'none';
    }

    // Guarda o actualiza un usuario
    async function guardarUsuario() {
        const id = usuarioId.value;
        const esEdicion = id !== '';
        
        // Validar campos basicos
        if (!nombreUsuario.value.trim()) {
            mostrarError('El nombre es obligatorio');
            return;
        }
        
        if (!correoUsuario.value.trim()) {
            mostrarError('El correo electronico es obligatorio');
            return;
        }
        
        if (!esEdicion && !claveUsuario.value.trim()) {
            mostrarError('La clave es obligatoria');
            return;
        }
        
        if (!esEdicion && claveUsuario.value.trim().length < 6) {
            mostrarError('La clave debe tener al menos 6 caracteres');
            return;
        }
        
        if (esEdicion && checkCambiarClave.checked && nuevaClaveUsuario.value.trim().length < 6) {
            mostrarError('La nueva clave debe tener al menos 6 caracteres');
            return;
        }
        
        if (!rolUsuario.value) {
            mostrarError('Debe seleccionar un rol');
            return;
        }
        
        // Determinar a que URL enviar
        const url = esEdicion ? 'usuario/actualizar' : 'usuario/guardar';
        
        // Preparar datos del formulario
        const formData = new FormData(formularioUsuario);
        
        // Si es edicion y no se cambio clave, forzar el campo
        if (esEdicion && !checkCambiarClave.checked) {
            formData.delete('nueva_clave');
        }
        
        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cerrarModal();
                cargarUsuarios();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar usuario:', error);
            mostrarError('Error de conexion al guardar el usuario');
        }
    }

    // Cambia el estado de un usuario (activar/desactivar)
    async function cambiarEstadoUsuario(id, nuevoEstado) {
        const accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
        
        if (!confirm('Esta seguro de ' + accion + ' este usuario?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        formData.append('activo', nuevoEstado);
        
        try {
            const respuesta = await fetch('usuario/cambiarEstado', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cargarUsuarios();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al cambiar estado:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Elimina un usuario
    async function eliminarUsuario(id, nombre) {
        if (!confirm('Esta seguro de eliminar al usuario ' + nombre + '?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        
        try {
            const respuesta = await fetch('usuario/eliminar', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cargarUsuarios();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar usuario:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.style.display = 'block';
    }
});