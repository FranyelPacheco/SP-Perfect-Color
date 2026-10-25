// ARCHIVO: perfil.js
// OBJETIVO: Gestión asíncrona de datos personales y cambio seguro de contraseña en Mi Perfil

document.addEventListener('DOMContentLoaded', function () {
    var formDatos = document.getElementById('formEditarPerfil');
    var formClave = document.getElementById('formCambiarClave');

    // Botones para alternar visibilidad de contraseñas
    document.querySelectorAll('.btn-toggle-pass').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var icon = this.querySelector('i');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                }
            }
        });
    });

    var inputPerfilNombre = document.getElementById('perfilNombre');
    if (inputPerfilNombre) {
        restringirSoloLetras(inputPerfilNombre);
    }

    var inputPerfilCorreo = document.getElementById('perfilCorreo');
    var temporizadorPerfilCorreo;
    if (inputPerfilCorreo) {
        function verificarPerfilCorreoEnTiempoReal() {
            clearTimeout(temporizadorPerfilCorreo);
            var fb = document.getElementById('feedbackPerfilCorreo');
            var btn = document.getElementById('btnGuardarDatos');
            var correo = inputPerfilCorreo.value.trim();
            if (!correo) {
                inputPerfilCorreo.classList.remove('is-invalid', 'is-valid');
                inputPerfilCorreo.removeAttribute('title');
                if (fb) fb.classList.add('d-none');
                if (btn) btn.disabled = false;
                return;
            }
            var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!regex.test(correo)) {
                inputPerfilCorreo.classList.add('is-invalid');
                inputPerfilCorreo.classList.remove('is-valid');
                inputPerfilCorreo.setAttribute('title', 'Formato de correo inválido');
                if (fb) {
                    fb.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>Formato de correo inválido (ej: usuario@correo.com)';
                    fb.classList.remove('d-none');
                }
                if (btn) btn.disabled = true;
                return;
            }
            var idEl = document.getElementById('perfilUsuarioId');
            var id = idEl ? idEl.value : '';

            temporizadorPerfilCorreo = setTimeout(async function() {
                try {
                    var resp = await fetch('/SP%20Perfect%20Color/usuario/verificarCorreoAjax?correo=' + encodeURIComponent(correo) + '&id=' + encodeURIComponent(id));
                    var res = await resp.json();
                    if (res.estado === 'exito') {
                        if (res.datos.existe) {
                            inputPerfilCorreo.classList.add('is-invalid');
                            inputPerfilCorreo.classList.remove('is-valid');
                            inputPerfilCorreo.setAttribute('title', 'Correo ya registrado');
                            if (fb) {
                                fb.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>Correo ya registrado';
                                fb.setAttribute('title', 'Correo ya registrado');
                                fb.classList.remove('d-none');
                            }
                            if (btn) btn.disabled = true;
                        } else {
                            inputPerfilCorreo.classList.remove('is-invalid');
                            inputPerfilCorreo.classList.add('is-valid');
                            inputPerfilCorreo.removeAttribute('title');
                            if (fb) fb.classList.add('d-none');
                            if (btn) btn.disabled = false;
                        }
                    }
                } catch (e) {
                    console.error('Error al verificar correo:', e);
                }
            }, 300);
        }

        inputPerfilCorreo.addEventListener('input', verificarPerfilCorreoEnTiempoReal);
        inputPerfilCorreo.addEventListener('blur', verificarPerfilCorreoEnTiempoReal);
    }

    // Envío del Formulario de Datos Personales
    if (formDatos) {
        formDatos.addEventListener('submit', async function (e) {
            e.preventDefault();
            var alerta = document.getElementById('alertaDatosPerfil');
            var btn = document.getElementById('btnGuardarDatos');
            var spinner = btn ? btn.querySelector('.spinner-border') : null;
            var texto = btn ? btn.querySelector('.btn-texto') : null;

            ocultarAlerta(alerta);

            var nombre = document.getElementById('perfilNombre').value.trim();
            var correo = document.getElementById('perfilCorreo').value.trim();

            if (!nombre || !correo) {
                mostrarAlerta(alerta, 'danger', 'Todos los campos son obligatorios.');
                return;
            }

            if (nombre.length > 50) {
                mostrarAlerta(alerta, 'danger', 'El nombre no puede exceder los 50 caracteres.');
                return;
            }

            if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test(nombre)) {
                mostrarAlerta(alerta, 'danger', 'El nombre solo puede contener letras y espacios (sin números ni símbolos).');
                return;
            }

            var regexCorreo = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!regexCorreo.test(correo)) {
                mostrarAlerta(alerta, 'danger', 'El correo electrónico no tiene un formato válido.');
                return;
            }

            if (inputPerfilCorreo && inputPerfilCorreo.classList.contains('is-invalid')) {
                mostrarAlerta(alerta, 'danger', 'El correo electrónico es inválido o ya se encuentra registrado por otro usuario.');
                return;
            }

            setLoading(btn, spinner, texto, true);

            try {
                var formData = new FormData(formDatos);
                var res = await fetch('/SP%20Perfect%20Color/perfil/actualizarDatos', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var json = await res.json();

                if (json.estado === 'exito') {
                    mostrarAlerta(alerta, 'success', json.mensaje);
                    // Actualizar nombre en sidebar si existe
                    document.querySelectorAll('.user-name').forEach(function (el) {
                        el.innerHTML = '<i class="bi bi-person-circle me-1"></i>' + escapeHtml(nombre);
                    });
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    mostrarAlerta(alerta, 'danger', json.mensaje || 'Error al actualizar los datos');
                }
            } catch (err) {
                mostrarAlerta(alerta, 'danger', 'Error de conexión con el servidor.');
            } finally {
                setLoading(btn, spinner, texto, false);
            }
        });
    }

    // Envío del Formulario de Cambio de Contraseña
    if (formClave) {
        formClave.addEventListener('submit', async function (e) {
            e.preventDefault();
            var alerta = document.getElementById('alertaClavePerfil');
            var btn = document.getElementById('btnGuardarClave');
            var spinner = btn ? btn.querySelector('.spinner-border') : null;
            var texto = btn ? btn.querySelector('.btn-texto') : null;

            ocultarAlerta(alerta);

            var claveActual = document.getElementById('claveActual').value;
            var nuevaClave = document.getElementById('nuevaClave').value;
            var confirmarClave = document.getElementById('confirmarClave').value;

            if (!claveActual || !nuevaClave || !confirmarClave) {
                mostrarAlerta(alerta, 'danger', 'Por favor complete todos los campos de contraseña.');
                return;
            }

            if (nuevaClave.length < 6 || nuevaClave.length > 20) {
                mostrarAlerta(alerta, 'danger', 'La nueva contraseña debe tener entre 6 y 20 caracteres.');
                return;
            }

            if (/['"`;\\<>]|--|\/\*|\*\//.test(nuevaClave)) {
                mostrarAlerta(alerta, 'danger', 'La nueva contraseña contiene caracteres no permitidos (sin comillas ni signos <, >, ;)');
                return;
            }

            if (nuevaClave === claveActual) {
                mostrarAlerta(alerta, 'danger', 'La nueva contraseña no puede ser igual a la contraseña actual.');
                return;
            }

            if (nuevaClave !== confirmarClave) {
                mostrarAlerta(alerta, 'danger', 'La confirmación de la nueva contraseña no coincide.');
                return;
            }

            setLoading(btn, spinner, texto, true);

            try {
                var formData = new FormData(formClave);
                var res = await fetch('/SP%20Perfect%20Color/perfil/cambiarClave', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var json = await res.json();

                if (json.estado === 'exito') {
                    mostrarAlerta(alerta, 'success', json.mensaje);
                    formClave.reset();
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    mostrarAlerta(alerta, 'danger', json.mensaje || 'Error al actualizar la contraseña');
                }
            } catch (err) {
                mostrarAlerta(alerta, 'danger', 'Error de conexión con el servidor.');
            } finally {
                setLoading(btn, spinner, texto, false);
            }
        });
    }

    function mostrarAlerta(el, tipo, mensaje) {
        if (!el) return;
        el.className = 'alert alert-' + tipo + ' mb-3';
        el.innerHTML = '<i class="bi ' + (tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + ' me-2"></i>' + escapeHtml(mensaje);
        el.classList.remove('d-none');
    }

    function ocultarAlerta(el) {
        if (!el) return;
        el.classList.add('d-none');
    }

    function setLoading(btn, spinner, texto, isLoading) {
        if (!btn) return;
        btn.disabled = isLoading;
        if (spinner) {
            if (isLoading) spinner.classList.remove('d-none');
            else spinner.classList.add('d-none');
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
