// ARCHIVO: usuario.js
// OBJETIVO: Gestión dinámica de Usuarios, Roles, Permisos por Módulo y Perfil de Usuario

document.addEventListener('DOMContentLoaded', function () {
    var ROL = typeof SESSION_USER_ROL !== 'undefined' ? SESSION_USER_ROL : null;
    var MI_ID = typeof SESSION_USER_ID !== 'undefined' ? SESSION_USER_ID : null;
    var GESTIONAR = typeof PUEDE_GESTIONAR !== 'undefined' ? PUEDE_GESTIONAR : (ROL === 1);

    var formUsuario = document.getElementById('formularioUsuario');
    var formRol = document.getElementById('formularioRol');

    if (formRol) {
        var validacionRol = validarUnico({
            input: document.getElementById('nombreRol'),
            feedback: document.getElementById('feedbackNombreRol'),
            entidad: 'rol', campo: 'nombre',
            getId: function () { return document.getElementById('rolId').value || null; },
            boton: document.getElementById('btnGuardarRol')
        });
        var modalRolEl = document.getElementById('modalRol');
        modalRolEl.addEventListener('hidden.bs.modal', validacionRol.limpiar);
        modalRolEl.addEventListener('shown.bs.modal', function () {
            if (document.getElementById('nombreRol').value) validacionRol.revalidar();
        });
    }

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

        var inputNombreUsuario = document.getElementById('nombreUsuario');
        if (inputNombreUsuario) {
            aplicarRestriccionSoloLetras(inputNombreUsuario);
        }

        var inputCorreoUsuario = document.getElementById('correoUsuario');
        if (inputCorreoUsuario) {
            inputCorreoUsuario.addEventListener('input', verificarCorreoUsuarioEnTiempoReal);
            inputCorreoUsuario.addEventListener('blur', verificarCorreoUsuarioEnTiempoReal);
        }

        var modalEl = document.getElementById('modalUsuario');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function () {
                if (document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                document.getElementById('formularioUsuario').reset();
                limpiarValidacionesUsuario();
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

        // Delegación de eventos robusta para botones de acción de usuario
        $(document).on('click', '.btn-editar-usuario', function (e) {
            e.preventDefault();
            var tip = bootstrap.Tooltip.getInstance(this);
            if (tip) tip.hide();
            var id = parseInt($(this).attr('data-id') || $(this).data('id'));
            if (!isNaN(id)) abrirModalEditarUsuario(id);
        });

        $(document).on('click', '.btn-eliminar-usuario', function (e) {
            e.preventDefault();
            var tip = bootstrap.Tooltip.getInstance(this);
            if (tip) tip.hide();
            var id = parseInt($(this).attr('data-id') || $(this).data('id'));
            var nombre = $(this).attr('data-nombre') || 'este usuario';
            if (!isNaN(id)) eliminarUsuario(id, nombre);
        });

        cargarUsuarios();
    }

    async function cargarUsuarios() {
        try {
            var res = await fetch('/SP%20Perfect%20Color/usuario/listarAjax');
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
                    '<button class="btn btn-sm btn-warning btn-editar-usuario" data-id="' + u.id_usuario + '" title="Editar" aria-label="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';
                if (parseInt(u.id_usuario) !== parseInt(MI_ID)) {
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-usuario" data-id="' + u.id_usuario + '" data-nombre="' + u.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
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

    function limpiarValidacionesUsuario() {
        var inputCorreo = document.getElementById('correoUsuario');
        if (inputCorreo) {
            inputCorreo.classList.remove('is-invalid', 'is-valid');
            inputCorreo.removeAttribute('title');
        }
        var fbCorreo = document.getElementById('feedbackCorreoUsuario');
        if (fbCorreo) {
            fbCorreo.classList.add('d-none');
            fbCorreo.removeAttribute('title');
        }
        var inputNombre = document.getElementById('nombreUsuario');
        if (inputNombre) {
            inputNombre.classList.remove('is-invalid', 'is-valid');
        }
        var fbNombre = document.getElementById('feedbackNombreUsuario');
        if (fbNombre) {
            fbNombre.classList.add('d-none');
        }
        actualizarBotonGuardarUsuario();
    }

    function actualizarBotonGuardarUsuario() {
        var btn = formUsuario ? formUsuario.querySelector('button[type="submit"]') : null;
        if (!btn) return;
        var hayInvalido = formUsuario && formUsuario.querySelector('.is-invalid') !== null;
        btn.disabled = hayInvalido;
    }

    var temporizadorCorreoUsuario;
    function verificarCorreoUsuarioEnTiempoReal() {
        clearTimeout(temporizadorCorreoUsuario);
        var input = document.getElementById('correoUsuario');
        var fb = document.getElementById('feedbackCorreoUsuario');
        if (!input) return;
        var correo = input.value.trim();
        if (!correo) {
            input.classList.remove('is-invalid', 'is-valid');
            input.removeAttribute('title');
            if (fb) fb.classList.add('d-none');
            actualizarBotonGuardarUsuario();
            return;
        }

        var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!regex.test(correo)) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            input.setAttribute('title', 'Formato de correo inválido');
            if (fb) {
                fb.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>Formato de correo inválido (ej: usuario@correo.com)';
                fb.classList.remove('d-none');
            }
            actualizarBotonGuardarUsuario();
            return;
        }

        var id = document.getElementById('usuarioId') ? document.getElementById('usuarioId').value : '';

        temporizadorCorreoUsuario = setTimeout(async function() {
            try {
                var resp = await fetch('/SP%20Perfect%20Color/usuario/verificarCorreoAjax?correo=' + encodeURIComponent(correo) + '&id=' + encodeURIComponent(id));
                var res = await resp.json();
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        input.classList.add('is-invalid');
                        input.classList.remove('is-valid');
                        input.setAttribute('title', 'Correo ya registrado');
                        if (fb) {
                            fb.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>Correo ya registrado';
                            fb.setAttribute('title', 'Correo ya registrado');
                            fb.classList.remove('d-none');
                        }
                    } else {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        input.removeAttribute('title');
                        if (fb) fb.classList.add('d-none');
                    }
                    actualizarBotonGuardarUsuario();
                }
            } catch (e) {
                console.error('Error al verificar correo de usuario:', e);
            }
        }, 300);
    }

    function aplicarRestriccionSoloLetras(input) {
        if (!input) return;

        input.addEventListener('keydown', function(e) {
            if (['Backspace', 'Tab', 'Enter', 'Escape', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key) || e.ctrlKey || e.metaKey || e.altKey) {
                return;
            }
            if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(e.key)) {
                e.preventDefault();
            }
        });

        input.addEventListener('beforeinput', function(e) {
            if (e.data && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]*$/.test(e.data)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function() {
            var cursorPos = this.selectionStart;
            var limpio = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            if (this.value !== limpio) {
                this.value = limpio;
                if (cursorPos !== null) {
                    this.setSelectionRange(cursorPos - 1, cursorPos - 1);
                }
            }
        });

        input.addEventListener('paste', function(e) {
            var textoPegado = (e.clipboardData || window.clipboardData).getData('text');
            if (textoPegado && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(textoPegado)) {
                e.preventDefault();
                var limpio = textoPegado.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
                document.execCommand('insertText', false, limpio);
            }
        });
    }

    function abrirModalCrearUsuario() {
        document.getElementById('formularioUsuario').reset();
        document.getElementById('usuarioId').value = '';
        limpiarValidacionesUsuario();
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
        if (contEst) {
            contEst.style.display = 'none'; // No permitir crearlo inactivo
            var selectEstado = document.getElementById('estadoUsuario');
            if (selectEstado) selectEstado.value = '1';
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario')).show();
    }

    async function abrirModalEditarUsuario(id) {
        try {
            var res = await fetch('/SP%20Perfect%20Color/usuario/obtener?id=' + id);
            var json = await res.json();
            if (json.estado !== 'exito') {
                mostrarNotificacion(json.mensaje, 'error');
                return;
            }

            var u = json.datos;
            document.getElementById('formularioUsuario').reset();
            limpiarValidacionesUsuario();
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
        var url = esEdicion ? '/SP%20Perfect%20Color/usuario/actualizar' : '/SP%20Perfect%20Color/usuario/guardar';

        var nombre = document.getElementById('nombreUsuario').value.trim();
        var correo = document.getElementById('correoUsuario').value.trim();

        if (!nombre) { mostrarErrorUsuario('El nombre es obligatorio'); return; }
        if (nombre.length > 50) { mostrarErrorUsuario('El nombre no puede exceder los 50 caracteres'); return; }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(nombre)) {
            mostrarErrorUsuario('El nombre solo puede contener letras y espacios (sin números ni símbolos)');
            return;
        }

        if (!correo) { mostrarErrorUsuario('El correo electrónico es obligatorio'); return; }
        var regexCorreo = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!regexCorreo.test(correo)) {
            mostrarErrorUsuario('El correo electrónico no es válido');
            return;
        }
        var inputCorreo = document.getElementById('correoUsuario');
        if (inputCorreo && inputCorreo.classList.contains('is-invalid')) {
            mostrarErrorUsuario('El correo electrónico es inválido o ya se encuentra registrado');
            return;
        }

        if (!esEdicion) {
            var clave = document.getElementById('claveUsuario').value.trim();
            if (!clave) { mostrarErrorUsuario('La contraseña es obligatoria'); return; }
            if (clave.length < 6 || clave.length > 20) { mostrarErrorUsuario('La clave debe tener entre 6 y 20 caracteres'); return; }
            if (/['"`;\\<>]|--|\/\*|\*\//.test(clave)) {
                mostrarErrorUsuario('La contraseña contiene caracteres no permitidos (sin comillas ni signos <, >, ;)');
                return;
            }
        }

        var formData = new FormData(document.getElementById('formularioUsuario'));

        if (esEdicion) {
            var chk = document.getElementById('chkCambiarClave');
            var cambiarClave = chk && chk.checked;
            if (cambiarClave) {
                var nuevaClave = document.getElementById('nuevaClaveUsuario').value.trim();
                if (!nuevaClave || nuevaClave.length < 6 || nuevaClave.length > 20) {
                    mostrarErrorUsuario('La nueva clave debe tener entre 6 y 20 caracteres');
                    return;
                }
                if (/['"`;\\<>]|--|\/\*|\*\//.test(nuevaClave)) {
                    mostrarErrorUsuario('La nueva contraseña contiene caracteres no permitidos');
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
                mostrarNotificacion(json.mensaje, 'exito');
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
            fetch('/SP%20Perfect%20Color/usuario/eliminar', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarUsuarios();
                        mostrarNotificacion(json.mensaje, 'exito');
                    } else {
                        mostrarNotificacion(json.mensaje, 'error');
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

        // Delegación de eventos robusta para la tabla de roles
        $(document).on('click', '.btn-editar-rol', function (e) {
            e.preventDefault();
            var tip = bootstrap.Tooltip.getInstance(this);
            if (tip) tip.hide();
            var id = parseInt($(this).attr('data-id') || $(this).data('id'));
            if (!isNaN(id)) abrirModalEditarRol(id);
        });

        $(document).on('click', '.btn-toggle-rol', function (e) {
            e.preventDefault();
            var tip = bootstrap.Tooltip.getInstance(this);
            if (tip) tip.hide();
            var id = parseInt($(this).attr('data-id') || $(this).data('id'));
            var activoActual = parseInt($(this).attr('data-activo') || $(this).data('activo'));
            if (!isNaN(id)) toggleRol(id, activoActual);
        });

        $(document).on('click', '.btn-eliminar-rol', function (e) {
            e.preventDefault();
            var tip = bootstrap.Tooltip.getInstance(this);
            if (tip) tip.hide();
            var id = parseInt($(this).attr('data-id') || $(this).data('id'));
            var nombre = $(this).attr('data-nombre') || 'este rol';
            var totalUsuarios = parseInt($(this).attr('data-usuarios') || $(this).data('usuarios')) || 0;
            if (!isNaN(id)) eliminarRol(id, nombre, totalUsuarios);
        });

        cargarRoles();
    }

    async function cargarRoles() {
        try {
            var res = await fetch('/SP%20Perfect%20Color/usuario/listarRolesAjax');
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
                    '<button class="btn btn-sm btn-warning btn-editar-rol" data-id="' + r.id_rol + '" title="Editar Rol" aria-label="Editar Rol" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';

                if (esAdmin) {
                    acciones += '<span class="badge bg-light text-muted border d-flex align-items-center" title="Rol raíz protegido"><i class="bi bi-shield-lock-fill me-1"></i>Raíz</span>';
                } else {
                    var btnClass = r.activo == 1 ? 'btn-secondary' : 'btn-success';
                    var iconClass = r.activo == 1 ? 'bi-toggle-off' : 'bi-toggle-on';
                    var titleText = r.activo == 1 ? 'Deshabilitar Rol' : 'Habilitar Rol';
                    acciones += '<button class="btn btn-sm ' + btnClass + ' btn-toggle-rol" data-id="' + r.id_rol + '" data-activo="' + r.activo + '" title="' + titleText + '" aria-label="' + titleText + '" data-bs-toggle="tooltip"><i class="bi ' + iconClass + '"></i></button>';
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-rol" data-id="' + r.id_rol + '" data-nombre="' + r.nombre.replace(/"/g, '&quot;') + '" data-usuarios="' + (r.total_usuarios || 0) + '" title="Eliminar Rol" aria-label="Eliminar Rol" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
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
            var res = await fetch('/SP%20Perfect%20Color/usuario/obtenerRol?id=' + id);
            var json = await res.json();
            if (json.estado !== 'exito') {
                mostrarNotificacion(json.mensaje, 'error');
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
        var url = esEdicion ? '/SP%20Perfect%20Color/usuario/actualizarRol' : '/SP%20Perfect%20Color/usuario/guardarRol';

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

                mostrarNotificacion(json.mensaje, 'exito');
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

            fetch('/SP%20Perfect%20Color/usuario/toggleRol', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarRoles();
                        cargarUsuarios();
                        mostrarNotificacion(json.mensaje, 'exito');
                    } else {
                        mostrarNotificacion(json.mensaje, 'error');
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

            fetch('/SP%20Perfect%20Color/usuario/eliminarRol', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarRoles();
                        cargarUsuarios();
                        mostrarNotificacion(json.mensaje, 'exito');
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
