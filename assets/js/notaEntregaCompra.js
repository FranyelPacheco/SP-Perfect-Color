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

            if (typeof confirmarConModal === 'function') {
                confirmarConModal('Anular Recepción de Compra', '¿Está seguro de anular esta nota de entrega? Se revertirá el incremento de stock de los insumos asociados.', function() {
                    ejecutarAnulacion(id);
                });
            } else if (confirm('¿Está seguro de anular esta nota de entrega de compra? El stock ingresado será revertido.')) {
                ejecutarAnulacion(id);
            }
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
                        render: function(data) {
                            var num = parseFloat(data || 0);
                            return '<strong>$ ' + num.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            var esCredito = row.id_cuenta_pagar && parseFloat(row.saldo_pendiente) > 0;
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
        var alertaError = document.getElementById('alertaErrorCompra');

        var inputBuscarProd = document.getElementById('inputBuscarProductoCompra');
        var btnLimpiarProd = document.getElementById('btnLimpiarProductoCompra');
        var listaSugerenciasProd = document.getElementById('listaSugerenciasProductoCompra');
        var contenedorBancoCompra = document.getElementById('contenedorBancoCompra');
        var contenedorReferenciaCompra = document.getElementById('contenedorReferenciaCompra');
        var idTipoPagoSelect = document.getElementById('idTipoPago');
        var itemsCompra = [];
        var catalogoInsumos = Array.isArray(window.insumosCompraCatalogo) ? window.insumosCompraCatalogo : [];

        // Toggle banco y referencia según método de pago (solo si no es efectivo)
        function toggleBancoRefCompra() {
            if (!idTipoPagoSelect) return;
            var val = parseInt(idTipoPagoSelect.value || 0);
            // 1 = Efectivo
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

        // Búsqueda integrada de productos al hacer clic / escribir en el selector
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
                    listaSugerenciasProd.innerHTML = '<div class="p-2 text-muted small text-center">No se encontraron productos</div>';
                } else {
                    filtrados.forEach(function(ins) {
                        var stock = parseFloat(ins.stock_actual || 0);
                        var precio = parseFloat(ins.precio_compra || 0);
                        var item = document.createElement('a');
                        item.className = 'dropdown-item d-flex justify-content-between align-items-center py-2 px-3 border-bottom small';
                        item.href = '#';
                        item.innerHTML = '<div><span class="badge bg-secondary me-1 font-monospace">' + ins.codigo + '</span> <strong>' + ins.nombre + '</strong></div>' +
                                         '<span class="badge bg-light text-dark border">Stock: ' + stock + ' | $ ' + precio.toFixed(2) + '</span>';
                        item.addEventListener('click', function(ev) {
                            ev.preventDefault();
                            inputBuscarProd.value = ins.codigo + ' - ' + ins.nombre;
                            selectProducto.value = ins.id_producto;
                            selectProducto.setAttribute('data-nombre', ins.nombre);
                            selectProducto.setAttribute('data-codigo', ins.codigo);
                            selectProducto.setAttribute('data-precio', ins.precio_compra || '0.00');
                            
                            precioInput.value = precio > 0 ? precio.toFixed(2) : '';
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
                mostrarError('Seleccione un producto o insumo.');
                inputBuscarProd.focus();
                return;
            }
            if (isNaN(cant) || cant <= 0) {
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
                        var nuevoOpt = document.createElement('option');
                        nuevoOpt.value = prodNuevo.id_producto;
                        nuevoOpt.setAttribute('data-codigo', prodNuevo.codigo);
                        nuevoOpt.setAttribute('data-nombre', prodNuevo.nombre);
                        var costoEst = parseFloat(fd.get('precio_compra') || 0);
                        nuevoOpt.setAttribute('data-precio', costoEst.toFixed(2));
                        nuevoOpt.setAttribute('data-stock', '0');
                        nuevoOpt.textContent = prodNuevo.codigo + ' - ' + prodNuevo.nombre + ' (Stock actual: 0)';

                        selectProducto.appendChild(nuevoOpt);
                        selectProducto.value = prodNuevo.id_producto;
                        precioInput.value = costoEst > 0 ? costoEst : '';
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
            cuerpoDetalle.innerHTML = '';
            if (itemsCompra.length === 0) {
                cuerpoDetalle.innerHTML = '<tr id="filaVacia"><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>Añada los insumos recibidos del proveedor usando el formulario superior.</td></tr>';
                totalDisplay.textContent = '$ 0,00';
                return;
            }

            var total = 0;
            itemsCompra.forEach(function(item, idx) {
                total += item.subtotal;
                var tr = document.createElement('tr');
                tr.innerHTML = '<td><span class="badge bg-secondary font-monospace me-2">' + item.codigo + '</span><strong>' + item.nombre + '</strong></td>' +
                    '<td class="text-center fw-bold text-primary">+' + item.cantidad.toLocaleString('es-VE') + '</td>' +
                    '<td class="text-end">$ ' + item.precio_compra.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                    '<td class="text-end fw-bold">$ ' + item.subtotal.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-item" data-index="' + idx + '"><i class="bi bi-trash"></i></button></td>';
                cuerpoDetalle.appendChild(tr);
            });

            totalDisplay.textContent = '$ ' + total.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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
