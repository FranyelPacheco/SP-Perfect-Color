// ARCHIVO: usuario.js
// OBJETIVO: Gestión dinámica de Usuarios, Roles, Permisos por Módulo y Perfil de Usuario

document.addEventListener('DOMContentLoaded', function () {
    var ROL = typeof SESSION_USER_ROL !== 'undefined' ? SESSION_USER_ROL : null;
    var MI_ID = typeof SESSION_USER_ID !== 'undefined' ? SESSION_USER_ID : null;
    var GESTIONAR = typeof PUEDE_GESTIONAR !== 'undefined' ? PUEDE_GESTIONAR : (ROL === 1);

    var formUsuario = document.getElementById('formularioUsuario');
    var formRol = document.getElementById('formularioRol');

    // Nombres legibles para insignias de módulos
    var NOMBRES_MODULOS = {
        'dashboard': 'Inicio',
        'cliente': 'Clientes',
        'proveedor': 'Proveedores',
        'inventario': 'Inventario',
        'presupuesto': 'Presupuestos',
        'notaEntrega': 'Notas de Entrega',
        'cuentaCobrar': 'CxC',
        'cuentaPagar': 'CxP',
        'banco': 'Bancos',
        'tipoPago': 'Tipos Pago',
        'reporte': 'Reportes',
        'usuario': 'Usuarios/Roles'
    };

    if (GESTIONAR) {
        initAdminUsuarios();
        initAdminRoles();
    } else {
        initVendor();
    }

    // Checkbox cambiar contraseña
    (function registrarCheckboxClave() {
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

    if (formUsuario) {
        formUsuario.addEventListener('submit', procesarFormularioUsuario);
    }

    if (formRol) {
        formRol.addEventListener('submit', procesarFormularioRol);
    }

    // Redibujar tablas al cambiar de pestaña
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        if (e.target.id === 'pills-roles-tab' && $.fn.DataTable.isDataTable('#tablaRoles')) {
            $('#tablaRoles').DataTable().columns.adjust().draw();
        }
        if (e.target.id === 'pills-usuarios-tab' && $.fn.DataTable.isDataTable('#tablaUsuarios')) {
            $('#tablaUsuarios').DataTable().columns.adjust().draw();
        }
    });

    // ============================================================
    // MÓDULO USUARIOS (ADMINISTRADOR / DELEGADOS)
    // ============================================================

    function initAdminUsuarios() {
        var btnNuevo = document.getElementById('btnNuevoUsuario');
        if (btnNuevo) btnNuevo.addEventListener('click', abrirModalCrearUsuario);

        var modalEl = document.getElementById('modalUsuario');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                if (document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                document.getElementById('formularioUsuario').reset();
                var el = document.getElementById('mensajeErrorUsuario');
                if (el) el.classList.add('d-none');
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
            });
        }

        // Delegación de eventos para botones de acción de usuario
        var tablaUsuariosEl = document.getElementById('tablaUsuarios');
        if (tablaUsuariosEl) {
            tablaUsuariosEl.addEventListener('click', function (e) {
                var btn = e.target.closest('.btn-editar-usuario');
                if (btn) { abrirModalEditarUsuario(parseInt(btn.dataset.id)); return; }
                btn = e.target.closest('.btn-eliminar-usuario');
                if (btn) { eliminarUsuario(parseInt(btn.dataset.id), btn.dataset.nombre); return; }
            });
        }

        cargarUsuarios();
    }

    async function cargarUsuarios() {
        try {
            var res = await fetch('usuario/listarAjax');
            var json = await res.json();
            if (json.estado !== 'exito') {
                console.error('Error al listar usuarios:', json.mensaje);
                return;
            }

            if (json.datos.roles) {
                llenarSelectRoles(json.datos.roles);
            }

            var usuarios = json.datos.usuarios || [];

            if (!$.fn.DataTable.isDataTable('#tablaUsuarios')) {
                $('#tablaUsuarios').DataTable({
                    language: window.DATATABLES_SPANISH,
                    pageLength: 10,
                    responsive: true
                });
            }

            var table = $('#tablaUsuarios').DataTable();
            table.clear();

            usuarios.forEach(function (u) {
                var acciones = '<div class="d-inline-flex gap-1">' +
                    '<button class="btn btn-sm btn-warning btn-editar-usuario" data-id="' + u.id_usuario + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';
                if (parseInt(u.id_usuario) !== parseInt(MI_ID)) {
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-usuario" data-id="' + u.id_usuario + '" data-nombre="' + u.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
                }
                acciones += '</div>';

                var estadoBadge = u.activo == 1 
                    ? '<span class="badge bg-success">Activo</span>' 
                    : '<span class="badge bg-secondary">Inactivo</span>';

                table.row.add([
                    u.nombre,
                    u.correo,
                    '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' + (u.rol_nombre || 'Sin Rol') + '</span>',
                    estadoBadge,
                    acciones
                ]);
            });

            table.draw();

        } catch (error) {
            console.error('Error al cargar usuarios:', error);
        }
    }

    function llenarSelectRoles(roles) {
        var select = document.getElementById('rolUsuario');
        if (!select) return;
        var valorActual = select.value;
        select.innerHTML = '<option value="">Seleccione un rol activo</option>';
        roles.forEach(function (r) {
            var op = document.createElement('option');
            op.value = r.id_rol;
            op.textContent = r.nombre;
            select.appendChild(op);
        });
        if (valorActual) select.value = valorActual;
    }

    function abrirModalCrearUsuario() {
        document.getElementById('formularioUsuario').reset();
        document.getElementById('usuarioId').value = '';
        var err = document.getElementById('mensajeErrorUsuario');
        if (err) err.classList.add('d-none');
        document.getElementById('tituloModalUsuario').textContent = 'Nuevo Usuario';

        document.getElementById('grupoClave').style.display = 'block';
        document.getElementById('claveUsuario').required = true;

        document.getElementById('grupoCambiarClave').style.display = 'none';
        document.getElementById('grupoNuevaClave').style.display = 'none';

        var contRol = document.getElementById('contenedorRol');
        if (contRol) contRol.style.display = 'block';
        var contEst = document.getElementById('contenedorEstado');
        if (contEst) contEst.style.display = 'block';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();
    }

    async function abrirModalEditarUsuario(id) {
        try {
            var res = await fetch('usuario/obtener?id=' + id);
            var json = await res.json();
            if (json.estado !== 'exito') {
                if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'error');
                else alert(json.mensaje);
                return;
            }

            var u = json.datos;
            document.getElementById('formularioUsuario').reset();
            var err = document.getElementById('mensajeErrorUsuario');
            if (err) err.classList.add('d-none');

            document.getElementById('usuarioId').value = u.id_usuario;
            document.getElementById('nombreUsuario').value = u.nombre;
            document.getElementById('correoUsuario').value = u.correo;

            document.getElementById('grupoClave').style.display = 'none';
            document.getElementById('claveUsuario').required = false;

            document.getElementById('grupoCambiarClave').style.display = 'block';
            document.getElementById('grupoNuevaClave').style.display = 'none';
            var chk = document.getElementById('chkCambiarClave');
            if (chk) chk.checked = false;

            document.getElementById('tituloModalUsuario').textContent = 'Editar Usuario';

            var contRol = document.getElementById('contenedorRol');
            if (contRol) {
                contRol.style.display = 'block';
                document.getElementById('rolUsuario').value = u.id_rol;
            }

            var contEstado = document.getElementById('contenedorEstado');
            if (contEstado) {
                contEstado.style.display = 'block';
                document.getElementById('estadoUsuario').value = u.activo;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();

        } catch (error) {
            console.error('Error al obtener usuario:', error);
            if (typeof mostrarNotificacion === 'function') mostrarNotificacion('Error al cargar datos del usuario', 'error');
        }
    }

    async function procesarFormularioUsuario(e) {
        e.preventDefault();

        var id = document.getElementById('usuarioId').value;
        var esEdicion = id !== '';
        var url = esEdicion ? 'usuario/actualizar' : 'usuario/guardar';

        var nombre = document.getElementById('nombreUsuario').value.trim();
        var correo = document.getElementById('correoUsuario').value.trim();

        if (!nombre) { mostrarErrorUsuario('El nombre es obligatorio'); return; }
        if (!correo) { mostrarErrorUsuario('El correo electrónico es obligatorio'); return; }

        if (!esEdicion) {
            var clave = document.getElementById('claveUsuario').value.trim();
            if (!clave) { mostrarErrorUsuario('La contraseña es obligatoria'); return; }
            if (clave.length < 6) { mostrarErrorUsuario('La clave debe tener al menos 6 caracteres'); return; }
        }

        var formData = new FormData(document.getElementById('formularioUsuario'));

        if (esEdicion) {
            var chk = document.getElementById('chkCambiarClave');
            var cambiarClave = chk && chk.checked;
            if (cambiarClave) {
                var nuevaClave = document.getElementById('nuevaClaveUsuario').value.trim();
                if (!nuevaClave || nuevaClave.length < 6) {
                    mostrarErrorUsuario('La nueva clave debe tener al menos 6 caracteres');
                    return;
                }
                formData.set('cambiar_clave', '1');
                formData.set('nueva_clave', nuevaClave);
            } else {
                formData.delete('nueva_clave');
            }
        }

        try {
            var res = await fetch(url, { method: 'POST', body: formData });
            var json = await res.json();

            if (json.estado === 'exito') {
                bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                if (GESTIONAR) cargarUsuarios();
                if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'exito');
                else alert(json.mensaje);
            } else {
                mostrarErrorUsuario(json.mensaje);
            }

        } catch (error) {
            console.error('Error al guardar usuario:', error);
            mostrarErrorUsuario('Error de conexión al guardar el usuario');
        }
    }

    function mostrarErrorUsuario(msg) {
        var errorDiv = document.getElementById('mensajeErrorUsuario');
        if (errorDiv) {
            errorDiv.textContent = msg;
            errorDiv.classList.remove('d-none');
        }
    }

    async function eliminarUsuario(id, nombre) {
        confirmarConModal('Eliminar Usuario', '¿Está seguro de eliminar al usuario ' + nombre + '?', function () {
            var fd = new FormData();
            fd.append('id', id);
            fetch('usuario/eliminar', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarUsuarios();
                        if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'exito');
                        else alert(json.mensaje);
                    } else {
                        if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'error');
                        else alert(json.mensaje);
                    }
                })
                .catch(function () {
                    if (typeof mostrarNotificacion === 'function') mostrarNotificacion('Error de conexión al eliminar usuario', 'error');
                });
        });
    }

    // ============================================================
    // MÓDULO ROLES Y PERMISOS
    // ============================================================

    function initAdminRoles() {
        var btnNuevoRol = document.getElementById('btnNuevoRol');
        if (btnNuevoRol) btnNuevoRol.addEventListener('click', abrirModalCrearRol);

        var modalRolEl = document.getElementById('modalRol');
        if (modalRolEl) {
            modalRolEl.addEventListener('hidden.bs.modal', function () {
                if (document.activeElement && modalRolEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                document.getElementById('formularioRol').reset();
                var err = document.getElementById('mensajeErrorRol');
                if (err) err.classList.add('d-none');
                document.querySelectorAll('.chk-modulo').forEach(function (c) { c.checked = false; });
                var estSelect = document.getElementById('estadoRol');
                if (estSelect) estSelect.disabled = false;
                var nombreInput = document.getElementById('nombreRol');
                if (nombreInput) nombreInput.readOnly = false;
            });
        }

        // Delegación de eventos para la tabla de roles
        var tablaRolesEl = document.getElementById('tablaRoles');
        if (tablaRolesEl) {
            tablaRolesEl.addEventListener('click', function (e) {
                var btnEdit = e.target.closest('.btn-editar-rol');
                if (btnEdit) { abrirModalEditarRol(parseInt(btnEdit.dataset.id)); return; }

                var btnToggle = e.target.closest('.btn-toggle-rol');
                if (btnToggle) {
                    var id = parseInt(btnToggle.dataset.id);
                    var activoActual = parseInt(btnToggle.dataset.activo);
                    toggleRol(id, activoActual);
                    return;
                }

                var btnEliminar = e.target.closest('.btn-eliminar-rol');
                if (btnEliminar) {
                    var id = parseInt(btnEliminar.dataset.id);
                    var nombre = btnEliminar.dataset.nombre;
                    var totalUsuarios = parseInt(btnEliminar.dataset.usuarios) || 0;
                    eliminarRol(id, nombre, totalUsuarios);
                    return;
                }
            });
        }

        cargarRoles();
    }

    async function cargarRoles() {
        try {
            var res = await fetch('usuario/listarRolesAjax');
            var json = await res.json();
            if (json.estado !== 'exito') {
                console.error('Error al listar roles:', json.mensaje);
                return;
            }

            var roles = json.datos || [];

            if (!$.fn.DataTable.isDataTable('#tablaRoles')) {
                $('#tablaRoles').DataTable({
                    language: window.DATATABLES_SPANISH,
                    pageLength: 10,
                    responsive: true
                });
            }

            var table = $('#tablaRoles').DataTable();
            table.clear();

            roles.forEach(function (r) {
                var esAdmin = parseInt(r.id_rol) === 1;

                // Acciones
                var acciones = '<div class="d-inline-flex gap-1">' +
                    '<button class="btn btn-sm btn-warning btn-editar-rol" data-id="' + r.id_rol + '" title="Editar Rol" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';

                if (esAdmin) {
                    acciones += '<span class="badge bg-light text-muted border d-flex align-items-center" title="Rol raíz protegido"><i class="bi bi-shield-lock-fill me-1"></i>Raíz</span>';
                } else {
                    var btnClass = r.activo == 1 ? 'btn-secondary' : 'btn-success';
                    var iconClass = r.activo == 1 ? 'bi-toggle-off' : 'bi-toggle-on';
                    var titleText = r.activo == 1 ? 'Deshabilitar Rol' : 'Habilitar Rol';
                    acciones += '<button class="btn btn-sm ' + btnClass + ' btn-toggle-rol" data-id="' + r.id_rol + '" data-activo="' + r.activo + '" title="' + titleText + '" data-bs-toggle="tooltip"><i class="bi ' + iconClass + '"></i></button>';
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-rol" data-id="' + r.id_rol + '" data-nombre="' + r.nombre.replace(/"/g, '&quot;') + '" data-usuarios="' + (r.total_usuarios || 0) + '" title="Eliminar Rol" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
                }
                acciones += '</div>';

                // Badges de módulos
                var modulosLista = (r.modulos || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
                var modulosHtml = '';
                if (esAdmin) {
                    modulosHtml = '<span class="badge bg-dark"><i class="bi bi-star-fill text-warning me-1"></i>Acceso Total (' + modulosLista.length + ' módulos)</span>';
                } else if (modulosLista.length === 0) {
                    modulosHtml = '<span class="badge bg-light text-muted border">Sin módulos</span>';
                } else {
                    modulosHtml = '<div class="d-flex flex-wrap gap-1">';
                    modulosLista.forEach(function (m) {
                        var etiqueta = NOMBRES_MODULOS[m] || m;
                        var colorClass = (m === 'usuario') ? 'bg-danger text-white' : 'bg-info-subtle text-info-emphasis border border-info-subtle';
                        modulosHtml += '<span class="badge ' + colorClass + '" style="font-size:0.75rem;">' + etiqueta + '</span>';
                    });
                    modulosHtml += '</div>';
                }

                var estadoBadge = r.activo == 1 
                    ? '<span class="badge bg-success">Habilitado</span>' 
                    : '<span class="badge bg-secondary">Deshabilitado</span>';

                var totalUsers = '<span class="badge bg-light text-dark border">' + (r.total_usuarios || 0) + '</span>';

                table.row.add([
                    '#' + r.id_rol,
                    '<strong>' + r.nombre + '</strong>',
                    modulosHtml,
                    totalUsers,
                    estadoBadge,
                    acciones
                ]);
            });

            table.draw();

        } catch (error) {
            console.error('Error al cargar roles:', error);
        }
    }

    function abrirModalCrearRol() {
        document.getElementById('formularioRol').reset();
        document.getElementById('rolId').value = '';
        var err = document.getElementById('mensajeErrorRol');
        if (err) err.classList.add('d-none');
        document.getElementById('tituloModalRol').textContent = 'Nuevo Rol';

        var est = document.getElementById('estadoRol');
        if (est) { est.value = '1'; est.disabled = false; }
        var nom = document.getElementById('nombreRol');
        if (nom) { nom.readOnly = false; }

        document.querySelectorAll('.chk-modulo').forEach(function (c) {
            c.checked = false;
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRol')).show();
    }

    async function abrirModalEditarRol(id) {
        try {
            var res = await fetch('usuario/obtenerRol?id=' + id);
            var json = await res.json();
            if (json.estado !== 'exito') {
                if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'error');
                else alert(json.mensaje);
                return;
            }

            var rol = json.datos;
            document.getElementById('formularioRol').reset();
            var err = document.getElementById('mensajeErrorRol');
            if (err) err.classList.add('d-none');

            document.getElementById('rolId').value = rol.id_rol;
            document.getElementById('nombreRol').value = rol.nombre;
            document.getElementById('tituloModalRol').textContent = 'Editar Rol: ' + rol.nombre;

            var esAdmin = parseInt(rol.id_rol) === 1;
            var estadoSelect = document.getElementById('estadoRol');
            if (estadoSelect) {
                estadoSelect.value = rol.activo;
                estadoSelect.disabled = esAdmin; // Administrador siempre activo
            }

            // Marcar los checkboxes de módulos autorizados
            var mods = (rol.modulos || '').split(',').map(function (s) { return s.trim(); });
            document.querySelectorAll('.chk-modulo').forEach(function (chk) {
                chk.checked = mods.includes(chk.value);
            });

            // Si es Administrador raíz, forzar checkbox usuario
            if (esAdmin) {
                var chkUser = document.getElementById('mod_usuario');
                if (chkUser) chkUser.checked = true;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRol')).show();

        } catch (error) {
            console.error('Error al obtener rol:', error);
            if (typeof mostrarNotificacion === 'function') mostrarNotificacion('Error al cargar datos del rol', 'error');
        }
    }

    async function procesarFormularioRol(e) {
        e.preventDefault();

        var id = document.getElementById('rolId').value;
        var esEdicion = id !== '';
        var url = esEdicion ? 'usuario/actualizarRol' : 'usuario/guardarRol';

        var nombre = document.getElementById('nombreRol').value.trim();
        if (!nombre) {
            mostrarErrorRol('El nombre del rol es obligatorio');
            return;
        }

        // Obtener módulos seleccionados
        var chks = document.querySelectorAll('.chk-modulo:checked');
        if (chks.length === 0) {
            mostrarErrorRol('Debe seleccionar al menos un módulo para el rol');
            return;
        }

        var formData = new FormData(document.getElementById('formularioRol'));

        try {
            var res = await fetch(url, { method: 'POST', body: formData });
            var json = await res.json();

            if (json.estado === 'exito') {
                bootstrap.Modal.getInstance(document.getElementById('modalRol')).hide();
                cargarRoles();
                // Actualizar lista de roles en el select de usuarios
                cargarUsuarios();

                if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'exito');
                else alert(json.mensaje);
            } else {
                mostrarErrorRol(json.mensaje);
            }

        } catch (error) {
            console.error('Error al guardar rol:', error);
            mostrarErrorRol('Error de conexión al guardar el rol');
        }
    }

    function mostrarErrorRol(msg) {
        var err = document.getElementById('mensajeErrorRol');
        if (err) {
            err.textContent = msg;
            err.classList.remove('d-none');
        }
    }

    function toggleRol(id, activoActual) {
        var nuevoEstado = activoActual == 1 ? 0 : 1;
        var mensaje = nuevoEstado == 0 
            ? '¿Está seguro de deshabilitar este rol?<div class="alert alert-warning py-2 px-3 small mt-2 mb-0 text-start"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Nota:</strong> Los usuarios asociados a este rol no podrán iniciar sesión mientras el rol permanezca inactivo.</div>'
            : '¿Está seguro de habilitar este rol?';
        var claseBtn = nuevoEstado == 1 ? 'btn-success' : 'btn-danger';

        confirmarConModal('Cambiar Estado de Rol', mensaje, function () {
            var fd = new FormData();
            fd.append('id', id);
            fd.append('activo', nuevoEstado);

            fetch('usuario/toggleRol', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarRoles();
                        cargarUsuarios();
                        if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'exito');
                        else alert(json.mensaje);
                    } else {
                        if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'error');
                        else alert(json.mensaje);
                    }
                })
                .catch(function () {
                    if (typeof mostrarNotificacion === 'function') mostrarNotificacion('Error de conexión al cambiar estado del rol', 'error');
                });
        }, claseBtn);
    }

    function eliminarRol(id, nombre, totalUsuarios) {
        if (id === 1) {
            confirmarConModal('Acción no permitida', 'El rol Administrador es el rol principal del sistema y no puede ser eliminado.', null, 'btn-secondary');
            return;
        }

        if (totalUsuarios > 0) {
            var advertencia = 'No se puede eliminar el rol <strong>' + nombre + '</strong> porque tiene <strong>' + totalUsuarios + '</strong> usuario(s) asignado(s).' +
                '<div class="alert alert-danger py-2 px-3 small mt-2 mb-0 text-start">' +
                '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Atención:</strong> Debe reasignar o eliminar los usuarios asociados antes de poder borrar este rol.' +
                '</div>';
            confirmarConModal('Rol en Uso', advertencia, null, 'btn-secondary');
            return;
        }

        var mensajeConfirmacion = '¿Está seguro de eliminar permanentemente el rol <strong>' + nombre + '</strong>?' +
            '<div class="alert alert-danger py-2 px-3 small mt-2 mb-0 text-start">' +
            '<i class="bi bi-trash-fill me-1"></i> <strong>Advertencia:</strong> Esta acción no se puede deshacer.' +
            '</div>';

        confirmarConModal('Eliminar Rol', mensajeConfirmacion, function () {
            var fd = new FormData();
            fd.append('id', id);

            fetch('usuario/eliminarRol', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarRoles();
                        cargarUsuarios();
                        if (typeof mostrarNotificacion === 'function') mostrarNotificacion(json.mensaje, 'exito');
                        else alert(json.mensaje);
                    } else {
                        confirmarConModal('Atención', json.mensaje, null, 'btn-secondary');
                    }
                })
                .catch(function () {
                    if (typeof mostrarNotificacion === 'function') mostrarNotificacion('Error de conexión al eliminar el rol', 'error');
                });
        }, 'btn-danger');
    }

    // ============================================================
    // VISTA DE PERFIL (VENDEDOR / USUARIOS GENERALES)
    // ============================================================

    function initVendor() {
        var btnEditar = document.getElementById('btnEditarPerfil');
        if (btnEditar) {
            btnEditar.addEventListener('click', function () {
                abrirModalEditarUsuario(MI_ID);
            });
        }
    }
});
