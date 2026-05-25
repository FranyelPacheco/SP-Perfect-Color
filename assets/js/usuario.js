document.addEventListener('DOMContentLoaded', function () {
    var ROL = typeof SESSION_USER_ROL !== 'undefined' ? SESSION_USER_ROL : null;
    var MI_ID = typeof SESSION_USER_ID !== 'undefined' ? SESSION_USER_ID : null;

    var formUsuario = document.getElementById('formularioUsuario');
    if (!formUsuario) {
        console.warn('El formulario #formularioUsuario no se encontró en esta página.');
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
        if (btnCancel) btnCancel.addEventListener('click', cerrarModal);

        var btnCerrar = document.getElementById('btnCerrarModalUsuario');
        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);

        var modal = document.getElementById('modalUsuario');
        if (modal) modal.addEventListener('click', function (e) {
            if (e.target === this) cerrarModal();
        });

        cargarUsuarios();
    }

    // ---- VENDEDOR ----
    function initVendor() {
        var areaAdmin = document.getElementById('areaAdminUsuarios');
        if (areaAdmin) areaAdmin.style.setProperty('display', 'none', 'important');

        var btnNuevo = document.getElementById('btnNuevoUsuario');
        if (btnNuevo && !btnNuevo.closest('#modalUsuario')) btnNuevo.style.setProperty('display', 'none', 'important');

        var modal = document.getElementById('modalUsuario');
        modal.style.setProperty('position', 'relative', 'important');
        modal.style.setProperty('display', 'block', 'important');
        modal.style.setProperty('background', 'none', 'important');
        modal.style.setProperty('box-shadow', 'none', 'important');
        modal.style.setProperty('padding', '0', 'important');
        modal.style.setProperty('top', 'auto', 'important');
        modal.style.setProperty('left', 'auto', 'important');
        modal.style.setProperty('width', '100%', 'important');
        modal.style.setProperty('height', 'auto', 'important');
        modal.style.setProperty('z-index', 'auto', 'important');
        modal.style.setProperty('border', 'none', 'important');
        modal.style.setProperty('border-radius', '0', 'important');

        var titulo = document.getElementById('tituloModalUsuario');
        if (titulo) titulo.textContent = 'Editar Usuario';

        var btnCerrarX = document.querySelector('.modal-header .cerrar') || document.querySelector('.modal-header span') || document.getElementById('btnCerrarModalUsuario');
        if (btnCerrarX) btnCerrarX.style.display = 'none';

        var btnCancelar = document.querySelector('.modal-footer .btn-secundario') || document.getElementById('btnCancelarUsuario');
        if (btnCancelar) btnCancelar.style.display = 'none';

        var txtRol = document.getElementById('rolUsuario');
        if (txtRol) {
            txtRol.removeAttribute('required');
            if (txtRol.closest('.grupo-formulario')) txtRol.closest('.grupo-formulario').style.display = 'none';
            else if (txtRol.parentElement) txtRol.parentElement.style.display = 'none';
        }

        var txtEstado = document.getElementById('estadoUsuario');
        if (txtEstado) {
            txtEstado.removeAttribute('required');
            if (txtEstado.closest('.grupo-formulario')) txtEstado.closest('.grupo-formulario').style.display = 'none';
            else if (txtEstado.parentElement) txtEstado.parentElement.style.display = 'none';
        }

        var grupoCambiarClave = document.getElementById('grupoCambiarClave');
        if (grupoCambiarClave) grupoCambiarClave.style.display = 'none';

        var grupoClave = document.getElementById('grupoClave');
        if (grupoClave) grupoClave.style.display = 'block';

        var claveInput = document.getElementById('claveUsuario');
        if (claveInput) {
            claveInput.disabled = true;
            claveInput.required = false;
            claveInput.placeholder = 'Ingrese su nueva contraseña';
        }

        var correoInput = document.getElementById('correoUsuario');
        var correoGroup = correoInput ? correoInput.closest('.grupo-formulario') : null;
        var btnHabPass = document.createElement('button');
        btnHabPass.id = 'btnHabilitarPassword';
        btnHabPass.type = 'button';
        btnHabPass.className = 'btn-link-password';
        btnHabPass.textContent = 'Cambiar Contraseña';
        if (correoGroup && correoGroup.parentNode) {
            correoGroup.parentNode.insertBefore(btnHabPass, correoGroup.nextSibling);
        }

        btnHabPass.addEventListener('click', function () {
            if (claveInput.disabled) {
                claveInput.disabled = false;
                claveInput.focus();
                this.textContent = 'Cancelar cambio';
            } else {
                claveInput.disabled = true;
                claveInput.value = '';
                this.textContent = 'Cambiar Contraseña';
            }
        });

        var btnGuardar = document.getElementById('btnGuardarUsuario');
        if (btnGuardar) btnGuardar.style.width = '100%';

        cargarPerfilVendedor();
    }

    // ---- Cargar datos propios del vendedor ----
    async function cargarPerfilVendedor() {
        console.log('Intentando cargar perfil para ID:', SESSION_USER_ID);
        try {
            var res = await fetch('/SP%20Perfect%20Color/usuario/obtener?id=' + SESSION_USER_ID);
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            var data = await res.json();
            console.log('Datos recibidos:', data);

            if (data.estado === 'exito' && data.datos) {
                document.getElementById('usuarioId').value = data.datos.id;
                document.getElementById('nombreUsuario').value = data.datos.nombre || '';
                document.getElementById('correoUsuario').value = data.datos.correo || '';
                console.log('Inputs rellenados correctamente');
            } else {
                console.warn('El servidor respondio pero sin datos validos:', data);
            }
        } catch (error) {
            console.error('Fallo al cargar perfil:', error);
        }
    }

    // ---- Llenar select de roles ----
    function llenarSelectRoles(roles) {
        var select = document.getElementById('rolUsuario');
        if (!select) return;
        select.innerHTML = '<option value="">Seleccione un rol</option>';
        roles.forEach(function (r) {
            var op = document.createElement('option');
            op.value = r.id;
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

            if (json.datos.roles) llenarSelectRoles(json.datos.roles);

            var tbody = document.getElementById('cuerpoTablaUsuarios');
            var usuarios = json.datos.usuarios || [];
            tbody.innerHTML = '';

            if (usuarios.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay usuarios registrados</td></tr>';
                return;
            }

            usuarios.forEach(function (u) {
                var tr = document.createElement('tr');

                var td1 = document.createElement('td');
                td1.textContent = u.nombre;
                tr.appendChild(td1);

                var td2 = document.createElement('td');
                td2.textContent = u.correo;
                tr.appendChild(td2);

                var td3 = document.createElement('td');
                td3.textContent = u.rol_nombre;
                tr.appendChild(td3);

                var td4 = document.createElement('td');
                var badge = document.createElement('span');
                badge.className = u.activo == 1 ? 'estado-activo' : 'estado-inactivo';
                badge.textContent = u.activo == 1 ? 'Activo' : 'Inactivo';
                td4.appendChild(badge);
                tr.appendChild(td4);

                var td5 = document.createElement('td');
                td5.className = 'acciones';

                var btnEditar = document.createElement('button');
                btnEditar.className = 'btn-primario';
                btnEditar.textContent = 'Editar';
                btnEditar.addEventListener('click', function () { abrirModalEditar(u.id); });
                td5.appendChild(btnEditar);

                if (u.id != MI_ID) {
                    var btnEliminar = document.createElement('button');
                    btnEliminar.className = 'btn-peligro';
                    btnEliminar.textContent = 'Eliminar';
                    btnEliminar.addEventListener('click', function () { eliminarUsuario(u.id, u.nombre); });
                    td5.appendChild(btnEliminar);
                }

                tr.appendChild(td5);
                tbody.appendChild(tr);
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
        document.getElementById('checkCambiarClave').checked = false;
        document.getElementById('nuevaClaveUsuario').style.display = 'none';
        document.getElementById('nuevaClaveUsuario').value = '';

        document.getElementById('contenedorRol').style.display = 'block';
        document.getElementById('contenedorEstado').style.display = 'block';
        document.getElementById('rolUsuario').required = true;

        document.getElementById('modalUsuario').style.display = 'flex';
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
            document.getElementById('usuarioId').value = u.id;
            document.getElementById('nombreUsuario').value = u.nombre;
            document.getElementById('correoUsuario').value = u.correo;

            document.getElementById('grupoClave').style.display = 'none';
            document.getElementById('claveUsuario').required = false;

            document.getElementById('grupoCambiarClave').style.display = 'block';
            document.getElementById('checkCambiarClave').checked = false;
            document.getElementById('nuevaClaveUsuario').style.display = 'none';
            document.getElementById('nuevaClaveUsuario').value = '';

            document.getElementById('tituloModalUsuario').textContent = 'Editar Usuario';
            document.getElementById('contenedorRol').style.display = 'block';
            document.getElementById('contenedorEstado').style.display = 'block';
            document.getElementById('rolUsuario').value = u.rol_id;
            document.getElementById('estadoUsuario').value = u.activo;
            document.getElementById('rolUsuario').required = true;

            document.getElementById('modalUsuario').style.display = 'flex';

        } catch (error) {
            console.error('Error al obtener usuario:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error al cargar los datos del usuario', 'error');
            }
        }
    }

    // ---- Checkbox cambio de clave ----
    document.getElementById('checkCambiarClave').addEventListener('change', function () {
        var input = document.getElementById('nuevaClaveUsuario');
        if (this.checked) {
            input.style.display = 'block';
            input.required = true;
        } else {
            input.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    });

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

        if (esEdicion && ROL === 1) {
            var checkClave = document.getElementById('checkCambiarClave');
            if (checkClave.checked) {
                var nuevaClave = document.getElementById('nuevaClaveUsuario').value.trim();
                if (nuevaClave.length < 6) { mostrarError('La nueva clave debe tener al menos 6 caracteres'); return; }
            }
            if (!checkClave.checked) {
                formData.delete('nueva_clave');
            }
        }

        if (esEdicion && ROL === 2) {
            var claveInput = document.getElementById('claveUsuario');
            if (!claveInput.disabled) {
                var claveValor = claveInput.value.trim();
                if (claveValor !== '' && claveValor.length < 6) {
                    mostrarError('La nueva clave debe tener al menos 6 caracteres');
                    return;
                }
                if (claveValor !== '') {
                    formData.set('cambiar_clave', '1');
                    formData.set('nueva_clave', claveValor);
                }
            }
            formData.delete('clave');
        }

        try {
            var res = await fetch(url, { method: 'POST', body: formData });
            var json = await res.json();

            if (json.estado === 'exito') {
                if (ROL === 1) {
                    cerrarModal();
                    cargarUsuarios();
                } else {
                    var btnHabPass = document.getElementById('btnHabilitarPassword');
                    if (btnHabPass) btnHabPass.textContent = 'Cambiar Contraseña';
                    claveInput.disabled = true;
                    claveInput.value = '';
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

    // ---- Cerrar modal ----
    function cerrarModal() {
        if (ROL === 2) return;
        document.getElementById('modalUsuario').style.display = 'none';
        document.getElementById('formularioUsuario').reset();
        document.getElementById('mensajeErrorUsuario').style.display = 'none';
        document.getElementById('grupoClave').style.display = 'block';
        document.getElementById('claveUsuario').required = false;
        document.getElementById('grupoCambiarClave').style.display = 'none';
        document.getElementById('checkCambiarClave').checked = false;
        document.getElementById('nuevaClaveUsuario').style.display = 'none';
        document.getElementById('nuevaClaveUsuario').value = '';
        document.getElementById('contenedorRol').style.display = 'block';
        document.getElementById('contenedorEstado').style.display = 'block';
        document.getElementById('rolUsuario').required = true;
    }

    // ---- Mostrar error ----
    function mostrarError(msg) {
        var errorDiv = document.getElementById('mensajeErrorUsuario');
        errorDiv.textContent = msg;
        errorDiv.style.display = 'block';
    }

    // ---- Eliminar usuario ----
    async function eliminarUsuario(id, nombre) {
        if (!confirm('¿Esta seguro de eliminar al usuario ' + nombre + '?')) return;

        var fd = new FormData();
        fd.append('id', id);

        try {
            var res = await fetch('usuario/eliminar', { method: 'POST', body: fd });
            var json = await res.json();

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

        } catch (error) {
            console.error('Error al eliminar usuario:', error);
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error de conexion', 'error');
            }
        }
    }
});
