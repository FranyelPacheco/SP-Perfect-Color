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
    var fraccionFiltroActual = 'todos';
    var contenedorFiltrosFraccion = document.getElementById('filtrosFraccionBase');
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

            if (tipoFiltroActual === '2') {
                if (contenedorFiltrosFraccion) contenedorFiltrosFraccion.classList.add('d-none');
                fraccionFiltroActual = 'todos';
                resetBotonesFraccion();
            } else {
                if (contenedorFiltrosFraccion) contenedorFiltrosFraccion.classList.remove('d-none');
            }

            aplicarFiltros();
        });
    });

    function resetBotonesFraccion() {
        var btns = document.querySelectorAll('.btn-filtro-fraccion');
        btns.forEach(function(b) {
            b.classList.remove('btn-primary', 'btn-outline-primary', 'active');
            b.classList.add('btn-outline-secondary');
            if (b.dataset.fraccion === 'todos') {
                b.classList.remove('btn-outline-secondary');
                b.classList.add('btn-outline-primary', 'active');
            }
        });
    }

    var botonesFraccion = document.querySelectorAll('.btn-filtro-fraccion');
    botonesFraccion.forEach(function(btn) {
        btn.addEventListener('click', function() {
            botonesFraccion.forEach(function(b) {
                b.classList.remove('btn-primary', 'btn-outline-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-outline-primary', 'active');
            fraccionFiltroActual = this.dataset.fraccion;
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

            // Filtro por fraccion / presentacion (funciona en Todos y en Bases)
            var coincideFraccion = true;
            if (fraccionFiltroActual !== 'todos') {
                var frac = (insumo.fraccion || '').trim();
                var nom = (insumo.nombre || '').toLowerCase();
                var um = (insumo.unidad_medida || '').toLowerCase();
                if (fraccionFiltroActual === '1') {
                    var esGalon = (parseInt(insumo.id_tipo_producto) === 1) || nom.indexOf('galon') !== -1 || nom.indexOf('galón') !== -1 || um === 'galon' || um === 'galón';
                    coincideFraccion = esGalon && (frac === '1') && (nom.indexOf('1/') === -1);
                } else {
                    coincideFraccion = (frac === fraccionFiltroActual);
                }
            }

            return coincideTexto && coincideTipo && coincideFraccion;
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

            // Indicador de stock físico y comprometido
            var stockVal = Math.floor(parseFloat(insumo.stock_actual) || 0);
            var stockMin = Math.floor(parseFloat(insumo.stock_minimo) || 0);
            var stockComp = Math.floor(parseFloat(insumo.stock_comprometido) || 0);
            var stockLibre = Math.max(0, stockVal - stockComp);
            var sinStockFisico = stockVal <= 0;

            var stockCls = (stockVal <= stockMin || stockLibre <= 0) ? 'catalogo-stock-alerta' : 'catalogo-stock-ok';
            var stockIcon = (stockVal <= stockMin || stockLibre <= 0) ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
            var stockTexto = (stockVal <= stockMin ? 'Stock bajo: ' : 'Stock: ') + stockVal;

            var badgeComprometidoHtml = '';
            if (stockComp > 0) {
                var agotadoPorCompromiso = stockLibre <= 0;
                badgeComprometidoHtml =
                    '<span class="badge ' + (agotadoPorCompromiso ? 'bg-danger text-white' : 'bg-warning text-dark') + ' border" style="font-size: 0.7rem;" title="Unidades en presupuestos pendientes de aprobar">' +
                        '<i class="bi bi-clock-history me-1"></i>Comprometido: ' + stockComp + (agotadoPorCompromiso ? ' (Todo reservado)' : '') +
                    '</span>';
            }

            var div = document.createElement('div');
            div.className = 'catalogo-card-item' + (yaAgregado ? ' item-agregado' : '');
            div.id = 'catalogo-item-' + insumo.id_insumo;

            var botonHtml = '';
            if (yaAgregado) {
                botonHtml = '<button type="button" class="btn btn-sm btn-secondary btn-catalogo-add disabled" disabled><i class="bi bi-check2 me-1"></i>Agregado</button>';
            } else if (sinStockFisico) {
                botonHtml = '<button type="button" class="btn btn-sm btn-outline-danger btn-catalogo-add disabled" disabled><i class="bi bi-slash-circle me-1"></i>Agotado</button>';
            } else {
                botonHtml = '<button type="button" class="btn btn-sm btn-outline-primary btn-catalogo-add btn-agregar"><i class="bi bi-plus-lg me-1"></i>Agregar</button>';
            }

            div.innerHTML =
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

        var stockFisico = Math.floor(parseFloat(insumo.stock_actual) || 0);
        var stockComprometido = Math.floor(parseFloat(insumo.stock_comprometido) || 0);
        var stockLibre = Math.max(0, stockFisico - stockComprometido);

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

        var nuevoItem = {
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
                        '<div class="fw-bold text-dark text-truncate" style="max-width: 250px;" title="' + escapeHtml(item.insumo_nombre) + '">' +
                            '<i class="bi bi-palette-fill text-success me-1"></i>' + escapeHtml(item.insumo_nombre) +
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
                var maxStock = item.stock_actual || 1;
                var stockComp = item.stock_comprometido || 0;
                var stockLibre = Math.max(0, maxStock - stockComp);
                var superaComprometido = (stockComp > 0 && item.cantidad > stockLibre);

                fila.innerHTML =
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
                    '</td>';
            }

            // Evento para cambiar cantidad (solo enteros y respetando límite de stock)
            var inputCant = fila.querySelector('.item-presupuesto-cantidad');
            inputCant.addEventListener('keydown', function(e) {
                if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '-' || e.key === '+') {
                    e.preventDefault();
                }
            });
            inputCant.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                var idx = parseInt(this.dataset.indice);
                var val = parseInt(this.value, 10);
                var it = itemsPresupuesto[idx];
                if (!it) return;

                if (!isNaN(val) && val >= 1) {
                    if (!it.es_mezcla && it.stock_actual && val > it.stock_actual) {
                        val = it.stock_actual;
                        this.value = val;
                        mostrarNotificacion('No puede superar el límite de stock disponible (' + it.stock_actual + ' unidades).', 'error');
                    }

                    if (!it.es_mezcla && it.stock_comprometido > 0) {
                        var libre = Math.max(0, it.stock_actual - it.stock_comprometido);
                        var divAviso = fila.querySelector('.aviso-comprometido-item');
                        if (val > libre) {
                            if (divAviso) divAviso.classList.remove('d-none');
                            mostrarNotificacion(
                                '¡Atención! "' + it.insumo_nombre + '" tiene ' + it.stock_comprometido +
                                ' unidad(es) comprometida(s) en otros presupuestos pendientes (Libres: ' + libre + ').',
                                'error'
                            );
                        } else {
                            if (divAviso) divAviso.classList.add('d-none');
                        }
                    }

                    it.cantidad = val;
                    it.subtotal = val * it.precio_unitario;
                    fila.querySelector('.item-presupuesto-subtotal').textContent = '$ ' + formatearMoneda(it.subtotal);
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
        var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        if (tasa > 0) {
            totalPresupuesto.innerHTML = '$ ' + formatearMoneda(total) + '<div class="text-white-50 fw-normal mt-1" style="font-size: 0.85rem;">Equiv: Bs. ' + formatearMoneda(total * tasa) + '</div>';
        } else {
            totalPresupuesto.textContent = '$ ' + formatearMoneda(total);
        }
    }

    // Recalcula solo el total global sumando todos los subtotales
    function recalcularTotalGlobal() {
        var total = 0;
        itemsPresupuesto.forEach(function(item) {
            total += item.subtotal;
        });
        var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
        if (tasa > 0) {
            totalPresupuesto.innerHTML = '$ ' + formatearMoneda(total) + '<div class="text-white-50 fw-normal mt-1" style="font-size: 0.85rem;">Equiv: Bs. ' + formatearMoneda(total * tasa) + '</div>';
        } else {
            totalPresupuesto.textContent = '$ ' + formatearMoneda(total);
        }
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
            return {
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
                    btnGuardarPresupuesto.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar';
                }
            }
        })
        .catch(function(error) {
            console.error('Error al guardar presupuesto:', error);
            mostrarError('Error de conexion al guardar el presupuesto');
            if (btnGuardarPresupuesto) {
                btnGuardarPresupuesto.disabled = false;
                btnGuardarPresupuesto.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar';
            }
        });
    }

    // Muestra un mensaje de error en el formulario
    function mostrarError(mensaje) {
        if (!mensajeError) {
            mostrarNotificacion(mensaje, 'error');
            return;
        }
        mensajeError.setAttribute('role', 'alert');
        mensajeError.textContent = mensaje;
        mensajeError.classList.remove('d-none');
        mensajeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});