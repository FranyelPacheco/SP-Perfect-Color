// ARCHIVO: usuario.js
// OBJETIVO: Gestión dinámica de Usuarios, Roles, Permisos por Módulo y Perfil de Usuario

$(function () {
    const MI_ID = typeof SESSION_USER_ID !== 'undefined' ? SESSION_USER_ID : null;
    const ROL = typeof SESSION_USER_ROL !== 'undefined' ? SESSION_USER_ROL : null;
    const GESTIONAR = typeof PUEDE_GESTIONAR !== 'undefined' ? PUEDE_GESTIONAR : (ROL === 1);
    const ES_ADMIN = ROL === 1;
    const TAMANO_MAX_CONTRATO = 5 * 1024 * 1024;
    const usuariosPorId = {};

    const $formUsuario = $('#formularioUsuario');
    const $formRol = $('#formularioRol');
    const $modalUsuario = $('#modalUsuario');
    const $modalRol = $('#modalRol');
    const REGEX_CORREO = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const REGEX_CLAVE_PROHIBIDA = /['"`;\\<>]|--|\/\*|\*\//;

    if ($formRol.length) {
        const validacionRol = validarUnico({
            input: $('#nombreRol'),
            feedback: $('#feedbackNombreRol'),
            entidad: 'rol', campo: 'nombre',
            getId: function () { return $('#rolId').val() || null; },
            boton: $('#btnGuardarRol')
        });
        $modalRol.on('hidden.bs.modal', validacionRol.limpiar);
        $modalRol.on('shown.bs.modal', function () {
            if ($('#nombreRol').val()) validacionRol.revalidar();
        });
    }

    // Nombres legibles para insignias de módulos
    const NOMBRES_MODULOS = {
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

    // Clic en botones de acción de las tablas: sin acción por defecto y sin tooltip abierto
    function alClicAccion(selector, fn) {
        $(document).on('click', selector, function (e) {
            e.preventDefault();
            bootstrap.Tooltip.getInstance(this)?.hide();
            fn.call(this);
        });
    }

    if (GESTIONAR) {
        initAdminUsuarios();
        initAdminRoles();
    }

    // Checkbox cambiar contraseña
    $('#chkCambiarClave').on('change click', function () {
        $('#grupoNuevaClave').css('display', this.checked ? 'block' : 'none');
    });

    $formUsuario.on('submit', procesarFormularioUsuario);
    $formRol.on('submit', procesarFormularioRol);

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
        $('#btnNuevoUsuario').on('click', abrirModalCrearUsuario);

        restringirSoloLetras($('#nombreUsuario'));

        $('#correoUsuario').on('input blur', verificarCorreoUsuarioEnTiempoReal);

        $modalUsuario.on('hidden.bs.modal', function () {
            if (document.activeElement && $.contains($modalUsuario[0], document.activeElement)) {
                document.activeElement.blur();
            }
            $formUsuario[0].reset();
            limpiarValidacionesUsuario();
            $('#mensajeErrorUsuario').addClass('d-none');
            $('#grupoClave').css('display', 'block');
            $('#claveUsuario').prop('required', false);
            $('#grupoCambiarClave, #grupoNuevaClave').css('display', 'none');
            $('#nuevaClaveUsuario').val('');
            $('#chkCambiarClave').prop('checked', false);
        });

        if (ES_ADMIN) initContratos();

        // Delegación de eventos robusta para botones de acción de usuario
        alClicAccion('.btn-editar-usuario', function () {
            const id = parseInt($(this).attr('data-id'));
            if (!isNaN(id)) abrirModalEditarUsuario(id);
        });

        alClicAccion('.btn-eliminar-usuario', function () {
            const id = parseInt($(this).attr('data-id'));
            const nombre = $(this).attr('data-nombre') || 'este usuario';
            if (!isNaN(id)) eliminarUsuario(id, nombre);
        });

        cargarUsuarios();
    }

    async function cargarUsuarios() {
        try {
            const json = await api.get('usuario/listarAjax');
            if (json.estado !== 'exito') {
                console.error('Error al listar usuarios:', json.mensaje);
                return;
            }

            if (json.datos.roles) {
                llenarSelectRoles(json.datos.roles);
            }

            const usuarios = json.datos.usuarios || [];

            if (!$.fn.DataTable.isDataTable('#tablaUsuarios')) {
                $('#tablaUsuarios').DataTable({
                    language: window.DATATABLES_SPANISH,
                    pageLength: 10,
                    responsive: true
                });
            }

            const table = $('#tablaUsuarios').DataTable();
            table.clear();

            $.each(usuarios, function (i, u) {
                usuariosPorId[u.id_usuario] = u;
                let acciones = '<div class="d-inline-flex gap-1">' +
                    '<button class="btn btn-sm btn-warning btn-editar-usuario" data-id="' + u.id_usuario + '" title="Editar" aria-label="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';
                if (ES_ADMIN) {
                    acciones += '<button class="btn btn-sm btn-info text-white btn-contrato-usuario" data-id="' + u.id_usuario + '" title="Contrato (PDF)" aria-label="Contrato (PDF)" data-bs-toggle="tooltip"><i class="bi bi-file-earmark-pdf-fill"></i></button>';
                }
                if (parseInt(u.id_usuario) !== parseInt(MI_ID)) {
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-usuario" data-id="' + u.id_usuario + '" data-nombre="' + u.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
                }
                acciones += '</div>';

                const estadoBadge = u.activo == 1
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-secondary">Inactivo</span>';

                table.row.add([
                    escapeHtml(u.nombre),
                    escapeHtml(u.correo),
                    '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' + escapeHtml(u.rol_nombre || 'Sin Rol') + '</span>',
                    estadoBadge,
                    u.tiene_contrato == 1
                        ? '<span class="badge bg-success">Cargado</span>'
                        : '<span class="badge bg-secondary">Sin contrato</span>',
                    acciones
                ]);
            });

            table.draw();

        } catch (error) {
            console.error('Error al cargar usuarios:', error);
        }
    }

    function llenarSelectRoles(roles) {
        const $select = $('#rolUsuario');
        if (!$select.length) return;
        const valorActual = $select.val();
        $select.html('<option value="">Seleccione un rol activo</option>');
        $.each(roles, function (i, r) {
            $select.append($('<option></option>').val(r.id_rol).text(r.nombre));
        });
        if (valorActual) $select.val(valorActual);
    }

    function limpiarValidacionesUsuario() {
        $('#correoUsuario').removeClass('is-invalid is-valid').removeAttr('title');
        $('#feedbackCorreoUsuario').addClass('d-none').removeAttr('title');
        $('#nombreUsuario').removeClass('is-invalid is-valid');
        $('#feedbackNombreUsuario').addClass('d-none');
        actualizarBotonGuardarUsuario();
    }

    function actualizarBotonGuardarUsuario() {
        $formUsuario.find('button[type="submit"]').prop('disabled', $formUsuario.find('.is-invalid').length > 0);
    }

    let temporizadorCorreoUsuario;
    function verificarCorreoUsuarioEnTiempoReal() {
        clearTimeout(temporizadorCorreoUsuario);
        const $input = $('#correoUsuario');
        const $fb = $('#feedbackCorreoUsuario');
        const correo = $input.val().trim();
        if (!correo) {
            $input.removeClass('is-invalid is-valid').removeAttr('title');
            $fb.addClass('d-none');
            actualizarBotonGuardarUsuario();
            return;
        }

        if (!REGEX_CORREO.test(correo)) {
            $input.addClass('is-invalid').removeClass('is-valid').attr('title', 'Formato de correo inválido');
            $fb.html('<i class="bi bi-exclamation-circle-fill me-1"></i>Formato de correo inválido (ej: usuario@correo.com)').removeClass('d-none');
            actualizarBotonGuardarUsuario();
            return;
        }

        const id = $('#usuarioId').val() || '';

        temporizadorCorreoUsuario = setTimeout(async function () {
            try {
                const res = await api.get('usuario/verificarCorreoAjax', { correo: correo, id: id });
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        $input.addClass('is-invalid').removeClass('is-valid').attr('title', 'Correo ya registrado');
                        $fb.html('<i class="bi bi-exclamation-circle-fill me-1"></i>Correo ya registrado').attr('title', 'Correo ya registrado').removeClass('d-none');
                    } else {
                        $input.removeClass('is-invalid').addClass('is-valid').removeAttr('title');
                        $fb.addClass('d-none');
                    }
                    actualizarBotonGuardarUsuario();
                }
            } catch (e) {
                console.error('Error al verificar correo de usuario:', e);
            }
        }, 300);
    }

    function abrirModalCrearUsuario() {
        $formUsuario[0].reset();
        $('#usuarioId').val('');
        limpiarValidacionesUsuario();
        $('#mensajeErrorUsuario').addClass('d-none');
        $('#tituloModalUsuario').text('Nuevo Usuario');

        $('#grupoClave').css('display', 'block');
        $('#claveUsuario').prop('required', true);

        $('#grupoCambiarClave, #grupoNuevaClave').css('display', 'none');

        $('#contenedorRol').css('display', 'block');
        // No permitir crearlo inactivo
        $('#contenedorEstado').css('display', 'none');
        $('#estadoUsuario').val('1');

        bootstrap.Modal.getOrCreateInstance($modalUsuario[0]).show();
    }

    async function abrirModalEditarUsuario(id) {
        try {
            const json = await api.get('usuario/obtener', { id: id });
            if (json.estado !== 'exito') {
                mostrarNotificacion(json.mensaje, 'error');
                return;
            }

            const u = json.datos;
            $formUsuario[0].reset();
            limpiarValidacionesUsuario();
            $('#mensajeErrorUsuario').addClass('d-none');

            $('#usuarioId').val(u.id_usuario);
            $('#nombreUsuario').val(u.nombre);
            $('#correoUsuario').val(u.correo);

            $('#grupoClave').css('display', 'none');
            $('#claveUsuario').prop('required', false);

            $('#grupoCambiarClave').css('display', 'block');
            $('#grupoNuevaClave').css('display', 'none');
            $('#chkCambiarClave').prop('checked', false);

            $('#tituloModalUsuario').text('Editar Usuario');

            const $contRol = $('#contenedorRol');
            if ($contRol.length) {
                $contRol.css('display', 'block');
                $('#rolUsuario').val(u.id_rol);
            }

            const $contEstado = $('#contenedorEstado');
            if ($contEstado.length) {
                $contEstado.css('display', 'block');
                $('#estadoUsuario').val(u.activo);
            }

            bootstrap.Modal.getOrCreateInstance($modalUsuario[0]).show();

        } catch (error) {
            console.error('Error al obtener usuario:', error);
            mostrarNotificacion('No se pudieron cargar los datos del usuario. Actualice la página e intente de nuevo.', 'error');
        }
    }

    async function procesarFormularioUsuario(e) {
        e.preventDefault();

        const id = $('#usuarioId').val();
        const esEdicion = id !== '';

        const nombre = $('#nombreUsuario').val().trim();
        const correo = $('#correoUsuario').val().trim();

        if (!nombre) { mostrarErrorUsuario('El nombre es obligatorio'); return; }
        if (nombre.length > 50) { mostrarErrorUsuario('El nombre no puede exceder los 50 caracteres'); return; }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(nombre)) {
            mostrarErrorUsuario('El nombre solo puede contener letras y espacios (sin números ni símbolos)');
            return;
        }

        if (!correo) { mostrarErrorUsuario('El correo electrónico es obligatorio'); return; }
        if (!REGEX_CORREO.test(correo)) {
            mostrarErrorUsuario('El correo electrónico no es válido');
            return;
        }
        if ($('#correoUsuario').hasClass('is-invalid')) {
            mostrarErrorUsuario('El correo electrónico es inválido o ya se encuentra registrado');
            return;
        }

        if (!esEdicion) {
            const clave = $('#claveUsuario').val().trim();
            if (!clave) { mostrarErrorUsuario('La contraseña es obligatoria'); return; }
            if (clave.length < 6 || clave.length > 20) { mostrarErrorUsuario('La clave debe tener entre 6 y 20 caracteres'); return; }
            if (REGEX_CLAVE_PROHIBIDA.test(clave)) {
                mostrarErrorUsuario('La contraseña contiene caracteres no permitidos (sin comillas ni signos <, >, ;)');
                return;
            }
        }

        const formData = new FormData($formUsuario[0]);

        if (esEdicion) {
            if ($('#chkCambiarClave').is(':checked')) {
                const nuevaClave = $('#nuevaClaveUsuario').val().trim();
                if (!nuevaClave || nuevaClave.length < 6 || nuevaClave.length > 20) {
                    mostrarErrorUsuario('La nueva clave debe tener entre 6 y 20 caracteres');
                    return;
                }
                if (REGEX_CLAVE_PROHIBIDA.test(nuevaClave)) {
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
            const json = await api.post(esEdicion ? 'usuario/actualizar' : 'usuario/guardar', formData);

            if (json.estado === 'exito') {
                bootstrap.Modal.getInstance($modalUsuario[0]).hide();
                if (GESTIONAR) cargarUsuarios();
                mostrarNotificacion(json.mensaje, 'exito');
            } else {
                mostrarErrorUsuario(json.mensaje);
            }

        } catch (error) {
            console.error('Error al guardar usuario:', error);
            mostrarErrorUsuario('No se pudo guardar el usuario. Revise su conexión e intente de nuevo.');
        }
    }

    function mostrarErrorUsuario(msg) {
        $('#mensajeErrorUsuario').text(msg).removeClass('d-none');
    }

    function eliminarUsuario(id, nombre) {
        confirmarConModal('Eliminar Usuario', '¿Está seguro de eliminar al usuario ' + escapeHtml(nombre) + '?', function () {
            api.post('usuario/eliminar', { id: id })
                .then(function (json) {
                    if (json.estado === 'exito') {
                        cargarUsuarios();
                        mostrarNotificacion(json.mensaje, 'exito');
                    } else {
                        mostrarNotificacion(json.mensaje, 'error');
                    }
                })
                .catch(function () {
                    mostrarNotificacion('No se pudo eliminar usuario. Revise su conexión e intente de nuevo.', 'error');
                });
        });
    }

    // ============================================================
    // CONTRATO EN PDF DEL TRABAJADOR (SOLO ADMINISTRADOR)
    // ============================================================

    function initContratos() {
        const $modal = $('#modalContrato');
        const $form = $('#formularioContrato');
        const $archivo = $('#archivoContrato');
        const $error = $('#mensajeErrorContrato');
        const URL_CONTRATO = '/SP%20Perfect%20Color/usuario/verContrato?id=';

        function errorContrato(msg) {
            $error.text(msg).removeClass('d-none');
        }

        function pintarEstado(id) {
            const tiene = usuariosPorId[id] && usuariosPorId[id].tiene_contrato == 1;
            $('#textoEstadoContrato').html(tiene
                ? '<i class="bi bi-check-circle-fill text-success me-1"></i>Contrato cargado'
                : '<i class="bi bi-dash-circle text-secondary me-1"></i>Sin contrato');
            $('#enlaceVerContrato').attr('href', URL_CONTRATO + id).toggleClass('d-none', !tiene);
            $('#btnEliminarContrato').toggleClass('d-none', !tiene);
            $('#etiquetaArchivoContrato').text(tiene ? 'Reemplazar contrato' : 'Archivo del contrato');
        }

        alClicAccion('.btn-contrato-usuario', function () {
            const id = parseInt($(this).attr('data-id'));
            if (isNaN(id) || !usuariosPorId[id]) return;
            $('#contratoUsuarioId').val(id);
            $('#nombreContrato').text(usuariosPorId[id].nombre);
            $form[0].reset();
            $error.addClass('d-none');
            pintarEstado(id);
            bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        });

        $form.on('submit', async function (e) {
            e.preventDefault();
            $error.addClass('d-none');
            const id = $('#contratoUsuarioId').val();
            const archivo = $archivo[0].files[0];

            if (!archivo) return errorContrato('Seleccione un archivo PDF');
            if (archivo.type !== 'application/pdf' && !/\.pdf$/i.test(archivo.name)) return errorContrato('Solo se permiten archivos PDF');
            if (archivo.size > TAMANO_MAX_CONTRATO) return errorContrato('El PDF no puede superar los 5 MB');

            const $btn = $('#btnSubirContrato').prop('disabled', true);
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('contrato', archivo);
                const json = await api.post('usuario/subirContrato', formData);
                if (json.estado === 'exito') {
                    bootstrap.Modal.getInstance($modal[0]).hide();
                    mostrarNotificacion(json.mensaje, 'exito');
                    cargarUsuarios();
                } else {
                    errorContrato(json.mensaje);
                }
            } catch (err) {
                errorContrato('No se pudo subir el contrato. Revise su conexión e intente de nuevo.');
            } finally {
                $btn.prop('disabled', false);
            }
        });

        $('#btnEliminarContrato').on('click', function () {
            const id = $('#contratoUsuarioId').val();
            // Un modal de confirmación no puede abrirse encima de este: se cierra primero
            $modal.one('hidden.bs.modal', function () {
                confirmarConModal('Quitar contrato', '¿Está seguro de quitar el contrato de <strong>' + escapeHtml(usuariosPorId[id].nombre) + '</strong>? El archivo se borrará.', function () {
                    api.post('usuario/eliminarContrato', { id: id })
                        .then(function (json) {
                            mostrarNotificacion(json.mensaje, json.estado === 'exito' ? 'exito' : 'error');
                            if (json.estado === 'exito') cargarUsuarios();
                        })
                        .catch(function () {
                            mostrarNotificacion('No se pudo quitar el contrato. Revise su conexión e intente de nuevo.', 'error');
                        });
                });
            });
            bootstrap.Modal.getInstance($modal[0]).hide();
        });
    }

    // ============================================================
    // MÓDULO ROLES Y PERMISOS
    // ============================================================

    function initAdminRoles() {
        $('#btnNuevoRol').on('click', abrirModalCrearRol);

        $modalRol.on('hidden.bs.modal', function () {
            if (document.activeElement && $.contains($modalRol[0], document.activeElement)) {
                document.activeElement.blur();
            }
            $formRol[0].reset();
            $('#mensajeErrorRol').addClass('d-none');
            $('.chk-modulo').prop('checked', false);
            $('#estadoRol').prop('disabled', false);
            $('#nombreRol').prop('readOnly', false);
        });

        // Delegación de eventos robusta para la tabla de roles
        alClicAccion('.btn-editar-rol', function () {
            const id = parseInt($(this).attr('data-id'));
            if (!isNaN(id)) abrirModalEditarRol(id);
        });

        alClicAccion('.btn-toggle-rol', function () {
            const id = parseInt($(this).attr('data-id'));
            const activoActual = parseInt($(this).attr('data-activo'));
            if (!isNaN(id)) toggleRol(id, activoActual);
        });

        alClicAccion('.btn-eliminar-rol', function () {
            const id = parseInt($(this).attr('data-id'));
            const nombre = $(this).attr('data-nombre') || 'este rol';
            const totalUsuarios = parseInt($(this).attr('data-usuarios')) || 0;
            if (!isNaN(id)) eliminarRol(id, nombre, totalUsuarios);
        });

        cargarRoles();
    }

    async function cargarRoles() {
        try {
            const json = await api.get('usuario/listarRolesAjax');
            if (json.estado !== 'exito') {
                console.error('Error al listar roles:', json.mensaje);
                return;
            }

            const roles = json.datos || [];

            if (!$.fn.DataTable.isDataTable('#tablaRoles')) {
                $('#tablaRoles').DataTable({
                    language: window.DATATABLES_SPANISH,
                    pageLength: 10,
                    responsive: true
                });
            }

            const table = $('#tablaRoles').DataTable();
            table.clear();

            $.each(roles, function (i, r) {
                const esAdmin = parseInt(r.id_rol) === 1;

                // Acciones
                let acciones = '<div class="d-inline-flex gap-1">' +
                    '<button class="btn btn-sm btn-warning btn-editar-rol" data-id="' + r.id_rol + '" title="Editar Rol" aria-label="Editar Rol" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>';

                if (esAdmin) {
                    acciones += '<span class="badge bg-light text-muted border d-flex align-items-center" title="Rol raíz protegido"><i class="bi bi-shield-lock-fill me-1"></i>Raíz</span>';
                } else {
                    const btnClass = r.activo == 1 ? 'btn-secondary' : 'btn-success';
                    const iconClass = r.activo == 1 ? 'bi-toggle-off' : 'bi-toggle-on';
                    const titleText = r.activo == 1 ? 'Deshabilitar Rol' : 'Habilitar Rol';
                    acciones += '<button class="btn btn-sm ' + btnClass + ' btn-toggle-rol" data-id="' + r.id_rol + '" data-activo="' + r.activo + '" title="' + titleText + '" aria-label="' + titleText + '" data-bs-toggle="tooltip"><i class="bi ' + iconClass + '"></i></button>';
                    acciones += '<button class="btn btn-sm btn-danger btn-eliminar-rol" data-id="' + r.id_rol + '" data-nombre="' + escapeHtml(r.nombre) + '" data-usuarios="' + (r.total_usuarios || 0) + '" title="Eliminar Rol" aria-label="Eliminar Rol" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>';
                }
                acciones += '</div>';

                // Badges de módulos
                const modulosLista = (r.modulos || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
                let modulosHtml = '';
                if (esAdmin) {
                    modulosHtml = '<span class="badge bg-dark"><i class="bi bi-star-fill text-warning me-1"></i>Acceso Total (' + modulosLista.length + ' módulos)</span>';
                } else if (modulosLista.length === 0) {
                    modulosHtml = '<span class="badge bg-light text-muted border">Sin módulos</span>';
                } else {
                    modulosHtml = '<div class="d-flex flex-wrap gap-1">';
                    $.each(modulosLista, function (j, m) {
                        const etiqueta = NOMBRES_MODULOS[m] || m;
                        const colorClass = (m === 'usuario') ? 'bg-danger text-white' : 'bg-info-subtle text-info-emphasis border border-info-subtle';
                        modulosHtml += '<span class="badge ' + colorClass + '" style="font-size:0.75rem;">' + etiqueta + '</span>';
                    });
                    modulosHtml += '</div>';
                }

                const estadoBadge = r.activo == 1
                    ? '<span class="badge bg-success">Habilitado</span>'
                    : '<span class="badge bg-secondary">Deshabilitado</span>';

                const totalUsers = '<span class="badge bg-light text-dark border">' + (r.total_usuarios || 0) + '</span>';

                table.row.add([
                    '#' + r.id_rol,
                    '<strong>' + escapeHtml(r.nombre) + '</strong>',
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
        $formRol[0].reset();
        $('#rolId').val('');
        $('#mensajeErrorRol').addClass('d-none');
        $('#tituloModalRol').text('Nuevo Rol');

        $('#estadoRol').val('1').prop('disabled', false);
        $('#nombreRol').prop('readOnly', false);
        $('.chk-modulo').prop('checked', false);

        bootstrap.Modal.getOrCreateInstance($modalRol[0]).show();
    }

    async function abrirModalEditarRol(id) {
        try {
            const json = await api.get('usuario/obtenerRol', { id: id });
            if (json.estado !== 'exito') {
                mostrarNotificacion(json.mensaje, 'error');
                return;
            }

            const rol = json.datos;
            $formRol[0].reset();
            $('#mensajeErrorRol').addClass('d-none');

            $('#rolId').val(rol.id_rol);
            $('#nombreRol').val(rol.nombre);
            $('#tituloModalRol').text('Editar Rol: ' + rol.nombre);

            const esAdmin = parseInt(rol.id_rol) === 1;
            // Administrador siempre activo
            $('#estadoRol').val(rol.activo).prop('disabled', esAdmin);

            // Marcar los checkboxes de módulos autorizados
            const mods = (rol.modulos || '').split(',').map(function (s) { return s.trim(); });
            $('.chk-modulo').each(function () {
                this.checked = mods.includes(this.value);
            });

            // Si es Administrador raíz, forzar checkbox usuario
            if (esAdmin) $('#mod_usuario').prop('checked', true);

            bootstrap.Modal.getOrCreateInstance($modalRol[0]).show();

        } catch (error) {
            console.error('Error al obtener rol:', error);
            mostrarNotificacion('No se pudieron cargar los datos del rol. Actualice la página e intente de nuevo.', 'error');
        }
    }

    async function procesarFormularioRol(e) {
        e.preventDefault();

        const id = $('#rolId').val();
        const esEdicion = id !== '';

        if (!$('#nombreRol').val().trim()) {
            mostrarErrorRol('El nombre del rol es obligatorio');
            return;
        }

        if ($('.chk-modulo:checked').length === 0) {
            mostrarErrorRol('Debe seleccionar al menos un módulo para el rol');
            return;
        }

        try {
            const json = await api.post(esEdicion ? 'usuario/actualizarRol' : 'usuario/guardarRol', new FormData($formRol[0]));

            if (json.estado === 'exito') {
                bootstrap.Modal.getInstance($modalRol[0]).hide();
                cargarRoles();
                // Actualizar lista de roles en el select de usuarios
                cargarUsuarios();

                mostrarNotificacion(json.mensaje, 'exito');
            } else {
                mostrarErrorRol(json.mensaje);
            }

        } catch (error) {
            console.error('Error al guardar rol:', error);
            mostrarErrorRol('No se pudo guardar el rol. Revise su conexión e intente de nuevo.');
        }
    }

    function mostrarErrorRol(msg) {
        $('#mensajeErrorRol').text(msg).removeClass('d-none');
    }

    function toggleRol(id, activoActual) {
        const nuevoEstado = activoActual == 1 ? 0 : 1;
        const mensaje = nuevoEstado == 0
            ? '¿Está seguro de deshabilitar este rol?<div class="alert alert-warning py-2 px-3 small mt-2 mb-0 text-start"><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Nota:</strong> Los usuarios asociados a este rol no podrán iniciar sesión mientras el rol permanezca inactivo.</div>'
            : '¿Está seguro de habilitar este rol?';
        const claseBtn = nuevoEstado == 1 ? 'btn-success' : 'btn-danger';

        confirmarConModal('Cambiar Estado de Rol', mensaje, function () {
            api.post('usuario/toggleRol', { id: id, activo: nuevoEstado })
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
                    mostrarNotificacion('No se pudo cambiar el estado del rol. Revise su conexión e intente de nuevo.', 'error');
                });
        }, claseBtn);
    }

    function eliminarRol(id, nombre, totalUsuarios) {
        if (id === 1) {
            confirmarConModal('Acción no permitida', 'El rol Administrador es el rol principal del sistema y no puede ser eliminado.', null, 'btn-secondary');
            return;
        }

        if (totalUsuarios > 0) {
            const advertencia = 'No se puede eliminar el rol <strong>' + escapeHtml(nombre) + '</strong> porque tiene <strong>' + totalUsuarios + '</strong> usuario(s) asignado(s).' +
                '<div class="alert alert-danger py-2 px-3 small mt-2 mb-0 text-start">' +
                '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Atención:</strong> Debe reasignar o eliminar los usuarios asociados antes de poder borrar este rol.' +
                '</div>';
            confirmarConModal('Rol en Uso', advertencia, null, 'btn-secondary');
            return;
        }

        const mensajeConfirmacion = '¿Está seguro de eliminar permanentemente el rol <strong>' + escapeHtml(nombre) + '</strong>?' +
            '<div class="alert alert-danger py-2 px-3 small mt-2 mb-0 text-start">' +
            '<i class="bi bi-trash-fill me-1"></i> <strong>Advertencia:</strong> Esta acción no se puede deshacer.' +
            '</div>';

        confirmarConModal('Eliminar Rol', mensajeConfirmacion, function () {
            api.post('usuario/eliminarRol', { id: id })
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
                    mostrarNotificacion('No se pudo eliminar el rol. Revise su conexión e intente de nuevo.', 'error');
                });
        }, 'btn-danger');
    }
});
