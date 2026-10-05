// Archivo: presupuestoForm.js
// Manejo del formulario de creacion de presupuestos con UX/UI interactiva y formulación de mezclas

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    var clientePresupuesto = document.getElementById('clientePresupuesto');
    var busquedaInsumo = document.getElementById('busquedaInsumoPresupuesto');
    var btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
    var listaInsumosDisponibles = document.getElementById('listaInsumosDisponibles');
    var cuerpoTablaItems = document.getElementById('cuerpoTablaItems');
    var totalPresupuesto = document.getElementById('totalPresupuesto');
    var formularioPresupuesto = document.getElementById('formularioPresupuesto');
    var mensajeError = document.getElementById('mensajeErrorPresupuesto');
    var filaVacia = document.getElementById('filaVacia');
    var contadorInsumos = document.getElementById('contadorInsumos');
    var badgeCantidadItems = document.getElementById('badgeCantidadItems');
    var resumenConteoItems = document.getElementById('resumenConteoItems');
    var btnGuardarPresupuesto = document.getElementById('btnGuardarPresupuesto');

    // Referencias al Modal de Preparar Mezcla
    var modalPrepararMezcla = document.getElementById('modalPrepararMezcla');
    var btnModalPrepararMezcla = document.getElementById('btnModalPrepararMezcla');
    var formularioMezcla = document.getElementById('formularioMezcla');
    var mezclaNombre = document.getElementById('mezclaNombre');
    var mezclaPresentacion = document.getElementById('mezclaPresentacion');
    var mezclaCantidad = document.getElementById('mezclaCantidad');
    var cuerpoBasesMezcla = document.getElementById('cuerpoBasesMezcla');
    var btnAgregarFilaBase = document.getElementById('btnAgregarFilaBase');
    var contadorBasesMezcla = document.getElementById('contadorBasesMezcla');
    var mezclaPrecioVenta = document.getElementById('mezclaPrecioVenta');
    var ayudaPrecioMezcla = document.getElementById('ayudaPrecioMezcla');
    var mezclaSubtotalEstimado = document.getElementById('mezclaSubtotalEstimado');
    var totalGalonesMezcla = document.getElementById('totalGalonesMezcla');
    var mensajeErrorMezcla = document.getElementById('mensajeErrorMezcla');

    // Array para almacenar los items del presupuesto y catalogo
    var itemsPresupuesto = [];
    var insumosDisponibles = [];
    var clientesDisponibles = [];
    var tipoFiltroActual = 'todos';

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
        busquedaInsumo.addEventListener('input', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                aplicarFiltros();
            }, 150);
        });
    }

    // Boton para limpiar busqueda
    if (btnLimpiarBusqueda) {
        btnLimpiarBusqueda.addEventListener('click', function() {
            if (busquedaInsumo) {
                busquedaInsumo.value = '';
                aplicarFiltros();
                busquedaInsumo.focus();
            }
        });
    }

    // Botones de filtro por tipo (Solo Bases y Simples)
    var botonesFiltro = document.querySelectorAll('.btn-filtro-tipo');
    botonesFiltro.forEach(function(btn) {
        btn.addEventListener('click', function() {
            botonesFiltro.forEach(function(b) {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary', 'active');
            tipoFiltroActual = this.dataset.tipo;
            aplicarFiltros();
        });
    });

    // Evento para enviar el formulario de presupuesto
    formularioPresupuesto.addEventListener('submit', function(evento) {
        evento.preventDefault();
        guardarPresupuesto();
    });

    // Cargar clientes desde el servidor
    function cargarClientes() {
        fetch('/SP%20Perfect%20Color/presupuesto/obtenerClientesAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    clientesDisponibles = resultado.datos.clientes || [];
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

    // Cargar insumos desde el servidor
    function cargarInsumos() {
        fetch('/SP%20Perfect%20Color/presupuesto/obtenerInsumosAjax')
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    // Excluir cualquier producto con id_tipo_producto == 3 (Preparados no se venden directos)
                    insumosDisponibles = (resultado.datos.insumos || []).filter(function(i) {
                        return parseInt(i.id_tipo_producto) !== 3;
                    });
                    aplicarFiltros();
                } else {
                    console.error('Error del servidor:', resultado.mensaje);
                    if (listaInsumosDisponibles) {
                        listaInsumosDisponibles.innerHTML = '<div class="text-center text-danger py-4">Error al cargar insumos</div>';
                    }
                }
            })
            .catch(function(error) {
                console.error('Error al cargar insumos:', error);
                if (listaInsumosDisponibles) {
                    listaInsumosDisponibles.innerHTML = '<div class="text-center text-danger py-4">Error de conexion al cargar insumos</div>';
                }
            });
    }

    // Llena el select de clientes
    function llenarSelectClientes() {
        if (!clientePresupuesto) return;

        clientePresupuesto.innerHTML = '<option value="">Seleccione un cliente...</option>';
        if (clientesDisponibles.length === 0) {
            clientePresupuesto.innerHTML += '<option value="" disabled>No hay clientes registrados</option>';
            return;
        }

        clientesDisponibles.forEach(function(cliente) {
            var opcion = document.createElement('option');
            opcion.value = cliente.id_cliente;
            opcion.textContent = cliente.cedula + ' - ' + cliente.nombres + ' ' + cliente.apellidos;
            clientePresupuesto.appendChild(opcion);
        });
    }

    // Aplica busqueda y filtro por tipo simultaneamente
    function aplicarFiltros() {
        var texto = busquedaInsumo ? busquedaInsumo.value.trim().toLowerCase() : '';

        var filtrados = insumosDisponibles.filter(function(insumo) {
            // Filtro por texto
            var coincideTexto = !texto ||
                (insumo.nombre && insumo.nombre.toLowerCase().indexOf(texto) !== -1) ||
                (insumo.codigo && insumo.codigo.toLowerCase().indexOf(texto) !== -1) ||
                (insumo.rubro_nombre && insumo.rubro_nombre.toLowerCase().indexOf(texto) !== -1);

            // Filtro por tipo
            var coincideTipo = true;
            if (tipoFiltroActual !== 'todos') {
                var tipoId = parseInt(insumo.id_tipo_producto);
                coincideTipo = (tipoId === parseInt(tipoFiltroActual));
            }

            return coincideTexto && coincideTipo;
        });

        mostrarInsumosDisponibles(filtrados);
    }

    // Muestra la lista de insumos en el catalogo
    function mostrarInsumosDisponibles(insumos) {
        if (!listaInsumosDisponibles) return;

        if (contadorInsumos) {
            contadorInsumos.textContent = insumos.length + ' disponible' + (insumos.length === 1 ? '' : 's');
        }

        listaInsumosDisponibles.innerHTML = '';

        if (insumos.length === 0) {
            listaInsumosDisponibles.innerHTML =
                '<div class="text-center text-muted py-5">' +
                    '<i class="bi bi-search fs-2 d-block mb-2 text-secondary opacity-50"></i>' +
                    '<span class="fw-semibold">No se encontraron productos</span>' +
                    '<div class="small text-muted mt-1">Pruebe con otro término o cambie el filtro de tipo</div>' +
                '</div>';
            return;
        }

        insumos.forEach(function(insumo) {
            var yaAgregado = itemsPresupuesto.some(function(item) {
                return !item.es_mezcla && item.id_insumo === insumo.id_insumo;
            });

            // Badge de tipo con maximo contraste
            var tipoId = parseInt(insumo.id_tipo_producto);
            var tipoNom = tipoId === 1 ? 'Base' : 'Simple';
            var badgeTipoClass = tipoId === 1 ? 'badge-tipo-base' : 'badge-tipo-simple';
            var iconTipo = tipoId === 1 ? 'bi-droplet-half' : 'bi-box-seam';

            // Indicador de stock
            var stockVal = parseFloat(insumo.stock_actual) || 0;
            var stockMin = parseFloat(insumo.stock_minimo) || 0;
            var stockCls = stockVal <= stockMin ? 'catalogo-stock-alerta' : 'catalogo-stock-ok';
            var stockIcon = stockVal <= stockMin ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
            var stockTexto = (stockVal <= stockMin ? 'Stock bajo: ' : 'Stock: ') + Math.round(stockVal);

            var div = document.createElement('div');
            div.className = 'catalogo-card-item' + (yaAgregado ? ' item-agregado' : '');
            div.id = 'catalogo-item-' + insumo.id_insumo;

            div.innerHTML =
                '<div class="catalogo-info">' +
                    '<div class="d-flex align-items-center gap-1 mb-1">' +
                        '<span class="catalogo-codigo">' + insumo.codigo + '</span>' +
                        '<span class="' + badgeTipoClass + '"><i class="bi ' + iconTipo + ' me-1"></i>' + tipoNom + '</span>' +
                    '</div>' +
                    '<div class="catalogo-nombre" title="' + insumo.nombre + '">' + insumo.nombre + '</div>' +
                    '<div class="catalogo-meta">' +
                        '<span class="catalogo-stock-pill ' + stockCls + '"><i class="bi ' + stockIcon + '"></i>' + stockTexto + '</span>' +
                        (insumo.rubro_nombre ? '<span class="text-muted small"><i class="bi bi-tag me-1"></i>' + insumo.rubro_nombre + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="catalogo-precio-box">' +
                    '<span class="catalogo-precio-val">$ ' + formatearMoneda(insumo.precio_venta) + '</span>' +
                    (yaAgregado
                        ? '<button type="button" class="btn btn-sm btn-secondary btn-catalogo-add disabled" disabled><i class="bi bi-check2 me-1"></i>Agregado</button>'
                        : '<button type="button" class="btn btn-sm btn-outline-primary btn-catalogo-add btn-agregar"><i class="bi bi-plus-lg me-1"></i>Agregar</button>'
                    ) +
                '</div>';

            var btnAdd = div.querySelector('.btn-agregar');
            if (btnAdd) {
                btnAdd.addEventListener('click', function() {
                    agregarItem(insumo);
                });
            }

            listaInsumosDisponibles.appendChild(div);
        });
    }

    // Agrega un insumo del catalogo a la tabla de items
    function agregarItem(insumo) {
        var existe = itemsPresupuesto.some(function(item) {
            return !item.es_mezcla && item.id_insumo === insumo.id_insumo;
        });

        if (existe) {
            mostrarNotificacion('Este insumo ya fue agregado', 'error');
            return;
        }

        var nuevoItem = {
            es_mezcla: false,
            id_insumo: insumo.id_insumo,
            insumo_codigo: insumo.codigo,
            insumo_nombre: insumo.nombre,
            id_tipo_producto: insumo.id_tipo_producto,
            tipo_producto_nombre: parseInt(insumo.id_tipo_producto) === 1 ? 'Base' : 'Simple',
            cantidad: 1,
            precio_unitario: parseFloat(insumo.precio_venta) || 0,
            subtotal: parseFloat(insumo.precio_venta) || 0
        };

        itemsPresupuesto.push(nuevoItem);
        actualizarTablaItems();

        // Actualizar visualmente la tarjeta del catalogo
        var card = document.getElementById('catalogo-item-' + insumo.id_insumo);
        if (card) {
            card.classList.add('item-agregado');
            var btnBox = card.querySelector('.catalogo-precio-box');
            if (btnBox) {
                var btn = btnBox.querySelector('.btn-catalogo-add');
                if (btn) {
                    btn.className = 'btn btn-sm btn-secondary btn-catalogo-add disabled';
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Agregado';
                }
            }
        }
    }

    // Actualiza la tabla de items y recalcula totales
    function actualizarTablaItems() {
        if (!cuerpoTablaItems || !totalPresupuesto) return;

        // Ocultar fila vacia si hay items
        if (filaVacia) {
            filaVacia.style.display = itemsPresupuesto.length > 0 ? 'none' : '';
        }

        // Limpiar filas anteriores excepto filaVacia
        var filas = cuerpoTablaItems.querySelectorAll('tr:not(#filaVacia)');
        filas.forEach(function(fila) { fila.remove(); });

        var total = 0;
        var cantidadTotalItems = itemsPresupuesto.length;

        // Renderizar cada item
        itemsPresupuesto.forEach(function(item, indice) {
            var fila = document.createElement('tr');

            if (item.es_mezcla) {
                // Renglón de Pintura Preparada (Mezcla de Colorimetría)
                fila.innerHTML =
                    '<td>' +
                        '<div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="' + item.insumo_nombre + '">' +
                            '<i class="bi bi-palette-fill text-success me-1"></i>' + item.insumo_nombre +
                        '</div>' +
                        '<div class="small text-muted text-truncate" style="max-width: 250px;" title="' + (item.resumen_formula || '') + '">' +
                            '<strong>' + (item.presentacion || '') + '</strong>: ' + (item.resumen_formula || '') +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-1 mt-1">' +
                            '<span class="badge bg-success" style="font-size: 0.68rem; padding: 0.25em 0.5em;"><i class="bi bi-palette-fill me-1"></i>Preparado</span>' +
                            '<span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">' + item.presentacion + '</span>' +
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
                    '</td>';
            } else {
                // Renglón de Producto Directo (Simple o Base pura)
                var tipoId = parseInt(item.id_tipo_producto);
                var tipoNom = tipoId === 1 ? 'Base' : 'Simple';
                var badgeCls = tipoId === 1 ? 'badge-tipo-base' : 'badge-tipo-simple';

                fila.innerHTML =
                    '<td>' +
                        '<div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="' + item.insumo_nombre + '">' + item.insumo_nombre + '</div>' +
                        '<div class="d-flex align-items-center gap-1 mt-1">' +
                            '<span class="badge bg-light text-muted border" style="font-size: 0.7rem;">' + item.insumo_codigo + '</span>' +
                            '<span class="' + badgeCls + '" style="font-size: 0.68rem; padding: 0.2em 0.5em;">' + tipoNom + '</span>' +
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
                        '<span class="item-presupuesto-subtotal">$ ' + formatearMoneda(item.subtotal) + '</span>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<button type="button" class="btn btn-sm btn-light text-danger btn-quitar-item" data-indice="' + indice + '" title="Quitar item">' +
                            '<i class="bi bi-trash-fill"></i>' +
                        '</button>' +
                    '</td>';
            }

            // Evento para cambiar cantidad
            var inputCant = fila.querySelector('.item-presupuesto-cantidad');
            inputCant.addEventListener('input', function() {
                var idx = parseInt(this.dataset.indice);
                var val = parseFloat(this.value);
                if (!isNaN(val) && val > 0) {
                    itemsPresupuesto[idx].cantidad = val;
                    itemsPresupuesto[idx].subtotal = val * itemsPresupuesto[idx].precio_unitario;
                    fila.querySelector('.item-presupuesto-subtotal').textContent = '$ ' + formatearMoneda(itemsPresupuesto[idx].subtotal);
                    recalcularTotalGlobal();
                }
            });

            // Evento para cambiar precio
            var inputPrecio = fila.querySelector('.item-presupuesto-precio');
            inputPrecio.addEventListener('input', function() {
                var idx = parseInt(this.dataset.indice);
                var val = parseFloat(this.value);
                if (!isNaN(val) && val >= 0) {
                    itemsPresupuesto[idx].precio_unitario = val;
                    itemsPresupuesto[idx].subtotal = itemsPresupuesto[idx].cantidad * val;
                    fila.querySelector('.item-presupuesto-subtotal').textContent = '$ ' + formatearMoneda(itemsPresupuesto[idx].subtotal);
                    recalcularTotalGlobal();
                }
            });

            // Evento para quitar item
            fila.querySelector('.btn-quitar-item').addEventListener('click', function() {
                var idx = parseInt(this.dataset.indice);
                var itemEliminado = itemsPresupuesto[idx];
                itemsPresupuesto.splice(idx, 1);
                actualizarTablaItems();

                // Restaurar boton en catalogo si era un producto simple
                if (itemEliminado && !itemEliminado.es_mezcla) {
                    var card = document.getElementById('catalogo-item-' + itemEliminado.id_insumo);
                    if (card) {
                        card.classList.remove('item-agregado');
                        var btnBox = card.querySelector('.catalogo-precio-box');
                        if (btnBox) {
                            var btnOld = btnBox.querySelector('.btn-catalogo-add');
                            if (btnOld) btnOld.remove();
                            var nuevoBtn = document.createElement('button');
                            nuevoBtn.type = 'button';
                            nuevoBtn.className = 'btn btn-sm btn-outline-primary btn-catalogo-add btn-agregar';
                            nuevoBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Agregar';
                            nuevoBtn.addEventListener('click', function() {
                                var insumoOrig = insumosDisponibles.find(function(i) { return i.id_insumo === itemEliminado.id_insumo; });
                                if (insumoOrig) agregarItem(insumoOrig);
                            });
                            btnBox.appendChild(nuevoBtn);
                        }
                    }
                }
            });

            cuerpoTablaItems.insertBefore(fila, filaVacia);
            total += item.subtotal;
        });

        // Actualizar badges y totales
        if (badgeCantidadItems) {
            badgeCantidadItems.textContent = cantidadTotalItems + ' item' + (cantidadTotalItems === 1 ? '' : 's');
        }
        if (resumenConteoItems) {
            resumenConteoItems.textContent = cantidadTotalItems + ' item' + (cantidadTotalItems === 1 ? '' : 's') + ' en el presupuesto';
        }
        totalPresupuesto.textContent = '$ ' + formatearMoneda(total);
    }

    // Recalcula solo el total global sumando todos los subtotales
    function recalcularTotalGlobal() {
        var total = 0;
        itemsPresupuesto.forEach(function(item) {
            total += item.subtotal;
        });
        totalPresupuesto.textContent = '$ ' + formatearMoneda(total);
    }

    // ==========================================
    // LÓGICA DE PREPARACIÓN DE PINTURA (MEZCLAS)
    // ==========================================

    // Obtener lista de insumos base concentrados disponibles
    function obtenerBasesDisponibles() {
        return insumosDisponibles.filter(function(i) {
            return parseInt(i.id_tipo_producto) === 1;
        });
    }

    // Abrir el modal de preparación de pintura
    if (btnModalPrepararMezcla) {
        btnModalPrepararMezcla.addEventListener('click', function() {
            var bases = obtenerBasesDisponibles();
            if (bases.length === 0) {
                mostrarNotificacion('No hay tintes base en el inventario para formular mezclas.', 'error');
                return;
            }

            // Resetear formulario
            if (formularioMezcla) formularioMezcla.reset();
            if (cuerpoBasesMezcla) cuerpoBasesMezcla.innerHTML = '';
            if (mezclaCantidad) mezclaCantidad.value = '1';
            if (mezclaPresentacion) mezclaPresentacion.value = '1/4 Galón';
            if (mensajeErrorMezcla) mensajeErrorMezcla.classList.add('d-none');

            // Agregar primera fila de tinte base por defecto
            agregarFilaBase();

            recalcularCostosMezcla();

            if (modalPrepararMezcla) {
                bootstrap.Modal.getOrCreateInstance(modalPrepararMezcla).show();
            }
        });
    }

    // Boton agregar otra fila de base concentrada
    if (btnAgregarFilaBase) {
        btnAgregarFilaBase.addEventListener('click', function() {
            agregarFilaBase();
        });
    }

    // Agrega una fila de base concentrada a la tabla de formulación (hasta 6)
    function agregarFilaBase() {
        if (!cuerpoBasesMezcla) return;
        var totalFilas = cuerpoBasesMezcla.querySelectorAll('tr').length;
        if (totalFilas >= 6) {
            mostrarNotificacion('El límite máximo es de 6 tintes base concentrados por mezcla.', 'error');
            return;
        }

        var bases = obtenerBasesDisponibles();
        var fila = document.createElement('tr');

        // Construir opciones de bases
        var opcionesBasesHtml = '<option value="">Seleccione base...</option>';
        bases.forEach(function(b) {
            var stockInt = Math.round(parseFloat(b.stock_actual) || 0);
            opcionesBasesHtml += '<option value="' + b.id_insumo + '" data-precio="' + b.precio_venta + '" data-nombre="' + b.nombre + '" data-codigo="' + b.codigo + '">' +
                                     b.codigo + ' - ' + b.nombre + ' (' + stockInt + ' gal)' +
                                 '</option>';
        });

        fila.innerHTML =
            '<td>' +
                '<select class="form-select form-select-sm select-base-tinte" required>' +
                    opcionesBasesHtml +
                '</select>' +
            '</td>' +
            '<td>' +
                '<div class="d-flex gap-1 align-items-center">' +
                    '<select class="form-select form-select-sm select-fraccion-tinte" style="min-width: 140px;">' +
                        '<option value="64">1/2 Galón (64/128)</option>' +
                        '<option value="32" selected>1/4 Galón (32/128)</option>' +
                        '<option value="16">1/8 Galón (16/128)</option>' +
                        '<option value="8">1/16 Galón (8/128)</option>' +
                        '<option value="4">1/32 Galón (4/128)</option>' +
                        '<option value="2">1/64 Galón (2/128)</option>' +
                        '<option value="1">1/128 Galón (1/128)</option>' +
                        '<option value="custom">Otra fracc. (/128)</option>' +
                    '</select>' +
                    '<input type="number" class="form-control form-control-sm input-fraccion-custom d-none text-center" min="1" step="1" value="32" style="width: 70px;" placeholder="/128">' +
                '</div>' +
            '</td>' +
            '<td class="text-end">' +
                '<span class="badge bg-light text-dark border consumo-galon-text">0.2500 gal</span>' +
            '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-base" title="Quitar base"><i class="bi bi-x-lg"></i></button>' +
            '</td>';

        var selectBase = fila.querySelector('.select-base-tinte');
        var selectFraccion = fila.querySelector('.select-fraccion-tinte');
        var inputCustom = fila.querySelector('.input-fraccion-custom');
        var labelConsumo = fila.querySelector('.consumo-galon-text');
        var btnEliminar = fila.querySelector('.btn-eliminar-base');

        function actualizarConsumoFila() {
            var fraccion128 = selectFraccion.value === 'custom' ? (parseInt(inputCustom.value) || 0) : parseInt(selectFraccion.value);
            var galones = fraccion128 / 128.0;
            labelConsumo.textContent = galones.toFixed(4) + ' gal';
            recalcularCostosMezcla();
        }

        selectBase.addEventListener('change', recalcularCostosMezcla);

        selectFraccion.addEventListener('change', function() {
            if (this.value === 'custom') {
                inputCustom.classList.remove('d-none');
                inputCustom.focus();
            } else {
                inputCustom.classList.add('d-none');
                inputCustom.value = this.value;
            }
            actualizarConsumoFila();
        });

        inputCustom.addEventListener('input', function() {
            actualizarConsumoFila();
        });

        btnEliminar.addEventListener('click', function() {
            fila.remove();
            actualizarContadorBases();
            recalcularCostosMezcla();
        });

        cuerpoBasesMezcla.appendChild(fila);
        actualizarContadorBases();
        recalcularCostosMezcla();
    }

    function actualizarContadorBases() {
        if (!cuerpoBasesMezcla || !contadorBasesMezcla) return;
        var count = cuerpoBasesMezcla.querySelectorAll('tr').length;
        contadorBasesMezcla.textContent = count + '/6 bases';
        if (btnAgregarFilaBase) {
            btnAgregarFilaBase.disabled = count >= 6;
        }
    }

    // Recalcula el costo estimado de las bases y el subtotal
    function recalcularCostosMezcla() {
        if (!cuerpoBasesMezcla) return;
        var filas = cuerpoBasesMezcla.querySelectorAll('tr');
        var costoTotalBases = 0;
        var galonesTotales = 0;

        filas.forEach(function(fila) {
            var selectBase = fila.querySelector('.select-base-tinte');
            var selectFraccion = fila.querySelector('.select-fraccion-tinte');
            var inputCustom = fila.querySelector('.input-fraccion-custom');

            if (selectBase && selectBase.value) {
                var option = selectBase.selectedOptions[0];
                var precioGalon = parseFloat(option ? option.dataset.precio : 0) || 0;
                var fraccion128 = selectFraccion.value === 'custom' ? (parseInt(inputCustom.value) || 0) : (parseInt(selectFraccion.value) || 0);
                var consumoGalon = fraccion128 / 128.0;

                costoTotalBases += (consumoGalon * precioGalon);
                galonesTotales += consumoGalon;
            }
        });

        if (totalGalonesMezcla) {
            totalGalonesMezcla.textContent = 'Consumo total fórmula: ' + galonesTotales.toFixed(4) + ' gal';
        }

        if (ayudaPrecioMezcla) {
            ayudaPrecioMezcla.textContent = 'Costo estimado bases: $ ' + formatearMoneda(costoTotalBases);
        }

        // Si el precio de venta está vacío o es 0, sugerir costo redondeado
        if (mezclaPrecioVenta && (!mezclaPrecioVenta.value || parseFloat(mezclaPrecioVenta.value) === 0)) {
            if (costoTotalBases > 0) {
                mezclaPrecioVenta.value = (costoTotalBases * 1.3).toFixed(2); // Sugiere margen 30%
            }
        }

        var cant = parseInt(mezclaCantidad.value) || 1;
        var precio = parseFloat(mezclaPrecioVenta.value) || 0;
        if (mezclaSubtotalEstimado) {
            mezclaSubtotalEstimado.textContent = '$ ' + formatearMoneda(cant * precio);
        }
    }

    if (mezclaCantidad) {
        mezclaCantidad.addEventListener('input', recalcularCostosMezcla);
    }
    if (mezclaPrecioVenta) {
        mezclaPrecioVenta.addEventListener('input', recalcularCostosMezcla);
    }

    // Guardar formulación de mezcla en la tabla de items
    if (formularioMezcla) {
        formularioMezcla.addEventListener('submit', function(e) {
            e.preventDefault();

            var nombre = mezclaNombre.value.trim();
            if (!nombre) {
                mostrarErrorMezcla('El nombre o identificación del color es obligatorio');
                return;
            }

            var presentacion = mezclaPresentacion.value;
            var cantidad = parseInt(mezclaCantidad.value, 10);
            if (isNaN(cantidad) || cantidad < 1) {
                mostrarErrorMezcla('La cantidad de envases debe ser al menos 1');
                return;
            }

            var precioVenta = parseFloat(mezclaPrecioVenta.value);
            if (isNaN(precioVenta) || precioVenta <= 0) {
                mostrarErrorMezcla('El precio de venta por envase debe ser mayor a 0');
                return;
            }

            var filasBases = cuerpoBasesMezcla.querySelectorAll('tr');
            if (filasBases.length === 0) {
                mostrarErrorMezcla('Debe agregar al menos un tinte base concentrado a la formulación');
                return;
            }

            var mezclas = [];
            var basesIds = [];
            var resumenPartes = [];
            var tieneError = false;

            filasBases.forEach(function(fila) {
                if (tieneError) return;
                var selectBase = fila.querySelector('.select-base-tinte');
                var selectFraccion = fila.querySelector('.select-fraccion-tinte');
                var inputCustom = fila.querySelector('.input-fraccion-custom');

                var idBase = parseInt(selectBase.value);
                if (isNaN(idBase) || idBase < 1) {
                    mostrarErrorMezcla('Debe seleccionar una base en cada fila de la formulación');
                    tieneError = true;
                    return;
                }

                if (basesIds.indexOf(idBase) !== -1) {
                    mostrarErrorMezcla('No puede repetir el mismo tinte base en la mezcla. Ajuste la fracción.');
                    tieneError = true;
                    return;
                }
                basesIds.push(idBase);

                var fraccion128 = selectFraccion.value === 'custom' ? (parseInt(inputCustom.value) || 0) : parseInt(selectFraccion.value);
                if (fraccion128 < 1) {
                    mostrarErrorMezcla('La fracción de tinte debe ser al menos 1/128');
                    tieneError = true;
                    return;
                }

                var opt = selectBase.selectedOptions[0];
                var nombreBase = opt ? opt.dataset.nombre : 'Base';
                var consumoGalon = fraccion128 / 128.0;

                mezclas.push({
                    id_producto_base: idBase,
                    nombre_base: nombreBase,
                    fraccion_128: fraccion128,
                    cantidad_consumida: consumoGalon
                });

                resumenPartes.push(nombreBase + ' ' + fraccion128 + '/128');
            });

            if (tieneError) return;

            // Crear item de mezcla para la cotización
            var nuevoItemMezcla = {
                es_mezcla: true,
                id_insumo: null,
                insumo_codigo: 'MEZCLA',
                insumo_nombre: nombre,
                descripcion: 'Pintura Preparada: ' + nombre + ' (' + presentacion + ')',
                presentacion: presentacion,
                id_tipo_producto: 3,
                tipo_producto_nombre: 'Preparado',
                resumen_formula: resumenPartes.join(', '),
                cantidad: cantidad,
                precio_unitario: precioVenta,
                subtotal: cantidad * precioVenta,
                mezclas: mezclas
            };

            itemsPresupuesto.push(nuevoItemMezcla);
            actualizarTablaItems();

            bootstrap.Modal.getInstance(modalPrepararMezcla).hide();
            mostrarNotificacion('Pintura preparada agregada al presupuesto', 'exito');
        });
    }

    function mostrarErrorMezcla(msg) {
        if (!mensajeErrorMezcla) return;
        mensajeErrorMezcla.textContent = msg;
        mensajeErrorMezcla.classList.remove('d-none');
        setTimeout(function() {
            mensajeErrorMezcla.classList.add('d-none');
        }, 4000);
    }

    // ==========================================
    // ENVÍO DE FORMULARIO DE PRESUPUESTO
    // ==========================================

    function guardarPresupuesto() {
        if (!clientePresupuesto.value) {
            mostrarError('Debe seleccionar un cliente');
            clientePresupuesto.focus();
            return;
        }

        if (itemsPresupuesto.length === 0) {
            mostrarError('Debe agregar al menos un item o formulación al presupuesto');
            return;
        }

        // Deshabilitar boton para prevenir doble envio
        if (btnGuardarPresupuesto) {
            btnGuardarPresupuesto.disabled = true;
            btnGuardarPresupuesto.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        }

        var formData = new FormData();
        formData.append('id_cliente', clientePresupuesto.value);
        formData.append('observaciones', document.getElementById('observacionesPresupuesto').value);

        var itemsSimplificados = itemsPresupuesto.map(function(item) {
            if (item.es_mezcla) {
                return {
                    tipo: 'mezcla',
                    descripcion: item.descripcion,
                    presentacion: item.presentacion,
                    cantidad: item.cantidad,
                    precio_unitario: item.precio_unitario,
                    mezclas: item.mezclas
                };
            }
            return {
                tipo: 'simple',
                id_insumo: item.id_insumo,
                descripcion: item.insumo_nombre,
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
                mostrarNotificacion('Presupuesto #' + resultado.datos.id_presupuesto + ' creado exitosamente. Total: $ ' + formatearMoneda(resultado.datos.total), 'exito');
                setTimeout(function() {
                    window.location.href = '/SP%20Perfect%20Color/presupuesto';
                }, 1200);
            } else {
                mostrarError(resultado.mensaje);
                if (btnGuardarPresupuesto) {
                    btnGuardarPresupuesto.disabled = false;
                    btnGuardarPresupuesto.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Crear Presupuesto';
                }
            }
        })
        .catch(function(error) {
            console.error('Error al guardar presupuesto:', error);
            mostrarError('Error de conexion al guardar el presupuesto');
            if (btnGuardarPresupuesto) {
                btnGuardarPresupuesto.disabled = false;
                btnGuardarPresupuesto.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Crear Presupuesto';
            }
        });
    }

    // Muestra un mensaje de error en el formulario
    function mostrarError(mensaje) {
        if (!mensajeError) {
            mostrarNotificacion(mensaje, 'error');
            return;
        }
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
        mensajeError.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(function() {
            mensajeError.classList.add('d-none');
        }, 5000);
    }
});