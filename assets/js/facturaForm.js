// Archivo: facturaForm.js
// Manejo del formulario de factura directa

document.addEventListener('DOMContentLoaded', function() {
    var clienteFactura = document.getElementById('clienteFactura');
    var metodoPago = document.getElementById('metodoPago');
    var busquedaInsumo = document.getElementById('busquedaInsumoFactura');
    var listaInsumosDisponibles = document.getElementById('listaInsumosDisponibles');
    var cuerpoTablaItems = document.getElementById('cuerpoTablaItems');
    var totalFactura = document.getElementById('totalFactura');
    var formularioFactura = document.getElementById('formularioFactura');
    var mensajeError = document.getElementById('mensajeErrorFactura');
    var filaVacia = document.getElementById('filaVacia');

    var itemsFactura = [];
    var insumosDisponibles = [];

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

    // Evento para enviar formulario
    if (formularioFactura) {
        formularioFactura.addEventListener('submit', function(evento) {
            evento.preventDefault();
            guardarFactura();
        });
    }

    function cargarClientes() {
        fetch('/SP%20Perfect%20Color/factura/obtenerClientesAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    var select = document.getElementById('clienteFactura');
                    select.innerHTML = '<option value="">Seleccione un cliente...</option>';
                    resultado.datos.clientes.forEach(function(cliente) {
                        var opcion = document.createElement('option');
                        opcion.value = cliente.id;
                        opcion.textContent = cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos;
                        select.appendChild(opcion);
                    });
                }
            });
    }

    function cargarInsumos() {
        fetch('/SP%20Perfect%20Color/inventario/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    insumosDisponibles = resultado.datos.insumos;
                    mostrarInsumosDisponibles(insumosDisponibles);
                }
            });
    }

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
                    '<span class="insumo-detalle">' + (insumo.marca || 'Sin marca') + ' | Stock: ' + formatearMoneda(insumo.stock_actual) + '</span>' +
                '</div>' +
                '<span class="insumo-precio">Bs. ' + formatearMoneda(insumo.precio_venta) + '</span>' +
                '<button type="button" class="btn-primario btn-agregar">Agregar</button>';
            
            div.querySelector('.btn-agregar').addEventListener('click', function() {
                agregarItem(insumo);
            });
            
            listaInsumosDisponibles.appendChild(div);
        });
    }

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

    function agregarItem(insumo) {
        for (var i = 0; i < itemsFactura.length; i++) {
            if (itemsFactura[i].insumo_id === insumo.id) {
                mostrarNotificacion('Este insumo ya fue agregado', 'error');
                return;
            }
        }

        var nuevoItem = {
            insumo_id: insumo.id,
            insumo_codigo: insumo.codigo,
            insumo_nombre: insumo.nombre,
            cantidad: 1,
            precio_unitario: parseFloat(insumo.precio_venta),
            subtotal: parseFloat(insumo.precio_venta)
        };

        itemsFactura.push(nuevoItem);
        actualizarTablaItems();
    }

    function actualizarTablaItems() {
        if (!cuerpoTablaItems || !totalFactura) return;
        if (filaVacia) filaVacia.style.display = itemsFactura.length > 0 ? 'none' : '';

        var filas = cuerpoTablaItems.querySelectorAll('tr:not(#filaVacia)');
        filas.forEach(function(fila) { fila.remove(); });

        var total = 0;

        itemsFactura.forEach(function(item, indice) {
            var fila = document.createElement('tr');
            
            fila.innerHTML = 
                '<td>' + item.insumo_codigo + '</td>' +
                '<td>' + item.insumo_nombre + '</td>' +
                '<td><input type="number" class="cantidad-input" value="' + item.cantidad + '" min="0.01" step="0.01" data-indice="' + indice + '"></td>' +
                '<td><input type="number" class="precio-input" value="' + item.precio_unitario + '" min="0.01" step="0.01" data-indice="' + indice + '"></td>' +
                '<td>Bs. ' + formatearMoneda(item.subtotal) + '</td>' +
                '<td><button type="button" class="btn-peligro btn-agregar" data-indice="' + indice + '">Quitar</button></td>';

            fila.querySelector('.cantidad-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevaCantidad = parseFloat(this.value) || 0;
                if (nuevaCantidad <= 0) { this.value = itemsFactura[idx].cantidad; return; }
                itemsFactura[idx].cantidad = nuevaCantidad;
                itemsFactura[idx].subtotal = nuevaCantidad * itemsFactura[idx].precio_unitario;
                actualizarTablaItems();
            });

            fila.querySelector('.precio-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevoPrecio = parseFloat(this.value) || 0;
                if (nuevoPrecio <= 0) { this.value = itemsFactura[idx].precio_unitario; return; }
                itemsFactura[idx].precio_unitario = nuevoPrecio;
                itemsFactura[idx].subtotal = itemsFactura[idx].cantidad * nuevoPrecio;
                actualizarTablaItems();
            });

            fila.querySelector('.btn-peligro').addEventListener('click', function() {
                var idx = parseInt(this.dataset.indice);
                itemsFactura.splice(idx, 1);
                actualizarTablaItems();
            });

            cuerpoTablaItems.insertBefore(fila, filaVacia);
            total += item.subtotal;
        });

        totalFactura.textContent = 'Bs. ' + formatearMoneda(total);
    }

    function guardarFactura() {
        if (!clienteFactura.value) {
            mostrarError('Debe seleccionar un cliente');
            return;
        }

        if (!metodoPago.value) {
            mostrarError('Debe seleccionar un metodo de pago');
            return;
        }

        if (itemsFactura.length === 0) {
            mostrarError('Debe agregar al menos un item');
            return;
        }

        var formData = new FormData();
        formData.append('cliente_id', clienteFactura.value);
        formData.append('metodo_pago', metodoPago.value);
        formData.append('nota_entrega_id', document.getElementById('notaEntregaId').value);
        
        var itemsSimplificados = itemsFactura.map(function(item) {
            return {
                insumo_id: item.insumo_id,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario
            };
        });
        formData.append('items', JSON.stringify(itemsSimplificados));

        fetch('/SP%20Perfect%20Color/factura/guardar', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion('Factura ' + resultado.datos.numero_factura + ' creada exitosamente', 'exito');
                setTimeout(function() {
                    window.location.href = '/SP%20Perfect%20Color/factura';
                }, 2000);
            } else {
                mostrarError(resultado.mensaje);
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
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