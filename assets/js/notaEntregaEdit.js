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
                    presupuesto_detalle_id: parseInt(item.presupuesto_detalle_id),
                    insumo_codigo: item.insumo_codigo || '',
                    insumo_nombre: item.insumo_nombre || '',
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
                '<td>-</td>' +
                '<td><input type="number" class="form-control form-control-sm" style="width:80px" value="' + item.cantidad + '" min="0.01" step="0.01" data-idx="' + idx + '"></td>' +
                '<td><input type="number" class="form-control form-control-sm" style="width:100px" value="' + item.precio_unitario + '" min="0.01" step="0.01" data-idx="' + idx + '"></td>' +
                '<td>$ ' + item.subtotal.toFixed(2) + '</td>' +
                '<td></td>';

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

            cuerpoTabla.appendChild(fila);
            total += item.subtotal;
        });
        totalSpan.textContent = '$ ' + total.toFixed(2);
    }

    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            if (items.length === 0) { mostrarError('Debe agregar al menos un item'); return; }
            var fd = new FormData();
            fd.append('nota_id', document.querySelector('input[name="nota_id"]').value);
            fd.append('items', JSON.stringify(items.map(function(i) {
                return { presupuesto_detalle_id: i.presupuesto_detalle_id, cantidad: i.cantidad, precio_unitario: i.precio_unitario };
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
