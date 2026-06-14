// Archivo: inventario.js
// Manejo de la vista de gestion de inventario

const DATATABLES_SPANISH = {
    "emptyTable": "No hay informacion",
    "zeroRecords": "No se encontraron registros",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
    "search": "Buscar:",
    "paginate": { "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior" }
};

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const busquedaInsumos = document.getElementById('busquedaInsumos');
    const btnNuevoInsumo = document.getElementById('btnNuevoInsumo');
    const modalInsumo = document.getElementById('modalInsumo');
    const btnCerrarModal = document.getElementById('btnCerrarModalInsumo');
    const btnCancelar = document.getElementById('btnCancelarInsumo');
    const formularioInsumo = document.getElementById('formularioInsumo');
    const tituloModal = document.getElementById('tituloModalInsumo');
    const insumoId = document.getElementById('insumoId');
    const codigoInsumo = document.getElementById('codigoInsumo');
    const nombreInsumo = document.getElementById('nombreInsumo');
    const marcaInsumo = document.getElementById('marcaInsumo');
    const categoriaInsumo = document.getElementById('categoriaInsumo');
    const unidadMedidaInsumo = document.getElementById('unidadMedidaInsumo');
    const stockActualInsumo = document.getElementById('stockActualInsumo');
    const stockMinimoInsumo = document.getElementById('stockMinimoInsumo');
    const precioVentaInsumo = document.getElementById('precioVentaInsumo');
    const precioCompraInsumo = document.getElementById('precioCompraInsumo');
    const fechaVencimientoInsumo = document.getElementById('fechaVencimientoInsumo');
    const proveedorInsumo = document.getElementById('proveedorInsumo');
    const mensajeError = document.getElementById('mensajeErrorInsumo');
    const alertasStockBajo = document.getElementById('alertasStockBajo');
    const contenidoAlertas = document.getElementById('contenidoAlertas');

    let proveedoresGlobal = [];
    const esAdmin = document.getElementById('btnNuevoInsumo') !== null;

    // Cargar lista de insumos al iniciar
    cargarInsumos();

    // Evento para abrir modal de nuevo insumo
    if (btnNuevoInsumo) {
        btnNuevoInsumo.addEventListener('click', function() {
            tituloModal.textContent = 'Nuevo Insumo';
            insumoId.value = '';
            codigoInsumo.value = '';
            codigoInsumo.disabled = false;
            nombreInsumo.value = '';
            marcaInsumo.value = '';
            categoriaInsumo.value = '';
            unidadMedidaInsumo.value = '';
            stockActualInsumo.value = '0';
            stockMinimoInsumo.value = '5';
            precioVentaInsumo.value = '0';
            precioCompraInsumo.value = '0';
            fechaVencimientoInsumo.value = '';
            proveedorInsumo.value = '';
            mensajeError.classList.add('d-none');
            bootstrap.Modal.getOrCreateInstance(modalInsumo).show();
        });
    }

    // Eventos para cerrar modal
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalInsumo).hide();
        });
        btnCancelar.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modalInsumo).hide();
        });
    }

    // Evento para enviar formulario
    if (formularioInsumo) {
        formularioInsumo.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            await guardarInsumo();
        });
    }

    // Limpiar formulario cuando el modal se cierra
    if (modalInsumo) {
        modalInsumo.addEventListener('hidden.bs.modal', function () {
            formularioInsumo.reset();
            mensajeError.classList.add('d-none');
        });
    }

    // Funcion para cargar la lista de insumos
    async function cargarInsumos() {
        try {
            const respuesta = await fetch('inventario/listarAjax');
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarInsumos(resultado.datos.insumos);

                if (resultado.datos.proveedores) {
                    proveedoresGlobal = resultado.datos.proveedores;
                    llenarSelectProveedores();
                }

                if (resultado.datos.alertas && resultado.datos.alertas.length > 0) {
                    mostrarAlertas(resultado.datos.alertas);
                }
            }
        } catch (error) {
            console.error('Error al cargar insumos:', error);
        }
    }

    // Muestra los insumos en la tabla usando API DataTables
    function mostrarInsumos(insumos) {
        if (!$.fn.DataTable.isDataTable('#tablaInsumos')) {
            $('#tablaInsumos').DataTable({
                dom: 'lrtip',
                language: DATATABLES_SPANISH
            });
        }

        var table = $('#tablaInsumos').DataTable();
        table.clear();

        insumos.forEach(function(insumo) {
            var acciones = '';
            if (esAdmin) {
                acciones = '<div class="d-flex gap-2">' +
                    '<button class="btn btn-sm btn-warning btn-editar-insumo" data-id="' + insumo.id + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                    '<button class="btn btn-sm btn-danger btn-eliminar-insumo" data-id="' + insumo.id + '" data-nombre="' + insumo.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                    '</div>';
            }

            var stockStyle = parseFloat(insumo.stock_actual) <= parseFloat(insumo.stock_minimo) ? 'stock-bajo' : 'stock-normal';
            var stockHtml = '<span class="' + stockStyle + '">' + formatearMoneda(insumo.stock_actual) + '</span>';

            var row = [
                insumo.codigo,
                insumo.nombre,
                insumo.marca || '-',
                insumo.categoria || '-',
                stockHtml,
                formatearMoneda(insumo.precio_venta),
                insumo.proveedores_nombre || '-'
            ];

            if (esAdmin) {
                row.push(acciones);
            }

            table.row.add(row);
        });

        table.draw();
    }

    // Delegated events for action buttons
    document.getElementById('tablaInsumos').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-editar-insumo');
        if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
        btn = e.target.closest('.btn-eliminar-insumo');
        if (btn) { eliminarInsumo(parseInt(btn.dataset.id), btn.dataset.nombre); return; }
    });

    // Muestra las alertas de stock bajo
    function mostrarAlertas(alertas) {
        alertasStockBajo.classList.remove('d-none');
        contenidoAlertas.innerHTML = '';

        alertas.forEach(function(alerta) {
            const div = document.createElement('div');
            div.className = 'alerta-item';
            div.textContent = alerta.codigo + ' - ' + alerta.nombre + ' (Stock: ' + formatearMoneda(alerta.stock_actual) + ', Minimo: ' + formatearMoneda(alerta.stock_minimo) + ')';
            contenidoAlertas.appendChild(div);
        });
    }

    // Llena el select de proveedores
    function llenarSelectProveedores() {
        if (!proveedorInsumo) return;

        proveedorInsumo.innerHTML = '<option value="">Seleccione un proveedor...</option>';
        proveedoresGlobal.forEach(function(proveedor) {
            const opcion = document.createElement('option');
            opcion.value = proveedor.id;
            opcion.textContent = proveedor.rif + ' - ' + proveedor.nombre_empresa;
            proveedorInsumo.appendChild(opcion);
        });
    }

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('inventario/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const insumo = resultado.datos;

                tituloModal.textContent = 'Editar Insumo';
                insumoId.value = insumo.id;
                codigoInsumo.value = insumo.codigo;
                codigoInsumo.disabled = true;
                nombreInsumo.value = insumo.nombre;
                marcaInsumo.value = insumo.marca || '';
                categoriaInsumo.value = insumo.categoria || '';
                unidadMedidaInsumo.value = insumo.unidad_medida || '';
                stockActualInsumo.value = insumo.stock_actual;
                stockMinimoInsumo.value = insumo.stock_minimo;
                precioVentaInsumo.value = insumo.precio_venta;
                precioCompraInsumo.value = insumo.precio_compra;
                proveedorInsumo.value = insumo.proveedores_id || '';
                mensajeError.classList.add('d-none');

                bootstrap.Modal.getOrCreateInstance(modalInsumo).show();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener insumo:', error);
            mostrarNotificacion('Error al cargar los datos del insumo', 'error');
        }
    }

    // Guarda o actualiza un insumo
    async function guardarInsumo() {
        const id = insumoId.value;
        const esEdicion = id !== '';

        if (!codigoInsumo.value.trim()) {
            mostrarError('El codigo es obligatorio');
            return;
        }

        if (!nombreInsumo.value.trim()) {
            mostrarError('El nombre del insumo es obligatorio');
            return;
        }

        const precioVenta = parseFloat(precioVentaInsumo.value);
        if (isNaN(precioVenta) || precioVenta <= 0) {
            mostrarError('El precio de venta debe ser un numero positivo');
            return;
        }

        const url = esEdicion ? 'inventario/actualizar' : 'inventario/guardar';
        const formData = new FormData(formularioInsumo);

        if (esEdicion) {
            formData.set('id', id);
            formData.set('codigo', codigoInsumo.value);
        }

        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                bootstrap.Modal.getInstance(modalInsumo).hide();
                cargarInsumos();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar insumo:', error);
            mostrarError('Error de conexion al guardar el insumo');
        }
    }

    // Elimina un insumo
    async function eliminarInsumo(id, nombre) {
        if (!confirm('Esta seguro de eliminar el insumo ' + nombre + '?')) {
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        try {
            const respuesta = await fetch('inventario/eliminar', {
                method: 'POST',
                body: formData
            });

            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                cargarInsumos();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar insumo:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Enlazar busqueda manual a DataTables
    if (busquedaInsumos) {
        busquedaInsumos.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaInsumos')) {
                $('#tablaInsumos').DataTable().search(this.value).draw();
            }
        });
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
    }
});
