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
                insumo_marca: item.insumo_marca || '',
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
                    if (sel) {
                        sel.innerHTML = '<option value="">Seleccione...</option>';
                        res.datos.tipos_pago.forEach(function(tp) {
                            var op = document.createElement('option');
                            op.value = tp.id_tipo_pago;
                            op.textContent = tp.nombre;
                            sel.appendChild(op);
                        });
                    }
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
                    if (sel) {
                        sel.innerHTML = '<option value="">Seleccione un banco...</option>';
                        res.datos.bancos.forEach(function(b) {
                            var op = document.createElement('option');
                            op.value = b.id_banco;
                            op.textContent = b.nombre;
                            sel.appendChild(op);
                        });
                    }
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

        itemsNota.forEach(function(item, indice) {
            var fila = document.createElement('tr');
            
            fila.innerHTML = 
                '<td>' + item.insumo_codigo + '</td>' +
                '<td>' + item.insumo_nombre + '</td>' +
                '<td>' + item.stock_actual.toFixed(2) + '</td>' +
                '<td><input type="number" class="cantidad-input" value="' + item.cantidad + '" min="0.01" step="0.01" data-indice="' + indice + '"></td>' +
                '<td><input type="number" class="precio-input" value="' + item.precio_unitario + '" min="0.01" step="0.01" data-indice="' + indice + '"></td>' +
                '<td>$ ' + formatearMoneda(item.subtotal) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger" data-indice="' + indice + '"><i class="bi bi-trash"></i> Quitar</button></td>';

            fila.querySelector('.cantidad-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevaCantidad = parseFloat(this.value) || 0;
                
                if (nuevaCantidad <= 0) {
                    this.value = itemsNota[idx].cantidad;
                    return;
                }
                
                itemsNota[idx].cantidad = nuevaCantidad;
                itemsNota[idx].subtotal = nuevaCantidad * itemsNota[idx].precio_unitario;
                actualizarTablaItems();
            });

            fila.querySelector('.precio-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevoPrecio = parseFloat(this.value) || 0;
                
                if (nuevoPrecio <= 0) {
                    this.value = itemsNota[idx].precio_unitario;
                    return;
                }
                
                itemsNota[idx].precio_unitario = nuevoPrecio;
                itemsNota[idx].subtotal = itemsNota[idx].cantidad * nuevoPrecio;
                actualizarTablaItems();
            });

            fila.querySelector('.btn-outline-danger').addEventListener('click', function() {
                var idx = parseInt(this.dataset.indice);
                itemsNota.splice(idx, 1);
                actualizarTablaItems();
            });

            cuerpoTablaItems.insertBefore(fila, filaVacia);
            total += item.subtotal;
        });

        totalNota.textContent = '$ ' + formatearMoneda(total);
    }

    function guardarNotaEntrega() {
        try {
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

            var presupuestoInput = document.getElementById('presupuestoId');

            // Forzar remover required de fecha si esta oculta
            if (contenedorVencimiento && contenedorVencimiento.style.display === 'none') {
                if (fechaVencimientoInput) fechaVencimientoInput.required = false;
            }

            var formData = new FormData();
            formData.append('id_cliente', clienteNota.value);
            formData.append('id_presupuesto', presupuestoInput ? presupuestoInput.value : '');
            var condPago = condicionPagoSelect ? condicionPagoSelect.value : 'contado';
            formData.append('condicion_pago', condPago);
            formData.append('estado', 'entregado');

            var tipoPagoInput = document.getElementById('tipoPago');
            // Tipo de pago obligatorio cuando es contado
            if (condPago === 'contado') {
                if (!tipoPagoInput || !tipoPagoInput.value) {
                    mostrarError('Debe seleccionar un tipo de pago');
                    return;
                }
            }
            if (tipoPagoInput && tipoPagoInput.value) {
                formData.append('id_tipo_pago', tipoPagoInput.value);
            }

            var tipoPagoVal = tipoPagoInput ? parseInt(tipoPagoInput.value) : 0;
            // Transferencia=2, Pago Movil=3 â€” banco y referencia obligatorios
            if (tipoPagoVal === 2 || tipoPagoVal === 3) {
                var bancoInput = document.getElementById('bancoPago');
                if (!bancoInput || !bancoInput.value) {
                    mostrarError('Debe seleccionar un banco para transferencia o pago movil');
                    return;
                }
                var referenciaInput = document.getElementById('referenciaPago');
                if (!referenciaInput || !referenciaInput.value.trim()) {
                    mostrarError('Debe ingresar el numero de referencia para transferencia o pago movil');
                    return;
                }
                formData.append('id_banco', bancoInput.value);
                formData.append('referencia', referenciaInput.value.trim());
            } else {
                var bancoInput = document.getElementById('bancoPago');
                if (bancoInput && bancoInput.value) {
                    formData.append('id_banco', bancoInput.value);
                }
                var referenciaInput = document.getElementById('referenciaPago');
                if (referenciaInput && referenciaInput.value) {
                    formData.append('referencia', referenciaInput.value);
                }
            }

            if (fechaVencimientoInput) {
                formData.append('fecha_vencimiento', fechaVencimientoInput.value);
            }
            
            var itemsSimplificados = itemsNota.map(function(item) {
                return {
                    id_presupuesto_detalle: item.id_presupuesto_detalle,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio_unitario
                };
            });
            formData.append('items', JSON.stringify(itemsSimplificados));

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
                    }, 2000);
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
        mensajeError.style.display = 'block';
        setTimeout(function() { mensajeError.style.display = 'none'; }, 5000);
    }
});
