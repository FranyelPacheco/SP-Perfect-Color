// Archivo: notaEntregaForm.js
// Manejo del formulario de creacion de nota de entrega desde presupuesto

$(function() {
    const $clienteNota = $('#clienteNota');
    const $cuerpoTablaItems = $('#cuerpoTablaItems');
    const $totalNota = $('#totalNota');
    const $mensajeError = $('#mensajeErrorNota');
    const $filaVacia = $('#filaVacia');
    const $fechaVencimiento = $('#fechaVencimiento');
    const $montoInicial = $('#montoInicial');
    const $tipoPagoInicial = $('#tipoPagoInicial');
    const $textoSaldoRestante = $('#textoSaldoRestante');

    const FORMATO_BS = { minimumFractionDigits: 2, maximumFractionDigits: 2 };

    const itemsNota = [];
    let clientesDisponibles = [];

    // Cargar datos iniciales
    cargarClientes();
    cargarTiposPago();
    cargarBancos();

    // Pre-cargar items desde presupuesto si existe detalle
    if (typeof presupuestoDetalle !== 'undefined' && presupuestoDetalle.length > 0) {
        $.each(presupuestoDetalle, function(i, item) {
            itemsNota.push({
                id_presupuesto_detalle: item.id_presupuesto_detalle,
                insumo_codigo: item.insumo_codigo,
                insumo_nombre: item.insumo_nombre,
                stock_actual: parseFloat(item.stock_actual) || 0,
                cantidad: parseFloat(item.cantidad),
                precio_unitario: parseFloat(item.precio_unitario),
                subtotal: parseFloat(item.subtotal)
            });
        });
        actualizarTablaItems();
    }

    // Evento para enviar el formulario
    $('#formularioNotaEntrega').on('submit', function(evento) {
        evento.preventDefault();
        guardarNotaEntrega();
    });

    // Cargar clientes
    function cargarClientes() {
        api.get('notaEntrega/obtenerClientesAjax')
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    clientesDisponibles = resultado.datos.clientes;
                    llenarSelectClientes();
                }
            })
            .catch(function(error) {
                console.error('No se pudieron cargar los clientes. Recargue la página:', error);
            });
    }

    // Opciones <option> a partir de una lista de {id, nombre}
    function opcionesHtml(vacio, lista, campoId) {
        let opts = '<option value="">' + vacio + '</option>';
        $.each(lista, function(i, e) {
            opts += '<option value="' + e[campoId] + '">' + escapeHtml(e.nombre) + '</option>';
        });
        return opts;
    }

    // Cargar tipos de pago
    function cargarTiposPago() {
        api.get('notaEntrega/obtenerTiposPagoAjax')
            .then(function(res) {
                if (res.estado === 'exito') {
                    $('#tipoPago, #tipoPagoInicial').html(opcionesHtml('Seleccione...', res.datos.tipos_pago, 'id_tipo_pago'));
                }
            });
    }

    // Cargar bancos
    function cargarBancos() {
        api.get('notaEntrega/obtenerBancosAjax')
            .then(function(res) {
                if (res.estado === 'exito') {
                    $('#bancoPago, #bancoPagoInicial').html(opcionesHtml('Seleccione un banco...', res.datos.bancos, 'id_banco'));
                }
            });
    }

    function llenarSelectClientes() {
        if (!$clienteNota.length) return;
        $clienteNota.html('<option value="">Seleccione un cliente...</option>');

        $.each(clientesDisponibles, function(i, cliente) {
            $clienteNota.append($('<option></option>').val(cliente.id_cliente).text(cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos));
        });

        if (typeof presupuestoClienteId !== 'undefined' && presupuestoClienteId > 0) {
            $clienteNota.val(presupuestoClienteId).prop('disabled', true);
        }
    }

    function actualizarTablaItems() {
        if (!$cuerpoTablaItems.length || !$totalNota.length) return;

        $filaVacia.css('display', itemsNota.length > 0 ? 'none' : '');
        $cuerpoTablaItems.children('tr').not('#filaVacia').remove();

        let total = 0;

        $.each(itemsNota, function(i, item) {
            $('<tr></tr>').html(
                '<td><span class="badge bg-light text-secondary border font-monospace">' + escapeHtml(item.insumo_codigo) + '</span></td>' +
                '<td class="fw-semibold">' + escapeHtml(item.insumo_nombre) + '</td>' +
                '<td class="text-secondary">' + Math.floor(item.stock_actual) + '</td>' +
                '<td><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-6">' + Math.floor(item.cantidad) + '</span></td>' +
                '<td>$ ' + formatearMoneda(item.precio_unitario) + '</td>' +
                '<td class="fw-bold text-dark">$ ' + formatearMoneda(item.subtotal) + '</td>'
            ).insertBefore($filaVacia);
            total += item.subtotal;
        });

        const tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        if (tasa > 0) {
            $totalNota.html('<div>$ ' + formatearMoneda(total) + '</div><div class="text-muted small fw-normal">Bs. ' + (total * tasa).toLocaleString('es-VE', FORMATO_BS) + '</div>');
        } else {
            $totalNota.text('$ ' + formatearMoneda(total));
        }
        actualizarCalculoInicial(total);
    }

    function actualizarCalculoInicial(totalTotal) {
        if (!$montoInicial.length || !$textoSaldoRestante.length) return;
        const total = (typeof totalTotal === 'number') ? totalTotal : itemsNota.reduce(function(acc, i) { return acc + i.subtotal; }, 0);
        const ini = parseFloat($montoInicial.val()) || 0;
        const restante = Math.max(0, total - ini);
        $textoSaldoRestante.text('$ ' + formatearMoneda(restante));
        const tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        if (tasa > 0) {
            $('#textoSaldoRestanteBs').text('Equiv: Bs. ' + (restante * tasa).toLocaleString('es-VE', FORMATO_BS));
            $('#montoInicialBsTexto').text('Bs. ' + (ini * tasa).toLocaleString('es-VE', FORMATO_BS));
        }
    }

    $montoInicial.on('input', function() {
        const ini = parseFloat(this.value) || 0;
        if (ini > 0) {
            $('#contenedorTipoPagoInicial').css('display', 'block');
            verificarTipoPagoInicial();
        } else {
            $('#contenedorTipoPagoInicial, #contenedorBancoInicial, #contenedorReferenciaInicial').css('display', 'none');
        }
        actualizarCalculoInicial();
    });

    function verificarTipoPagoInicial() {
        const val = parseInt($tipoPagoInicial.val());
        $('#contenedorBancoInicial, #contenedorReferenciaInicial').css('display', (val === 2 || val === 3 || val === 4) ? 'block' : 'none');
    }

    $tipoPagoInicial.on('change', verificarTipoPagoInicial);

    function guardarNotaEntrega(confirmado) {
        try {
            const decision = $('#decisionNota').val() || 'aprobar';

            const formData = new FormData();
            formData.append('id_presupuesto', $('#presupuestoId').val() || '');
            formData.append('decision', decision);

            if (decision === 'rechazar') {
                if (confirmado !== true) {
                    confirmarConModal('Rechazar presupuesto', '¿Está seguro de que desea rechazar este presupuesto / nota de entrega?', function() { guardarNotaEntrega(true); });
                    return;
                }
            } else {
                if (!$clienteNota.length) {
                    mostrarError('No se pudo leer el cliente. Recargue la página.');
                    return;
                }
                if (!$clienteNota.val()) {
                    mostrarError('Debe seleccionar un cliente');
                    return;
                }

                if (itemsNota.length === 0) {
                    mostrarError('Debe agregar al menos un item');
                    return;
                }

                formData.append('id_cliente', $clienteNota.val());
                const condPago = $('#condicionPago').val() || 'contado';
                formData.append('condicion_pago', condPago);

                if (condPago === 'contado') {
                    const tipoPago = $('#tipoPago').val();
                    if (!tipoPago) {
                        mostrarError('Debe seleccionar el tipo de pago para aprobar la entrega al contado');
                        return;
                    }
                    formData.append('id_tipo_pago', tipoPago);

                    if (parseInt(tipoPago) !== 1) { // Si no es efectivo
                        const banco = $('#bancoPago').val();
                        if (!banco) {
                            mostrarError('Debe seleccionar un banco para el pago al contado');
                            return;
                        }
                        const referencia = ($('#referenciaPago').val() || '').trim();
                        if (!/^\d{5}$/.test(referencia)) {
                            mostrarError('La referencia debe tener los últimos 5 dígitos del comprobante');
                            return;
                        }
                        formData.append('id_banco', banco);
                        formData.append('referencia', referencia);
                    } else {
                        formData.append('referencia', 'Efectivo');
                    }
                } else {
                    // Crédito
                    if (!$fechaVencimiento.val()) {
                        mostrarError('Debe seleccionar una fecha de vencimiento para la venta a crédito');
                        return;
                    }
                    formData.append('fecha_vencimiento', $fechaVencimiento.val());

                    const iniVal = parseFloat($montoInicial.val()) || 0;
                    if (iniVal > 0) {
                        const tipoIni = $tipoPagoInicial.val();
                        if (!tipoIni) {
                            mostrarError('Debe seleccionar el método de pago para el abono inicial');
                            return;
                        }
                        formData.append('monto_inicial', iniVal);
                        formData.append('id_tipo_pago_inicial', tipoIni);

                        if (parseInt(tipoIni) !== 1) { // Si no es efectivo
                            const bancoIni = $('#bancoPagoInicial').val();
                            if (!bancoIni) {
                                mostrarError('Debe seleccionar el banco receptor para el abono inicial');
                                return;
                            }
                            const refIni = ($('#referenciaPagoInicial').val() || '').trim();
                            if (!/^\d{5}$/.test(refIni)) {
                                mostrarError('La referencia debe tener los últimos 5 dígitos del comprobante');
                                return;
                            }
                            formData.append('id_banco_inicial', bancoIni);
                            formData.append('referencia_inicial', refIni);
                        } else {
                            formData.append('referencia_inicial', 'Efectivo');
                        }
                    }
                }

                const itemsSimplificados = itemsNota.map(function(item) {
                    return {
                        id_presupuesto_detalle: item.id_presupuesto_detalle,
                        cantidad: item.cantidad,
                        precio_unitario: item.precio_unitario
                    };
                });
                formData.append('items', JSON.stringify(itemsSimplificados));
            }

            api.post('notaEntrega/guardar', formData)
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarNotificacion('Nota de entrega #' + resultado.datos.id_nota_entrega + ' creada exitosamente', 'exito');
                    setTimeout(function() {
                        window.location.href = '/SP%20Perfect%20Color/notaEntrega';
                    }, 1500);
                } else {
                    mostrarError(resultado.mensaje);
                }
            })
            .catch(function(error) {
                console.error('Error en la petición:', error);
                mostrarError('No se pudo conectar con el servidor. Intente de nuevo.');
            });
        } catch (error) {
            console.error('Error en guardarNotaEntrega:', error);
            mostrarError('Ocurrió un problema inesperado. Recargue la página e intente de nuevo.');
        }
    }

    function mostrarError(mensaje) {
        if (!$mensajeError.length) { mostrarNotificacion(mensaje, 'error'); return; }
        $mensajeError.attr('role', 'alert').text(mensaje).removeClass('d-none').css('display', 'block');
        $mensajeError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
