// Archivo: inventario.js
// Manejo de la vista de gestion de inventario


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
    const rubroInsumo = document.getElementById('rubroInsumo');
    const unidadMedidaInsumo = document.getElementById('unidadMedidaInsumo');
    const stockActualInsumo = document.getElementById('stockActualInsumo');
    const stockMinimoInsumo = document.getElementById('stockMinimoInsumo');
    const precioVentaInsumo = document.getElementById('precioVentaInsumo');
    const precioCompraInsumo = document.getElementById('precioCompraInsumo');
    const fechaVencimientoInsumo = null; // removed in v2
    const proveedorInsumo = document.getElementById('proveedorInsumo');
    const mensajeError = document.getElementById('mensajeErrorInsumo');
    const alertasStockBajo = document.getElementById('alertasStockBajo');
    const contenidoAlertas = document.getElementById('contenidoAlertas');

    let proveedoresGlobal = [];
    let rubrosGlobal = [];
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
            rubroInsumo.value = '';
            rubroInsumo.disabled = false;
            if (rubrosGlobal.length) llenarSelectRubros(rubrosGlobal);
            unidadMedidaInsumo.value = '';
            stockActualInsumo.value = '0';
            stockMinimoInsumo.value = '5';
            precioVentaInsumo.value = '0';
            precioCompraInsumo.value = '0';
            // fecha_vencimiento removed in v2
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
                if (resultado.datos.rubros) {
                    rubrosGlobal = resultado.datos.rubros;
                    llenarSelectRubros(rubrosGlobal);
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
                language: window.DATATABLES_SPANISH
            });
        }

        var table = $('#tablaInsumos').DataTable();
        table.clear();

        insumos.forEach(function(insumo) {
            var acciones = '';
            if (esAdmin) {
                acciones = '<div class="d-flex gap-2">' +
                    '<button class="btn btn-sm btn-warning btn-editar-insumo" data-id="' + insumo.id_insumo + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                    '<button class="btn btn-sm btn-danger btn-eliminar-insumo" data-id="' + insumo.id_insumo + '" data-nombre="' + insumo.nombre.replace(/"/g, '&quot;') + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                    '</div>';
            }

            var stockStyle = parseFloat(insumo.stock_actual) <= parseFloat(insumo.stock_minimo) ? 'stock-bajo' : 'stock-normal';
            var stockHtml = '<span class="' + stockStyle + '">' + formatearMoneda(insumo.stock_actual) + '</span>';

            var row = [
                insumo.codigo,
                insumo.nombre,
                insumo.marca || '-',
                insumo.rubro_nombre || '-',
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
        if (btn) {
            var id = parseInt(btn.dataset.id);
            if (isNaN(id)) {
                console.error('btn.dataset.id no es un numero:', btn.dataset.id, btn.outerHTML);
                mostrarNotificacion('Error: ID de insumo invalido (' + btn.dataset.id + ')', 'error');
                return;
            }
            abrirModalEditar(id);
            return;
        }
        btn = e.target.closest('.btn-eliminar-insumo');
        if (btn) {
            var id = parseInt(btn.dataset.id);
            if (isNaN(id)) {
                console.error('btn.dataset.id no es un numero:', btn.dataset.id, btn.outerHTML);
                mostrarNotificacion('Error: ID de insumo invalido', 'error');
                return;
            }
            eliminarInsumo(id, btn.dataset.nombre);
            return;
        }
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
            opcion.value = proveedor.id_proveedor;
            opcion.textContent = proveedor.rif + ' - ' + proveedor.nombre_empresa;
            proveedorInsumo.appendChild(opcion);
        });
    }

    function llenarSelectRubros(rubros) {
        if (!rubroInsumo) return;
        rubroInsumo.innerHTML = '<option value="">Seleccione un rubro</option>';
        rubros.forEach(function(rubro) {
            var op = document.createElement('option');
            op.value = rubro.id_rubro;
            op.textContent = rubro.nombre;
            rubroInsumo.appendChild(op);
        });
    }

    // Carga los rubros filtrados por proveedor
    async function cargarRubrosPorProveedor(proveedorId) {
        if (!proveedorId) {
            llenarSelectRubros(rubrosGlobal);
            rubroInsumo.disabled = false;
            return [];
        }
        try {
            const respuesta = await fetch('inventario/obtenerRubrosPorProveedorAjax?id_proveedor=' + proveedorId);
            const resultado = await respuesta.json();
            if (resultado.estado === 'exito') {
                const rubros = resultado.datos.rubros;
                if (rubros.length === 0) {
                    llenarSelectRubros(rubrosGlobal);
                } else {
                    llenarSelectRubros(rubros);
                }
                rubroInsumo.disabled = false;
                return rubros;
            }
        } catch (error) {
            console.error('Error al cargar rubros por proveedor:', error);
        }
        return [];
    }

    // Cuando cambia el proveedor, filtrar rubros
    if (proveedorInsumo) {
        proveedorInsumo.addEventListener('change', async function() {
            const rubros = await cargarRubrosPorProveedor(this.value);
            if (this.value && rubros.length === 1) {
                rubroInsumo.value = rubros[0].id_rubro;
                rubroInsumo.disabled = true;
            } else {
                rubroInsumo.value = '';
                rubroInsumo.disabled = false;
            }
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
                insumoId.value = insumo.id_insumo;
                codigoInsumo.value = insumo.codigo;
                codigoInsumo.disabled = true;
                nombreInsumo.value = insumo.nombre;
                marcaInsumo.value = insumo.marca || '';
                unidadMedidaInsumo.value = insumo.unidad_medida || '';
                stockActualInsumo.value = insumo.stock_actual;
                stockMinimoInsumo.value = insumo.stock_minimo;
                precioVentaInsumo.value = insumo.precio_venta;
                precioCompraInsumo.value = insumo.precio_compra;
                proveedorInsumo.value = insumo.proveedores_id || '';
                // Cargar rubros segun el proveedor del insumo
                const rubrosEdit = await cargarRubrosPorProveedor(proveedorInsumo.value);
                if (proveedorInsumo.value && rubrosEdit.length === 1) {
                    rubroInsumo.value = rubrosEdit[0].id_rubro;
                    rubroInsumo.disabled = true;
                } else {
                    rubroInsumo.value = insumo.id_rubro || '';
                    rubroInsumo.disabled = false;
                }
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
        confirmarConModal('Eliminar', 'Esta seguro de eliminar el insumo ' + nombre + '?', function() {
            const formData = new FormData();
            formData.append('id', id);
            fetch('inventario/eliminar', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(resultado) {
                    if (resultado.estado === 'exito') {
                        cargarInsumos();
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
                    console.error('Error al eliminar insumo:', error);
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion('Error de conexion al eliminar el insumo', 'error');
                    } else {
                        alert('Error de conexion al eliminar el insumo');
                    }
                });
        });
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

    // Re-cargar datos si la pagina se restaura desde bfcache
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            cargarInsumos();
        }
    });
});
