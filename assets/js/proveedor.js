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
    const rifProveedor = document.getElementById('rifProveedor');
    const nombreEmpresaProveedor = document.getElementById('nombreEmpresaProveedor');
    const contactoProveedor = document.getElementById('contactoProveedor');
    const telefonoProveedor = document.getElementById('telefonoProveedor');
    const correoProveedor = document.getElementById('correoProveedor');
    const direccionProveedor = document.getElementById('direccionProveedor');
    const rubrosContainer = document.getElementById('rubrosContainer');
    const btnAgregarRubro = document.getElementById('btnAgregarRubro');
    const mensajeError = document.getElementById('mensajeErrorProveedor');

    var esAdmin = document.getElementById('btnNuevoProveedor') !== null;

    // Cargar rubros disponibles
    fetch('inventario/listarRubrosAjax')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.estado === 'exito') {
                rubrosDisponibles = res.datos.rubros;
            }
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

    if (rifProveedor) {
        rifProveedor.addEventListener('input', function() {
            let valor = this.value.toUpperCase();
            // Auto-agregar guion despues de la letra inicial
            if (valor.length === 1 && /^[JVEG]$/.test(valor)) {
                valor = valor + '-';
            }
            // Extraer la parte de solo digitos (despues del guion)
            let partes = valor.split('-');
            if (partes.length === 2) {
                // Limitar digitos a maximo 9
                partes[1] = partes[1].replace(/[^0-9]/g, '').slice(0, 9);
                valor = partes[0] + '-' + partes[1];
            }
            this.value = valor;
        });
    }

    if (telefonoProveedor) {
        telefonoProveedor.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    async function cargarProveedores() {
        try {
            const respuesta = await fetch('proveedor/listarAjax');
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
            $('#tablaProveedores').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH
            });
        }

        var table = $('#tablaProveedores').DataTable();
        table.clear();

        proveedores.forEach(function(proveedor) {
            var acciones = '';
            if (esAdmin) {
                acciones = '<div class="d-flex gap-2">' +
                    '<button class="btn btn-sm btn-warning btn-editar-proveedor" data-id="' + proveedor.id_proveedor + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                    '<button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="' + proveedor.id_proveedor + '" data-nombre="' + proveedor.nombre_empresa.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                    '</div>';
            }

            var row = [
                proveedor.rif,
                proveedor.nombre_empresa,
                proveedor.contacto || '-',
                proveedor.telefonos || '-',
                proveedor.correo || '-',
                proveedor.rubros || '-'
            ];

            if (esAdmin) {
                row.push(acciones);
            }

            table.row.add(row);
        });

        table.draw();
    }

    document.getElementById('tablaProveedores').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-editar-proveedor');
        if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
        btn = e.target.closest('.btn-eliminar-proveedor');
        if (btn) { eliminarProveedor(parseInt(btn.dataset.id), btn.dataset.nombre); return; }
    });

    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('proveedor/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const proveedor = resultado.datos;

                tituloModal.textContent = 'Editar Proveedor';
                proveedorId.value = proveedor.id_proveedor;
                rifProveedor.value = proveedor.rif;
                rifProveedor.disabled = true;
                nombreEmpresaProveedor.value = proveedor.nombre_empresa;
                contactoProveedor.value = proveedor.contacto || '';
                telefonoProveedor.value = proveedor.telefonos || '';
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

        const rif = rifProveedor.value.trim().toUpperCase();
        if (!rif) {
            mostrarError('El RIF es obligatorio');
            return;
        }

        const formatoRIF = /^[JVEG]-\d{1,9}$/;
        if (!formatoRIF.test(rif)) {
            mostrarError('El RIF debe tener formato valido (Ej: J-123456789)');
            return;
        }

        if (!nombreEmpresaProveedor.value.trim()) {
            mostrarError('El nombre de la empresa es obligatorio');
            return;
        }

        if (telefonoProveedor.value.trim() && telefonoProveedor.value.trim().length !== 11) {
            mostrarError('El telefono debe tener 11 digitos');
            return;
        }

        const url = esEdicion ? 'proveedor/actualizar' : 'proveedor/guardar';
        const formData = new FormData(formularioProveedor);
        formData.set('rif', rif);

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
            fetch('proveedor/eliminar', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(resultado) {
                    if (resultado.estado === 'exito') {
                        cargarProveedores();
                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion(resultado.mensaje, 'exito');
                        } else {
                            alert(resultado.mensaje);
                        }
                    } else {
                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion(resultado.mensaje, 'error');
                        } else {
                            alert(resultado.mensaje);
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Error al eliminar proveedor:', error);
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion('Error de conexion al eliminar el proveedor', 'error');
                    } else {
                        alert('Error de conexion al eliminar el proveedor');
                    }
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
