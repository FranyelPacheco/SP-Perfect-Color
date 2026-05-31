const DATATABLES_SPANISH = {
    "emptyTable": "No hay informacion",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
};

document.addEventListener('DOMContentLoaded', function () {
    var ROL = typeof SESSION_USER_ROL !== 'undefined' ? SESSION_USER_ROL : null;
    var MI_ID = typeof SESSION_USER_ID !== 'undefined' ? SESSION_USER_ID : null;

    var formUsuario = document.getElementById('formularioUsuario');
    if (!formUsuario) {
        console.warn('El formulario #formularioUsuario no se encontró en esta página.');
        return;
    }

    if (ROL === 1) {
        if (document.getElementById('areaAdminUsuarios') && document.getElementById('modalUsuario')) {
            initAdmin();
        }
    } else if (ROL === 2) {
        if (document.getElementById('modalUsuario')) {
            initVendor();
        }
    }

    formUsuario.addEventListener('submit', procesarFormulario);

    // ---- ADMIN ----
    function initAdmin() {
        var btnNuevo = document.getElementById('btnNuevoUsuario');
        if (btnNuevo) btnNuevo.addEventListener('click', abrirModalCrear);

        var selRol = document.getElementById('rolUsuario');
        if (selRol) selRol.setAttribute('required', '');

        var selEstado = document.getElementById('estadoUsuario');
        if (selEstado) selEstado.setAttribute('required', '');

        var btnCancel = document.getElementById('btnCancelarUsuario');
        if (btnCancel) btnCancel.addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
        });

        var btnCerrar = document.getElementById('btnCerrarModalUsuario');
        if (btnCerrar) btnCerrar.addEventListener('click', function() {
            bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
        });

        var modal = document.getElementById('modalUsuario');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('formularioUsuario').reset();
                document.getElementById('mensajeErrorUsuario').style.display = 'none';
                document.getElementById('grupoClave').style.display = 'block';
                document.getElementById('claveUsuario').required = false;
                document.getElementById('grupoCambiarClave').style.display = 'none';
                document.getElementById('checkCambiarClave').checked = false;
                document.getElementById('nuevaClaveUsuario').style.display = 'none';
                document.getElementById('nuevaClaveUsuario').value = '';
                document.getElementById('contenedorRol').style.display = 'block';
                document.getElementById('contenedorEstado').style.display = 'block';
                document.getElementById('rolUsuario').required = true;
            });
        }

        cargarUsuarios();
    }

    // ---- VENDEDOR ----
    function initVendor() {
        var areaAdmin = document.getElementById('areaAdminUsuarios');
        if (areaAdmin) areaAdmin.classList.add('d-none');

        var btnNuevo = document.getElementById('btnNuevoUsuario');
        if (btnNuevo && !btnNuevo.closest('#modalUsuario')) btnNuevo.classList.add('d-none');

        var titulo = document.getElementById('tituloModalUsuario');
        if (titulo) titulo.textContent = 'Editar Usuario';

        var txtRol = document.getElementById('rolUsuario');
        if (txtRol) {
            txtRol.removeAttribute('required');
            if (txtRol.closest('.grupo-formulario')) txtRol.closest('.grupo-formulario').classList.add('d-none');
            else if (txtRol.parentElement) txtRol.parentElement.classList.add('d-none');
        }

        var txtEstado = document.getElementById('estadoUsuario');
        if (txtEstado) {
            txtEstado.removeAttribute('required');
            if (txtEstado.closest('.grupo-formulario')) txtEstado.closest('.grupo-formulario').classList.add('d-none');
            else if (txtEstado.parentElement) txtEstado.parentElement.classList.add('d-none');
        }

        var grupoClave = document.getElementById('grupoClave');
        if (grupoClave) grupoClave.classList.add('d-none');

        var btnToggle = document.getElementById('btnToggleClave');
        if (btnToggle) {
            btnToggle.classList.remove('d-none');
            btnToggle.addEventListener('click', function () {
                this.classList.add('d-none');
                var grupo = document.getElementById('grupoCambiarClave');
                var inputNuevaClave = document.getElementById('nuevaClaveUsuario');
                var checkbox = document.getElementById('checkCambiarClave');
                if (grupo && inputNuevaClave) {
                    grupo.classList.remove('d-none');
                    inputNuevaClave.classList.remove('d-none');
                }
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        cargarPerfilVendedor();
    }

    // ---- Cargar datos propios del vendedor ----
    async function cargarPerfilVendedor() {
        console.log('Intentando cargar perfil para ID:', SESSION_USER_ID);
        try {
            var res = await fetch('/SP%20Perfect%20Color/usuario/obtener?id=' + SESSION_USER_ID);
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            var data = await res.json();
            console.log('Datos recibidos:', data);

            if (data.estado === 'exito' && data.datos) {
                document.getElementById('usuarioId').value = data.datos.id;
                document.getElementById('nombreUsuario').value = data.datos.nombre || '';
                document.getElementById('correoUsuario').value = data.datos.correo || '';
                console.log('Inputs rellenados correctamente');
            } else {
                console.warn('El servidor respondio pero sin datos validos:', data);
            }
        } catch (error) {
            console.error('Fallo al cargar perfil:', error);
        }
    }

    // ---- Llenar select de roles ----
    function llenarSelectRoles(roles) {
        var select = document.getElementById('rolUsuario');
        if (!select) return;
        select.innerHTML = '<option value="">Seleccione un rol</option>';
        roles.forEach(function (r) {
            var op = document.createElement('option');
            op.value = r.id;
            op.textContent = r.nombre;
            select.appendChild(op);
        });
    }

    // ---- Cargar lista de usuarios (Admin) ----
    async function cargarUsuarios() {
        try {
            var res = await fetch('usuario/listarAjax');
            var json = await res.json();
            if (json.estado !== 'exito') {
                console.error('Error al listar usuarios:', json.mensaje);
                return;
            }

            console.log('Respuesta listarAjax:', json);

            if (json.datos.roles) llenarSelectRoles(json.datos.roles);

            var usuarios = json.datos.usuarios || [];

            if (!$.fn.DataTable.isDataTable('#tablaUsuarios')) {
                $('#tablaUsuarios').DataTable({
                    dom: 'lrtip',
                    language: DATATABLES_SPANISH
                });
            }

            var table = $('#tablaUsuarios').DataTable();
            table.clear();

            usuarios.forEach(function (u) {
                var acciones = '<div class="d-flex gap-2">' +
                    '<button class="btn btn-sm btn-warning btn-editar-usuario" data-id="' + u.id + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';
                if (u.id != MI_ID) {
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-usuario" data-id="' + u.id + '" data-nombre="' + u.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
                }
                acciones += '</div>';

                var estadoBadge = u.activo == 1 ? 'Activo' : 'Inactivo';

                table.row.add([
                    u.nombre,
                    u.correo,
                    u.rol_nombre,
                    estadoBadge,
                    acciones
                ]);
            });

            table.draw();

            // Delegated events for action buttons
            document.getElementById('tablaUsuarios').addEventListener('click', function(e) {
                var btn = e.target.closest('.btn-editar-usuario');
                if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
                btn = e.target.closest('.btn-eliminar-usuario');
                if (btn) { eliminarUsuario(parseInt(btn.dataset.id), btn.dataset.nombre); return; }
            });

        } catch (error) {
            console.error('Error al cargar usuarios:', error);
        }
    }

    // ---- Abrir modal para nuevo usuario ----
    function abrirModalCrear() {
        document.getElementById('formularioUsuario').reset();
        document.getElementById('usuarioId').value = '';
        document.getElementById('mensajeErrorUsuario').style.display = 'none';
        document.getElementById('tituloModalUsuario').textContent = 'Nuevo Usuario';

        document.getElementById('grupoClave').style.display = 'block';
        document.getElementById('claveUsuario').required = true;

        document.getElementById('grupoCambiarClave').style.display = 'none';
        document.getElementById('checkCambiarClave').checked = false;
        document.getElementById('nuevaClaveUsuario').style.display = 'none';
        document.getElementById('nuevaClaveUsuario').value = '';

        document.getElementById('contenedorRol').style.display = 'block';
        document.getElementById('contenedorEstado').style.display = 'block';
        document.getElementById('rolUsuario').required = true;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();
    }

    // ---- Abrir modal para editar usuario ----
    async function abrirModalEditar(id) {
        try {
            var res = await fetch('usuario/obtener?id=' + id);
            var json = await res.json();
            if (json.estado !== 'exito') {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(json.mensaje, 'error');
                } else {
                    alert(json.mensaje);
                }
                return;
            }

            var u = json.datos;
            document.getElementById('formularioUsuario').reset();
            document.getElementById('mensajeErrorUsuario').style.display = 'none';
            document.getElementById('usuarioId').value = u.id;
            document.getElementById('nombreUsuario').value = u.nombre;
            document.getElementById('correoUsuario').value = u.correo;

            document.getElementById('grupoClave').style.display = 'none';
            document.getElementById('claveUsuario').required = false;

            document.getElementById('grupoCambiarClave').style.display = 'block';
            document.getElementById('checkCambiarClave').checked = false;
            document.getElementById('nuevaClaveUsuario').style.display = 'none';
            document.getElementById('nuevaClaveUsuario').value = '';

            document.getElementById('tituloModalUsuario').textContent = 'Editar Usuario';
            document.getElementById('contenedorRol').style.display = 'block';
            document.getElementById('contenedorEstado').style.display = 'block';
            document.getElementById('rolUsuario').value = u.rol_id;
            document.getElementById('estadoUsuario').value = u.activo;
            document.getElementById('rolUsuario').required = true;

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();

        } catch (error) {
            console.error('Error al obtener usuario:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error al cargar los datos del usuario', 'error');
            }
        }
    }

    // ---- Checkbox cambio de clave ----
    document.getElementById('checkCambiarClave').addEventListener('change', function () {
        var input = document.getElementById('nuevaClaveUsuario');
        if (this.checked) {
            input.style.display = 'block';
            input.required = true;
        } else {
            input.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    });

    // ---- Procesar formulario ----
    async function procesarFormulario(e) {
        e.preventDefault();

        var id = document.getElementById('usuarioId').value;
        var esEdicion = id !== '';
        var url = esEdicion ? 'usuario/actualizar' : 'usuario/guardar';
        var errorDiv = document.getElementById('mensajeErrorUsuario');
        errorDiv.style.display = 'none';

        var nombre = document.getElementById('nombreUsuario').value.trim();
        var correo = document.getElementById('correoUsuario').value.trim();

        if (!nombre) { mostrarError('El nombre es obligatorio'); return; }
        if (!correo) { mostrarError('El correo electronico es obligatorio'); return; }

        if (!esEdicion) {
            var clave = document.getElementById('claveUsuario').value.trim();
            if (!clave) { mostrarError('La clave es obligatoria'); return; }
            if (clave.length < 6) { mostrarError('La clave debe tener al menos 6 caracteres'); return; }
        }

        var formData = new FormData(document.getElementById('formularioUsuario'));

        if (esEdicion && ROL === 1) {
            var checkClave = document.getElementById('checkCambiarClave');
            if (checkClave.checked) {
                var nuevaClave = document.getElementById('nuevaClaveUsuario').value.trim();
                if (nuevaClave.length < 6) { mostrarError('La nueva clave debe tener al menos 6 caracteres'); return; }
            }
            if (!checkClave.checked) {
                formData.delete('nueva_clave');
            }
        }

        if (esEdicion && ROL === 2) {
            var checkClave = document.getElementById('checkCambiarClave');
            if (checkClave && checkClave.checked) {
                var nuevaClave = document.getElementById('nuevaClaveUsuario').value.trim();
                if (nuevaClave !== '' && nuevaClave.length < 6) {
                    mostrarError('La nueva clave debe tener al menos 6 caracteres');
                    return;
                }
                if (nuevaClave !== '') {
                    formData.set('cambiar_clave', '1');
                    formData.set('nueva_clave', nuevaClave);
                }
            }
            if (!checkClave || !checkClave.checked) {
                formData.delete('nueva_clave');
            }
            formData.delete('clave');
        }

        try {
            var res = await fetch(url, { method: 'POST', body: formData });
            var json = await res.json();

            if (json.estado === 'exito') {
                if (ROL === 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                    cargarUsuarios();
                } else {
                    var btnToggle = document.getElementById('btnToggleClave');
                    if (btnToggle) btnToggle.classList.remove('d-none');
                    var grupoCC = document.getElementById('grupoCambiarClave');
                    if (grupoCC) grupoCC.style.display = 'none';
                    document.getElementById('checkCambiarClave').checked = false;
                    document.getElementById('nuevaClaveUsuario').value = '';
                    document.getElementById('nuevaClaveUsuario').style.display = 'none';
                }

                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    alert(json.mensaje);
                }
            } else {
                mostrarError(json.mensaje);
            }

        } catch (error) {
            console.error('Error al guardar usuario:', error);
            mostrarError('Error de conexion al guardar el usuario');
        }
    }

    // ---- Mostrar error ----
    function mostrarError(msg) {
        var errorDiv = document.getElementById('mensajeErrorUsuario');
        errorDiv.textContent = msg;
        errorDiv.style.display = 'block';
    }

    // ---- Eliminar usuario ----
    async function eliminarUsuario(id, nombre) {
        if (!confirm('¿Esta seguro de eliminar al usuario ' + nombre + '?')) return;

        var fd = new FormData();
        fd.append('id', id);

        try {
            var res = await fetch('usuario/eliminar', { method: 'POST', body: fd });
            var json = await res.json();

            if (json.estado === 'exito') {
                cargarUsuarios();
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    alert(json.mensaje);
                }
            } else {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(json.mensaje, 'error');
                } else {
                    alert(json.mensaje);
                }
            }

        } catch (error) {
            console.error('Error al eliminar usuario:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error de conexion', 'error');
            }
        }
    }
});
