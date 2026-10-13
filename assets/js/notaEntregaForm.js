// Archivo: notaEntregaForm.js
// Manejo del formulario de creacion de nota de entrega desde presupuesto

console.log('[notaEntregaForm] Archivo JS cargado por el navegador');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[notaEntregaForm] DOMContentLoaded - script iniciado');
    var clienteNota = document.getElementById('clienteNota');
    var cuerpoTablaItems = document.getElementById('cuerpoTablaItems');
    var totalNota = document.getElementById('totalNota');
    var formularioNotaEntrega = document.getElementById('formularioNotaEntrega');
    var mensajeError = document.getElementById('mensajeErrorNota');
    var filaVacia = document.getElementById('filaVacia');

    var contenedorVencimiento = document.getElementById('contenedorVencimiento');
    var fechaVencimientoInput = document.getElementById('fechaVencimiento');
    var condicionPagoSelect = document.getElementById('condicionPago');

    var itemsNota = [];
    var clientesDisponibles = [];

    // Cargar datos iniciales
    cargarClientes();
    cargarTiposPago();
    cargarBancos();

    // Pre-cargar items desde presupuesto si existe detalle
    if (typeof presupuestoDetalle !== 'undefined' && presupuestoDetalle.length > 0) {
        presupuestoDetalle.forEach(function(item) {
            var nuevoItem = {
                id_presupuesto_detalle: item.id_presupuesto_detalle,
                insumo_codigo: item.insumo_codigo,
                insumo_nombre: item.insumo_nombre,
                stock_actual: parseFloat(item.stock_actual) || 0,
                cantidad: parseFloat(item.cantidad),
                precio_unitario: parseFloat(item.precio_unitario),
                subtotal: parseFloat(item.subtotal)
            };
            itemsNota.push(nuevoItem);
        });
        actualizarTablaItems();
    }

    // Evento para enviar el formulario
    if (formularioNotaEntrega) {
        formularioNotaEntrega.addEventListener('submit', function(evento) {
            evento.preventDefault();
            guardarNotaEntrega();
        });
    }

    // Cargar clientes
    function cargarClientes() {
        fetch('/SP%20Perfect%20Color/notaEntrega/obtenerClientesAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    clientesDisponibles = resultado.datos.clientes;
                    llenarSelectClientes();
                }
            })
            .catch(function(error) {
                console.error('Error al cargar clientes:', error);
            });
    }

    // Cargar tipos de pago
    function cargarTiposPago() {
        fetch('/SP%20Perfect%20Color/notaEntrega/obtenerTiposPagoAjax')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    var sel = document.getElementById('tipoPago');
                    var selIni = document.getElementById('tipoPagoInicial');
                    var opts = '<option value="">Seleccione...</option>';
                    res.datos.tipos_pago.forEach(function(tp) {
                        opts += '<option value="' + tp.id_tipo_pago + '">' + tp.nombre + '</option>';
                    });
                    if (sel) sel.innerHTML = opts;
                    if (selIni) selIni.innerHTML = opts;
                }
            });
    }

    // Cargar bancos
    function cargarBancos() {
        fetch('/SP%20Perfect%20Color/notaEntrega/obtenerBancosAjax')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    var sel = document.getElementById('bancoPago');
                    var selIni = document.getElementById('bancoPagoInicial');
                    var opts = '<option value="">Seleccione un banco...</option>';
                    res.datos.bancos.forEach(function(b) {
                        opts += '<option value="' + b.id_banco + '">' + b.nombre + '</option>';
                    });
                    if (sel) sel.innerHTML = opts;
                    if (selIni) selIni.innerHTML = opts;
                }
            });
    }

    function llenarSelectClientes() {
        if (!clienteNota) return;
        clienteNota.innerHTML = '<option value="">Seleccione un cliente...</option>';
        
        clientesDisponibles.forEach(function(cliente) {
            var opcion = document.createElement('option');
            opcion.value = cliente.id_cliente;
            opcion.textContent = cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos;
            clienteNota.appendChild(opcion);
        });

        if (typeof presupuestoClienteId !== 'undefined' && presupuestoClienteId > 0) {
            clienteNota.value = presupuestoClienteId;
            clienteNota.disabled = true;
        }
    }

    function actualizarTablaItems() {
        if (!cuerpoTablaItems || !totalNota) return;

        if (filaVacia) {
            filaVacia.style.display = itemsNota.length > 0 ? 'none' : '';
        }

        var filas = cuerpoTablaItems.querySelectorAll('tr:not(#filaVacia)');
        filas.forEach(function(fila) { fila.remove(); });

        var total = 0;

        itemsNota.forEach(function(item) {
            var fila = document.createElement('tr');
            
            fila.innerHTML = 
                '<td><span class="badge bg-light text-secondary border font-monospace">' + item.insumo_codigo + '</span></td>' +
                '<td class="fw-semibold">' + item.insumo_nombre + '</td>' +
                '<td class="text-secondary">' + Math.floor(item.stock_actual) + '</td>' +
                '<td><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-6">' + Math.floor(item.cantidad) + '</span></td>' +
                '<td>$ ' + formatearMoneda(item.precio_unitario) + '</td>' +
                '<td class="fw-bold text-dark">$ ' + formatearMoneda(item.subtotal) + '</td>';

            cuerpoTablaItems.insertBefore(fila, filaVacia);
            total += item.subtotal;
        });

        var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        if (tasa > 0) {
            var bsTotal = total * tasa;
            totalNota.innerHTML = '<div>$ ' + formatearMoneda(total) + '</div><div class="text-muted small fw-normal">Bs. ' + bsTotal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</div>';
        } else {
            totalNota.textContent = '$ ' + formatearMoneda(total);
        }
        actualizarCalculoInicial(total);
    }

    var montoInicialInput = document.getElementById('montoInicial');
    var tipoPagoInicialSelect = document.getElementById('tipoPagoInicial');
    var textoSaldoRestante = document.getElementById('textoSaldoRestante');

    function actualizarCalculoInicial(totalTotal) {
        if (!montoInicialInput || !textoSaldoRestante) return;
        var total = (typeof totalTotal === 'number') ? totalTotal : (itemsNota.reduce(function(acc, i) { return acc + i.subtotal; }, 0));
        var ini = parseFloat(montoInicialInput.value) || 0;
        var restante = Math.max(0, total - ini);
        textoSaldoRestante.textContent = '$ ' + formatearMoneda(restante);
        var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        var textoSaldoBs = document.getElementById('textoSaldoRestanteBs');
        var montoIniBs = document.getElementById('montoInicialBsTexto');
        if (textoSaldoBs && tasa > 0) {
            textoSaldoBs.textContent = 'Equiv: Bs. ' + (restante * tasa).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (montoIniBs && tasa > 0) {
            montoIniBs.textContent = 'Bs. ' + (ini * tasa).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    if (montoInicialInput) {
        montoInicialInput.addEventListener('input', function() {
            var ini = parseFloat(this.value) || 0;
            var contT = document.getElementById('contenedorTipoPagoInicial');
            var contB = document.getElementById('contenedorBancoInicial');
            var contR = document.getElementById('contenedorReferenciaInicial');
            if (ini > 0) {
                if (contT) contT.style.display = 'block';
                verificarTipoPagoInicial();
            } else {
                if (contT) contT.style.display = 'none';
                if (contB) contB.style.display = 'none';
                if (contR) contR.style.display = 'none';
            }
            actualizarCalculoInicial();
        });
    }

    function verificarTipoPagoInicial() {
        if (!tipoPagoInicialSelect) return;
        var val = parseInt(tipoPagoInicialSelect.value);
        var contB = document.getElementById('contenedorBancoInicial');
        var contR = document.getElementById('contenedorReferenciaInicial');
        if (val === 2 || val === 3 || val === 4) {
            if (contB) contB.style.display = 'block';
            if (contR) contR.style.display = 'block';
        } else {
            if (contB) contB.style.display = 'none';
            if (contR) contR.style.display = 'none';
        }
    }

    if (tipoPagoInicialSelect) {
        tipoPagoInicialSelect.addEventListener('change', verificarTipoPagoInicial);
    }

    function guardarNotaEntrega() {
        try {
            var presupuestoInput = document.getElementById('presupuestoId');
            var decisionSelect = document.getElementById('decisionNota');
            var decision = decisionSelect ? decisionSelect.value : 'aprobar';

            var formData = new FormData();
            formData.append('id_presupuesto', presupuestoInput ? presupuestoInput.value : '');
            formData.append('decision', decision);

            if (decision === 'rechazar') {
                // Confirmación para rechazar
                if (!confirm('¿Está seguro de que desea rechazar este presupuesto / nota de entrega?')) {
                    return;
                }
            } else {
                if (!clienteNota) {
                    mostrarError('Error interno: campo cliente no encontrado');
                    return;
                }
                if (!clienteNota.value) {
                    mostrarError('Debe seleccionar un cliente');
                    return;
                }

                if (itemsNota.length === 0) {
                    mostrarError('Debe agregar al menos un item');
                    return;
                }

                formData.append('id_cliente', clienteNota.value);
                var condPago = condicionPagoSelect ? condicionPagoSelect.value : 'contado';
                formData.append('condicion_pago', condPago);

                if (condPago === 'contado') {
                    var tipoPagoInput = document.getElementById('tipoPago');
                    if (!tipoPagoInput || !tipoPagoInput.value) {
                        mostrarError('Debe seleccionar el tipo de pago para aprobar la entrega al contado');
                        return;
                    }
                    formData.append('id_tipo_pago', tipoPagoInput.value);

                    var tipoPagoVal = parseInt(tipoPagoInput.value);
                    if (tipoPagoVal !== 1) { // Si no es efectivo
                        var bancoInput = document.getElementById('bancoPago');
                        if (!bancoInput || !bancoInput.value) {
                            mostrarError('Debe seleccionar un banco para el pago al contado');
                            return;
                        }
                        var referenciaInput = document.getElementById('referenciaPago');
                        if (!referenciaInput || !referenciaInput.value.trim()) {
                            mostrarError('Debe ingresar la referencia para el pago al contado');
                            return;
                        }
                        formData.append('id_banco', bancoInput.value);
                        formData.append('referencia', referenciaInput.value.trim());
                    } else {
                        formData.append('referencia', 'Efectivo');
                    }
                } else {
                    // Crédito
                    if (!fechaVencimientoInput || !fechaVencimientoInput.value) {
                        mostrarError('Debe seleccionar una fecha de vencimiento para la venta a crédito');
                        return;
                    }
                    formData.append('fecha_vencimiento', fechaVencimientoInput.value);

                    var montoInicialInput = document.getElementById('montoInicial');
                    var iniVal = parseFloat(montoInicialInput ? montoInicialInput.value : 0) || 0;
                    if (iniVal > 0) {
                        var tipoPagoInicialSelect = document.getElementById('tipoPagoInicial');
                        if (!tipoPagoInicialSelect || !tipoPagoInicialSelect.value) {
                            mostrarError('Debe seleccionar el método de pago para el abono inicial');
                            return;
                        }
                        formData.append('monto_inicial', iniVal);
                        formData.append('id_tipo_pago_inicial', tipoPagoInicialSelect.value);

                        var tipoIniVal = parseInt(tipoPagoInicialSelect.value);
                        if (tipoIniVal !== 1) { // Si no es efectivo
                            var bancoIni = document.getElementById('bancoPagoInicial');
                            if (!bancoIni || !bancoIni.value) {
                                mostrarError('Debe seleccionar el banco receptor para el abono inicial');
                                return;
                            }
                            var refIni = document.getElementById('referenciaPagoInicial');
                            if (!refIni || !refIni.value.trim()) {
                                mostrarError('Debe ingresar el número de referencia para el abono inicial');
                                return;
                            }
                            formData.append('id_banco_inicial', bancoIni.value);
                            formData.append('referencia_inicial', refIni.value.trim());
                        } else {
                            formData.append('referencia_inicial', 'Efectivo');
                        }
                    }
                }
                
                var itemsSimplificados = itemsNota.map(function(item) {
                    return {
                        id_presupuesto_detalle: item.id_presupuesto_detalle,
                        cantidad: item.cantidad,
                        precio_unitario: item.precio_unitario
                    };
                });
                formData.append('items', JSON.stringify(itemsSimplificados));
            }

            fetch('/SP%20Perfect%20Color/notaEntrega/guardar', {
                method: 'POST',
                body: formData
            })
            .then(function(respuesta) {
                return respuesta.json();
            })
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
                console.error('Error en el fetch:', error);
                mostrarError('Error de conexion: ' + error.message);
            });
        } catch (error) {
            console.error('Error en guardarNotaEntrega:', error);
            mostrarError('Error interno: ' + error.message);
        }
    }

    function mostrarError(mensaje) {
        if (!mensajeError) { alert(mensaje); return; }
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
        mensajeError.style.display = 'block';
        mensajeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(function() { 
            mensajeError.style.display = 'none'; 
            mensajeError.classList.add('d-none');
        }, 5000);
    }
});
