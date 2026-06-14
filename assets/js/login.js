document.addEventListener('DOMContentLoaded', function() {
    var formulario = document.getElementById('formularioLogin');
    var mensajeError = document.getElementById('mensajeError');
    var correoInput = document.getElementById('correo');
    var claveInput = document.getElementById('clave');
    var btnLogin = document.getElementById('btnLogin');
    var btnText = document.getElementById('btnText');
    var btnSpinner = document.getElementById('btnSpinner');
    var toggleClave = document.getElementById('toggleClave');
    var toggleIcono = document.getElementById('toggleIcono');

    if (!formulario) return;

    correoInput.focus();

    if (toggleClave) {
        toggleClave.addEventListener('click', function() {
            var type = claveInput.getAttribute('type') === 'password' ? 'text' : 'password';
            claveInput.setAttribute('type', type);
            toggleIcono.classList.toggle('bi-eye-slash-fill');
            toggleIcono.classList.toggle('bi-eye-fill');
        });
    }

    formulario.addEventListener('submit', async function(evento) {
        evento.preventDefault();

        mensajeError.classList.add('d-none');
        mensajeError.textContent = '';
        mensajeError.classList.remove('shake');

        btnLogin.disabled = true;
        btnText.textContent = 'Ingresando...';
        btnSpinner.classList.remove('d-none');

        var datosFormulario = new FormData(formulario);

        try {
            var respuesta = await fetch('/SP%20Perfect%20Color/login/iniciarSesion', {
                method: 'POST',
                body: datosFormulario
            });

            var resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                window.location.href = resultado.datos.redirect;
            } else {
                mensajeError.textContent = resultado.mensaje;
                mensajeError.classList.remove('d-none');
                mensajeError.classList.add('shake');
                btnLogin.disabled = false;
                btnText.textContent = 'Iniciar Sesión';
                btnSpinner.classList.add('d-none');
            }
        } catch (error) {
            mensajeError.textContent = 'Error de conexion. Intente nuevamente.';
            mensajeError.classList.remove('d-none');
            mensajeError.classList.add('shake');
            btnLogin.disabled = false;
            btnText.textContent = 'Iniciar Sesión';
            btnSpinner.classList.add('d-none');
        }
    });
});
