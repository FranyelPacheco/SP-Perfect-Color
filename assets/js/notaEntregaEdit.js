document.addEventListener('DOMContentLoaded', function() {
    var busquedaInsumo = document.getElementById('busquedaInsumoEdit');
    var listaInsumos = document.getElementById('listaInsumosEdit');
    var cuerpoTabla = document.getElementById('cuerpoTablaItemsEdit');
    var totalSpan = document.getElementById('totalEdit');
    var formulario = document.getElementById('formularioEditarNota');
    var mensajeError = document.getElementById('mensajeErrorEdit');
    var filaVacia = document.getElementById('filaVaciaEdit');

    var items = [];
    var insumos = [];

    cargarInsumos();
    cargarItemsExistentes();

    function cargarInsumos() {
        fetch('/SP%20Perfect%20Color/notaEntrega/obtenerInsumosAjax')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    insumos = res.datos.insumos;
                    mostrarInsumos(insumos);
                }
            });
    }

    function cargarItemsExistentes() {
        if (typeof notaDetalleExistente !== 'undefined' && notaDetalleExistente.length > 0) {
            var espera = setInterval(function() {
                if (insumos.length > 0) {
                    clearInterval(espera);
                    notaDetalleExistente.forEach(function(item) {
                        var insumo = insumos.find(function(i) { return i.id == item.insumo_id; });
                        if (insumo) {
                            items.push({
                                insumo_id: insumo.id,
                                insumo_codigo: insumo.codigo,
                                insumo_nombre: insumo.nombre,
                                stock_actual: parseFloat(insumo.stock_actual),
                                cantidad: parseFloat(item.cantidad),
                                precio_unitario: parseFloat(item.precio_unitario),
                                subtotal: parseFloat(item.subtotal)
                            });
                        }
                    });
                    actualizarTabla();
                }
            }, 100);
        }
    }

    function mostrarInsumos(lista) {
        if (!listaInsumos) return;
        listaInsumos.innerHTML = '';
        if (lista.length === 0) {
            listaInsumos.innerHTML = '<div class="list-group-item text-center text-muted">No se encontraron insumos</div>';
            return;
        }
        lista.forEach(function(insumo) {
            var div = document.createElement('div');
            div.className = 'list-group-item d-flex justify-content-between align-items-center';
            div.innerHTML =
                '<div><strong>' + insumo.codigo + ' - ' + insumo.nombre + '</strong><br><small class="text-muted">Stock: ' + insumo.stock_actual + ' | $ ' + insumo.precio_venta + '</small></div>' +
                '<button type="button" class="btn btn-sm btn-agregar agregar-item-edit">Agregar</button>';
            div.querySelector('.agregar-item-edit').addEventListener('click', function() {
                agregarItem(insumo);
            });
            listaInsumos.appendChild(div);
        });
    }

    if (busquedaInsumo) {
        var timer;
        busquedaInsumo.addEventListener('keyup', function() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                var t = busquedaInsumo.value.trim().toLowerCase();
                if (!t) { mostrarInsumos(insumos); return; }
                var filtrados = insumos.filter(function(i) {
                    return i.nombre.toLowerCase().indexOf(t) !== -1 || i.codigo.toLowerCase().indexOf(t) !== -1;
                });
                mostrarInsumos(filtrados);
            }, 200);
        });
    }

    function agregarItem(insumo) {
        for (var i = 0; i < items.length; i++) {
            if (items[i].insumo_id === insumo.id) {
                mostrarError('Este insumo ya esta en la lista');
                return;
            }
        }
        if (parseFloat(insumo.stock_actual) <= 0) {
            mostrarError('No hay stock disponible');
            return;
        }
        items.push({
            insumo_id: insumo.id,
            insumo_codigo: insumo.codigo,
            insumo_nombre: insumo.nombre,
            stock_actual: parseFloat(insumo.stock_actual),
            cantidad: 1,
            precio_unitario: parseFloat(insumo.precio_venta),
            subtotal: parseFloat(insumo.precio_venta)
        });
        actualizarTabla();
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
                '<td>' + item.stock_actual + '</td>' +
                '<td><input type="number" class="form-control form-control-sm" style="width:80px" value="' + item.cantidad + '" min="0.01" step="0.01" data-idx="' + idx + '"></td>' +
                '<td><input type="number" class="form-control form-control-sm" style="width:100px" value="' + item.precio_unitario + '" min="0.01" step="0.01" data-idx="' + idx + '"></td>' +
                '<td>$ ' + item.subtotal.toFixed(2) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger" data-idx="' + idx + '"><i class="bi bi-trash"></i></button></td>';

            fila.querySelector('input[type="number"]').addEventListener('change', function() {
                var i = parseInt(this.dataset.idx);
                var nv = parseFloat(this.value) || 0;
                if (nv <= 0 || nv > items[i].stock_actual) {
                    this.value = items[i].cantidad;
                    if (nv > items[i].stock_actual) mostrarError('Stock insuficiente');
                    return;
                }
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

            fila.querySelector('.btn-outline-danger').addEventListener('click', function() {
                var i = parseInt(this.dataset.idx);
                items.splice(i, 1);
                actualizarTabla();
            });

            cuerpoTabla.appendChild(fila);
            total += item.subtotal;
        });
        totalSpan.textContent = '$ ' + total.toFixed(2);
    }

    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            if (items.length === 0) { mostrarError('Debe agregar al menos un insumo'); return; }
            var fd = new FormData();
            fd.append('nota_id', document.querySelector('input[name="nota_id"]').value);
            fd.append('items', JSON.stringify(items.map(function(i) {
                return { insumo_id: i.insumo_id, cantidad: i.cantidad, precio_unitario: i.precio_unitario };
            })));
            fetch('/SP%20Perfect%20Color/notaEntrega/actualizar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    mostrarNotificacion(res.mensaje, 'exito');
                    setTimeout(function() { window.location.href = '/SP%20Perfect%20Color/notaEntrega/ver?id=' + res.datos.nota_id; }, 1500);
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
