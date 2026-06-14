// Archivo: notaEntregaForm.js
// Manejo del formulario de creacion de nota de entrega directa

console.log('[notaEntregaForm] Archivo JS cargado por el navegador');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[notaEntregaForm] DOMContentLoaded - script iniciado');
    var clienteNota = document.getElementById('clienteNota');
    var busquedaInsumo = document.getElementById('busquedaInsumoNota');
    var listaInsumosDisponibles = document.getElementById('listaInsumosDisponibles');
    var cuerpoTablaItems = document.getElementById('cuerpoTablaItems');
    var totalNota = document.getElementById('totalNota');
    var formularioNotaEntrega = document.getElementById('formularioNotaEntrega');
    var mensajeError = document.getElementById('mensajeErrorNota');
    var filaVacia = document.getElementById('filaVacia');

    var contenedorVencimiento = document.getElementById('contenedorVencimiento');
    var fechaVencimientoInput = document.getElementById('fechaVencimiento');
    var tipoPagoSelect = document.getElementById('tipoPago');

    var itemsNota = [];
    var insumosDisponibles = [];
    var clientesDisponibles = [];

    // Cargar datos iniciales
    cargarClientes();
    cargarInsumos();

    // Pre-cargar items desde presupuesto si existe detalle
    if (typeof presupuestoDetalle !== 'undefined' && presupuestoDetalle.length > 0) {
        var esperaInsumos = setInterval(function() {
            if (insumosDisponibles.length > 0) {
                clearInterval(esperaInsumos);
                presupuestoDetalle.forEach(function(item) {
                    var insumo = insumosDisponibles.find(function(i) { return i.id == item.insumo_id; });
                    if (insumo) {
                        var nuevoItem = {
                            insumo_id: insumo.id,
                            insumo_codigo: insumo.codigo,
                            insumo_nombre: insumo.nombre,
                            stock_actual: parseFloat(insumo.stock_actual),
                            cantidad: parseFloat(item.cantidad),
                            precio_unitario: parseFloat(item.precio_unitario),
                            subtotal: parseFloat(item.subtotal)
                        };
                        itemsNota.push(nuevoItem);
                    }
                });
                actualizarTablaItems();
            }
        }, 100);
    }

    // Evento para buscar insumos
    var temporizadorBusqueda;
    if (busquedaInsumo) {
        busquedaInsumo.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                filtrarInsumos(busquedaInsumo.value.trim().toLowerCase());
            }, 200);
        });
    }

    // Evento para enviar el formulario
    if (formularioNotaEntrega) {
        formularioNotaEntrega.addEventListener('submit', function(evento) {
            evento.preventDefault();
            var accion = evento.submitter ? evento.submitter.value : 'pendiente';
            guardarNotaEntrega(accion);
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

    // Cargar insumos
    function cargarInsumos() {
        fetch('/SP%20Perfect%20Color/notaEntrega/obtenerInsumosAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    insumosDisponibles = resultado.datos.insumos;
                    mostrarInsumosDisponibles(insumosDisponibles);
                }
            })
            .catch(function(error) {
                console.error('Error al cargar insumos:', error);
            });
    }

    // Llena el select de clientes
    function llenarSelectClientes() {
        if (!clienteNota) return;
        clienteNota.innerHTML = '<option value="">Seleccione un cliente...</option>';
        
        clientesDisponibles.forEach(function(cliente) {
            var opcion = document.createElement('option');
            opcion.value = cliente.id;
            opcion.textContent = cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos;
            clienteNota.appendChild(opcion);
        });

        // Pre-seleccionar cliente desde presupuesto
        if (typeof presupuestoClienteId !== 'undefined' && presupuestoClienteId > 0) {
            clienteNota.value = presupuestoClienteId;
            clienteNota.disabled = true;
        }
    }

    // Muestra los insumos disponibles
    function mostrarInsumosDisponibles(insumos) {
        if (!listaInsumosDisponibles) return;
        listaInsumosDisponibles.innerHTML = '';

        if (insumos.length === 0) {
            listaInsumosDisponibles.innerHTML = '<div class="insumo-item" style="justify-content: center; color: #999;">No se encontraron insumos</div>';
            return;
        }

        insumos.forEach(function(insumo) {
            var div = document.createElement('div');
            div.className = 'insumo-item';
            
            div.innerHTML = 
                '<div class="insumo-info">' +
                    '<span class="insumo-nombre">' + insumo.codigo + ' - ' + insumo.nombre + '</span>' +
                    '<span class="insumo-detalle">Stock: ' + formatearMoneda(insumo.stock_actual) + '</span>' +
                '</div>' +
                '<span class="insumo-precio">$ ' + formatearMoneda(insumo.precio_venta) + '</span>' +
                '<button type="button" class="btn-primario btn-agregar">Agregar</button>';
            
            div.querySelector('.btn-agregar').addEventListener('click', function() {
                agregarItem(insumo);
            });
            
            listaInsumosDisponibles.appendChild(div);
        });
    }

    // Filtra insumos por busqueda
    function filtrarInsumos(termino) {
        if (!termino) {
            mostrarInsumosDisponibles(insumosDisponibles);
            return;
        }

        var filtrados = insumosDisponibles.filter(function(insumo) {
            return insumo.nombre.toLowerCase().indexOf(termino) !== -1 ||
                   insumo.codigo.toLowerCase().indexOf(termino) !== -1;
        });

        mostrarInsumosDisponibles(filtrados);
    }

    // Agrega un insumo a la tabla
    function agregarItem(insumo) {
        // Verificar si ya existe
        for (var i = 0; i < itemsNota.length; i++) {
            if (itemsNota[i].insumo_id === insumo.id) {
                mostrarNotificacion('Este insumo ya fue agregado', 'error');
                return;
            }
        }

        // Verificar stock
        if (parseFloat(insumo.stock_actual) <= 0) {
            mostrarNotificacion('No hay stock disponible para este insumo', 'error');
            return;
        }

        var nuevoItem = {
            insumo_id: insumo.id,
            insumo_codigo: insumo.codigo,
            insumo_nombre: insumo.nombre,
            stock_actual: parseFloat(insumo.stock_actual),
            cantidad: 1,
            precio_unitario: parseFloat(insumo.precio_venta),
            subtotal: parseFloat(insumo.precio_venta)
        };

        itemsNota.push(nuevoItem);
        actualizarTablaItems();
    }

    // Actualiza la tabla de items
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
                '<td>' + formatearMoneda(item.stock_actual) + '</td>' +
                '<td><input type="number" class="cantidad-input" value="' + item.cantidad + '" min="0.01" step="0.01" data-indice="' + indice + '"></td>' +
                '<td><input type="number" class="precio-input" value="' + item.precio_unitario + '" min="0.01" step="0.01" data-indice="' + indice + '"></td>' +
                '<td>$ ' + formatearMoneda(item.subtotal) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger" data-indice="' + indice + '"><i class="bi bi-trash"></i> Quitar</button></td>';

            // Evento para cambiar cantidad
            fila.querySelector('.cantidad-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevaCantidad = parseFloat(this.value) || 0;
                
                if (nuevaCantidad <= 0 || nuevaCantidad > itemsNota[idx].stock_actual) {
                    this.value = itemsNota[idx].cantidad;
                    if (nuevaCantidad > itemsNota[idx].stock_actual) {
                        mostrarNotificacion('No hay suficiente stock disponible', 'error');
                    }
                    return;
                }
                
                itemsNota[idx].cantidad = nuevaCantidad;
                itemsNota[idx].subtotal = nuevaCantidad * itemsNota[idx].precio_unitario;
                actualizarTablaItems();
            });

            // Evento para cambiar precio
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

            // Evento para quitar item
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

    // Guarda la nota de entrega
    function guardarNotaEntrega(accion) {
        accion = accion || 'pendiente';
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
                mostrarError('Debe agregar al menos un insumo');
                return;
            }

            var tipoPagoSelect = document.getElementById('tipoPago');
            var presupuestoInput = document.getElementById('presupuestoId');

            // Si el contenedor de fecha esta oculto, forzar remover required
            if (contenedorVencimiento && contenedorVencimiento.style.display === 'none') {
                if (fechaVencimientoInput) fechaVencimientoInput.required = false;
            }

            var formData = new FormData();
            formData.append('cliente_id', clienteNota.value);
            formData.append('presupuesto_id', presupuestoInput ? presupuestoInput.value : '');
            formData.append('tipo_pago', tipoPagoSelect ? tipoPagoSelect.value : 'credito');
            formData.append('estado', accion);

            var metodoPagoInput = document.getElementById('metodoPago');
            if (metodoPagoInput) {
                formData.append('metodo_pago', metodoPagoInput.value);
            }

            if (fechaVencimientoInput) {
                formData.append('fecha_vencimiento', fechaVencimientoInput.value);
                console.log('fecha_vencimiento adjuntado:', fechaVencimientoInput.value);
            } else {
                console.warn('Input fechaVencimiento no encontrado en el DOM');
            }
            
            var itemsSimplificados = itemsNota.map(function(item) {
                return {
                    insumo_id: item.insumo_id,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio_unitario
                };
            });
            formData.append('items', JSON.stringify(itemsSimplificados));

            if (typeof Object.fromEntries === 'function') {
                console.log('Final FormData:', Object.fromEntries(formData));
            }
            for (var par of formData.entries()) {
                console.log('  ' + par[0] + ':', par[1]);
            }
            console.log('Iniciando fetch a /SP%20Perfect%20Color/notaEntrega/guardar...');
            fetch('/SP%20Perfect%20Color/notaEntrega/guardar', {
                method: 'POST',
                body: formData
            })
            .then(function(respuesta) {
                console.log('Respuesta HTTP recibida, status:', respuesta.status);
                return respuesta.json();
            })
            .then(function(resultado) {
                console.log('Respuesta JSON del servidor:', resultado);
                if (resultado.estado === 'exito') {
                    mostrarNotificacion('Nota de entrega #' + resultado.datos.nota_id + ' creada exitosamente', 'exito');
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