// Archivo: notaEntregaCompra.js
// Manejo de Notas de Entrega de Compra a Proveedores (Recepción de Insumos, aumento de stock y CxP)

$(function() {
    const FORMATO_MONEDA = { minimumFractionDigits: 2, maximumFractionDigits: 2 };
    const fmt = function(n) { return n.toLocaleString('es-VE', FORMATO_MONEDA); };

    // === GESTIÓN DE LISTADO (DATATABLES) ===
    if ($('#tablaNotasCompra').length) {
        cargarNotasCompra();

        vincularBusqueda('#busquedaNotasCompra', '#tablaNotasCompra');

        $(document).on('click', '.btn-anular-nota-compra', function(e) {
            e.preventDefault();
            const id = $(this).attr('data-id');
            if (!id) return;

            confirmarConModal('Anular Recepción de Compra', '¿Está seguro de anular esta nota de entrega? Se revertirá el incremento de stock de los insumos asociados.', function() {
                ejecutarAnulacion(id);
            });
        });
    }

    function ejecutarAnulacion(id) {
        api.post('notaEntregaCompra/anular', { id: id })
        .then(function(res) {
            if (res.estado === 'exito') {
                mostrarNotificacion(res.mensaje, 'exito');
                cargarNotasCompra();
            } else {
                mostrarNotificacion(res.mensaje || 'Error al anular', 'error');
            }
        })
        .catch(function() {
            mostrarNotificacion('No se pudo conectar con el servidor. Intente de nuevo.', 'error');
        });
    }

    function cargarNotasCompra() {
        api.get('notaEntregaCompra/listarAjax')
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarNotasCompra(resultado.datos.notas || []);
                }
            })
            .catch(function(err) {
                console.error('[NotaEntregaCompra] Error al listar:', err);
            });
    }

    function mostrarNotasCompra(notas) {
        if (!$.fn.DataTable.isDataTable('#tablaNotasCompra')) {
            $('#tablaNotasCompra').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH || {},
                order: [[0, 'desc']],
                columns: [
                    {
                        data: 'id_compra',
                        render: function(data) {
                            return '<strong class="text-primary">#' + String(data).padStart(5, '0') + '</strong>';
                        }
                    },
                    {
                        data: 'fecha',
                        render: function(data) {
                            if (!data) return '-';
                            const d = new Date(data.replace(' ', 'T'));
                            if (isNaN(d.getTime())) return data;
                            return d.toLocaleDateString('es-VE') + ' ' + d.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
                        }
                    },
                    {
                        data: 'proveedor_nombre',
                        render: function(data) {
                            return '<strong>' + (data || '-') + '</strong>';
                        }
                    },
                    {
                        data: 'proveedor_rif',
                        render: function(data) {
                            return '<span class="badge bg-secondary font-monospace">' + (data || '-') + '</span>';
                        }
                    },
                    {
                        data: 'total',
                        render: function(data, type, row) {
                            const num = parseFloat(data || 0);
                            const tasa = parseFloat(row.tasa_bcv || window.TASA_CAMBIO_HOY || 0);
                            let html = '<strong>$ ' + fmt(num) + '</strong>';
                            if (tasa > 0) {
                                html += '<div class="text-muted small">Bs. ' + fmt(num * tasa) + '</div>';
                            }
                            return html;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const esCredito = row.id_cuenta_pagar && (parseFloat(row.saldo_pendiente) > 0 || row.condicion_compra === 'credito');
                            return esCredito
                                ? '<span class="badge bg-warning text-dark">Crédito</span>'
                                : '<span class="badge bg-success">Contado</span>';
                        }
                    },
                    {
                        data: 'situacion_pago',
                        render: function(data) {
                            if (data === 'pagado') {
                                return '<span class="badge bg-success">Pagado</span>';
                            }
                            return '<span class="badge bg-danger">Pendiente CxP</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/notaEntregaCompra/ver?id=' + row.id_compra + '" class="btn btn-sm btn-info text-white" title="Ver Detalle">' +
                                '<i class="bi bi-eye"></i>' +
                                '</a>' +
                                (row.id_cuenta_pagar ? '' :
                                    '<button type="button" class="btn btn-sm btn-outline-danger btn-anular-nota-compra" data-id="' + row.id_compra + '" title="Anular Recepción" aria-label="Anular Recepción">' +
                                    '<i class="bi bi-x-circle"></i>' +
                                    '</button>') +
                                '</div>';
                        }
                    }
                ]
            });
        }

        const table = $('#tablaNotasCompra').DataTable();
        table.clear();
        $.each(notas, function(i, n) {
            table.row.add(n);
        });
        table.draw();
    }

    // === GESTIÓN DE FORMULARIO DE REGISTRO ===
    const $formNotaCompra = $('#formNotaCompra');
    if (!$formNotaCompra.length) return;

    const $condicionSelect = $('#condicionPago');
    const $campoVencimiento = $('#campoVencimiento');
    const $camposContado = $('#camposContado');
    const $selectProducto = $('#selectProducto');
    const $cantidadInput = $('#cantidadItem');
    const $precioInput = $('#precioItem');
    const $cuerpoDetalle = $('#cuerpoDetalleCompra');
    const $totalDisplay = $('#totalCompraDisplay');
    const $totalBsDisplay = $('#totalCompraBsDisplay');
    const $alertaError = $('#alertaErrorCompra');

    const $inputBuscarProd = $('#inputBuscarProductoCompra');
    const $btnLimpiarProd = $('#btnLimpiarProductoCompra');
    const $listaSugerenciasProd = $('#listaSugerenciasProductoCompra');
    const $idTipoPagoSelect = $('#idTipoPago');

    // Campos de Abono Inicial (Crédito)
    const $montoInicialInput = $('#montoInicialCompra');
    const $idTipoPagoInicialSelect = $('#idTipoPagoInicialCompra');

    const itemsCompra = [];
    let totalActualCompra = 0.00;
    const catalogoInsumos = Array.isArray(window.insumosCompraCatalogo) ? window.insumosCompraCatalogo : [];

    // Bloquear decimales/puntos en cantidadItem y rapidoStockMinimo
    $cantidadInput.add($('#rapidoStockMinimo')).on('keydown', function(e) {
        if (['.', ',', 'e', 'E', '-', '+'].includes(e.key)) {
            e.preventDefault();
        }
    }).on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Muestra banco y referencia salvo que el método sea efectivo (1) o no esté elegido (0)
    function toggleBancoRef($tipoPago, selectores) {
        if (!$tipoPago.length) return;
        const val = parseInt($tipoPago.val() || 0);
        $(selectores).css('display', (val === 1 || val === 0) ? 'none' : 'block');
    }

    // Toggle banco y referencia según método de pago Contado (solo si no es efectivo)
    function toggleBancoRefCompra() {
        toggleBancoRef($idTipoPagoSelect, '#contenedorBancoCompra, #contenedorReferenciaCompra');
    }
    $idTipoPagoSelect.on('change', toggleBancoRefCompra);
    toggleBancoRefCompra();

    // Toggle banco y referencia para Abono Inicial en Crédito
    function toggleBancoRefInicialCompra() {
        toggleBancoRef($idTipoPagoInicialSelect, '#contenedorBancoInicialCompra, #contenedorRefInicialCompra');
    }
    $idTipoPagoInicialSelect.on('change', toggleBancoRefInicialCompra);
    toggleBancoRefInicialCompra();

    function actualizarCalculoAbonoInicial() {
        const tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        let abono = parseFloat($montoInicialInput.val()) || 0;
        if (abono < 0) abono = 0;
        if (totalActualCompra > 0 && abono > totalActualCompra) {
            abono = totalActualCompra;
            $montoInicialInput.val(abono.toFixed(2));
        }
        const saldo = Math.max(0, totalActualCompra - abono);

        $('#saldoPendienteCompraDisplay').text('$ ' + fmt(saldo));
        $('#montoInicialCompraBsDisplay').text('Equiv: Bs. ' + fmt(abono * tasa));
        $('#saldoPendienteCompraBsDisplay').text('(Bs. ' + fmt(saldo * tasa) + ')');

        const $contenedorMetodoInicial = $('#contenedorMetodoInicialCompra');
        if ($contenedorMetodoInicial.length) {
            if (abono > 0) {
                $contenedorMetodoInicial.css('display', 'block');
                toggleBancoRefInicialCompra();
            } else {
                $contenedorMetodoInicial.css('display', 'none');
            }
        }
    }

    $montoInicialInput.on('keydown', function(e) {
        if (['e', 'E', '+', '-'].includes(e.key)) {
            e.preventDefault();
        }
    });
    $montoInicialInput.on('input', function() {
        let val = this.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
        const puntos = val.split('.');
        if (puntos.length > 2) {
            val = puntos[0] + '.' + puntos.slice(1).join('');
        }
        const partes = val.split('.');
        if (partes[0].length > 7) {
            partes[0] = partes[0].substring(0, 7);
        }
        if (partes.length > 1 && partes[1].length > 2) {
            partes[1] = partes[1].substring(0, 2);
        }
        this.value = partes.length > 1 ? partes[0] + '.' + partes[1] : partes[0];
        actualizarCalculoAbonoInicial();
    });

    const $contadorObsCompra = $('#contadorObservacionesCompra');
    $('#observaciones').on('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\:\;\#\/]/g, '').substring(0, 250);
        const palabras = this.value.trim().split(/\s+/).filter(Boolean);
        if (palabras.length > 50) {
            this.value = palabras.slice(0, 50).join(' ');
        }
        const countP = this.value.trim().split(/\s+/).filter(Boolean).length;
        $contadorObsCompra.text(this.value.length + ' / 250 caracteres (' + countP + ' / 50 palabras)');
    });

    // Búsqueda integrada de productos con diseño limpio .buscador-dropdown-item
    if ($inputBuscarProd.length && $selectProducto.length && $listaSugerenciasProd.length) {
        const renderizarSugerenciasCompra = function(filtro) {
            $listaSugerenciasProd.empty();
            const q = (filtro || '').toLowerCase().trim();
            const filtrados = catalogoInsumos.filter(function(ins) {
                if (!q) return true;
                return (escapeHtml(ins.codigo || '') + ' ' + escapeHtml(ins.nombre || '')).toLowerCase().includes(q);
            });

            if (filtrados.length === 0) {
                $listaSugerenciasProd.html('<div class="p-3 text-muted small text-center"><i class="bi bi-search me-1"></i>No se encontraron productos</div>');
            } else {
                $.each(filtrados, function(i, ins) {
                    const stock = Math.floor(parseFloat(ins.stock_actual || 0));
                    const pVenta = parseFloat(ins.precio_venta || 0);
                    const pCompra = parseFloat(ins.precio_compra || 0);
                    $('<div class="buscador-dropdown-item"></div>')
                        .html(
                            '<div class="item-titulo">' + escapeHtml(ins.nombre || '') + '</div>' +
                            '<div class="item-meta">' +
                                '<span class="badge-codigo">' + escapeHtml(ins.codigo || '') + '</span>' +
                                '<span class="badge-stock ' + (stock <= 0 ? 'sin-stock' : '') + '">Stock actual: ' + stock + '</span>' +
                                '<span class="badge-precio">P. Venta: $ ' + pVenta.toFixed(2) + '</span>' +
                            '</div>')
                        .on('click', function(ev) {
                            ev.preventDefault();
                            $inputBuscarProd.val(ins.codigo + ' - ' + ins.nombre);
                            $selectProducto.val(ins.id_producto).attr({
                                'data-nombre': ins.nombre,
                                'data-codigo': ins.codigo,
                                'data-precio': pCompra > 0 ? pCompra.toFixed(2) : ''
                            });
                            $precioInput.val(pCompra > 0 ? pCompra.toFixed(2) : '');
                            $btnLimpiarProd.removeClass('d-none');
                            $listaSugerenciasProd.removeClass('show');
                            $cantidadInput.trigger('focus');
                        })
                        .appendTo($listaSugerenciasProd);
                });
            }
            $listaSugerenciasProd.addClass('show');
        };

        $inputBuscarProd.on('focus', function() {
            renderizarSugerenciasCompra(this.value);
        });

        $inputBuscarProd.on('input', function() {
            if (!this.value.trim()) {
                $selectProducto.val('');
                $btnLimpiarProd.addClass('d-none');
            }
            renderizarSugerenciasCompra(this.value);
        });

        $btnLimpiarProd.on('click', function() {
            $inputBuscarProd.val('');
            $selectProducto.val('');
            $precioInput.val('');
            $btnLimpiarProd.addClass('d-none');
            $listaSugerenciasProd.removeClass('show');
            $inputBuscarProd.trigger('focus');
        });

        $(document).on('click', function(e) {
            if (!$.contains($inputBuscarProd.closest('.position-relative')[0], e.target)) {
                $listaSugerenciasProd.removeClass('show');
            }
        });
    }

    // Cambio de condición Contado / Crédito
    $condicionSelect.on('change', function() {
        if (this.value === 'credito') {
            $campoVencimiento.removeClass('d-none');
            $camposContado.addClass('d-none');
            actualizarCalculoAbonoInicial();
        } else {
            $campoVencimiento.addClass('d-none');
            $camposContado.removeClass('d-none');
            toggleBancoRefCompra();
        }
    });

    // Botón añadir producto
    $('#btnAgregarItem').on('click', function() {
        ocultarError();
        const idProd = parseInt($selectProducto.val(), 10);
        const cant = parseInt($cantidadInput.val(), 10);
        const precio = parseFloat($precioInput.val());

        if (!idProd || isNaN(idProd)) {
            mostrarError('Seleccione un producto de la lista.');
            $inputBuscarProd.trigger('focus');
            return;
        }
        if (isNaN(cant) || cant < 1) {
            mostrarError('Ingrese una cantidad entera válida (mínimo 1).');
            $cantidadInput.trigger('focus');
            return;
        }
        if (isNaN(precio) || precio < 0) {
            mostrarError('Ingrese un costo unitario válido.');
            $precioInput.trigger('focus');
            return;
        }

        const nombreProd = $selectProducto.attr('data-nombre') || $inputBuscarProd.val();
        const codigoProd = $selectProducto.attr('data-codigo') || '';

        // Verificar si ya existe en la lista
        const existente = itemsCompra.find(function(it) { return it.id_producto === idProd; });
        if (existente) {
            existente.cantidad += cant;
            existente.precio_compra = precio;
            existente.subtotal = Math.round(existente.cantidad * existente.precio_compra * 100) / 100;
        } else {
            itemsCompra.push({
                id_producto: idProd,
                codigo: codigoProd,
                nombre: nombreProd,
                cantidad: cant,
                precio_compra: precio,
                subtotal: Math.round(cant * precio * 100) / 100
            });
        }

        // Limpiar campos de ítem
        $selectProducto.val('');
        $inputBuscarProd.val('');
        $btnLimpiarProd.addClass('d-none');
        $cantidadInput.val('');
        $precioInput.val('');

        renderizarTablaDetalle();
    });

    // Gestión del modal de nuevo insumo rápido
    const $formRapidoInsumo = $('#formularioRapidoInsumo');
    const $rapidoUnidad = $('#rapidoUnidad');
    $('#rapidoTipo').on('change', function() {
        $rapidoUnidad.html(this.value === '1'
            ? '<option value="Galon">Galón</option>'
            : '<option value="Unidad">Unidad</option><option value="KG">KG</option>');
    });

    const HTML_BOTON_RAPIDO = '<i class="bi bi-check-lg me-1"></i>Crear e Insertar en Compra';
    $formRapidoInsumo.on('submit', function(e) {
        e.preventDefault();
        const $alertaRapida = $('#alertaErrorRapidoInsumo').addClass('d-none');

        const fd = new FormData($formRapidoInsumo[0]);
        const $btnGuardar = $('#btnGuardarRapidoInsumo');
        $btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');

        api.post('inventario/guardar', fd)
        .then(function(res) {
            $btnGuardar.prop('disabled', false).html(HTML_BOTON_RAPIDO);

            if (res.estado === 'exito') {
                const prodNuevo = res.datos || {};
                const costoEst = parseFloat(fd.get('precio_compra') || 0);
                catalogoInsumos.push({
                    id_producto: prodNuevo.id_producto,
                    codigo: prodNuevo.codigo,
                    nombre: prodNuevo.nombre,
                    stock_actual: 0,
                    precio_compra: costoEst
                });

                $selectProducto.val(prodNuevo.id_producto).attr({
                    'data-codigo': prodNuevo.codigo,
                    'data-nombre': prodNuevo.nombre,
                    'data-precio': costoEst.toFixed(2)
                });
                $inputBuscarProd.val(prodNuevo.codigo + ' - ' + prodNuevo.nombre);
                $btnLimpiarProd.removeClass('d-none');
                $precioInput.val(costoEst > 0 ? costoEst.toFixed(2) : '');
                $cantidadInput.val(1);

                // Cerrar modal
                const modalInstance = bootstrap.Modal.getInstance($('#modalRapidoInsumo')[0]);
                if (modalInstance) modalInstance.hide();
                $formRapidoInsumo[0].reset();

                mostrarNotificacion('Insumo registrado e insertado en la orden', 'exito');
            } else {
                $alertaRapida.text(res.mensaje || 'Error al crear insumo.').removeClass('d-none');
            }
        })
        .catch(function() {
            $btnGuardar.prop('disabled', false).html(HTML_BOTON_RAPIDO);
            $alertaRapida.text('No se pudo conectar con el servidor. Intente de nuevo.').removeClass('d-none');
        });
    });

    // Renderizar tabla de detalle
    function renderizarTablaDetalle() {
        const tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        $cuerpoDetalle.empty();
        if (itemsCompra.length === 0) {
            $cuerpoDetalle.html('<tr id="filaVacia"><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>Añada los insumos recibidos del proveedor usando el formulario superior.</td></tr>');
            totalActualCompra = 0.00;
            $totalDisplay.text('$ 0,00');
            $totalBsDisplay.text('Bs. 0,00');
            actualizarCalculoAbonoInicial();
            return;
        }

        let total = 0;
        $.each(itemsCompra, function(idx, item) {
            total += item.subtotal;
            $('<tr></tr>').html(
                '<td><span class="badge bg-secondary font-monospace me-2">' + escapeHtml(item.codigo) + '</span><strong>' + escapeHtml(item.nombre) + '</strong></td>' +
                '<td class="text-center fw-bold text-primary">+' + Math.floor(item.cantidad) + '</td>' +
                '<td class="text-end">$ ' + fmt(item.precio_compra) + '</td>' +
                '<td class="text-end fw-bold">$ ' + fmt(item.subtotal) + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-item" data-index="' + idx + '"><i class="bi bi-trash"></i></button></td>'
            ).appendTo($cuerpoDetalle);
        });

        totalActualCompra = Math.round(total * 100) / 100;
        $totalDisplay.text('$ ' + fmt(totalActualCompra));
        $totalBsDisplay.text('Bs. ' + fmt(totalActualCompra * tasa));
        actualizarCalculoAbonoInicial();
    }

    // Quitar ítem (delegado: las filas se regeneran en cada render)
    $cuerpoDetalle.on('click', '.btn-quitar-item', function() {
        itemsCompra.splice(parseInt($(this).attr('data-index')), 1);
        renderizarTablaDetalle();
    });

    // Enviar formulario
    $formNotaCompra.on('submit', function(e) {
        e.preventDefault();
        ocultarError();

        if (itemsCompra.length === 0) {
            mostrarError('Debe agregar al menos un producto a la nota de entrega.');
            return;
        }

        if (!$('#idProveedor').val()) {
            mostrarError('Seleccione un proveedor.');
            return;
        }

        // Validar abono inicial si es crédito
        if ($condicionSelect.val() === 'credito' && $montoInicialInput.length) {
            const abonoVal = parseFloat($montoInicialInput.val() || 0);
            if (abonoVal > totalActualCompra) {
                mostrarError('El abono inicial no puede ser mayor al total de la compra.');
                return;
            }
            if (abonoVal > 0 && $idTipoPagoInicialSelect.length) {
                if (parseInt($idTipoPagoInicialSelect.val() || 1) !== 1) {
                    if (!$('#idBancoInicialCompra').val()) {
                        mostrarError('Seleccione el banco para el abono inicial.');
                        return;
                    }
                    if (!/^\d{5}$/.test(($('#referenciaInicialCompra').val() || '').trim())) {
                        mostrarError('La referencia debe tener los últimos 5 dígitos del comprobante.');
                        return;
                    }
                }
            }
        }

        const fd = new FormData($formNotaCompra[0]);
        fd.append('items', JSON.stringify(itemsCompra));

        const $btnSubmit = $('#btnGuardarNotaCompra');
        const HTML_BOTON_REGISTRAR = '<i class="bi bi-check-circle-fill me-2"></i>Registrar Recepción y Sumar Stock';
        $btnSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Registrando y Sumando Stock...');

        api.post('notaEntregaCompra/guardar', fd)
        .then(function(res) {
            if (res.estado === 'exito') {
                mostrarNotificacion(res.mensaje, 'exito');
                setTimeout(function() {
                    window.location.href = '/SP%20Perfect%20Color/notaEntregaCompra/ver?id=' + res.datos.id_compra;
                }, 800);
            } else {
                $btnSubmit.prop('disabled', false).html(HTML_BOTON_REGISTRAR);
                mostrarError(res.mensaje || 'Error al guardar la nota de entrega.');
            }
        })
        .catch(function(err) {
            $btnSubmit.prop('disabled', false).html(HTML_BOTON_REGISTRAR);
            mostrarError('No se pudo conectar con el servidor. Intente de nuevo.');
            console.error(err);
        });
    });

    function mostrarError(msg) {
        $alertaError.text(msg).removeClass('d-none');
    }

    function ocultarError() {
        $alertaError.addClass('d-none').text('');
    }
});
