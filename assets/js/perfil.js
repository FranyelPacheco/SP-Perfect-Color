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
                    document.querySelectorAll('.user-nombre').forEach(function (el) {
                        el.textContent = nombre;
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

    // Foto de perfil
    var inputFoto = document.getElementById('inputFotoPerfil');
    var btnCambiarFoto = document.getElementById('btnCambiarFoto');
    var btnQuitarFoto = document.getElementById('btnQuitarFoto');
    var alertaFoto = document.getElementById('alertaFotoPerfil');
    var rutaAvatares = '/SP%20Perfect%20Color/assets/uploads/avatares/';

    function mostrarFoto(nombreArchivo) {
        var img = document.getElementById('fotoPerfilImg');
        var inicial = document.getElementById('fotoPerfilInicial');
        if (img) {
            img.src = nombreArchivo ? rutaAvatares + encodeURIComponent(nombreArchivo) : '';
            img.classList.toggle('d-none', !nombreArchivo);
        }
        if (inicial) inicial.classList.toggle('d-none', !!nombreArchivo);
        if (btnQuitarFoto) btnQuitarFoto.classList.toggle('d-none', !nombreArchivo);

        document.querySelectorAll('.user-name').forEach(function (el) {
            var actual = el.querySelector('.user-avatar, .bi-person-circle');
            var nuevo;
            if (nombreArchivo) {
                nuevo = document.createElement('img');
                nuevo.className = 'user-avatar me-1';
                nuevo.alt = '';
                nuevo.src = rutaAvatares + encodeURIComponent(nombreArchivo);
            } else {
                nuevo = document.createElement('i');
                nuevo.className = 'bi bi-person-circle me-1';
            }
            if (actual) actual.replaceWith(nuevo);
            else el.prepend(nuevo);
        });
    }

    if (inputFoto && btnCambiarFoto) {
        btnCambiarFoto.addEventListener('click', function () { inputFoto.click(); });

        inputFoto.addEventListener('change', async function () {
            var archivo = inputFoto.files[0];
            ocultarAlerta(alertaFoto);
            if (!archivo) return;

            if (['image/jpeg', 'image/png'].indexOf(archivo.type) === -1) {
                mostrarAlerta(alertaFoto, 'danger', 'Solo se permiten imágenes JPG o PNG.');
                inputFoto.value = '';
                return;
            }
            if (archivo.size > 2 * 1024 * 1024) {
                mostrarAlerta(alertaFoto, 'danger', 'La imagen no puede superar los 2 MB.');
                inputFoto.value = '';
                return;
            }

            var spinner = btnCambiarFoto.querySelector('.spinner-border');
            setLoading(btnCambiarFoto, spinner, null, true);
            try {
                var formData = new FormData();
                formData.append('foto', archivo);
                var res = await fetch('/SP%20Perfect%20Color/perfil/subirFoto', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var json = await res.json();
                if (json.estado === 'exito') {
                    mostrarFoto(json.datos.foto);
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    mostrarAlerta(alertaFoto, 'danger', json.mensaje || 'Error al subir la foto');
                }
            } catch (err) {
                mostrarAlerta(alertaFoto, 'danger', 'Error de conexión con el servidor.');
            } finally {
                inputFoto.value = '';
                setLoading(btnCambiarFoto, spinner, null, false);
            }
        });
    }

    if (btnQuitarFoto) {
        btnQuitarFoto.addEventListener('click', async function () {
            ocultarAlerta(alertaFoto);
            btnQuitarFoto.disabled = true;
            try {
                var res = await fetch('/SP%20Perfect%20Color/perfil/eliminarFoto', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var json = await res.json();
                if (json.estado === 'exito') {
                    mostrarFoto(null);
                    mostrarNotificacion(json.mensaje, 'exito');
                } else {
                    mostrarAlerta(alertaFoto, 'danger', json.mensaje || 'Error al eliminar la foto');
                }
            } catch (err) {
                mostrarAlerta(alertaFoto, 'danger', 'Error de conexión con el servidor.');
            } finally {
                btnQuitarFoto.disabled = false;
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
