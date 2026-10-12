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
    const feedbackCodigoInsumo = document.getElementById('feedbackCodigoInsumo');
    const btnGuardarInsumo = document.getElementById('btnGuardarInsumo');

    let timerCodigo = null;

    function verificarCodigoEnTiempoReal() {
        if (!codigoInsumo) return;
        const cod = codigoInsumo.value.trim().toUpperCase();
        const id = insumoId.value || '';

        clearTimeout(timerCodigo);

        if (!cod) {
            codigoInsumo.classList.remove('is-invalid', 'is-valid');
            if (feedbackCodigoInsumo) feedbackCodigoInsumo.classList.add('d-none');
            if (btnGuardarInsumo) btnGuardarInsumo.disabled = false;
            return;
        }

        timerCodigo = setTimeout(async function() {
            try {
                const resp = await fetch('/SP%20Perfect%20Color/inventario/verificarCodigoAjax?codigo=' + encodeURIComponent(cod) + '&id=' + encodeURIComponent(id));
                const res = await resp.json();
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        codigoInsumo.classList.add('is-invalid');
                        codigoInsumo.classList.remove('is-valid');
                        if (feedbackCodigoInsumo) {
                            feedbackCodigoInsumo.textContent = 'El insumo con código ' + cod + ' ya existe en el sistema';
                            feedbackCodigoInsumo.classList.remove('d-none');
                        }
                        if (btnGuardarInsumo) btnGuardarInsumo.disabled = true;
                    } else {
                        codigoInsumo.classList.remove('is-invalid');
                        codigoInsumo.classList.add('is-valid');
                        if (feedbackCodigoInsumo) feedbackCodigoInsumo.classList.add('d-none');
                        if (btnGuardarInsumo) btnGuardarInsumo.disabled = false;
                    }
                }
            } catch (e) {
                console.error('Error al verificar codigo:', e);
            }
        }, 300);
    }

    if (codigoInsumo) {
        codigoInsumo.addEventListener('input', verificarCodigoEnTiempoReal);
    }

    let proveedoresGlobal = [];
    let rubrosGlobal = [];
    let insumosGlobal = [];
    let basesGlobal = [];
    const esAdmin = document.getElementById('btnPrepararMezcla') !== null || document.getElementById('btnNuevoInsumo') !== null;

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
            codigoInsumo.classList.remove('is-invalid', 'is-valid');
            if (feedbackCodigoInsumo) feedbackCodigoInsumo.classList.add('d-none');
            if (btnGuardarInsumo) btnGuardarInsumo.disabled = false;
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
                insumosGlobal = resultado.datos.insumos || [];
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
                            '<button class="btn btn-sm btn-danger btn-eliminar-insumo" data-id="' + row.id_insumo + '" data-nombre="' + nom + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
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
                codigoInsumo.classList.remove('is-invalid', 'is-valid');
                if (feedbackCodigoInsumo) feedbackCodigoInsumo.classList.add('d-none');
                if (btnGuardarInsumo) btnGuardarInsumo.disabled = false;
                nombreInsumo.value = insumo.nombre;
                if (tipoProductoInsumo) tipoProductoInsumo.value = insumo.id_tipo_producto || '2';
                ajustarUnidadSegunTipo(insumo.unidad_medida);
                if (stockActualInsumo) {
                    stockActualInsumo.value = Math.round(parseFloat(insumo.stock_actual) || 0);
                    stockActualInsumo.readOnly = true;
                }
                if (stockMinimoInsumo) stockMinimoInsumo.value = Math.round(parseFloat(insumo.stock_minimo) || 0);
                if (precioVentaInsumo) precioVentaInsumo.value = insumo.precio_venta;
                if (precioCompraInsumo) precioCompraInsumo.value = insumo.precio_compra || 0;
                if (proveedorInsumo) {
                    proveedorInsumo.value = insumo.proveedores_id || '';
                    proveedorInsumo.disabled = true;
                }
                // Cargar rubros segun el proveedor del insumo
                const rubrosEdit = await cargarRubrosPorProveedor(insumo.proveedores_id || '');
                if (rubroInsumo) {
                    rubroInsumo.value = insumo.id_rubro || '';
                    rubroInsumo.disabled = true;
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

    // =========================================================================
    // SECCIÓN: PREPARAR MEZCLA / COLOR PERSONALIZADO (TALLER)
    // =========================================================================
    const btnPrepararMezcla = document.getElementById('btnPrepararMezcla');
    const modalPrepararMezcla = document.getElementById('modalPrepararMezcla');
    const formMezcla = document.getElementById('formularioMezcla');
    const cuerpoBasesMezcla = document.getElementById('cuerpoBasesMezcla');
    const btnAgregarFilaBase = document.getElementById('btnAgregarFilaBase');
    const contadorBasesMezcla = document.getElementById('contadorBasesMezcla');
    const totalGalonesMezcla = document.getElementById('totalGalonesMezcla');
    const mezclaNombre = document.getElementById('mezclaNombre');
    const mezclaPresentacion = document.getElementById('mezclaPresentacion');
    const mezclaCantidad = document.getElementById('mezclaCantidad');
    const mezclaPrecioVenta = document.getElementById('mezclaPrecioVenta');
    const mezclaSubtotalEstimado = document.getElementById('mezclaSubtotalEstimado');
    const mensajeErrorMezcla = document.getElementById('mensajeErrorMezcla');

    async function cargarBasesParaMezcla() {
        try {
            const resp = await fetch('/SP%20Perfect%20Color/inventario/listarBasesAjax');
            const res = await resp.json();
            if (res.estado === 'exito') {
                basesGlobal = res.datos.bases || [];
            }
        } catch (e) {
            console.error('Error al cargar bases:', e);
        }
    }

    if (btnPrepararMezcla) {
        btnPrepararMezcla.addEventListener('click', async function() {
            if (formMezcla) formMezcla.reset();
            if (mensajeErrorMezcla) mensajeErrorMezcla.classList.add('d-none');
            if (cuerpoBasesMezcla) cuerpoBasesMezcla.innerHTML = '';
            await cargarBasesParaMezcla();
            agregarFilaBaseMezcla();
            recalcularTotalesMezcla();
            if (modalPrepararMezcla) {
                bootstrap.Modal.getOrCreateInstance(modalPrepararMezcla).show();
            }
        });
    }

    if (btnAgregarFilaBase) {
        btnAgregarFilaBase.addEventListener('click', function() {
            agregarFilaBaseMezcla();
        });
    }

    function agregarFilaBaseMezcla() {
        if (!cuerpoBasesMezcla) return;
        const totalFilas = cuerpoBasesMezcla.querySelectorAll('tr').length;
        if (totalFilas >= 6) {
            mostrarNotificacion('El límite máximo es de 6 tintes base por mezcla.', 'error');
            return;
        }

        const fila = document.createElement('tr');
        fila.innerHTML =
            '<td class="position-relative">' +
                '<div class="input-group input-group-sm">' +
                    '<span class="input-group-text bg-light text-muted px-2"><i class="bi bi-search"></i></span>' +
                    '<input type="text" class="form-control form-control-sm input-buscar-base" placeholder="Buscar base por código o nombre..." autocomplete="off">' +
                    '<button type="button" class="btn btn-outline-secondary btn-limpiar-base d-none" title="Limpiar"><i class="bi bi-x"></i></button>' +
                '</div>' +
                '<input type="hidden" class="select-base-tinte" value="" required>' +
                '<div class="dropdown-menu w-100 p-0 lista-sugerencias-base shadow border" style="max-height: 220px; overflow-y: auto; z-index: 1055;"></div>' +
            '</td>' +
            '<td>' +
                '<select class="form-select form-select-sm select-fraccion-tinte">' +
                    '<option value="1" data-galon="1.0">1 Galón (1.00 gal)</option>' +
                    '<option value="1/2" data-galon="0.5">1/2 Galón (0.50 gal)</option>' +
                    '<option value="1/4" data-galon="0.25" selected>1/4 Galón (0.25 gal)</option>' +
                    '<option value="1/8" data-galon="0.125">1/8 Galón (0.125 gal)</option>' +
                    '<option value="1/16" data-galon="0.0625">1/16 Galón (0.0625 gal)</option>' +
                    '<option value="1/32" data-galon="0.03125">1/32 Galón (0.0312 gal)</option>' +
                    '<option value="1/64" data-galon="0.015625">1/64 Galón (0.0156 gal)</option>' +
                    '<option value="1/128" data-galon="0.0078125">1/128 Galón (0.0078 gal)</option>' +
                '</select>' +
            '</td>' +
            '<td class="text-end">' +
                '<span class="badge bg-light text-dark border consumo-galon-text">0.2500 gal</span>' +
            '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-base-fila" title="Quitar"><i class="bi bi-x-lg"></i></button>' +
            '</td>';

        const inputBuscar = fila.querySelector('.input-buscar-base');
        const inputHidden = fila.querySelector('.select-base-tinte');
        const btnLimpiar = fila.querySelector('.btn-limpiar-base');
        const listaSug = fila.querySelector('.lista-sugerencias-base');
        const selFrac = fila.querySelector('.select-fraccion-tinte');
        const labelConsumo = fila.querySelector('.consumo-galon-text');
        const btnElim = fila.querySelector('.btn-eliminar-base-fila');

        function renderizarSugerencias(filtro) {
            listaSug.innerHTML = '';
            const q = (filtro || '').toLowerCase().trim();
            const filtradas = basesGlobal.filter(function(b) {
                if (!q) return true;
                const texto = ((b.codigo || '') + ' ' + (b.nombre || '')).toLowerCase();
                return texto.includes(q);
            });

            if (filtradas.length === 0) {
                listaSug.innerHTML = '<div class="p-2 text-muted small text-center">No se encontraron tintes base</div>';
            } else {
                filtradas.forEach(function(b) {
                    const stock = parseFloat(b.stock_actual) || 0;
                    const item = document.createElement('a');
                    item.className = 'dropdown-item d-flex justify-content-between align-items-center py-2 px-3 border-bottom small';
                    item.href = '#';
                    item.innerHTML = '<div><span class="badge bg-secondary me-1">' + b.codigo + '</span> <strong>' + b.nombre + '</strong></div>' +
                                     '<span class="badge bg-light text-dark border">' + stock.toFixed(2) + ' gal</span>';
                    item.addEventListener('click', function(ev) {
                        ev.preventDefault();
                        inputBuscar.value = b.codigo + ' - ' + b.nombre;
                        inputHidden.value = b.id_producto;
                        inputHidden.dataset.precio = b.precio_venta || 0;
                        inputHidden.dataset.nombre = b.nombre;
                        btnLimpiar.classList.remove('d-none');
                        listaSug.classList.remove('show');
                        recalcularTotalesMezcla();
                    });
                    listaSug.appendChild(item);
                });
            }
            listaSug.classList.add('show');
        }

        inputBuscar.addEventListener('focus', function() {
            renderizarSugerencias(this.value);
        });

        inputBuscar.addEventListener('input', function() {
            if (!this.value.trim()) {
                inputHidden.value = '';
                inputHidden.dataset.precio = 0;
                btnLimpiar.classList.add('d-none');
                recalcularTotalesMezcla();
            }
            renderizarSugerencias(this.value);
        });

        btnLimpiar.addEventListener('click', function() {
            inputBuscar.value = '';
            inputHidden.value = '';
            inputHidden.dataset.precio = 0;
            btnLimpiar.classList.add('d-none');
            listaSug.classList.remove('show');
            recalcularTotalesMezcla();
            inputBuscar.focus();
        });

        document.addEventListener('click', function(e) {
            if (!fila.contains(e.target)) {
                listaSug.classList.remove('show');
            }
        });

        selFrac.addEventListener('change', function() {
            const gal = parseFloat(this.selectedOptions[0]?.dataset?.galon || 0);
            labelConsumo.textContent = gal.toFixed(4) + ' gal';
            recalcularTotalesMezcla();
        });

        btnElim.addEventListener('click', function() {
            fila.remove();
            actualizarContadorBasesMezcla();
            recalcularTotalesMezcla();
        });

        cuerpoBasesMezcla.appendChild(fila);
        actualizarContadorBasesMezcla();
        recalcularTotalesMezcla();
    }

    function actualizarContadorBasesMezcla() {
        if (!cuerpoBasesMezcla || !contadorBasesMezcla) return;
        const count = cuerpoBasesMezcla.querySelectorAll('tr').length;
        contadorBasesMezcla.textContent = count + '/6 bases';
        if (btnAgregarFilaBase) btnAgregarFilaBase.disabled = count >= 6;
    }

    function recalcularTotalesMezcla() {
        if (!cuerpoBasesMezcla) return;
        let totalGal = 0;
        let costoBases = 0;
        cuerpoBasesMezcla.querySelectorAll('tr').forEach(function(r) {
            const selBase = r.querySelector('.select-base-tinte');
            const selFrac = r.querySelector('.select-fraccion-tinte');
            if (selBase && selBase.value) {
                const precio = parseFloat(selBase.dataset?.precio || selBase.selectedOptions?.[0]?.dataset?.precio || 0);
                const gal = parseFloat(selFrac?.selectedOptions[0]?.dataset?.galon || 0);
                totalGal += gal;
                costoBases += (gal * precio);
            }
        });

        const capacidadObjetivo = parseFloat(mezclaPresentacion?.selectedOptions[0]?.dataset?.factor || 1.0);
        const cant = parseInt(mezclaCantidad?.value || 1) || 1;
        const volumenTotalTaller = totalGal * cant;

        const indicadorBalance = document.getElementById('indicadorBalanceFormula');
        if (indicadorBalance) {
            if (totalGal === 0) {
                indicadorBalance.innerHTML = '<span class="text-muted small">Volumen: 0.0000 / ' + capacidadObjetivo.toFixed(4) + ' gal</span>';
            } else if (Math.abs(totalGal - capacidadObjetivo) < 0.0001) {
                indicadorBalance.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Fórmula exacta: ' + totalGal.toFixed(4) + ' / ' + capacidadObjetivo.toFixed(4) + ' gal (' + cant + ' envases = ' + volumenTotalTaller.toFixed(4) + ' gal)</span>';
            } else if (totalGal < capacidadObjetivo) {
                const falta = (capacidadObjetivo - totalGal).toFixed(4);
                indicadorBalance.innerHTML = '<span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Faltan ' + falta + ' gal para completar ' + capacidadObjetivo.toFixed(4) + ' gal</span>';
            } else {
                const sobra = (totalGal - capacidadObjetivo).toFixed(4);
                indicadorBalance.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Exceso de fórmula: excede por ' + sobra + ' gal</span>';
            }
        }

        const precio = parseFloat(mezclaPrecioVenta?.value || 0) || 0;
        if (mezclaSubtotalEstimado) {
            mezclaSubtotalEstimado.textContent = '$ ' + formatearMoneda(cant * precio);
        }
    }

    if (mezclaPresentacion) mezclaPresentacion.addEventListener('change', recalcularTotalesMezcla);
    if (mezclaCantidad) mezclaCantidad.addEventListener('input', recalcularTotalesMezcla);
    if (mezclaPrecioVenta) mezclaPrecioVenta.addEventListener('input', recalcularTotalesMezcla);

    if (formMezcla) {
        formMezcla.addEventListener('submit', async function(e) {
            e.preventDefault();
            mensajeErrorMezcla.classList.add('d-none');

            const nombre = mezclaNombre.value.trim();
            const presentacion = mezclaPresentacion.value;
            const capacidadObjetivo = parseFloat(mezclaPresentacion?.selectedOptions[0]?.dataset?.factor || 1.0);
            const cantidad = parseInt(mezclaCantidad.value) || 1;
            const precioVenta = parseFloat(mezclaPrecioVenta.value) || 0;

            const filas = cuerpoBasesMezcla.querySelectorAll('tr');
            if (filas.length === 0) {
                mensajeErrorMezcla.textContent = 'Debe agregar al menos una base para la formulación.';
                mensajeErrorMezcla.classList.remove('d-none');
                return;
            }

            let sumaFormula = 0;
            const basesData = [];
            let errorBases = false;
            filas.forEach(function(r) {
                const selBase = r.querySelector('.select-base-tinte');
                const selFrac = r.querySelector('.select-fraccion-tinte');
                const idBase = parseInt(selBase?.value || 0);
                if (!idBase) {
                    errorBases = true;
                    return;
                }
                const fraccion = selFrac?.value || '1/4';
                const cantGal = parseFloat(selFrac?.selectedOptions[0]?.dataset?.galon || 0.25);
                sumaFormula += cantGal;
                basesData.push({
                    id_producto_base: idBase,
                    fraccion: fraccion,
                    cantidad_galon: cantGal
                });
            });

            if (errorBases || basesData.length === 0) {
                mensajeErrorMezcla.textContent = 'Por favor seleccione todos los tintes base agregados.';
                mensajeErrorMezcla.classList.remove('d-none');
                return;
            }

            // Validación estricta de balance de volumen: debe dar la cantidad exacta
            if (Math.abs(sumaFormula - capacidadObjetivo) >= 0.0001) {
                if (sumaFormula < capacidadObjetivo) {
                    const falta = (capacidadObjetivo - sumaFormula).toFixed(4);
                    mensajeErrorMezcla.textContent = 'La mezcla debe dar la cantidad exacta de la presentación (' + capacidadObjetivo.toFixed(4) + ' gal). Le faltan ' + falta + ' galones.';
                } else {
                    const sobra = (sumaFormula - capacidadObjetivo).toFixed(4);
                    mensajeErrorMezcla.textContent = 'La mezcla excede la capacidad de la presentación (' + capacidadObjetivo.toFixed(4) + ' gal). Le sobran ' + sobra + ' galones.';
                }
                mensajeErrorMezcla.classList.remove('d-none');
                return;
            }

            const fd = new FormData();
            fd.append('nombre', nombre);
            fd.append('presentacion', presentacion);
            fd.append('cantidad', cantidad);
            fd.append('precio_venta', precioVenta);
            fd.append('bases', JSON.stringify(basesData));

            try {
                const resp = await fetch('/SP%20Perfect%20Color/inventario/guardarMezcla', {
                    method: 'POST',
                    body: fd
                });
                const res = await resp.json();
                if (res.estado === 'exito') {
                    bootstrap.Modal.getInstance(modalPrepararMezcla).hide();
                    mostrarNotificacion(res.mensaje, 'exito');
                    cargarInsumos();
                } else {
                    mensajeErrorMezcla.textContent = res.mensaje;
                    mensajeErrorMezcla.classList.remove('d-none');
                }
            } catch (err) {
                console.error(err);
                mensajeErrorMezcla.textContent = 'Error de conexión al registrar la mezcla.';
                mensajeErrorMezcla.classList.remove('d-none');
            }
        });
    }

    // =========================================================================
    // SECCIÓN: AJUSTE POR MERMA / DAÑO
    // =========================================================================
    const btnAjusteMerma = document.getElementById('btnAjusteMerma');
    const modalAjusteMerma = document.getElementById('modalAjusteMerma');
    const formAjusteMerma = document.getElementById('formularioAjusteMerma');
    const ajusteProducto = document.getElementById('ajusteProducto');
    const ajusteStockDisponible = document.getElementById('ajusteStockDisponible');
    const ajusteCantidad = document.getElementById('ajusteCantidad');
    const ajusteMotivo = document.getElementById('ajusteMotivo');
    const ajusteObservacion = document.getElementById('ajusteObservacion');
    const mensajeErrorAjuste = document.getElementById('mensajeErrorAjuste');

    if (btnAjusteMerma) {
        btnAjusteMerma.addEventListener('click', function() {
            if (formAjusteMerma) formAjusteMerma.reset();
            if (mensajeErrorAjuste) mensajeErrorAjuste.classList.add('d-none');
            if (ajusteStockDisponible) ajusteStockDisponible.textContent = '-';

            if (ajusteProducto) {
                ajusteProducto.innerHTML = '<option value="">Seleccione el producto afectado...</option>';
                insumosGlobal.forEach(function(p) {
                    const stock = parseFloat(p.stock_actual) || 0;
                    if (stock > 0) {
                        const opt = document.createElement('option');
                        opt.value = p.id_producto;
                        opt.textContent = p.codigo + ' - ' + p.nombre + ' (Stock: ' + stock + ' ' + (p.unidad_medida || '') + ')';
                        opt.dataset.stock = stock;
                        opt.dataset.unidad = p.unidad_medida || '';
                        ajusteProducto.appendChild(opt);
                    }
                });
            }

            if (modalAjusteMerma) {
                bootstrap.Modal.getOrCreateInstance(modalAjusteMerma).show();
            }
        });
    }

    if (ajusteProducto) {
        ajusteProducto.addEventListener('change', function() {
            const opt = this.selectedOptions[0];
            if (opt && opt.value) {
                const stock = opt.dataset.stock || '0';
                const unidad = opt.dataset.unidad || '';
                ajusteStockDisponible.textContent = stock + ' ' + unidad;
            } else {
                ajusteStockDisponible.textContent = '-';
            }
        });
    }

    if (formAjusteMerma) {
        formAjusteMerma.addEventListener('submit', async function(e) {
            e.preventDefault();
            mensajeErrorAjuste.classList.add('d-none');

            const idProd = parseInt(ajusteProducto.value) || 0;
            const cantRaw = ajusteCantidad.value.trim();
            const cant = Number(cantRaw);
            const motivoSel = ajusteMotivo.value;
            const obs = ajusteObservacion.value.trim();
            const motivoFinal = motivoSel + (obs ? ': ' + obs : '');

            if (!idProd) {
                mensajeErrorAjuste.textContent = 'Seleccione un producto.';
                mensajeErrorAjuste.classList.remove('d-none');
                return;
            }

            const opt = ajusteProducto.selectedOptions[0];
            const stockActual = parseFloat(opt ? opt.dataset.stock : 0) || 0;

            if (!cantRaw || !Number.isInteger(cant) || cant < 1) {
                mensajeErrorAjuste.textContent = 'La cantidad a descontar debe ser un número entero mayor o igual a 1.';
                mensajeErrorAjuste.classList.remove('d-none');
                return;
            }

            if (cant > stockActual) {
                mensajeErrorAjuste.textContent = 'La cantidad (' + cant + ') excede el stock disponible (' + stockActual + ').';
                mensajeErrorAjuste.classList.remove('d-none');
                return;
            }

            const fd = new FormData();
            fd.append('id_producto', idProd);
            fd.append('cantidad', cant);
            fd.append('motivo', motivoFinal);

            try {
                const resp = await fetch('/SP%20Perfect%20Color/inventario/ajustarMerma', {
                    method: 'POST',
                    body: fd
                });
                const res = await resp.json();
                if (res.estado === 'exito') {
                    bootstrap.Modal.getInstance(modalAjusteMerma).hide();
                    mostrarNotificacion(res.mensaje, 'exito');
                    cargarInsumos();
                } else {
                    mensajeErrorAjuste.textContent = res.mensaje;
                    mensajeErrorAjuste.classList.remove('d-none');
                }
            } catch (err) {
                console.error(err);
                mensajeErrorAjuste.textContent = 'Error de conexión al registrar ajuste.';
                mensajeErrorAjuste.classList.remove('d-none');
            }
        });
    }

    // Re-cargar datos si la pagina se restaura desde bfcache
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            cargarInsumos();
        }
    });
});
