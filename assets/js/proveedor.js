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
    const contactoProveedor = document.getElementById('contactoProveedor');
    const operadoraProveedor = document.getElementById('operadoraProveedor');
    const telefonoNumeroProveedor = document.getElementById('telefonoNumeroProveedor');
    const telefonoProveedor = document.getElementById('telefonoProveedor');
    const correoProveedor = document.getElementById('correoProveedor');
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

    if (btnNuevoProveedor) {
        btnNuevoProveedor.addEventListener('click', function() {
            tituloModal.textContent = 'Nuevo Proveedor';
            proveedorId.value = '';
            rifProveedor.value = '';
            rifProveedor.disabled = false;
            nombreEmpresaProveedor.value = '';
            contactoProveedor.value = '';
            telefonoProveedor.value = '';
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
        }
        if (feedbackRifProveedor) {
            feedbackRifProveedor.classList.add('d-none');
        }
        if (btnGuardarProveedor) {
            btnGuardarProveedor.disabled = false;
        }
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
                        if (feedbackRifProveedor) {
                            feedbackRifProveedor.textContent = 'El proveedor con RIF ' + rifCompleto + ' ya existe en el sistema';
                            feedbackRifProveedor.classList.remove('d-none');
                        }
                        if (btnGuardarProveedor) btnGuardarProveedor.disabled = true;
                    } else {
                        rifProveedor.classList.remove('is-invalid');
                        rifProveedor.classList.add('is-valid');
                        if (feedbackRifProveedor) {
                            feedbackRifProveedor.classList.add('d-none');
                        }
                        if (btnGuardarProveedor) btnGuardarProveedor.disabled = false;
                    }
                }
            } catch (e) {
                console.error('Error al verificar RIF:', e);
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

    if (telefonoNumeroProveedor) {
        telefonoNumeroProveedor.addEventListener('input', function() {
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
                    data: 'contacto',
                    render: function(d) { return d || '-'; }
                },
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
                            '<button class="btn btn-sm btn-warning btn-editar-proveedor" data-id="' + row.id_proveedor + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="' + row.id_proveedor + '" data-nombre="' + nom + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
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

                // Parsear RIF (J-12345678)
                const partesRif = (proveedor.rif || '').split('-');
                if (partesRif.length === 2 && tipoRifProveedor) {
                    tipoRifProveedor.value = partesRif[0];
                    rifProveedor.value = partesRif[1];
                } else {
                    rifProveedor.value = proveedor.rif;
                }

                nombreEmpresaProveedor.value = proveedor.nombre_empresa;
                contactoProveedor.value = proveedor.contacto || '';

                // Parsear telefono
                const telRaw = (proveedor.telefonos || '').replace(/[^0-9]/g, '');
                if (telRaw.length === 11 && operadoraProveedor && telefonoNumeroProveedor) {
                    operadoraProveedor.value = telRaw.substring(0, 4);
                    telefonoNumeroProveedor.value = telRaw.substring(4);
                } else if (telefonoNumeroProveedor) {
                    telefonoNumeroProveedor.value = telRaw;
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

        if (!nombreEmpresaProveedor.value.trim()) {
            mostrarError('El nombre de la empresa es obligatorio');
            nombreEmpresaProveedor.focus();
            return;
        }

        if (!correoProveedor.value.trim()) {
            mostrarError('El correo electronico es obligatorio');
            correoProveedor.focus();
            return;
        }

        const telNum = telefonoNumeroProveedor ? telefonoNumeroProveedor.value.trim() : '';
        const op = operadoraProveedor ? operadoraProveedor.value : '';
        if (telNum && telNum.length !== 7) {
            mostrarError('El numero de telefono debe tener exactamente 7 digitos');
            telefonoNumeroProveedor.focus();
            return;
        }
        if (telefonoProveedor) {
            telefonoProveedor.value = telNum ? (op + telNum) : '';
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
        if (telNum) {
            formData.set('telefono', op + telNum);
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
