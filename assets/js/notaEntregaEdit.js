document.addEventListener('DOMContentLoaded', function() {
    var cuerpoTabla = document.getElementById('cuerpoTablaItemsEdit');
    var totalSpan = document.getElementById('totalEdit');
    var formulario = document.getElementById('formularioEditarNota');
    var mensajeError = document.getElementById('mensajeErrorEdit');
    var filaVacia = document.getElementById('filaVaciaEdit');

    var items = [];

    cargarItemsExistentes();

    function cargarItemsExistentes() {
        if (typeof notaDetalleExistente !== 'undefined' && notaDetalleExistente.length > 0) {
            notaDetalleExistente.forEach(function(item) {
                items.push({
                    id_presupuesto_detalle: parseInt(item.id_presupuesto_detalle),
                    id_insumo: item.id_insumo ? parseInt(item.id_insumo) : 0,
                    insumo_codigo: item.insumo_codigo || '',
                    insumo_nombre: item.insumo_nombre || '',
                    stock_actual: parseFloat(item.stock_actual) || 0,
                    cantidad: parseFloat(item.cantidad),
                    precio_unitario: parseFloat(item.precio_unitario),
                    subtotal: parseFloat(item.subtotal)
                });
            });
            actualizarTabla();
        }
    }

    function actualizarTabla() {
        if (!cuerpoTabla || !totalSpan) return;
        if (filaVacia) filaVacia.style.display = items.length > 0 ? 'none' : '';
        var filas = cuerpoTabla.querySelectorAll('tr:not(#filaVaciaEdit)');
        filas.forEach(function(f) { f.remove(); });

        var total = 0;
        items.forEach(function(item, idx) {
            var fila = document.createElement('tr');
            fila.innerHTML =
                '<td>' + item.insumo_codigo + '</td>' +
                '<td>' + item.insumo_nombre + '</td>' +
                '<td>' + item.stock_actual.toFixed(2) + '</td>' +
                '<td><input type="number" class="form-control form-control-sm" style="width:80px" value="' + item.cantidad + '" min="0.01" step="0.01" data-idx="' + idx + '"></td>' +
                '<td><input type="number" class="form-control form-control-sm" style="width:100px" value="' + item.precio_unitario + '" min="0.01" step="0.01" data-idx="' + idx + '"></td>' +
                '<td>$ ' + item.subtotal.toFixed(2) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-item" data-idx="' + idx + '"><i class="bi bi-trash"></i></button></td>';

            fila.querySelectorAll('input[type="number"]')[0].addEventListener('change', function() {
                var i = parseInt(this.dataset.idx);
                var nv = parseFloat(this.value) || 0;
                if (nv <= 0) { this.value = items[i].cantidad; return; }
                items[i].cantidad = nv;
                items[i].subtotal = nv * items[i].precio_unitario;
                actualizarTabla();
            });

            fila.querySelectorAll('input[type="number"]')[1].addEventListener('change', function() {
                var i = parseInt(this.dataset.idx);
                var np = parseFloat(this.value) || 0;
                if (np <= 0) { this.value = items[i].precio_unitario; return; }
                items[i].precio_unitario = np;
                items[i].subtotal = items[i].cantidad * np;
                actualizarTabla();
            });

            fila.querySelector('.btn-quitar-item').addEventListener('click', function() {
                var i = parseInt(this.dataset.idx);
                items.splice(i, 1);
                actualizarTabla();
            });

            cuerpoTabla.appendChild(fila);
            total += item.subtotal;
        });
        totalSpan.textContent = '$ ' + total.toFixed(2);
    }

    // Modal de búsqueda de insumos
    var modalInsumo = document.getElementById('modalAgregarInsumo');
    var inputBusqueda = document.getElementById('busquedaInsumoModal');
    var resultadosInsumo = document.getElementById('resultadosInsumoModal');

    document.getElementById('btnAgregarInsumo').addEventListener('click', function() {
        if (modalInsumo) {
            var modal = new bootstrap.Modal(modalInsumo);
            modal.show();
            if (inputBusqueda) {
                inputBusqueda.value = '';
                inputBusqueda.focus();
            }
            if (resultadosInsumo) resultadosInsumo.innerHTML = '';
        }
    });

    if (inputBusqueda) {
        var timerBusqueda = null;
        inputBusqueda.addEventListener('input', function() {
            clearTimeout(timerBusqueda);
            var termino = this.value.trim();
            if (termino.length < 1) {
                resultadosInsumo.innerHTML = '<div class="text-muted p-2">Escriba al menos 1 caracter para buscar</div>';
                return;
            }
            timerBusqueda = setTimeout(function() {
                fetch('/SP%20Perfect%20Color/inventario/buscarAjax?termino=' + encodeURIComponent(termino))
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.estado === 'exito' && res.datos.insumos.length > 0) {
                            resultadosInsumo.innerHTML = '';
                            res.datos.insumos.forEach(function(ins) {
                                var yaAgregado = items.some(function(it) {
                                    return it.id_insumo === parseInt(ins.id_insumo) || (it.insumo_codigo === ins.codigo && it.id_presupuesto_detalle > 0);
                                });
                                var div = document.createElement('div');
                                div.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center' + (yaAgregado ? ' disabled' : '');
                                div.innerHTML = '<div><strong>' + ins.codigo + '</strong> - ' + ins.nombre + ' <small class="text-muted">(Stock: ' + ins.stock_actual + ')</small></div>';
                                if (!yaAgregado) {
                                    var btn = document.createElement('button');
                                    btn.className = 'btn btn-sm btn-success';
                                    btn.innerHTML = '<i class="bi bi-plus-lg"></i>';
                                    btn.addEventListener('click', function() {
                                        agregarInsumo(ins);
                                        var modal = bootstrap.Modal.getInstance(modalInsumo);
                                        if (modal) modal.hide();
                                    });
                                    div.appendChild(btn);
                                } else {
                                    var span = document.createElement('span');
                                    span.className = 'text-muted small';
                                    span.textContent = 'Ya agregado';
                                    div.appendChild(span);
                                }
                                resultadosInsumo.appendChild(div);
                            });
                        } else {
                            resultadosInsumo.innerHTML = '<div class="text-muted p-2">No se encontraron insumos</div>';
                        }
                    })
                    .catch(function() {
                        resultadosInsumo.innerHTML = '<div class="text-danger p-2">Error al buscar insumos</div>';
                    });
            }, 300);
        });
    }

    function agregarInsumo(insumo) {
        var cantidad = 1;
        var precio = parseFloat(insumo.precio_venta) || 0;
        items.push({
            id_presupuesto_detalle: 0,
            id_insumo: parseInt(insumo.id_insumo),
            insumo_codigo: insumo.codigo || '',
            insumo_nombre: insumo.nombre || '',
            stock_actual: parseFloat(insumo.stock_actual) || 0,
            cantidad: cantidad,
            precio_unitario: precio,
            subtotal: cantidad * precio
        });
        actualizarTabla();
    }

    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            if (items.length === 0) { mostrarError('Debe agregar al menos un item'); return; }
            var fd = new FormData();
            fd.append('id_nota_entrega', document.querySelector('input[name="id_nota_entrega"]').value);
            fd.append('items', JSON.stringify(items.map(function(i) {
                if (i.id_presupuesto_detalle > 0) {
                    return { id_presupuesto_detalle: i.id_presupuesto_detalle, cantidad: i.cantidad, precio_unitario: i.precio_unitario };
                } else {
                    return { id_presupuesto_detalle: 0, id_insumo: i.id_insumo, cantidad: i.cantidad, precio_unitario: i.precio_unitario };
                }
            })));
            fetch('/SP%20Perfect%20Color/notaEntrega/actualizar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    mostrarNotificacion(res.mensaje, 'exito');
                    setTimeout(function() { window.location.href = '/SP%20Perfect%20Color/notaEntrega/ver?id=' + res.datos.id_nota_entrega; }, 1500);
                } else {
                    mostrarError(res.mensaje);
                }
            })
            .catch(function() { mostrarError('Error de conexion'); });
        });
    }

    function mostrarError(msg) {
        if (!mensajeError) { alert(msg); return; }
        mensajeError.textContent = msg;
        mensajeError.style.display = 'block';
        setTimeout(function() { mensajeError.style.display = 'none'; }, 5000);
    }
});
