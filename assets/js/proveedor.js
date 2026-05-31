// Archivo: proveedor.js
// Manejo de la vista de gestion de proveedores

const DATATABLES_SPANISH = {
    "emptyTable": "No hay informacion",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
};

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
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
    const rubrosProveedor = document.getElementById('rubrosProveedor');
    const mensajeError = document.getElementById('mensajeErrorProveedor');

    var esAdmin = document.getElementById('btnNuevoProveedor') !== null;

    // Cargar lista de proveedores al iniciar
    cargarProveedores();

    // Evento para abrir modal de nuevo proveedor
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
            rubrosProveedor.value = '';
            mensajeError.classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(modalProveedor).show();
        });
    }

    // Eventos para cerrar modal
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalProveedor).hide();
        });
        btnCancelar.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalProveedor).hide();
        });
    }

    // Evento para enviar formulario
    if (formularioProveedor) {
        formularioProveedor.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            await guardarProveedor();
        });
    }

    // Limpiar formulario cuando el modal se cierra
    if (modalProveedor) {
        modalProveedor.addEventListener('hidden.bs.modal', function () {
            formularioProveedor.reset();
            mensajeError.classList.add('d-none');
        });
    }

    // Formatear RIF automaticamente
    if (rifProveedor) {
        rifProveedor.addEventListener('input', function() {
            let valor = this.value.toUpperCase();
            if (valor.length === 1 && /^[JGVEP]$/.test(valor)) {
                valor = valor + '-';
            }
            this.value = valor;
        });
    }

    // Permitir solo numeros en telefono
    if (telefonoProveedor) {
        telefonoProveedor.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Funcion para cargar la lista de proveedores
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

    // Muestra los proveedores en la tabla usando API DataTables
    function mostrarProveedores(proveedores) {
        if (!$.fn.DataTable.isDataTable('#tablaProveedores')) {
            $('#tablaProveedores').DataTable({
                dom: 'lrtip',
                language: DATATABLES_SPANISH
            });
        }

        var table = $('#tablaProveedores').DataTable();
        table.clear();

        proveedores.forEach(function(proveedor) {
            var acciones = '';
            if (esAdmin) {
                acciones = '<div class="d-flex gap-2">' +
                    '<button class="btn btn-sm btn-warning btn-editar-proveedor" data-id="' + proveedor.id + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                    '<button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="' + proveedor.id + '" data-nombre="' + proveedor.nombre_empresa.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                    '</div>';
            }

            var row = [
                proveedor.rif,
                proveedor.nombre_empresa,
                proveedor.contacto || '-',
                proveedor.telefono || '-',
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

    // Delegated events for action buttons
    document.getElementById('tablaProveedores').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-editar-proveedor');
        if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
        btn = e.target.closest('.btn-eliminar-proveedor');
        if (btn) { eliminarProveedor(parseInt(btn.dataset.id), btn.dataset.nombre); return; }
    });

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('proveedor/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const proveedor = resultado.datos;

                tituloModal.textContent = 'Editar Proveedor';
                proveedorId.value = proveedor.id;
                rifProveedor.value = proveedor.rif;
                rifProveedor.disabled = true;
                nombreEmpresaProveedor.value = proveedor.nombre_empresa;
                contactoProveedor.value = proveedor.contacto || '';
                telefonoProveedor.value = proveedor.telefono || '';
                correoProveedor.value = proveedor.correo || '';
                direccionProveedor.value = proveedor.direccion || '';
                rubrosProveedor.value = proveedor.rubros || '';
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

    // Guarda o actualiza un proveedor
    async function guardarProveedor() {
        const id = proveedorId.value;
        const esEdicion = id !== '';

        const rif = rifProveedor.value.trim().toUpperCase();
        if (!rif) {
            mostrarError('El RIF es obligatorio');
            return;
        }

        const formatoRIF = /^[JGVEP]-\d{8,9}$/;
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

    // Elimina un proveedor
    async function eliminarProveedor(id, nombre) {
        if (!confirm('Esta seguro de eliminar al proveedor ' + nombre + '?')) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        try {
            const respuesta = await fetch('proveedor/eliminar', {
                method: 'POST',
                body: formData
            });

            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                cargarProveedores();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar proveedor:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Enlazar busqueda manual a DataTables
    if (busquedaProveedores) {
        busquedaProveedores.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaProveedores')) {
                $('#tablaProveedores').DataTable().search(this.value).draw();
            }
        });
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
    }
});
