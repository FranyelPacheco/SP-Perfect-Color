// Archivo: presupuestoForm.js
// Manejo del formulario de creacion de presupuestos

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    var clientePresupuesto = document.getElementById('clientePresupuesto');
    var busquedaInsumo = document.getElementById('busquedaInsumoPresupuesto');
    var listaInsumosDisponibles = document.getElementById('listaInsumosDisponibles');
    var cuerpoTablaItems = document.getElementById('cuerpoTablaItems');
    var totalPresupuesto = document.getElementById('totalPresupuesto');
    var formularioPresupuesto = document.getElementById('formularioPresupuesto');
    var mensajeError = document.getElementById('mensajeErrorPresupuesto');
    var filaVacia = document.getElementById('filaVacia');

    // Array para almacenar los items del presupuesto
    var itemsPresupuesto = [];
    var insumosDisponibles = [];
    var clientesDisponibles = [];

    // Verificar que los elementos esenciales existen
    if (!clientePresupuesto || !formularioPresupuesto) {
        console.error('Error: No se encontraron los elementos del formulario');
        return;
    }

    // Cargar datos iniciales
    cargarClientes();
    cargarInsumos();

    // Evento para buscar insumos mientras se escribe
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
    formularioPresupuesto.addEventListener('submit', function(evento) {
        evento.preventDefault();
        guardarPresupuesto();
    });

    // Funcion para cargar clientes desde el servidor
    function cargarClientes() {
        fetch('/SP%20Perfect%20Color/presupuesto/obtenerClientesAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    clientesDisponibles = resultado.datos.clientes;
                    llenarSelectClientes();
                } else {
                    console.error('Error del servidor:', resultado.mensaje);
                    if (clientePresupuesto) {
                        clientePresupuesto.innerHTML = '<option value="">Error al cargar clientes</option>';
                    }
                }
            })
            .catch(function(error) {
                console.error('Error al cargar clientes:', error);
                if (clientePresupuesto) {
                    clientePresupuesto.innerHTML = '<option value="">Error de conexion al cargar clientes</option>';
                }
            });
    }

    // Funcion para cargar insumos desde el servidor
    function cargarInsumos() {
        fetch('/SP%20Perfect%20Color/presupuesto/obtenerInsumosAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    insumosDisponibles = resultado.datos.insumos;
                    mostrarInsumosDisponibles(insumosDisponibles);
                } else {
                    console.error('Error del servidor:', resultado.mensaje);
                    if (listaInsumosDisponibles) {
                        listaInsumosDisponibles.innerHTML = '<div class="insumo-item" style="justify-content: center; color: #f44336;">Error al cargar insumos</div>';
                    }
                }
            })
            .catch(function(error) {
                console.error('Error al cargar insumos:', error);
                if (listaInsumosDisponibles) {
                    listaInsumosDisponibles.innerHTML = '<div class="insumo-item" style="justify-content: center; color: #f44336;">Error de conexion al cargar insumos</div>';
                }
            });
    }

    // Llena el select de clientes con los datos obtenidos
    function llenarSelectClientes() {
        if (!clientePresupuesto) {
            console.error('Error: No se encontro el select de clientes');
            return;
        }

        clientePresupuesto.innerHTML = '<option value="">Seleccione un cliente...</option>';
        
        if (clientesDisponibles.length === 0) {
            clientePresupuesto.innerHTML += '<option value="" disabled>No hay clientes registrados</option>';
            return;
        }

        clientesDisponibles.forEach(function(cliente) {
            var opcion = document.createElement('option');
            opcion.value = cliente.id;
            opcion.textContent = cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos;
            clientePresupuesto.appendChild(opcion);
        });
    }

    // Muestra la lista de insumos disponibles
    function mostrarInsumosDisponibles(insumos) {
        if (!listaInsumosDisponibles) {
            console.error('Error: No se encontro el contenedor de insumos');
            return;
        }

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
                '<span class="insumo-precio">$ ' + formatearMoneda(insumo.precio_venta) + '</span>' +
                '<button type="button" class="btn-primario btn-agregar" data-id="' + insumo.id + '">Agregar</button>';
            
            // Evento para agregar insumo al presupuesto
            div.querySelector('.btn-agregar').addEventListener('click', function() {
                agregarItem(insumo);
            });
            
            listaInsumosDisponibles.appendChild(div);
        });
    }

    // Filtra insumos por el termino de busqueda
    function filtrarInsumos(termino) {
        if (!termino) {
            mostrarInsumosDisponibles(insumosDisponibles);
            return;
        }

        var filtrados = insumosDisponibles.filter(function(insumo) {
            return insumo.nombre.toLowerCase().indexOf(termino) !== -1 ||
                   insumo.codigo.toLowerCase().indexOf(termino) !== -1 ||
                   (insumo.marca && insumo.marca.toLowerCase().indexOf(termino) !== -1) ||
                   (insumo.categoria && insumo.categoria.toLowerCase().indexOf(termino) !== -1);
        });

        mostrarInsumosDisponibles(filtrados);
    }

    // Agrega un insumo a la tabla de items
    function agregarItem(insumo) {
        // Verificar si el insumo ya esta en la lista
        var existe = false;
        for (var i = 0; i < itemsPresupuesto.length; i++) {
            if (itemsPresupuesto[i].insumo_id === insumo.id) {
                existe = true;
                break;
            }
        }

        if (existe) {
            mostrarNotificacion('Este insumo ya fue agregado', 'error');
            return;
        }

        // Agregar al array de items
        var nuevoItem = {
            insumo_id: insumo.id,
            insumo_codigo: insumo.codigo,
            insumo_nombre: insumo.nombre,
            cantidad: 1,
            precio_unitario: parseFloat(insumo.precio_venta),
            subtotal: parseFloat(insumo.precio_venta)
        };

        itemsPresupuesto.push(nuevoItem);
        actualizarTablaItems();
    }

    // Actualiza la tabla de items y recalcula totales
    function actualizarTablaItems() {
        if (!cuerpoTablaItems || !totalPresupuesto) {
            console.error('Error: No se encontraron los elementos de la tabla');
            return;
        }

        // Ocultar fila vacia si hay items
        if (filaVacia) {
            filaVacia.style.display = itemsPresupuesto.length > 0 ? 'none' : '';
        }

        // Limpiar tabla (excepto fila vacia)
        var filas = cuerpoTablaItems.querySelectorAll('tr:not(#filaVacia)');
        filas.forEach(function(fila) {
            fila.remove();
        });

        var total = 0;

        // Agregar cada item
        itemsPresupuesto.forEach(function(item, indice) {
            var fila = document.createElement('tr');
            
            fila.innerHTML = 
                '<td>' + item.insumo_codigo + '</td>' +
                '<td>' + item.insumo_nombre + '</td>' +
                '<td>' +
                    '<input type="number" class="cantidad-input" value="' + item.cantidad + '" ' +
                           'min="0.01" step="0.01" data-indice="' + indice + '">' +
                '</td>' +
                '<td>' +
                    '<input type="number" class="precio-input" value="' + item.precio_unitario + '" ' +
                           'min="0.01" step="0.01" data-indice="' + indice + '">' +
                '</td>' +
                '<td>$ ' + formatearMoneda(item.subtotal) + '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" data-indice="' + indice + '"><i class="bi bi-trash"></i> Quitar</button>' +
                '</td>';

            // Evento para cambiar cantidad
            fila.querySelector('.cantidad-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevaCantidad = parseFloat(this.value) || 0;
                
                if (nuevaCantidad <= 0) {
                    this.value = itemsPresupuesto[idx].cantidad;
                    return;
                }
                
                itemsPresupuesto[idx].cantidad = nuevaCantidad;
                itemsPresupuesto[idx].subtotal = nuevaCantidad * itemsPresupuesto[idx].precio_unitario;
                actualizarTablaItems();
            });

            // Evento para cambiar precio
            fila.querySelector('.precio-input').addEventListener('change', function() {
                var idx = parseInt(this.dataset.indice);
                var nuevoPrecio = parseFloat(this.value) || 0;
                
                if (nuevoPrecio <= 0) {
                    this.value = itemsPresupuesto[idx].precio_unitario;
                    return;
                }
                
                itemsPresupuesto[idx].precio_unitario = nuevoPrecio;
                itemsPresupuesto[idx].subtotal = itemsPresupuesto[idx].cantidad * nuevoPrecio;
                actualizarTablaItems();
            });

            // Evento para quitar item
            fila.querySelector('.btn-outline-danger').addEventListener('click', function() {
                var idx = parseInt(this.dataset.indice);
                itemsPresupuesto.splice(idx, 1);
                actualizarTablaItems();
            });

            cuerpoTablaItems.insertBefore(fila, filaVacia);
            total += item.subtotal;
        });

        // Actualizar total
        totalPresupuesto.textContent = '$ ' + formatearMoneda(total);
    }

    // Guarda el presupuesto en el servidor
    function guardarPresupuesto() {
        // Validar cliente
        if (!clientePresupuesto.value) {
            mostrarError('Debe seleccionar un cliente');
            return;
        }

        // Validar items
        if (itemsPresupuesto.length === 0) {
            mostrarError('Debe agregar al menos un insumo al presupuesto');
            return;
        }

        // Preparar datos del formulario
        var formData = new FormData();
        formData.append('cliente_id', clientePresupuesto.value);
        formData.append('observaciones', document.getElementById('observacionesPresupuesto').value);

        // Preparar items como JSON
        var itemsSimplificados = itemsPresupuesto.map(function(item) {
            return {
                insumo_id: item.insumo_id,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario
            };
        });
        formData.append('items', JSON.stringify(itemsSimplificados));

        fetch('/SP%20Perfect%20Color/presupuesto/guardar', {
            method: 'POST',
            body: formData
        })
        .then(function(respuesta) { return respuesta.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                mostrarNotificacion('Presupuesto #' + resultado.datos.presupuesto_id + ' creado exitosamente. Total: $ ' + formatearMoneda(resultado.datos.total), 'exito');
                
                // Redirigir a la lista despues de 2 segundos
                setTimeout(function() {
                    window.location.href = '/SP%20Perfect%20Color/presupuesto';
                }, 2000);
            } else {
                mostrarError(resultado.mensaje);
            }
        })
        .catch(function(error) {
            console.error('Error al guardar presupuesto:', error);
            mostrarError('Error de conexion al guardar el presupuesto');
        });
    }

    // Muestra un mensaje de error en el formulario
    function mostrarError(mensaje) {
        if (!mensajeError) {
            alert(mensaje);
            return;
        }
        mensajeError.textContent = mensaje;
        mensajeError.style.display = 'block';
        
        // Ocultar despues de 5 segundos
        setTimeout(function() {
            mensajeError.style.display = 'none';
        }, 5000);
    }
});