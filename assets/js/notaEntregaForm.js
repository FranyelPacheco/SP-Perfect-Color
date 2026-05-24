// Archivo: notaEntregaForm.js
// Manejo del formulario de creacion de nota de entrega directa

document.addEventListener('DOMContentLoaded', function() {
    var clienteNota = document.getElementById('clienteNota');
    var busquedaInsumo = document.getElementById('busquedaInsumoNota');
    var listaInsumosDisponibles = document.getElementById('listaInsumosDisponibles');
    var cuerpoTablaItems = document.getElementById('cuerpoTablaItems');
    var totalNota = document.getElementById('totalNota');
    var formularioNotaEntrega = document.getElementById('formularioNotaEntrega');
    var mensajeError = document.getElementById('mensajeErrorNota');
    var filaVacia = document.getElementById('filaVacia');

    var itemsNota = [];
    var insumosDisponibles = [];
    var clientesDisponibles = [];

    // Cargar datos iniciales
    cargarClientes();
    cargarInsumos();

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
                '<span class="insumo-precio">Bs. ' + formatearMoneda(insumo.precio_venta) + '</span>' +
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
                '<td>Bs. ' + formatearMoneda(item.subtotal) + '</td>' +
                '<td><button type="button" class="btn-peligro btn-agregar" data-indice="' + indice + '">Quitar</button></td>';

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
            fila.querySelector('.btn-peligro').addEventListener('click', function() {
                var idx = parseInt(this.dataset.indice);
                itemsNota.splice(idx, 1);
                actualizarTablaItems();
            });

            cuerpoTablaItems.insertBefore(fila, filaVacia);
            total += item.subtotal;
        });

        totalNota.textContent = 'Bs. ' + formatearMoneda(total);
    }

    // Guarda la nota de entrega
    function guardarNotaEntrega() {
        if (!clienteNota.value) {
            mostrarError('Debe seleccionar un cliente');
            return;
        }

        if (itemsNota.length === 0) {
            mostrarError('Debe agregar al menos un insumo');
            return;
        }

        var formData = new FormData();
        formData.append('cliente_id', clienteNota.value);
        formData.append('presupuesto_id', document.getElementById('presupuestoId').value);
        
        var itemsSimplificados = itemsNota.map(function(item) {
            return {
                insumo_id: item.insumo_id,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario
            };
        });
        formData.append('items', JSON.stringify(itemsSimplificados));

        fetch('/SP%20Perfect%20Color/notaEntrega/guardar', {
            method: 'POST',
            body: formData
        })
        .then(function(respuesta) { return respuesta.json(); })
        .then(function(resultado) {
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
            console.error('Error al guardar nota:', error);
            mostrarError('Error de conexion');
        });
    }

    function mostrarError(mensaje) {
        if (!mensajeError) { alert(mensaje); return; }
        mensajeError.textContent = mensaje;
        mensajeError.style.display = 'block';
        setTimeout(function() { mensajeError.style.display = 'none'; }, 5000);
    }
});