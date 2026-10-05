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
    const tipoProductoInsumo = document.getElementById('tipoProductoInsumo');
    const rubroInsumo = document.getElementById('rubroInsumo');
    const unidadMedidaInsumo = document.getElementById('unidadMedidaInsumo');
    const stockActualInsumo = document.getElementById('stockActualInsumo');
    const stockMinimoInsumo = document.getElementById('stockMinimoInsumo');
    const precioVentaInsumo = document.getElementById('precioVentaInsumo');
    const precioCompraInsumo = document.getElementById('precioCompraInsumo');
    const proveedorInsumo = document.getElementById('proveedorInsumo');
    const mensajeError = document.getElementById('mensajeErrorInsumo');
    const alertasStockBajo = document.getElementById('alertasStockBajo');
    const contenidoAlertas = document.getElementById('contenidoAlertas');

    let proveedoresGlobal = [];
    let rubrosGlobal = [];
    const esAdmin = document.getElementById('btnNuevoInsumo') !== null;

    // Ajusta la unidad de medida según el tipo de producto
    function ajustarUnidadSegunTipo(unidadPrevia) {
        if (!tipoProductoInsumo || !unidadMedidaInsumo) return;
        const tipoVal = String(tipoProductoInsumo.value);
        if (tipoVal === '1') {
            // Base solo puede ser galón
            unidadMedidaInsumo.innerHTML = '<option value="Galon">Galón</option>';
            unidadMedidaInsumo.value = 'Galon';
        } else {
            // Simple: Unidad o KG
            unidadMedidaInsumo.innerHTML = '<option value="Unidad">Unidad</option><option value="KG">KG</option>';
            if (unidadPrevia && (unidadPrevia === 'Unidad' || unidadPrevia === 'KG')) {
                unidadMedidaInsumo.value = unidadPrevia;
            } else {
                unidadMedidaInsumo.value = 'Unidad';
            }
        }
    }

    if (tipoProductoInsumo) {
        tipoProductoInsumo.addEventListener('change', function() {
            ajustarUnidadSegunTipo();
        });
    }

    // Restringir inputs de stock a solo números enteros
    [stockActualInsumo, stockMinimoInsumo].forEach(function(input) {
        if (!input) return;
        input.addEventListener('keydown', function(e) {
            if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '-') {
                e.preventDefault();
            }
        });
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

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
            if (tipoProductoInsumo) tipoProductoInsumo.value = '2';
            rubroInsumo.value = '';
            rubroInsumo.disabled = false;
            if (rubrosGlobal.length) llenarSelectRubros(rubrosGlobal);
            ajustarUnidadSegunTipo();
            stockActualInsumo.value = '0';
            stockMinimoInsumo.value = '5';
            precioVentaInsumo.value = '0';
            precioCompraInsumo.value = '0';
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
    }
    if (btnCancelar) {
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
            const respuesta = await fetch('/SP%20Perfect%20Color/inventario/listarAjax');
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

    // Muestra los insumos en la tabla usando API DataTables con columns explicitas
    function mostrarInsumos(insumos) {
        if (!$.fn.DataTable.isDataTable('#tablaInsumos')) {
            var columnsDef = [
                { data: 'codigo' },
                { data: 'nombre' },
                {
                    data: null,
                    render: function(data, type, row) {
                        if (!row) return '';
                        var tipoId = parseInt(row.id_tipo_producto);
                        var tipoNom = row.tipo_producto_nombre || (tipoId === 1 ? 'Base' : (tipoId === 3 ? 'Preparado' : 'Simple'));
                        var badgeClass = 'badge-tipo-simple';
                        var iconClass = 'bi-box-seam';
                        if (tipoId === 1 || tipoNom.toLowerCase() === 'base') {
                            badgeClass = 'badge-tipo-base';
                            iconClass = 'bi-droplet-half';
                        } else if (tipoId === 3 || tipoNom.toLowerCase() === 'preparado') {
                            badgeClass = 'badge-tipo-preparado';
                            iconClass = 'bi-palette-fill';
                        }
                        return '<span class="' + badgeClass + '"><i class="bi ' + iconClass + ' me-1"></i>' + tipoNom + '</span>';
                    }
                },
                {
                    data: 'rubro_nombre',
                    render: function(d) { return d || '-'; }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        if (!row) return '';
                        var stockStyle = parseFloat(row.stock_actual) <= parseFloat(row.stock_minimo) ? 'stock-bajo' : 'stock-normal';
                        return '<span class="' + stockStyle + '">' + formatearMoneda(row.stock_actual) + '</span>';
                    }
                },
                {
                    data: 'precio_venta',
                    render: function(d) { return d != null ? formatearMoneda(d) : '0,00'; }
                },
                {
                    data: 'proveedores_nombre',
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
                        var nom = (row.nombre || '').replace(/"/g, '&quot;');
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-insumo" data-id="' + row.id_insumo + '" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger btn-eliminar-insumo" data-id="' + row.id_insumo + '" data-nombre="' + nom + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                            '</div>';
                    }
                });
            }

            $('#tablaInsumos').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: columnsDef
            });
        }

        var table = $('#tablaInsumos').DataTable();
        table.clear();

        if (Array.isArray(insumos)) {
            insumos.forEach(function(insumo) {
                table.row.add(insumo);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    $(document).on('click', '.btn-editar-insumo', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) {
            abrirModalEditar(id);
        } else {
            console.error('ID de insumo invalido:', $(this).attr('data-id'));
        }
    });

    $(document).on('click', '.btn-eliminar-insumo', function(e) {
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        var nombre = $(this).attr('data-nombre') || 'este insumo';
        if (!isNaN(id)) {
            eliminarInsumo(id, nombre);
        }
    });

    // Muestra las alertas de stock bajo
    function mostrarAlertas(alertas) {
        if (!alertasStockBajo || !contenidoAlertas) return;
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
            const respuesta = await fetch('/SP%20Perfect%20Color/inventario/obtenerRubrosPorProveedorAjax?id_proveedor=' + proveedorId);
            const resultado = await respuesta.json();
            if (resultado.estado === 'exito') {
                llenarSelectRubros(resultado.datos.rubros);
                return resultado.datos.rubros;
            }
        } catch (error) {
            console.error('Error al cargar rubros por proveedor:', error);
        }
        return [];
    }

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
            const respuesta = await fetch('/SP%20Perfect%20Color/inventario/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const insumo = resultado.datos;

                tituloModal.textContent = 'Editar Insumo';
                insumoId.value = insumo.id_insumo;
                codigoInsumo.value = insumo.codigo;
                codigoInsumo.disabled = true;
                nombreInsumo.value = insumo.nombre;
                if (tipoProductoInsumo) tipoProductoInsumo.value = insumo.id_tipo_producto || '2';
                ajustarUnidadSegunTipo(insumo.unidad_medida);
                stockActualInsumo.value = Math.round(parseFloat(insumo.stock_actual) || 0);
                stockMinimoInsumo.value = Math.round(parseFloat(insumo.stock_minimo) || 0);
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

        const stockActual = parseInt(stockActualInsumo.value, 10);
        const stockMinimo = parseInt(stockMinimoInsumo.value, 10);
        if (isNaN(stockActual) || stockActual < 0) {
            mostrarError('El stock actual debe ser un número entero no negativo');
            return;
        }
        if (isNaN(stockMinimo) || stockMinimo < 0) {
            mostrarError('El stock mínimo debe ser un número entero no negativo');
            return;
        }

        const url = esEdicion ? '/SP%20Perfect%20Color/inventario/actualizar' : '/SP%20Perfect%20Color/inventario/guardar';
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
            fetch('/SP%20Perfect%20Color/inventario/eliminar', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(resultado) {
                    if (resultado.estado === 'exito') {
                        cargarInsumos();
                        mostrarNotificacion(resultado.mensaje, 'exito');
                    } else {
                        mostrarNotificacion(resultado.mensaje, 'error');
                    }
                })
                .catch(function(error) {
                    console.error('Error al eliminar insumo:', error);
                    mostrarNotificacion('Error de conexion al eliminar el insumo', 'error');
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
