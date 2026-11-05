// Archivo: presupuestoForm.js
// Manejo del formulario de creacion de presupuestos con UX/UI interactiva y formulación de mezclas

$(function() {
    // Referencias a elementos del DOM
    const $clientePresupuesto = $('#clientePresupuesto');
    const $busquedaInsumo = $('#busquedaInsumoPresupuesto');
    const $listaInsumosDisponibles = $('#listaInsumosDisponibles');
    const $cuerpoTablaItems = $('#cuerpoTablaItems');
    const $totalPresupuesto = $('#totalPresupuesto');
    const $formularioPresupuesto = $('#formularioPresupuesto');
    const $mensajeError = $('#mensajeErrorPresupuesto');
    const $filaVacia = $('#filaVacia');
    const $contadorInsumos = $('#contadorInsumos');
    const $badgeCantidadItems = $('#badgeCantidadItems');
    const $resumenConteoItems = $('#resumenConteoItems');
    const $btnGuardarPresupuesto = $('#btnGuardarPresupuesto');

    const HTML_BOTON_AGREGAR = '<button type="button" class="btn btn-sm btn-outline-primary btn-catalogo-add btn-agregar"><i class="bi bi-plus-lg me-1"></i>Agregar</button>';
    const HTML_BOTON_AGREGADO = '<button type="button" class="btn btn-sm btn-secondary btn-catalogo-add disabled" disabled><i class="bi bi-check2 me-1"></i>Agregado</button>';
    const HTML_BOTON_GUARDAR = '<i class="bi bi-check-lg me-1"></i>Guardar';

    // Array para almacenar los items del presupuesto y catalogo
    const itemsPresupuesto = [];
    let insumosDisponibles = [];
    let clientesDisponibles = [];
    let tipoFiltroActual = 'todos';
    let fraccionFiltroActual = 'todos';

    // Verificar que los elementos esenciales existen
    if (!$clientePresupuesto.length || !$formularioPresupuesto.length) {
        console.error('Error: No se encontraron los elementos del formulario');
        return;
    }

    // Cargar datos iniciales
    cargarClientes();
    cargarInsumos();

    // Evento para buscar insumos mientras se escribe
    let temporizadorBusqueda;
    $busquedaInsumo.on('input', function() {
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(aplicarFiltros, 150);
    });

    // Boton para limpiar busqueda
    $('#btnLimpiarBusqueda').on('click', function() {
        $busquedaInsumo.val('');
        aplicarFiltros();
        $busquedaInsumo.trigger('focus');
    });

    // Botones de filtro por tipo (Solo Bases y Simples)
    const $contenedorFiltrosFraccion = $('#filtrosFraccionBase');
    const $botonesFiltro = $('.btn-filtro-tipo');
    const $botonesFraccion = $('.btn-filtro-fraccion');

    $botonesFiltro.on('click', function() {
        $botonesFiltro.removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
        tipoFiltroActual = $(this).attr('data-tipo');

        if (tipoFiltroActual === '2') {
            $contenedorFiltrosFraccion.addClass('d-none');
            fraccionFiltroActual = 'todos';
            resetBotonesFraccion();
        } else {
            $contenedorFiltrosFraccion.removeClass('d-none');
        }

        aplicarFiltros();
    });

    function resetBotonesFraccion() {
        $botonesFraccion.removeClass('btn-primary btn-outline-primary active').addClass('btn-outline-secondary');
        $botonesFraccion.filter('[data-fraccion="todos"]').removeClass('btn-outline-secondary').addClass('btn-outline-primary active');
    }

    $botonesFraccion.on('click', function() {
        $botonesFraccion.removeClass('btn-primary btn-outline-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-outline-primary active');
        fraccionFiltroActual = $(this).attr('data-fraccion');
        aplicarFiltros();
    });

    // Evento para enviar el formulario de presupuesto
    $formularioPresupuesto.on('submit', function(evento) {
        evento.preventDefault();
        guardarPresupuesto();
    });

    // Cargar clientes desde el servidor
    function cargarClientes() {
        const avisoError = '<option value="">No se pudieron cargar los clientes. Recargue la página</option>';
        api.get('presupuesto/obtenerClientesAjax')
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    clientesDisponibles = resultado.datos.clientes || [];
                    llenarSelectClientes();
                } else {
                    console.error('Error del servidor:', resultado.mensaje);
                    $clientePresupuesto.html(avisoError);
                }
            })
            .catch(function(error) {
                console.error('No se pudieron cargar los clientes. Recargue la página:', error);
                $clientePresupuesto.html(avisoError);
            });
    }

    // Cargar insumos desde el servidor
    function cargarInsumos() {
        const avisoError = '<div class="text-center text-danger py-4">No se pudo cargar el catálogo de productos. Recargue la página</div>';
        api.get('presupuesto/obtenerInsumosAjax')
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    // Excluir cualquier producto con id_tipo_producto == 3 (Preparados no se venden directos)
                    insumosDisponibles = (resultado.datos.insumos || []).filter(function(i) {
                        return parseInt(i.id_tipo_producto) !== 3;
                    });
                    aplicarFiltros();
                } else {
                    console.error('Error del servidor:', resultado.mensaje);
                    $listaInsumosDisponibles.html(avisoError);
                }
            })
            .catch(function(error) {
                console.error('No se pudo cargar el catálogo de productos. Recargue la página:', error);
                $listaInsumosDisponibles.html(avisoError);
            });
    }

    // Llena el select de clientes
    function llenarSelectClientes() {
        $clientePresupuesto.html('<option value="">Seleccione un cliente...</option>');
        if (clientesDisponibles.length === 0) {
            $clientePresupuesto.append('<option value="" disabled>No hay clientes. Registre uno en el módulo Clientes.</option>');
            return;
        }

        $.each(clientesDisponibles, function(i, cliente) {
            $clientePresupuesto.append($('<option></option>').val(cliente.id_cliente).text(cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos));
        });
    }

    // Aplica busqueda y filtro por tipo simultaneamente
    function aplicarFiltros() {
        const texto = ($busquedaInsumo.val() || '').trim().toLowerCase();

        const filtrados = insumosDisponibles.filter(function(insumo) {
            // Filtro por texto
            const coincideTexto = !texto ||
                (insumo.nombre && insumo.nombre.toLowerCase().indexOf(texto) !== -1) ||
                (insumo.codigo && insumo.codigo.toLowerCase().indexOf(texto) !== -1) ||
                (insumo.rubro_nombre && insumo.rubro_nombre.toLowerCase().indexOf(texto) !== -1);

            // Filtro por tipo
            let coincideTipo = true;
            if (tipoFiltroActual !== 'todos') {
                coincideTipo = (parseInt(insumo.id_tipo_producto) === parseInt(tipoFiltroActual));
            }

            // Filtro por fraccion / presentacion (funciona en Todos y en Bases)
            let coincideFraccion = true;
            if (fraccionFiltroActual !== 'todos') {
                const frac = (insumo.fraccion || '').trim();
                const nom = (insumo.nombre || '').toLowerCase();
                const um = (insumo.unidad_medida || '').toLowerCase();
                if (fraccionFiltroActual === '1') {
                    const esGalon = (parseInt(insumo.id_tipo_producto) === 1) || nom.indexOf('galon') !== -1 || nom.indexOf('galón') !== -1 || um === 'galon' || um === 'galón';
                    coincideFraccion = esGalon && (frac === '1') && (nom.indexOf('1/') === -1);
                } else {
                    coincideFraccion = (frac === fraccionFiltroActual);
                }
            }

            return coincideTexto && coincideTipo && coincideFraccion;
        });

        mostrarInsumosDisponibles(filtrados);
    }

    function estaAgregado(idInsumo) {
        return itemsPresupuesto.some(function(item) {
            return !item.es_mezcla && item.id_insumo === idInsumo;
        });
    }

    // Muestra la lista de insumos en el catalogo
    function mostrarInsumosDisponibles(insumos) {
        $contadorInsumos.text(insumos.length + ' disponible' + (insumos.length === 1 ? '' : 's'));
        $listaInsumosDisponibles.empty();

        if (insumos.length === 0) {
            $listaInsumosDisponibles.html(
                '<div class="text-center text-muted py-5">' +
                    '<i class="bi bi-search fs-2 d-block mb-2 text-secondary opacity-50"></i>' +
                    '<span class="fw-semibold">No se encontraron productos</span>' +
                    '<div class="small text-muted mt-1">Pruebe con otro término o cambie el filtro de tipo</div>' +
                '</div>');
            return;
        }

        $.each(insumos, function(i, insumo) {
            const yaAgregado = estaAgregado(insumo.id_insumo);

            // Badge de tipo con maximo contraste
            const tipoId = parseInt(insumo.id_tipo_producto);
            const tipoNom = tipoId === 1 ? 'Base' : 'Simple';
            const badgeTipoClass = tipoId === 1 ? 'badge-tipo-base' : 'badge-tipo-simple';
            const iconTipo = tipoId === 1 ? 'bi-droplet-half' : 'bi-box-seam';

            // Indicador de stock físico y comprometido
            const stockVal = Math.floor(parseFloat(insumo.stock_actual) || 0);
            const stockMin = Math.floor(parseFloat(insumo.stock_minimo) || 0);
            const stockComp = Math.floor(parseFloat(insumo.stock_comprometido) || 0);
            const stockLibre = Math.max(0, stockVal - stockComp);
            const sinStockFisico = stockVal <= 0;

            const alerta = (stockVal <= stockMin || stockLibre <= 0);
            const stockCls = alerta ? 'catalogo-stock-alerta' : 'catalogo-stock-ok';
            const stockIcon = alerta ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
            const stockTexto = (stockVal <= stockMin ? 'Stock bajo: ' : 'Stock: ') + stockVal;

            let badgeComprometidoHtml = '';
            if (stockComp > 0) {
                const agotadoPorCompromiso = stockLibre <= 0;
                badgeComprometidoHtml =
                    '<span class="badge ' + (agotadoPorCompromiso ? 'bg-danger text-white' : 'bg-warning text-dark') + ' border" style="font-size: 0.7rem;" title="Unidades en presupuestos pendientes de aprobar">' +
                        '<i class="bi bi-clock-history me-1"></i>Comprometido: ' + stockComp + (agotadoPorCompromiso ? ' (Todo reservado)' : '') +
                    '</span>';
            }

            let botonHtml;
            if (yaAgregado) {
                botonHtml = HTML_BOTON_AGREGADO;
            } else if (sinStockFisico) {
                botonHtml = '<button type="button" class="btn btn-sm btn-outline-danger btn-catalogo-add disabled" disabled><i class="bi bi-slash-circle me-1"></i>Agotado</button>';
            } else {
                botonHtml = HTML_BOTON_AGREGAR;
            }

            $('<div></div>')
                .addClass('catalogo-card-item' + (yaAgregado ? ' item-agregado' : ''))
                .attr({ id: 'catalogo-item-' + insumo.id_insumo, 'data-id': insumo.id_insumo })
                .html(
                    '<div class="catalogo-info">' +
                        '<div class="d-flex align-items-center gap-1 mb-1">' +
                            '<span class="catalogo-codigo">' + escapeHtml(insumo.codigo) + '</span>' +
                            '<span class="' + badgeTipoClass + '"><i class="bi ' + iconTipo + ' me-1"></i>' + tipoNom + '</span>' +
                        '</div>' +
                        '<div class="catalogo-nombre" title="' + escapeHtml(insumo.nombre) + '">' + escapeHtml(insumo.nombre) + '</div>' +
                        '<div class="catalogo-meta flex-wrap">' +
                            '<span class="catalogo-stock-pill ' + stockCls + '"><i class="bi ' + stockIcon + '"></i>' + stockTexto + '</span>' +
                            badgeComprometidoHtml +
                            (insumo.rubro_nombre ? '<span class="text-muted small"><i class="bi bi-tag me-1"></i>' + escapeHtml(insumo.rubro_nombre) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="catalogo-precio-box">' +
                        '<span class="catalogo-precio-val">$ ' + formatearMoneda(insumo.precio_venta) + '</span>' +
                        botonHtml +
                    '</div>')
                .appendTo($listaInsumosDisponibles);
        });
    }

    // Botón "Agregar" del catálogo (delegado: las tarjetas se redibujan al filtrar)
    $listaInsumosDisponibles.on('click', '.btn-agregar', function() {
        const id = String($(this).closest('.catalogo-card-item').attr('data-id'));
        const insumo = insumosDisponibles.find(function(i) { return String(i.id_insumo) === id; });
        if (insumo) agregarItem(insumo);
    });

    // Agrega un insumo del catalogo a la tabla de items
    function agregarItem(insumo) {
        if (estaAgregado(insumo.id_insumo)) {
            mostrarNotificacion('Este insumo ya fue agregado', 'error');
            return;
        }

        const stockFisico = Math.floor(parseFloat(insumo.stock_actual) || 0);
        const stockComprometido = Math.floor(parseFloat(insumo.stock_comprometido) || 0);
        const stockLibre = Math.max(0, stockFisico - stockComprometido);

        if (stockFisico <= 0) {
            mostrarNotificacion('No hay stock físico disponible de "' + insumo.nombre + '".', 'error');
            return;
        }

        if (stockComprometido > 0 && (1 > stockLibre)) {
            mostrarNotificacion(
                '¡Advertencia! "' + insumo.nombre + '" ya tiene ' + stockComprometido +
                ' unidad(es) comprometida(s) en presupuestos pendientes de aprobación (Stock físico total: ' + stockFisico + ').',
                'error'
            );
        }

        itemsPresupuesto.push({
            es_mezcla: false,
            id_insumo: insumo.id_insumo,
            insumo_codigo: insumo.codigo,
            insumo_nombre: insumo.nombre,
            id_tipo_producto: insumo.id_tipo_producto,
            tipo_producto_nombre: parseInt(insumo.id_tipo_producto) === 1 ? 'Base' : 'Simple',
            stock_actual: stockFisico,
            stock_comprometido: stockComprometido,
            cantidad: 1,
            precio_unitario: parseFloat(insumo.precio_venta) || 0,
            subtotal: parseFloat(insumo.precio_venta) || 0
        });
        actualizarTablaItems();

        // Actualizar visualmente la tarjeta del catalogo
        const $card = $('#catalogo-item-' + insumo.id_insumo);
        $card.addClass('item-agregado');
        $card.find('.catalogo-precio-box .btn-catalogo-add').replaceWith(HTML_BOTON_AGREGADO);
    }

    // Texto "Equiv: Bs." bajo el total si hay tasa del día
    function pintarTotal(total) {
        const tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        if (tasa > 0) {
            $totalPresupuesto.html('$ ' + formatearMoneda(total) + '<div class="text-white-50 fw-normal mt-1" style="font-size: 0.85rem;">Equiv: Bs. ' + formatearMoneda(total * tasa) + '</div>');
        } else {
            $totalPresupuesto.text('$ ' + formatearMoneda(total));
        }
    }

    // Actualiza la tabla de items y recalcula totales
    function actualizarTablaItems() {
        if (!$cuerpoTablaItems.length || !$totalPresupuesto.length) return;

        // Ocultar fila vacia si hay items y limpiar filas anteriores
        $filaVacia.css('display', itemsPresupuesto.length > 0 ? 'none' : '');
        $cuerpoTablaItems.children('tr').not('#filaVacia').remove();

        let total = 0;
        const cantidadTotalItems = itemsPresupuesto.length;

        // Renderizar cada item
        $.each(itemsPresupuesto, function(indice, item) {
            const $fila = $('<tr></tr>');

            if (item.es_mezcla) {
                // Renglón de Pintura Preparada (Mezcla de Colorimetría)
                $fila.html(
                    '<td>' +
                        '<div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="' + escapeHtml(item.insumo_nombre) + '">' +
                            '<i class="bi bi-palette-fill text-success me-1"></i>' + escapeHtml(item.insumo_nombre) +
                        '</div>' +
                        '<div class="small text-muted text-truncate" style="max-width: 250px;" title="' + escapeHtml(item.resumen_formula || '') + '">' +
                            '<strong>' + escapeHtml(item.presentacion || '') + '</strong>: ' + escapeHtml(item.resumen_formula || '') +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-1 mt-1">' +
                            '<span class="badge bg-success" style="font-size: 0.68rem; padding: 0.25em 0.5em;"><i class="bi bi-palette-fill me-1"></i>Preparado</span>' +
                            '<span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">' + escapeHtml(item.presentacion) + '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<input type="number" class="form-control form-control-sm item-presupuesto-cantidad mx-auto" ' +
                               'value="' + item.cantidad + '" min="1" step="1" data-indice="' + indice + '">' +
                    '</td>' +
                    '<td class="text-end">' +
                        '<input type="number" class="form-control form-control-sm item-presupuesto-precio ms-auto" ' +
                               'value="' + item.precio_unitario.toFixed(2) + '" min="0.01" step="0.01" data-indice="' + indice + '">' +
                    '</td>' +
                    '<td class="text-end">' +
                        '<span class="item-presupuesto-subtotal fw-semibold text-primary">$ ' + formatearMoneda(item.subtotal) + '</span>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<button type="button" class="btn btn-sm btn-light text-danger btn-quitar-item" data-indice="' + indice + '" title="Quitar mezcla">' +
                            '<i class="bi bi-trash-fill"></i>' +
                        '</button>' +
                    '</td>');
            } else {
                // Renglón de Producto Directo (Simple o Base pura)
                const tipoId = parseInt(item.id_tipo_producto);
                const tipoNom = tipoId === 1 ? 'Base' : 'Simple';
                const badgeCls = tipoId === 1 ? 'badge-tipo-base' : 'badge-tipo-simple';
                const maxStock = item.stock_actual || 1;
                const stockComp = item.stock_comprometido || 0;
                const stockLibre = Math.max(0, maxStock - stockComp);
                const superaComprometido = (stockComp > 0 && item.cantidad > stockLibre);

                $fila.html(
                    '<td>' +
                        '<div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="' + escapeHtml(item.insumo_nombre) + '">' + escapeHtml(item.insumo_nombre) + '</div>' +
                        '<div class="d-flex align-items-center flex-wrap gap-1 mt-1">' +
                            '<span class="badge bg-light text-muted border" style="font-size: 0.7rem;">' + escapeHtml(item.insumo_codigo) + '</span>' +
                            '<span class="' + badgeCls + '" style="font-size: 0.68rem; padding: 0.2em 0.5em;">' + tipoNom + '</span>' +
                            '<span class="badge bg-light text-dark border" style="font-size: 0.68rem;">Máx: ' + maxStock + '</span>' +
                        '</div>' +
                        '<div class="aviso-comprometido-item small text-danger fw-semibold mt-1 ' + (superaComprometido ? '' : 'd-none') + '" style="font-size: 0.72rem;">' +
                            '<i class="bi bi-exclamation-triangle-fill me-1"></i>¡Comprometido en otro presupuesto! (' + stockComp + ' reservadas, ' + stockLibre + ' libres)' +
                        '</div>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<input type="number" class="form-control form-control-sm item-presupuesto-cantidad mx-auto" ' +
                               'value="' + item.cantidad + '" min="1" max="' + maxStock + '" step="1" data-indice="' + indice + '">' +
                    '</td>' +
                    '<td class="text-end">' +
                        '<span class="fw-semibold text-secondary">$ ' + formatearMoneda(item.precio_unitario) + '</span>' +
                    '</td>' +
                    '<td class="text-end">' +
                        '<span class="item-presupuesto-subtotal fw-bold text-dark">$ ' + formatearMoneda(item.subtotal) + '</span>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<button type="button" class="btn btn-sm btn-light text-danger btn-quitar-item" data-indice="' + indice + '" title="Quitar item">' +
                            '<i class="bi bi-trash-fill"></i>' +
                        '</button>' +
                    '</td>');
            }

            $fila.insertBefore($filaVacia);
            total += item.subtotal;
        });

        // Actualizar badges y totales
        const textoItems = cantidadTotalItems + ' item' + (cantidadTotalItems === 1 ? '' : 's');
        $badgeCantidadItems.text(textoItems);
        $resumenConteoItems.text(textoItems + ' en el presupuesto');
        pintarTotal(total);
    }

    // Cantidad: solo enteros y respetando el límite de stock (delegado sobre la tabla)
    $cuerpoTablaItems.on('keydown', '.item-presupuesto-cantidad', function(e) {
        if (['.', ',', 'e', 'E', '-', '+'].includes(e.key)) {
            e.preventDefault();
        }
    });

    $cuerpoTablaItems.on('input', '.item-presupuesto-cantidad', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        const it = itemsPresupuesto[parseInt($(this).attr('data-indice'))];
        let val = parseInt(this.value, 10);
        if (!it) return;

        if (!isNaN(val) && val >= 1) {
            if (!it.es_mezcla && it.stock_actual && val > it.stock_actual) {
                val = it.stock_actual;
                this.value = val;
                mostrarNotificacion('No puede superar el límite de stock disponible (' + it.stock_actual + ' unidades).', 'error');
            }

            if (!it.es_mezcla && it.stock_comprometido > 0) {
                const libre = Math.max(0, it.stock_actual - it.stock_comprometido);
                const $aviso = $(this).closest('tr').find('.aviso-comprometido-item');
                $aviso.toggleClass('d-none', val <= libre);
                if (val > libre) {
                    mostrarNotificacion(
                        '¡Atención! "' + it.insumo_nombre + '" tiene ' + it.stock_comprometido +
                        ' unidad(es) comprometida(s) en otros presupuestos pendientes (Libres: ' + libre + ').',
                        'error'
                    );
                }
            }

            it.cantidad = val;
            it.subtotal = val * it.precio_unitario;
            $(this).closest('tr').find('.item-presupuesto-subtotal').text('$ ' + formatearMoneda(it.subtotal));
            recalcularTotalGlobal();
        }
    });

    // Quitar item de la tabla
    $cuerpoTablaItems.on('click', '.btn-quitar-item', function() {
        const idx = parseInt($(this).attr('data-indice'));
        const itemEliminado = itemsPresupuesto[idx];
        itemsPresupuesto.splice(idx, 1);
        actualizarTablaItems();

        // Restaurar boton en catalogo si era un producto simple
        if (itemEliminado && !itemEliminado.es_mezcla) {
            const $card = $('#catalogo-item-' + itemEliminado.id_insumo);
            $card.removeClass('item-agregado');
            $card.find('.catalogo-precio-box .btn-catalogo-add').remove();
            $card.find('.catalogo-precio-box').append(HTML_BOTON_AGREGAR);
        }
    });

    // Recalcula solo el total global sumando todos los subtotales
    function recalcularTotalGlobal() {
        pintarTotal(itemsPresupuesto.reduce(function(acc, item) { return acc + item.subtotal; }, 0));
    }

    // ==========================================
    // ENVÍO DE FORMULARIO DE PRESUPUESTO
    // ==========================================

    function guardarPresupuesto() {
        if (!$clientePresupuesto.val()) {
            mostrarError('Debe seleccionar un cliente');
            $clientePresupuesto.trigger('focus');
            return;
        }

        if (itemsPresupuesto.length === 0) {
            mostrarError('Debe agregar al menos un item o formulación al presupuesto');
            return;
        }

        // Deshabilitar boton para prevenir doble envio
        $btnGuardarPresupuesto.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');

        const restaurarBoton = function() {
            $btnGuardarPresupuesto.prop('disabled', false).html(HTML_BOTON_GUARDAR);
        };

        const formData = new FormData();
        formData.append('id_cliente', $clientePresupuesto.val());
        formData.append('observaciones', $('#observacionesPresupuesto').val());

        const itemsSimplificados = itemsPresupuesto.map(function(item) {
            return {
                id_insumo: item.id_insumo,
                descripcion: item.insumo_nombre,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario
            };
        });
        formData.append('items', JSON.stringify(itemsSimplificados));

        api.post('presupuesto/guardar', formData)
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion('Presupuesto #' + resultado.datos.id_presupuesto + ' creado exitosamente. Total: $ ' + formatearMoneda(resultado.datos.total), 'exito');
                setTimeout(function() {
                    window.location.href = '/SP%20Perfect%20Color/presupuesto';
                }, 1200);
            } else {
                mostrarError(resultado.mensaje);
                restaurarBoton();
            }
        })
        .catch(function(error) {
            console.error('Error al guardar presupuesto:', error);
            mostrarError('No se pudo guardar el presupuesto. Revise su conexión e intente de nuevo.');
            restaurarBoton();
        });
    }

    // Muestra un mensaje de error en el formulario
    function mostrarError(mensaje) {
        if (!$mensajeError.length) {
            mostrarNotificacion(mensaje, 'error');
            return;
        }
        $mensajeError.attr('role', 'alert').text(mensaje).removeClass('d-none');
        $mensajeError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
