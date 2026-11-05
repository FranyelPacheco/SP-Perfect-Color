// ARCHIVO: perfil.js
// OBJETIVO: Gestión asíncrona de datos personales y cambio seguro de contraseña en Mi Perfil

$(function () {
    const $formDatos = $('#formEditarPerfil');
    const $formClave = $('#formCambiarClave');
    const REGEX_CORREO = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    // Botones para alternar visibilidad de contraseñas
    $('.btn-toggle-pass').on('click', function () {
        const $input = $('#' + $(this).attr('data-target'));
        if (!$input.length) return;
        const mostrar = $input.attr('type') === 'password';
        $input.attr('type', mostrar ? 'text' : 'password');
        $(this).find('i').toggleClass('bi-eye', mostrar).toggleClass('bi-eye-slash', !mostrar);
    });

    const $inputPerfilNombre = $('#perfilNombre');
    restringirSoloLetras($inputPerfilNombre);

    const $inputPerfilCorreo = $('#perfilCorreo');
    let temporizadorPerfilCorreo;

    function verificarPerfilCorreoEnTiempoReal() {
        clearTimeout(temporizadorPerfilCorreo);
        const $fb = $('#feedbackPerfilCorreo');
        const $btn = $('#btnGuardarDatos');
        const correo = $inputPerfilCorreo.val().trim();
        if (!correo) {
            $inputPerfilCorreo.removeClass('is-invalid is-valid').removeAttr('title');
            $fb.addClass('d-none');
            $btn.prop('disabled', false);
            return;
        }
        if (!REGEX_CORREO.test(correo)) {
            $inputPerfilCorreo.addClass('is-invalid').removeClass('is-valid').attr('title', 'Formato de correo inválido');
            $fb.html('<i class="bi bi-exclamation-circle-fill me-1"></i>Formato de correo inválido (ej: usuario@correo.com)').removeClass('d-none');
            $btn.prop('disabled', true);
            return;
        }
        const id = $('#perfilUsuarioId').val() || '';

        temporizadorPerfilCorreo = setTimeout(async function() {
            try {
                const res = await api.get('usuario/verificarCorreoAjax', { correo: correo, id: id });
                if (res.estado !== 'exito') return;
                if (res.datos.existe) {
                    $inputPerfilCorreo.addClass('is-invalid').removeClass('is-valid').attr('title', 'Correo ya registrado');
                    $fb.html('<i class="bi bi-exclamation-circle-fill me-1"></i>Correo ya registrado').attr('title', 'Correo ya registrado').removeClass('d-none');
                    $btn.prop('disabled', true);
                } else {
                    $inputPerfilCorreo.removeClass('is-invalid').addClass('is-valid').removeAttr('title');
                    $fb.addClass('d-none');
                    $btn.prop('disabled', false);
                }
            } catch (e) {
                console.error('Error al verificar correo:', e);
            }
        }, 300);
    }

    $inputPerfilCorreo.on('input blur', verificarPerfilCorreoEnTiempoReal);

    // Envío del Formulario de Datos Personales
    $formDatos.on('submit', async function (e) {
        e.preventDefault();
        const $alerta = $('#alertaDatosPerfil');
        const $btn = $('#btnGuardarDatos');

        ocultarAlerta($alerta);

        const nombre = $('#perfilNombre').val().trim();
        const correo = $('#perfilCorreo').val().trim();

        if (!nombre || !correo) {
            mostrarAlerta($alerta, 'danger', 'Todos los campos son obligatorios.');
            return;
        }

        if (nombre.length > 50) {
            mostrarAlerta($alerta, 'danger', 'El nombre no puede exceder los 50 caracteres.');
            return;
        }

        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(nombre)) {
            mostrarAlerta($alerta, 'danger', 'El nombre solo puede contener letras y espacios (sin números ni símbolos).');
            return;
        }

        if (!REGEX_CORREO.test(correo)) {
            mostrarAlerta($alerta, 'danger', 'El correo electrónico no tiene un formato válido.');
            return;
        }

        if ($inputPerfilCorreo.hasClass('is-invalid')) {
            mostrarAlerta($alerta, 'danger', 'El correo electrónico es inválido o ya se encuentra registrado por otro usuario.');
            return;
        }

        setLoading($btn, true);

        try {
            const json = await api.post('perfil/actualizarDatos', new FormData($formDatos[0]));

            if (json.estado === 'exito') {
                mostrarAlerta($alerta, 'success', json.mensaje);
                // Actualizar nombre en sidebar si existe
                $('.user-nombre').text(nombre);
                mostrarNotificacion(json.mensaje, 'exito');
            } else {
                mostrarAlerta($alerta, 'danger', json.mensaje || 'Error al actualizar los datos');
            }
        } catch (err) {
            mostrarAlerta($alerta, 'danger', 'No se pudo conectar con el servidor. Intente de nuevo.');
        } finally {
            setLoading($btn, false);
        }
    });

    // Envío del Formulario de Cambio de Contraseña
    $formClave.on('submit', async function (e) {
        e.preventDefault();
        const $alerta = $('#alertaClavePerfil');
        const $btn = $('#btnGuardarClave');

        ocultarAlerta($alerta);

        const claveActual = $('#claveActual').val();
        const nuevaClave = $('#nuevaClave').val();
        const confirmarClave = $('#confirmarClave').val();

        if (!claveActual || !nuevaClave || !confirmarClave) {
            mostrarAlerta($alerta, 'danger', 'Por favor complete todos los campos de contraseña.');
            return;
        }

        if (nuevaClave.length < 6 || nuevaClave.length > 20) {
            mostrarAlerta($alerta, 'danger', 'La nueva contraseña debe tener entre 6 y 20 caracteres.');
            return;
        }

        if (/['"`;\\<>]|--|\/\*|\*\//.test(nuevaClave)) {
            mostrarAlerta($alerta, 'danger', 'La nueva contraseña contiene caracteres no permitidos (sin comillas ni signos <, >, ;)');
            return;
        }

        if (nuevaClave === claveActual) {
            mostrarAlerta($alerta, 'danger', 'La nueva contraseña no puede ser igual a la contraseña actual.');
            return;
        }

        if (nuevaClave !== confirmarClave) {
            mostrarAlerta($alerta, 'danger', 'La confirmación de la nueva contraseña no coincide.');
            return;
        }

        setLoading($btn, true);

        try {
            const json = await api.post('perfil/cambiarClave', new FormData($formClave[0]));

            if (json.estado === 'exito') {
                mostrarAlerta($alerta, 'success', json.mensaje);
                $formClave[0].reset();
                mostrarNotificacion(json.mensaje, 'exito');
            } else {
                mostrarAlerta($alerta, 'danger', json.mensaje || 'Error al actualizar la contraseña');
            }
        } catch (err) {
            mostrarAlerta($alerta, 'danger', 'No se pudo conectar con el servidor. Intente de nuevo.');
        } finally {
            setLoading($btn, false);
        }
    });

    // Foto de perfil
    const $inputFoto = $('#inputFotoPerfil');
    const $btnCambiarFoto = $('#btnCambiarFoto');
    const $btnQuitarFoto = $('#btnQuitarFoto');
    const $alertaFoto = $('#alertaFotoPerfil');
    const rutaAvatares = '/SP%20Perfect%20Color/assets/uploads/avatares/';

    function mostrarFoto(nombreArchivo) {
        $('#fotoPerfilImg')
            .attr('src', nombreArchivo ? rutaAvatares + encodeURIComponent(nombreArchivo) : '')
            .toggleClass('d-none', !nombreArchivo);
        $('#fotoPerfilInicial').toggleClass('d-none', !!nombreArchivo);
        $btnQuitarFoto.toggleClass('d-none', !nombreArchivo);

        $('.user-name').each(function () {
            const $actual = $(this).find('.user-avatar, .bi-person-circle').first();
            const $nuevo = nombreArchivo
                ? $('<img class="user-avatar me-1" alt="">').attr('src', rutaAvatares + encodeURIComponent(nombreArchivo))
                : $('<i class="bi bi-person-circle me-1"></i>');
            if ($actual.length) $actual.replaceWith($nuevo);
            else $(this).prepend($nuevo);
        });
    }

    if ($inputFoto.length && $btnCambiarFoto.length) {
        $btnCambiarFoto.on('click', function () { $inputFoto.trigger('click'); });

        $inputFoto.on('change', async function () {
            const archivo = this.files[0];
            ocultarAlerta($alertaFoto);
            if (!archivo) return;

            if (['image/jpeg', 'image/png'].indexOf(archivo.type) === -1) {
                mostrarAlerta($alertaFoto, 'danger', 'Solo se permiten imágenes JPG o PNG.');
                $inputFoto.val('');
                return;
            }
            if (archivo.size > 2 * 1024 * 1024) {
                mostrarAlerta($alertaFoto, 'danger', 'La imagen no puede superar los 2 MB.');
                $inputFoto.val('');
                return;
            }

            setLoading($btnCambiarFoto, true);
            try {
                const formData = new FormData();
                formData.append('foto', archivo);
                const json = await api.post('perfil/subirFoto', formData);
                if (json.estado === 'exito') {
                    mostrarFoto(json.datos.foto);
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    mostrarAlerta($alertaFoto, 'danger', json.mensaje || 'Error al subir la foto');
                }
            } catch (err) {
                mostrarAlerta($alertaFoto, 'danger', 'No se pudo conectar con el servidor. Intente de nuevo.');
            } finally {
                $inputFoto.val('');
                setLoading($btnCambiarFoto, false);
            }
        });
    }

    $btnQuitarFoto.on('click', async function () {
        ocultarAlerta($alertaFoto);
        $btnQuitarFoto.prop('disabled', true);
        try {
            const json = await api.post('perfil/eliminarFoto');
            if (json.estado === 'exito') {
                mostrarFoto(null);
                mostrarNotificacion(json.mensaje, 'exito');
            } else {
                mostrarAlerta($alertaFoto, 'danger', json.mensaje || 'Error al eliminar la foto');
            }
        } catch (err) {
            mostrarAlerta($alertaFoto, 'danger', 'No se pudo conectar con el servidor. Intente de nuevo.');
        } finally {
            $btnQuitarFoto.prop('disabled', false);
        }
    });

    function mostrarAlerta($el, tipo, mensaje) {
        $el.attr('class', 'alert alert-' + tipo + ' mb-3')
            .html('<i class="bi ' + (tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + ' me-2"></i>' + escapeHtml(mensaje))
            .removeClass('d-none');
    }

    function ocultarAlerta($el) {
        $el.addClass('d-none');
    }

    function setLoading($btn, cargando) {
        $btn.prop('disabled', cargando).find('.spinner-border').toggleClass('d-none', !cargando);
    }
});
