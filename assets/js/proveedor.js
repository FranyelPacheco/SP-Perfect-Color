// Archivo: proveedor.js
// Manejo de la vista de gestion de proveedores

var rubrosDisponibles = [];

document.addEventListener('DOMContentLoaded', function() {
    const busquedaProveedores = document.getElementById('busquedaProveedores');
    const btnNuevoProveedor = document.getElementById('btnNuevoProveedor');
    const modalProveedor = document.getElementById('modalProveedor');
    const btnCerrarModal = document.getElementById('btnCerrarModalProveedor');
    const btnCancelar = document.getElementById('btnCancelarProveedor');
    const formularioProveedor = document.getElementById('formularioProveedor');
    const tituloModal = document.getElementById('tituloModalProveedor');
    const proveedorId = document.getElementById('proveedorId');
    const tipoRifProveedor = document.getElementById('tipoRifProveedor');
    const rifProveedor = document.getElementById('rifProveedor');
    const rifCompletoProveedor = document.getElementById('rifCompletoProveedor');
    const feedbackRifProveedor = document.getElementById('feedbackRifProveedor');
    const nombreEmpresaProveedor = document.getElementById('nombreEmpresaProveedor');
    const operadoraProveedor = document.getElementById('operadoraProveedor');
    const telefonoNumeroProveedor = document.getElementById('telefonoNumeroProveedor');
    const telefonoProveedor = document.getElementById('telefonoProveedor');

    // Elementos segundo teléfono
    const btnToggleTel2Proveedor = document.getElementById('btnToggleTel2Proveedor');
    const btnQuitarTel2Proveedor = document.getElementById('btnQuitarTel2Proveedor');
    const seccionTel2Proveedor = document.getElementById('seccionTel2Proveedor');
    const operadoraProveedor2 = document.getElementById('operadoraProveedor2');
    const telefonoNumeroProveedor2 = document.getElementById('telefonoNumeroProveedor2');
    const telefonoProveedor2 = document.getElementById('telefonoProveedor2');

    const correoProveedor = document.getElementById('correoProveedor');
    const feedbackCorreoProveedor = document.getElementById('feedbackCorreoProveedor');
    const direccionProveedor = document.getElementById('direccionProveedor');
    const rubrosContainer = document.getElementById('rubrosContainer');
    const btnAgregarRubro = document.getElementById('btnAgregarRubro');
    const mensajeError = document.getElementById('mensajeErrorProveedor');
    const btnGuardarProveedor = formularioProveedor ? formularioProveedor.querySelector('button[type="submit"]') : null;

    var esAdmin = document.getElementById('btnNuevoProveedor') !== null;

    // Cargar rubros disponibles
    fetch('/SP%20Perfect%20Color/inventario/listarRubrosAjax')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.estado === 'exito') {
                rubrosDisponibles = res.datos.rubros;
            }
        })
        .catch(function(e) {
            console.error('Error al cargar rubros:', e);
        });

    cargarProveedores();

    function crearItemRubro(valor) {
        var div = document.createElement('div');
        div.className = 'input-group mb-2 rubro-item';
        var select = document.createElement('select');
        select.name = 'rubros[]';
        select.className = 'form-select';
        select.innerHTML = '<option value="">Seleccione un rubro...</option>';
        rubrosDisponibles.forEach(function(r) {
            var op = document.createElement('option');
            op.value = r.id_rubro;
            op.textContent = r.nombre;
            select.appendChild(op);
        });
        if (valor) select.value = valor;
        var btn = document.createElement('button');
        btn.className = 'btn btn-outline-danger btn-remove-rubro';
        btn.type = 'button';
        btn.innerHTML = '<i class="bi bi-x"></i>';
        div.appendChild(select);
        div.appendChild(btn);
        return div;
    }

    function resetRubrosContainer(valores) {
        if (!rubrosContainer) return;
        rubrosContainer.innerHTML = '';
        if (valores && valores.length) {
            valores.forEach(function(v) { rubrosContainer.appendChild(crearItemRubro(v)); });
        } else {
            rubrosContainer.appendChild(crearItemRubro(''));
        }
    }

    function resetearSegundoTelefonoProveedor() {
        if (seccionTel2Proveedor) seccionTel2Proveedor.classList.add('d-none');
        if (btnToggleTel2Proveedor) btnToggleTel2Proveedor.classList.remove('d-none');
        if (telefonoNumeroProveedor2) telefonoNumeroProveedor2.value = '';
        if (telefonoProveedor2) telefonoProveedor2.value = '';
    }

    if (btnToggleTel2Proveedor) {
        btnToggleTel2Proveedor.addEventListener('click', function() {
            if (seccionTel2Proveedor) seccionTel2Proveedor.classList.remove('d-none');
            this.classList.add('d-none');
        });
    }

    if (btnQuitarTel2Proveedor) {
        btnQuitarTel2Proveedor.addEventListener('click', function() {
            resetearSegundoTelefonoProveedor();
        });
    }

    if (btnNuevoProveedor) {
        btnNuevoProveedor.addEventListener('click', function() {
            tituloModal.textContent = 'Nuevo Proveedor';
            proveedorId.value = '';
            rifProveedor.value = '';
            rifProveedor.disabled = false;
            limpiarEstadoRif();
            limpiarEstadoCorreoProveedor();
            nombreEmpresaProveedor.value = '';
            telefonoProveedor.value = '';
            resetearSegundoTelefonoProveedor();
            correoProveedor.value = '';
            direccionProveedor.value = '';
            resetRubrosContainer();
            mensajeError.classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(modalProveedor).show();
        });
    }

    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalProveedor).hide();
        });
    }
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalProveedor).hide();
        });
    }

    if (modalProveedor) {
        modalProveedor.addEventListener('hidden.bs.modal', function() {
            formularioProveedor.reset();
            limpiarEstadoRif();
            limpiarEstadoCorreoProveedor();
            resetearSegundoTelefonoProveedor();
            mensajeError.classList.add('d-none');
        });
    }

    if (btnAgregarRubro) {
        btnAgregarRubro.addEventListener('click', function() {
            rubrosContainer.appendChild(crearItemRubro(''));
        });
    }

    if (rubrosContainer) {
        rubrosContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-remove-rubro');
            if (btn) {
                var items = rubrosContainer.querySelectorAll('.rubro-item');
                if (items.length > 1) {
                    btn.closest('.rubro-item').remove();
                }
            }
        });
    }

    if (formularioProveedor) {
        formularioProveedor.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            await guardarProveedor();
        });
    }

    if (modalProveedor) {
        modalProveedor.addEventListener('hidden.bs.modal', function () {
            formularioProveedor.reset();
            resetRubrosContainer();
            mensajeError.classList.add('d-none');
        });
    }

    function limpiarEstadoRif() {
        if (rifProveedor) {
            rifProveedor.classList.remove('is-invalid', 'is-valid');
            rifProveedor.removeAttribute('title');
        }
        if (feedbackRifProveedor) {
            feedbackRifProveedor.classList.add('d-none');
            feedbackRifProveedor.removeAttribute('title');
        }
        actualizarBotonGuardarProveedor();
    }

    function limpiarEstadoCorreoProveedor() {
        if (correoProveedor) {
            correoProveedor.classList.remove('is-invalid', 'is-valid');
            correoProveedor.removeAttribute('title');
        }
        if (feedbackCorreoProveedor) {
            feedbackCorreoProveedor.classList.add('d-none');
            feedbackCorreoProveedor.removeAttribute('title');
        }
        actualizarBotonGuardarProveedor();
    }

    function actualizarBotonGuardarProveedor() {
        if (!btnGuardarProveedor) return;
        var hayInvalido = (rifProveedor && rifProveedor.classList.contains('is-invalid')) ||
                          (correoProveedor && correoProveedor.classList.contains('is-invalid'));
        btnGuardarProveedor.disabled = hayInvalido;
    }

    var temporizadorRif;
    function verificarRifEnTiempoReal() {
        clearTimeout(temporizadorRif);
        const num = rifProveedor.value.trim();
        const tipo = tipoRifProveedor ? tipoRifProveedor.value : 'J';
        if (!num || num.length < 3) {
            limpiarEstadoRif();
            return;
        }

        const rifCompleto = tipo + '-' + num;
        const id = proveedorId.value || '';

        temporizadorRif = setTimeout(async function() {
            try {
                const resp = await fetch('/SP%20Perfect%20Color/proveedor/verificarRifAjax?rif=' + encodeURIComponent(rifCompleto) + '&id=' + encodeURIComponent(id));
                const res = await resp.json();
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        rifProveedor.classList.add('is-invalid');
                        rifProveedor.classList.remove('is-valid');
                        rifProveedor.setAttribute('title', 'Proveedor ya registrado');
                        if (feedbackRifProveedor) {
                            feedbackRifProveedor.textContent = 'Proveedor ya registrado';
                            feedbackRifProveedor.setAttribute('title', 'Proveedor ya registrado');
                            feedbackRifProveedor.classList.remove('d-none');
                        }
                    } else {
                        rifProveedor.classList.remove('is-invalid');
                        rifProveedor.classList.add('is-valid');
                        rifProveedor.removeAttribute('title');
                        if (feedbackRifProveedor) {
                            feedbackRifProveedor.classList.add('d-none');
                            feedbackRifProveedor.removeAttribute('title');
                        }
                    }
                    actualizarBotonGuardarProveedor();
                }
            } catch (e) {
                console.error('Error al verificar RIF:', e);
            }
        }, 300);
    }

    var temporizadorCorreoProveedor;
    function verificarCorreoEnTiempoReal() {
        clearTimeout(temporizadorCorreoProveedor);
        if (!correoProveedor) return;
        const correo = correoProveedor.value.trim();
        if (!correo) {
            limpiarEstadoCorreoProveedor();
            return;
        }

        const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!regexEmail.test(correo)) {
            correoProveedor.classList.add('is-invalid');
            correoProveedor.classList.remove('is-valid');
            correoProveedor.setAttribute('title', 'Formato de correo inválido');
            if (feedbackCorreoProveedor) {
                feedbackCorreoProveedor.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>Formato de correo inválido (ej: proveedor@correo.com)';
                feedbackCorreoProveedor.setAttribute('title', 'Formato de correo inválido');
                feedbackCorreoProveedor.classList.remove('d-none');
            }
            actualizarBotonGuardarProveedor();
            return;
        }

        const id = proveedorId.value || '';

        temporizadorCorreoProveedor = setTimeout(async function() {
            try {
                const resp = await fetch('/SP%20Perfect%20Color/proveedor/verificarCorreoAjax?correo=' + encodeURIComponent(correo) + '&id=' + encodeURIComponent(id));
                const res = await resp.json();
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        correoProveedor.classList.add('is-invalid');
                        correoProveedor.classList.remove('is-valid');
                        correoProveedor.setAttribute('title', 'Correo ya registrado');
                        if (feedbackCorreoProveedor) {
                            feedbackCorreoProveedor.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>Correo ya registrado';
                            feedbackCorreoProveedor.setAttribute('title', 'Correo ya registrado');
                            feedbackCorreoProveedor.classList.remove('d-none');
                        }
                    } else {
                        correoProveedor.classList.remove('is-invalid');
                        correoProveedor.classList.add('is-valid');
                        correoProveedor.removeAttribute('title');
                        if (feedbackCorreoProveedor) {
                            feedbackCorreoProveedor.classList.add('d-none');
                            feedbackCorreoProveedor.removeAttribute('title');
                        }
                    }
                    actualizarBotonGuardarProveedor();
                }
            } catch (e) {
                console.error('Error al verificar correo:', e);
            }
        }, 300);
    }

    if (rifProveedor) {
        rifProveedor.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            verificarRifEnTiempoReal();
        });
    }
    if (tipoRifProveedor) {
        tipoRifProveedor.addEventListener('change', verificarRifEnTiempoReal);
    }
    if (correoProveedor) {
        correoProveedor.addEventListener('input', verificarCorreoEnTiempoReal);
        correoProveedor.addEventListener('blur', verificarCorreoEnTiempoReal);
    }



    if (telefonoNumeroProveedor) {
        telefonoNumeroProveedor.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    if (telefonoNumeroProveedor2) {
        telefonoNumeroProveedor2.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    async function cargarProveedores() {
        try {
            const respuesta = await fetch('/SP%20Perfect%20Color/proveedor/listarAjax');
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarProveedores(resultado.datos.proveedores);
            }
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
        }
    }

    function mostrarProveedores(proveedores) {
        if (!$.fn.DataTable.isDataTable('#tablaProveedores')) {
            var columnsDef = [
                { data: 'rif' },
                { data: 'nombre_empresa' },
                {
                    data: 'telefonos',
                    render: function(d) { return d || '-'; }
                },
                {
                    data: 'correo',
                    render: function(d) { return d || '-'; }
                },
                {
                    data: 'rubros',
                    render: function(d) { return d || '-'; }
                }
            ];

            if (esAdmin) {
                columnsDef.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        if (!row) return '';
                        var nom = (row.nombre_empresa || '').replace(/"/g, '&quot;');
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-proveedor" data-id="' + row.id_proveedor + '" title="Editar" aria-label="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="' + row.id_proveedor + '" data-nombre="' + nom + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
                            '</div>';
                    }
                });
            }

            $('#tablaProveedores').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: columnsDef
            });
        }

        var table = $('#tablaProveedores').DataTable();
        table.clear();

        if (Array.isArray(proveedores)) {
            proveedores.forEach(function(proveedor) {
                table.row.add(proveedor);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    $(document).on('click', '.btn-editar-proveedor', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) {
            abrirModalEditar(id);
        }
    });

    $(document).on('click', '.btn-eliminar-proveedor', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        var nombre = $(this).attr('data-nombre') || 'este proveedor';
        if (!isNaN(id)) {
            eliminarProveedor(id, nombre);
        }
    });

    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('/SP%20Perfect%20Color/proveedor/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const proveedor = resultado.datos;

                tituloModal.textContent = 'Editar Proveedor';
                proveedorId.value = proveedor.id_proveedor;
                limpiarEstadoRif();
                limpiarEstadoCorreoProveedor();

                // Parsear RIF (J-12345678)
                const partesRif = (proveedor.rif || '').split('-');
                if (partesRif.length === 2 && tipoRifProveedor) {
                    tipoRifProveedor.value = partesRif[0];
                    rifProveedor.value = partesRif[1];
                } else {
                    rifProveedor.value = proveedor.rif;
                }

                nombreEmpresaProveedor.value = proveedor.nombre_empresa;

                // Parsear teléfonos (pueden venir separados por coma)
                resetearSegundoTelefonoProveedor();
                const arrayTels = (proveedor.telefonos || '').split(',').map(function(t) { return t.trim(); });
                if (arrayTels.length > 0 && arrayTels[0]) {
                    const telRaw1 = arrayTels[0].replace(/[^0-9]/g, '');
                    if (telRaw1.length === 11 && operadoraProveedor && telefonoNumeroProveedor) {
                        operadoraProveedor.value = telRaw1.substring(0, 4);
                        telefonoNumeroProveedor.value = telRaw1.substring(4);
                    } else if (telefonoNumeroProveedor) {
                        telefonoNumeroProveedor.value = telRaw1;
                    }
                }
                if (arrayTels.length > 1 && arrayTels[1]) {
                    const telRaw2 = arrayTels[1].replace(/[^0-9]/g, '');
                    if (telRaw2.length === 11 && operadoraProveedor2 && telefonoNumeroProveedor2) {
                        operadoraProveedor2.value = telRaw2.substring(0, 4);
                        telefonoNumeroProveedor2.value = telRaw2.substring(4);
                        if (seccionTel2Proveedor) seccionTel2Proveedor.classList.remove('d-none');
                        if (btnToggleTel2Proveedor) btnToggleTel2Proveedor.classList.add('d-none');
                    }
                }

                correoProveedor.value = proveedor.correo || '';
                direccionProveedor.value = proveedor.direccion || '';
                var rubrosArray = proveedor.rubros_id ? proveedor.rubros_id.split(',').filter(Boolean).map(Number) : [];
                resetRubrosContainer(rubrosArray.length ? rubrosArray : ['']);
                mensajeError.classList.add('d-none');

                bootstrap.Modal.getOrCreateInstance(modalProveedor).show();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener proveedor:', error);
            mostrarNotificacion('Error al cargar los datos del proveedor', 'error');
        }
    }

    async function guardarProveedor() {
        const id = proveedorId.value;
        const esEdicion = id !== '';

        const rifNum = rifProveedor.value.trim();
        const tipoRif = tipoRifProveedor ? tipoRifProveedor.value : 'J';
        if (!rifNum) {
            mostrarError('El RIF es obligatorio');
            rifProveedor.focus();
            return;
        }

        const rifFinal = tipoRif + '-' + rifNum;
        if (rifCompletoProveedor) {
            rifCompletoProveedor.value = rifFinal;
        }

        const nombreEmpresaVal = nombreEmpresaProveedor.value.trim();
        if (!nombreEmpresaVal) {
            mostrarError('El nombre de la empresa es obligatorio');
            nombreEmpresaProveedor.focus();
            return;
        }
        if (nombreEmpresaVal.length < 3 || nombreEmpresaVal.length > 60) {
            mostrarError('El nombre de la empresa debe tener entre 3 y 60 caracteres');
            nombreEmpresaProveedor.focus();
            return;
        }
        const regexEmpresa = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,\-&]+$/;
        if (!regexEmpresa.test(nombreEmpresaVal)) {
            mostrarError('El nombre de la empresa contiene caracteres no permitidos (solo letras, números y signos . , - &)');
            nombreEmpresaProveedor.focus();
            return;
        }



        const correoVal = correoProveedor.value.trim();
        if (!correoVal) {
            mostrarError('El correo electronico es obligatorio');
            correoProveedor.focus();
            return;
        }
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(correoVal)) {
            mostrarError('El correo electronico no tiene un formato valido');
            correoProveedor.focus();
            return;
        }
        if (correoProveedor.classList.contains('is-invalid')) {
            mostrarError('El correo electrónico es inválido o ya se encuentra registrado');
            correoProveedor.focus();
            return;
        }

        // Armar telefono 1 (obligatorio)
        const telNum = telefonoNumeroProveedor ? telefonoNumeroProveedor.value.trim() : '';
        const op = operadoraProveedor ? operadoraProveedor.value : '';
        if (!telNum) {
            mostrarError('El teléfono principal es obligatorio');
            telefonoNumeroProveedor.focus();
            return;
        }
        if (telNum.length !== 7) {
            mostrarError('El número de teléfono debe tener exactamente 7 dígitos');
            telefonoNumeroProveedor.focus();
            return;
        }
        const tel1Completo = op + telNum;
        if (telefonoProveedor) {
            telefonoProveedor.value = tel1Completo;
        }

        // Telefono 2
        const telNum2 = telefonoNumeroProveedor2 ? telefonoNumeroProveedor2.value.trim() : '';
        const op2 = operadoraProveedor2 ? operadoraProveedor2.value : '';
        let tel2Completo = '';
        if (telNum2) {
            if (telNum2.length !== 7) {
                mostrarError('El segundo número de teléfono debe tener exactamente 7 dígitos');
                telefonoNumeroProveedor2.focus();
                return;
            }
            tel2Completo = op2 + telNum2;
            if (tel1Completo === tel2Completo) {
                mostrarError('El segundo teléfono no puede ser igual al teléfono principal');
                telefonoNumeroProveedor2.focus();
                return;
            }
        }

        const selectsRubros = rubrosContainer.querySelectorAll('select[name="rubros[]"]');
        let rubroSeleccionado = false;
        selectsRubros.forEach(function(select) {
            if (select.value) rubroSeleccionado = true;
        });

        if (!rubroSeleccionado) {
            mostrarError('Debe seleccionar al menos un rubro');
            return;
        }

        const url = esEdicion ? '/SP%20Perfect%20Color/proveedor/actualizar' : '/SP%20Perfect%20Color/proveedor/guardar';
        const formData = new FormData(formularioProveedor);
        formData.set('rif', rifFinal);
        formData.set('telefono', tel1Completo);
        if (tel2Completo) {
            formData.set('telefono2', tel2Completo);
        } else {
            formData.delete('telefono2');
        }

        if (esEdicion) {
            formData.set('id', id);
        }

        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                bootstrap.Modal.getInstance(modalProveedor).hide();
                cargarProveedores();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar proveedor:', error);
            mostrarError('Error de conexion al guardar el proveedor');
        }
    }

    async function eliminarProveedor(id, nombre) {
        confirmarConModal('Eliminar', 'Esta seguro de eliminar al proveedor ' + nombre + '?', function() {
            const formData = new FormData();
            formData.append('id', id);

            fetch('/SP%20Perfect%20Color/proveedor/eliminar', {
                method: 'POST',
                body: formData
            })
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    cargarProveedores();
                    mostrarNotificacion(resultado.mensaje, 'exito');
                } else {
                    mostrarNotificacion(resultado.mensaje, 'error');
                }
            })
            .catch(function(error) {
                console.error('Error al eliminar proveedor:', error);
                mostrarNotificacion('Error de conexion al eliminar el proveedor', 'error');
            });
        });
    }

    if (busquedaProveedores) {
        busquedaProveedores.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaProveedores')) {
                $('#tablaProveedores').DataTable().search(this.value).draw();
            }
        });
    }

    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
    }
});
