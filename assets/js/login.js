// Archivo: login.js
// Manejo del formulario de inicio de sesion

document.addEventListener('DOMContentLoaded', function() {
    const formulario = document.getElementById('formularioLogin');
    const mensajeError = document.getElementById('mensajeError');

    // Verificar que los elementos existen antes de continuar
    if (!formulario) {
        console.error('No se encontro el formulario de login');
        return;
    }
    if (!mensajeError) {
        console.error('No se encontro el contenedor de mensajes de error');
        return;
    }

    formulario.addEventListener('submit', async function(evento) {
        // Prevenir el envio tradicional del formulario
        evento.preventDefault();

        // Ocultar mensajes de error anteriores
        mensajeError.style.display = 'none';
        mensajeError.textContent = '';

        // Recoger datos del formulario
        const datosFormulario = new FormData(formulario);

        try {
            // Enviar peticion AJAX al servidor
            const respuesta = await fetch('login/iniciarSesion', {
                method: 'POST',
                body: datosFormulario
            });

            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                // Redirigir al dashboard
                window.location.href = resultado.datos.redirect;
            } else {
                // Mostrar mensaje de error
                mensajeError.textContent = resultado.mensaje;
                mensajeError.style.display = 'block';
            }
        } catch (error) {
            // Mostrar el error real en consola para depuracion
            console.error('Error en la peticion:', error);
            
            // Manejar error de conexion
            mensajeError.textContent = 'Error de conexion. Intente nuevamente.';
            mensajeError.style.display = 'block';
        }
    });
});