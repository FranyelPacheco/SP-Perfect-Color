
document.addEventListener('DOMContentLoaded', function () {
    var ROL = typeof SESSION_USER_ROL !== 'undefined' ? SESSION_USER_ROL : null;
    var MI_ID = typeof SESSION_USER_ID !== 'undefined' ? SESSION_USER_ID : null;

    var formUsuario = document.getElementById('formularioUsuario');
    if (!formUsuario) {
        console.warn('El formulario #formularioUsuario no se encontrÃ³ en esta pÃ¡gina.');
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

    // Registrar evento del checkbox para AMBOS roles
    (function registrarCheckbox() {
        var chk = document.getElementById('chkCambiarClave');
        if (chk) {
            function toggleNuevaClave() {
                var grupo = document.getElementById('grupoNuevaClave');
                if (grupo) {
                    grupo.style.display = chk.checked ? 'block' : 'none';
                }
            }
            chk.addEventListener('change', toggleNuevaClave);
            chk.addEventListener('click', toggleNuevaClave);
        }
    })();

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
                var el;
                document.getElementById('formularioUsuario').reset();
                // Limpiar focus residual para evitar warning aria-hidden
                if (document.activeElement && modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                el = document.getElementById('mensajeErrorUsuario');
                if (el) el.style.display = 'none';
                el = document.getElementById('grupoClave');
                if (el) el.style.display = 'block';
                el = document.getElementById('claveUsuario');
                if (el) el.required = false;
                el = document.getElementById('grupoCambiarClave');
                if (el) el.style.display = 'none';
                el = document.getElementById('grupoNuevaClave');
                if (el) el.style.display = 'none';
                el = document.getElementById('nuevaClaveUsuario');
                if (el) el.value = '';
                el = document.getElementById('chkCambiarClave');
                if (el) el.checked = false;
                el = document.getElementById('contenedorRol');
                if (el) el.style.display = 'block';
                el = document.getElementById('contenedorEstado');
                if (el) el.style.display = 'block';
                el = document.getElementById('rolUsuario');
                if (el) el.required = true;
            });
        }

        cargarUsuarios();
    }

    // ---- VENDEDOR ----
    function initVendor() {
        var titulo = document.getElementById('tituloModalUsuario');
        if (titulo) titulo.textContent = 'Editar Perfil';

        var txtRol = document.getElementById('rolUsuario');
        if (txtRol) {
            txtRol.removeAttribute('required');
            if (txtRol.parentElement) txtRol.parentElement.classList.add('d-none');
        }

        var txtEstado = document.getElementById('estadoUsuario');
        if (txtEstado) {
            txtEstado.removeAttribute('required');
            if (txtEstado.parentElement) txtEstado.parentElement.classList.add('d-none');
        }

        var grupoClave = document.getElementById('grupoClave');
        if (grupoClave) grupoClave.classList.add('d-none');

        var grupoCambiar = document.getElementById('grupoCambiarClave');
        if (grupoCambiar) grupoCambiar.style.display = 'block';

        var btnEditar = document.getElementById('btnEditarPerfil');
        if (btnEditar) {
            btnEditar.addEventListener('click', function () {
                abrirModalEditar(MI_ID);
            });
        }
    }

    // ---- Llenar select de roles ----
    function llenarSelectRoles(roles) {
        var select = document.getElementById('rolUsuario');
        if (!select) return;
        select.innerHTML = '<option value="">Seleccione un rol</option>';
        roles.forEach(function (r) {
            var op = document.createElement('option');
            op.value = r.id_rol;
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
                    language: window.DATATABLES_SPANISH
                });
            }

            var table = $('#tablaUsuarios').DataTable();
            table.clear();

            usuarios.forEach(function (u) {
                var acciones = '<div class="d-flex gap-2">' +
                    '<button class="btn btn-sm btn-warning btn-editar-usuario" data-id="' + u.id_usuario + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';
                if (u.id_usuario != MI_ID) {
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-usuario" data-id="' + u.id_usuario + '" data-nombre="' + u.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
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
        document.getElementById('grupoNuevaClave').style.display = 'none';
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
            document.getElementById('usuarioId').value = u.id_usuario;
            document.getElementById('nombreUsuario').value = u.nombre;
            document.getElementById('correoUsuario').value = u.correo;

            document.getElementById('grupoClave').style.display = 'none';
            document.getElementById('claveUsuario').required = false;

            document.getElementById('grupoCambiarClave').style.display = 'block';
            document.getElementById('grupoNuevaClave').style.display = 'none';
            document.getElementById('nuevaClaveUsuario').value = '';
            var chk = document.getElementById('chkCambiarClave');
            if (chk) chk.checked = false;

            document.getElementById('tituloModalUsuario').textContent = ROL === 2 ? 'Editar Perfil' : 'Editar Usuario';

            var contRol = document.getElementById('contenedorRol');
            if (contRol) {
                contRol.style.display = 'block';
                document.getElementById('rolUsuario').value = u.id_rol;
                document.getElementById('rolUsuario').required = true;
            }
            var contEstado = document.getElementById('contenedorEstado');
            if (contEstado) {
                contEstado.style.display = 'block';
                document.getElementById('estadoUsuario').value = u.activo;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();

        } catch (error) {
            console.error('Error al obtener usuario:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error al cargar los datos del usuario', 'error');
            }
        }
    }

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

        if (esEdicion) {
            var chk = document.getElementById('chkCambiarClave');
            var cambiarClave = chk && chk.checked;
            if (cambiarClave) {
                var nuevaClave = document.getElementById('nuevaClaveUsuario').value.trim();
                if (!nuevaClave) {
                    mostrarError('Ingrese la nueva contraseÃ±a');
                    return;
                }
                if (nuevaClave.length < 6) {
                    mostrarError('La nueva clave debe tener al menos 6 caracteres');
                    return;
                }
                formData.set('cambiar_clave', '1');
                formData.set('nueva_clave', nuevaClave);
            } else {
                formData.delete('nueva_clave');
            }
            if (ROL === 2) {
                formData.delete('clave');
            }
        }

        try {
            var res = await fetch(url, { method: 'POST', body: formData });
            var json = await res.json();

            if (json.estado === 'exito') {
                if (ROL === 1) {
                    bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                    cargarUsuarios();
                } else {
                    var chk = document.getElementById('chkCambiarClave');
                    if (chk) chk.checked = false;
                    var grupoNC = document.getElementById('grupoNuevaClave');
                    if (grupoNC) grupoNC.style.display = 'none';
                    document.getElementById('nuevaClaveUsuario').value = '';
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
        confirmarConModal('Eliminar', 'Esta seguro de eliminar al usuario ' + nombre + '?', function() {
            var fd = new FormData();
            fd.append('id', id);
            fetch('usuario/eliminar', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(json) {
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
                })
                .catch(function() {
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion('Error de conexion', 'error');
                    }
                });
        });
    }
});
