// El login no carga utilidades.js: token CSRF para las peticiones $.ajax no-GET
$.ajaxPrefilter(function (opciones, originales, xhr) {
    const token = $('meta[name="csrf-token"]').attr('content');
    if (token && !opciones.crossDomain && (opciones.type || 'GET').toUpperCase() !== 'GET') xhr.setRequestHeader('X-CSRF-Token', token);
});

// Envía un formulario por POST y resuelve siempre con el JSON de respuesta
function enviarJson(url, formulario, cabeceras) {
    return new Promise(function (ok, fallo) {
        $.ajax({ url: url, type: 'POST', data: new FormData(formulario), processData: false, contentType: false, dataType: 'json', headers: cabeceras || {} })
            .done(ok)
            .fail(function (x) { x.responseJSON ? ok(x.responseJSON) : fallo(new Error('red')); });
    });
}

$(function() {
    const $formulario = $('#formularioLogin');
    const $mensajeError = $('#mensajeError');
    const $correoInput = $('#correo');
    const $claveInput = $('#clave');
    const $btnLogin = $('#btnLogin');
    const $btnText = $('#btnText');
    const $btnSpinner = $('#btnSpinner');

    if (!$formulario.length) return;

    $correoInput.trigger('focus');

    $('#toggleClave').on('click', function() {
        $claveInput.attr('type', $claveInput.attr('type') === 'password' ? 'text' : 'password');
        $('#toggleIcono').toggleClass('bi-eye-slash-fill bi-eye-fill');
    });

    function mostrarErrorLogin(mensaje) {
        $mensajeError.text(mensaje).removeClass('d-none').addClass('shake');
        $btnLogin.prop('disabled', false);
        $btnText.text('Iniciar Sesión');
        $btnSpinner.addClass('d-none');
    }

    $formulario.on('submit', async function(evento) {
        evento.preventDefault();

        $mensajeError.addClass('d-none').removeClass('shake').text('');
        $btnLogin.prop('disabled', true);
        $btnText.text('Ingresando...');
        $btnSpinner.removeClass('d-none');

        try {
            const resultado = await enviarJson('/SP%20Perfect%20Color/login/iniciarSesion', $formulario[0]);
            if (resultado.estado === 'exito') {
                window.location.href = resultado.datos.redirect;
            } else {
                mostrarErrorLogin(resultado.mensaje);
            }
        } catch (error) {
            mostrarErrorLogin('No se pudo conectar con el servidor. Intente de nuevo.');
        }
    });

    // Procesar formulario de recuperación de contraseña vía SMTP
    const $formRecuperar = $('#formRecuperarClave');
    if ($formRecuperar.length) {
        $formRecuperar.on('submit', async function (e) {
            e.preventDefault();
            const $alerta = $('#alertaRecuperar');
            const $btn = $('#btnEnviarRecuperar');
            const $spinner = $btn.find('.spinner-border');

            const avisar = function (tipo, mensaje) {
                $alerta.attr('class', 'alert alert-' + tipo).text(mensaje);
            };

            $alerta.addClass('d-none').text('');

            if (!$('#correoRecuperar').val().trim()) {
                avisar('danger', 'Por favor ingrese su correo electrónico.');
                return;
            }

            $btn.prop('disabled', true);
            $spinner.removeClass('d-none');

            try {
                const json = await enviarJson('/SP%20Perfect%20Color/login/recuperarClave', $formRecuperar[0], { 'X-Requested-With': 'XMLHttpRequest' });
                if (json.estado === 'exito') {
                    avisar('success', json.mensaje);
                    $formRecuperar[0].reset();
                } else {
                    avisar('danger', json.mensaje || 'Error al procesar la solicitud.');
                }
            } catch (err) {
                avisar('danger', 'No se pudo conectar con el servidor. Intente de nuevo.');
            } finally {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    }
});
