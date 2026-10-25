// Archivo: notaEntregaCompra.js
// Manejo de Notas de Entrega de Compra a Proveedores (Recepción de Insumos, aumento de stock y CxP)

document.addEventListener('DOMContentLoaded', function() {
    var tablaElement = document.getElementById('tablaNotasCompra');
    var busquedaInput = document.getElementById('busquedaNotasCompra');

    // === GESTIÓN DE LISTADO (DATATABLES) ===
    if (tablaElement) {
        cargarNotasCompra();

        if (busquedaInput) {
            busquedaInput.addEventListener('keyup', function() {
                if ($.fn.DataTable.isDataTable('#tablaNotasCompra')) {
                    $('#tablaNotasCompra').DataTable().search(this.value).draw();
                }
            });
        }

        $(document).on('click', '.btn-anular-nota-compra', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            if (!id) return;

            confirmarConModal('Anular Recepción de Compra', '¿Está seguro de anular esta nota de entrega? Se revertirá el incremento de stock de los insumos asociados.', function() {
                ejecutarAnulacion(id);
            });
        });
    }

    function ejecutarAnulacion(id) {
        var fd = new FormData();
        fd.append('id', id);

        fetch('/SP%20Perfect%20Color/notaEntregaCompra/anular', {
            method: 'POST',
            body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.estado === 'exito') {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(res.mensaje, 'exito');
                }
                cargarNotasCompra();
            } else {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(res.mensaje || 'Error al anular', 'error');
                }
            }
        })
        .catch(function() {
            if (typeof mostrarNotificacion === 'function') {
                mostrarNotificacion('Error de conexión', 'error');
            }
        });
    }

    function cargarNotasCompra() {
        fetch('/SP%20Perfect%20Color/notaEntregaCompra/listarAjax')
            .then(function(r) { return r.json(); })
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
                        data: 'id_nota_entrega',
                        render: function(data) {
                            return '<strong class="text-primary">#' + String(data).padStart(5, '0') + '</strong>';
                        }
                    },
                    {
                        data: 'fecha',
                        render: function(data) {
                            if (!data) return '-';
                            var d = new Date(data.replace(' ', 'T'));
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
                            var num = parseFloat(data || 0);
                            var tasa = parseFloat(row.tasa_bcv || window.TASA_CAMBIO_HOY || 0);
                            var html = '<strong>$ ' + num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>';
                            if (tasa > 0) {
                                var bs = num * tasa;
                                html += '<div class="text-muted small">Bs. ' + bs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</div>';
                            }
                            return html;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            var esCredito = row.id_cuenta_pagar && (parseFloat(row.saldo_pendiente) > 0 || row.condicion_compra === 'credito');
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
                                '<a href="/SP%20Perfect%20Color/notaEntregaCompra/ver?id=' + row.id_nota_entrega + '" class="btn btn-sm btn-info text-white" title="Ver Detalle">' +
                                '<i class="bi bi-eye"></i>' +
                                '</a>' +
                                '<button type="button" class="btn btn-sm btn-outline-danger btn-anular-nota-compra" data-id="' + row.id_nota_entrega + '" title="Anular Recepción">' +
                                '<i class="bi bi-x-circle"></i>' +
                                '</button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaNotasCompra').DataTable();
        table.clear();
        notas.forEach(function(n) {
            table.row.add(n);
        });
        table.draw();
    }

    // === GESTIÓN DE FORMULARIO DE REGISTRO ===
    var formNotaCompra = document.getElementById('formNotaCompra');
    if (formNotaCompra) {
        var condicionSelect = document.getElementById('condicionPago');
        var campoVencimiento = document.getElementById('campoVencimiento');
        var camposContado = document.getElementById('camposContado');
        var selectProducto = document.getElementById('selectProducto');
        var cantidadInput = document.getElementById('cantidadItem');
        var precioInput = document.getElementById('precioItem');
        var btnAgregar = document.getElementById('btnAgregarItem');
        var cuerpoDetalle = document.getElementById('cuerpoDetalleCompra');
        var totalDisplay = document.getElementById('totalCompraDisplay');
        var totalBsDisplay = document.getElementById('totalCompraBsDisplay');
        var alertaError = document.getElementById('alertaErrorCompra');

        var inputBuscarProd = document.getElementById('inputBuscarProductoCompra');
        var btnLimpiarProd = document.getElementById('btnLimpiarProductoCompra');
        var listaSugerenciasProd = document.getElementById('listaSugerenciasProductoCompra');
        var contenedorBancoCompra = document.getElementById('contenedorBancoCompra');
        var contenedorReferenciaCompra = document.getElementById('contenedorReferenciaCompra');
        var idTipoPagoSelect = document.getElementById('idTipoPago');

        // Campos de Abono Inicial (Crédito)
        var montoInicialInput = document.getElementById('montoInicialCompra');
        var montoInicialBsDisplay = document.getElementById('montoInicialCompraBsDisplay');
        var saldoPendienteDisplay = document.getElementById('saldoPendienteCompraDisplay');
        var saldoPendienteBsDisplay = document.getElementById('saldoPendienteCompraBsDisplay');
        var contenedorMetodoInicial = document.getElementById('contenedorMetodoInicialCompra');
        var idTipoPagoInicialSelect = document.getElementById('idTipoPagoInicialCompra');
        var contenedorBancoInicial = document.getElementById('contenedorBancoInicialCompra');
        var contenedorRefInicial = document.getElementById('contenedorRefInicialCompra');

        var itemsCompra = [];
        var totalActualCompra = 0.00;
        var catalogoInsumos = Array.isArray(window.insumosCompraCatalogo) ? window.insumosCompraCatalogo : [];

        // Bloquear decimales/puntos en cantidadItem y rapidoStockMinimo
        [cantidadInput, document.getElementById('rapidoStockMinimo')].forEach(function(inp) {
            if (!inp) return;
            inp.addEventListener('keydown', function(e) {
                if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '-' || e.key === '+') {
                    e.preventDefault();
                }
            });
            inp.addEventListener('input', function() {
                var limpio = this.value.replace(/[^0-9]/g, '');
                if (this.value !== limpio) {
                    this.value = limpio;
                }
            });
        });

        // Toggle banco y referencia según método de pago Contado (solo si no es efectivo)
        function toggleBancoRefCompra() {
            if (!idTipoPagoSelect) return;
            var val = parseInt(idTipoPagoSelect.value || 0);
            if (val === 1 || val === 0) {
                if (contenedorBancoCompra) contenedorBancoCompra.style.display = 'none';
                if (contenedorReferenciaCompra) contenedorReferenciaCompra.style.display = 'none';
            } else {
                if (contenedorBancoCompra) contenedorBancoCompra.style.display = 'block';
                if (contenedorReferenciaCompra) contenedorReferenciaCompra.style.display = 'block';
            }
        }
        if (idTipoPagoSelect) {
            idTipoPagoSelect.addEventListener('change', toggleBancoRefCompra);
            toggleBancoRefCompra();
        }

        // Toggle banco y referencia para Abono Inicial en Crédito
        function toggleBancoRefInicialCompra() {
            if (!idTipoPagoInicialSelect) return;
            var val = parseInt(idTipoPagoInicialSelect.value || 0);
            if (val === 1 || val === 0) {
                if (contenedorBancoInicial) contenedorBancoInicial.style.display = 'none';
                if (contenedorRefInicial) contenedorRefInicial.style.display = 'none';
            } else {
                if (contenedorBancoInicial) contenedorBancoInicial.style.display = 'block';
                if (contenedorRefInicial) contenedorRefInicial.style.display = 'block';
            }
        }
        if (idTipoPagoInicialSelect) {
            idTipoPagoInicialSelect.addEventListener('change', toggleBancoRefInicialCompra);
            toggleBancoRefInicialCompra();
        }

        function actualizarCalculoAbonoInicial() {
            var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
            var abono = parseFloat(montoInicialInput ? montoInicialInput.value : 0) || 0;
            if (abono < 0) abono = 0;
            if (totalActualCompra > 0 && abono > totalActualCompra) {
                abono = totalActualCompra;
                if (montoInicialInput) montoInicialInput.value = abono.toFixed(2);
            }
            var saldo = Math.max(0, totalActualCompra - abono);

            if (saldoPendienteDisplay) {
                saldoPendienteDisplay.textContent = '$ ' + saldo.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            if (montoInicialBsDisplay) {
                var bsAbono = abono * tasa;
                montoInicialBsDisplay.textContent = 'Equiv: Bs. ' + bsAbono.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            if (saldoPendienteBsDisplay) {
                var bsSaldo = saldo * tasa;
                saldoPendienteBsDisplay.textContent = '(Bs. ' + bsSaldo.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')';
            }
            if (contenedorMetodoInicial) {
                if (abono > 0) {
                    contenedorMetodoInicial.style.display = 'block';
                    toggleBancoRefInicialCompra();
                } else {
                    contenedorMetodoInicial.style.display = 'none';
                }
            }
        }
        if (montoInicialInput) {
            montoInicialInput.addEventListener('keydown', function(e) {
                if (['e', 'E', '+', '-'].includes(e.key)) {
                    e.preventDefault();
                }
            });
            montoInicialInput.addEventListener('input', function() {
                var val = this.value.replace(/,/g, '.');
                val = val.replace(/[^0-9.]/g, '');
                var puntos = val.split('.');
                if (puntos.length > 2) {
                    val = puntos[0] + '.' + puntos.slice(1).join('');
                }
                var partes = val.split('.');
                if (partes[0].length > 7) {
                    partes[0] = partes[0].substring(0, 7);
                }
                if (partes.length > 1 && partes[1].length > 2) {
                    partes[1] = partes[1].substring(0, 2);
                }
                this.value = partes.length > 1 ? partes[0] + '.' + partes[1] : partes[0];
                actualizarCalculoAbonoInicial();
            });
        }

        var observacionesCompra = document.getElementById('observaciones');
        var contadorObsCompra = document.getElementById('contadorObservacionesCompra');
        if (observacionesCompra) {
            observacionesCompra.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\:\;\#\/]/g, '');
                if (this.value.length > 250) {
                    this.value = this.value.substring(0, 250);
                }
                var palabras = this.value.trim().split(/\s+/).filter(Boolean);
                if (palabras.length > 50) {
                    this.value = palabras.slice(0, 50).join(' ');
                }
                if (contadorObsCompra) {
                    var countP = this.value.trim().split(/\s+/).filter(Boolean).length;
                    contadorObsCompra.textContent = this.value.length + ' / 250 caracteres (' + countP + ' / 50 palabras)';
                }
            });
        }

        // Búsqueda integrada de productos con diseño limpio .buscador-dropdown-item
        if (inputBuscarProd && selectProducto && listaSugerenciasProd) {
            function renderizarSugerenciasCompra(filtro) {
                listaSugerenciasProd.innerHTML = '';
                var q = (filtro || '').toLowerCase().trim();
                var filtrados = catalogoInsumos.filter(function(ins) {
                    if (!q) return true;
                    var txt = ((ins.codigo || '') + ' ' + (ins.nombre || '')).toLowerCase();
                    return txt.includes(q);
                });

                if (filtrados.length === 0) {
                    listaSugerenciasProd.innerHTML = '<div class="p-3 text-muted small text-center"><i class="bi bi-search me-1"></i>No se encontraron productos</div>';
                } else {
                    filtrados.forEach(function(ins) {
                        var stock = Math.floor(parseFloat(ins.stock_actual || 0));
                        var pVenta = parseFloat(ins.precio_venta || 0);
                        var pCompra = parseFloat(ins.precio_compra || 0);
                        var item = document.createElement('div');
                        item.className = 'buscador-dropdown-item';
                        item.innerHTML =
                            '<div class="item-titulo">' + (ins.nombre || '') + '</div>' +
                            '<div class="item-meta">' +
                                '<span class="badge-codigo">' + (ins.codigo || '') + '</span>' +
                                '<span class="badge-stock ' + (stock <= 0 ? 'sin-stock' : '') + '">Stock actual: ' + stock + '</span>' +
                                '<span class="badge-precio">P. Venta: $ ' + pVenta.toFixed(2) + '</span>' +
                            '</div>';
                        item.addEventListener('click', function(ev) {
                            ev.preventDefault();
                            inputBuscarProd.value = ins.codigo + ' - ' + ins.nombre;
                            selectProducto.value = ins.id_producto;
                            selectProducto.setAttribute('data-nombre', ins.nombre);
                            selectProducto.setAttribute('data-codigo', ins.codigo);
                            selectProducto.setAttribute('data-precio', pCompra > 0 ? pCompra.toFixed(2) : '');
                            
                            precioInput.value = pCompra > 0 ? pCompra.toFixed(2) : '';
                            if (btnLimpiarProd) btnLimpiarProd.classList.remove('d-none');
                            listaSugerenciasProd.classList.remove('show');
                            cantidadInput.focus();
                        });
                        listaSugerenciasProd.appendChild(item);
                    });
                }
                listaSugerenciasProd.classList.add('show');
            }

            inputBuscarProd.addEventListener('focus', function() {
                renderizarSugerenciasCompra(this.value);
            });

            inputBuscarProd.addEventListener('input', function() {
                if (!this.value.trim()) {
                    selectProducto.value = '';
                    if (btnLimpiarProd) btnLimpiarProd.classList.add('d-none');
                }
                renderizarSugerenciasCompra(this.value);
            });

            if (btnLimpiarProd) {
                btnLimpiarProd.addEventListener('click', function() {
                    inputBuscarProd.value = '';
                    selectProducto.value = '';
                    precioInput.value = '';
                    btnLimpiarProd.classList.add('d-none');
                    listaSugerenciasProd.classList.remove('show');
                    inputBuscarProd.focus();
                });
            }

            document.addEventListener('click', function(e) {
                if (!inputBuscarProd.closest('.position-relative').contains(e.target)) {
                    listaSugerenciasProd.classList.remove('show');
                }
            });
        }

        // Cambio de condición Contado / Crédito
        condicionSelect.addEventListener('change', function() {
            if (this.value === 'credito') {
                campoVencimiento.classList.remove('d-none');
                camposContado.classList.add('d-none');
                actualizarCalculoAbonoInicial();
            } else {
                campoVencimiento.classList.add('d-none');
                camposContado.classList.remove('d-none');
                toggleBancoRefCompra();
            }
        });

        // Botón añadir producto
        btnAgregar.addEventListener('click', function() {
            ocultarError();
            var idProd = parseInt(selectProducto.value, 10);
            var cant = parseInt(cantidadInput.value, 10);
            var precio = parseFloat(precioInput.value);

            if (!idProd || isNaN(idProd)) {
                mostrarError('Seleccione un producto o insumo de la lista.');
                inputBuscarProd.focus();
                return;
            }
            if (isNaN(cant) || cant < 1) {
                mostrarError('Ingrese una cantidad entera válida (mínimo 1).');
                cantidadInput.focus();
                return;
            }
            if (isNaN(precio) || precio < 0) {
                mostrarError('Ingrese un costo unitario válido.');
                precioInput.focus();
                return;
            }

            var nombreProd = selectProducto.getAttribute('data-nombre') || inputBuscarProd.value;
            var codigoProd = selectProducto.getAttribute('data-codigo') || '';

            // Verificar si ya existe en la lista
            var existente = itemsCompra.find(function(it) { return it.id_producto === idProd; });
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
            selectProducto.value = '';
            inputBuscarProd.value = '';
            if (btnLimpiarProd) btnLimpiarProd.classList.add('d-none');
            cantidadInput.value = '';
            precioInput.value = '';

            renderizarTablaDetalle();
        });

        // Gestión del modal de nuevo insumo rápido
        var formRapidoInsumo = document.getElementById('formularioRapidoInsumo');
        if (formRapidoInsumo) {
            formRapidoInsumo.addEventListener('submit', function(e) {
                e.preventDefault();
                var alertaRapida = document.getElementById('alertaErrorRapidoInsumo');
                if (alertaRapida) alertaRapida.classList.add('d-none');

                var fd = new FormData(formRapidoInsumo);
                var btnGuardar = document.getElementById('btnGuardarRapidoInsumo');
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

                fetch('/SP%20Perfect%20Color/inventario/guardar', {
                    method: 'POST',
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-check-lg me-1"></i>Crear e Insertar en Compra';

                    if (res.estado === 'exito') {
                        var prodNuevo = res.datos || {};
                        var costoEst = parseFloat(fd.get('precio_compra') || 0);
                        catalogoInsumos.push({
                            id_producto: prodNuevo.id_producto,
                            codigo: prodNuevo.codigo,
                            nombre: prodNuevo.nombre,
                            stock_actual: 0,
                            precio_compra: costoEst
                        });

                        selectProducto.value = prodNuevo.id_producto;
                        selectProducto.setAttribute('data-codigo', prodNuevo.codigo);
                        selectProducto.setAttribute('data-nombre', prodNuevo.nombre);
                        selectProducto.setAttribute('data-precio', costoEst.toFixed(2));
                        if (inputBuscarProd) {
                            inputBuscarProd.value = prodNuevo.codigo + ' - ' + prodNuevo.nombre;
                        }
                        if (btnLimpiarProd) btnLimpiarProd.classList.remove('d-none');
                        precioInput.value = costoEst > 0 ? costoEst.toFixed(2) : '';
                        cantidadInput.value = 1;

                        // Cerrar modal
                        var modalEl = document.getElementById('modalRapidoInsumo');
                        var modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        formRapidoInsumo.reset();

                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion('Insumo registrado e insertado en la orden', 'exito');
                        }
                    } else {
                        if (alertaRapida) {
                            alertaRapida.textContent = res.mensaje || 'Error al crear insumo.';
                            alertaRapida.classList.remove('d-none');
                        }
                    }
                })
                .catch(function(err) {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-check-lg me-1"></i>Crear e Insertar en Compra';
                    if (alertaRapida) {
                        alertaRapida.textContent = 'Error de conexión con el servidor.';
                        alertaRapida.classList.remove('d-none');
                    }
                });
            });
        }

        // Renderizar tabla de detalle
        function renderizarTablaDetalle() {
            var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
            cuerpoDetalle.innerHTML = '';
            if (itemsCompra.length === 0) {
                cuerpoDetalle.innerHTML = '<tr id="filaVacia"><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>Añada los insumos recibidos del proveedor usando el formulario superior.</td></tr>';
                totalActualCompra = 0.00;
                totalDisplay.textContent = '$ 0,00';
                if (totalBsDisplay) totalBsDisplay.textContent = 'Bs. 0,00';
                actualizarCalculoAbonoInicial();
                return;
            }

            var total = 0;
            itemsCompra.forEach(function(item, idx) {
                total += item.subtotal;
                var tr = document.createElement('tr');
                tr.innerHTML = '<td><span class="badge bg-secondary font-monospace me-2">' + item.codigo + '</span><strong>' + item.nombre + '</strong></td>' +
                    '<td class="text-center fw-bold text-primary">+' + Math.floor(item.cantidad) + '</td>' +
                    '<td class="text-end">$ ' + item.precio_compra.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                    '<td class="text-end fw-bold">$ ' + item.subtotal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-item" data-index="' + idx + '"><i class="bi bi-trash"></i></button></td>';
                cuerpoDetalle.appendChild(tr);
            });

            totalActualCompra = Math.round(total * 100) / 100;
            totalDisplay.textContent = '$ ' + totalActualCompra.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (totalBsDisplay) {
                var bsTotal = totalActualCompra * tasa;
                totalBsDisplay.textContent = 'Bs. ' + bsTotal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            actualizarCalculoAbonoInicial();

            // Enlazar botones quitar
            var btnsQuitar = cuerpoDetalle.querySelectorAll('.btn-quitar-item');
            btnsQuitar.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var idx = parseInt(this.getAttribute('data-index'));
                    itemsCompra.splice(idx, 1);
                    renderizarTablaDetalle();
                });
            });
        }

        // Enviar formulario
        formNotaCompra.addEventListener('submit', function(e) {
            e.preventDefault();
            ocultarError();

            if (itemsCompra.length === 0) {
                mostrarError('Debe agregar al menos un insumo/producto a la nota de entrega.');
                return;
            }

            var idProv = document.getElementById('idProveedor').value;
            if (!idProv) {
                mostrarError('Seleccione un proveedor.');
                return;
            }

            // Validar abono inicial si es crédito
            if (condicionSelect.value === 'credito' && montoInicialInput) {
                var abonoVal = parseFloat(montoInicialInput.value || 0);
                if (abonoVal > totalActualCompra) {
                    mostrarError('El abono inicial no puede ser mayor al total de la compra.');
                    return;
                }
                if (abonoVal > 0 && idTipoPagoInicialSelect) {
                    var tpIni = parseInt(idTipoPagoInicialSelect.value || 1);
                    if (tpIni !== 1) {
                        var bcoIni = document.getElementById('idBancoInicialCompra');
                        var refIni = document.getElementById('referenciaInicialCompra');
                        if (!bcoIni || !bcoIni.value) {
                            mostrarError('Seleccione el banco para el abono inicial.');
                            return;
                        }
                        if (!refIni || !refIni.value.trim()) {
                            mostrarError('Ingrese la referencia del abono inicial.');
                            return;
                        }
                    }
                }
            }

            var fd = new FormData(formNotaCompra);
            fd.append('items', JSON.stringify(itemsCompra));

            var btnSubmit = document.getElementById('btnGuardarNotaCompra');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando y Sumando Stock...';

            fetch('/SP%20Perfect%20Color/notaEntregaCompra/guardar', {
                method: 'POST',
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion(res.mensaje, 'exito');
                    }
                    setTimeout(function() {
                        window.location.href = '/SP%20Perfect%20Color/notaEntregaCompra/ver?id=' + res.datos.id_nota_entrega;
                    }, 800);
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Registrar Recepción y Sumar Stock';
                    mostrarError(res.mensaje || 'Error al guardar la nota de entrega.');
                }
            })
            .catch(function(err) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Registrar Recepción y Sumar Stock';
                mostrarError('Error de comunicación con el servidor.');
                console.error(err);
            });
        });

        function mostrarError(msg) {
            if (alertaError) {
                alertaError.textContent = msg;
                alertaError.classList.remove('d-none');
            }
        }

        function ocultarError() {
            if (alertaError) {
                alertaError.classList.add('d-none');
                alertaError.textContent = '';
            }
        }
    }
});
