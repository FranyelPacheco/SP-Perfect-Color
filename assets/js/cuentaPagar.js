// Archivo: cuentaPagar.js
// Manejo de la vista de cuentas por pagar


document.addEventListener('DOMContentLoaded', function() {
    var busquedaCuentas = document.getElementById('busquedaCuentasPagar');
    var selectProveedor = document.getElementById('proveedorCxP');
    var formNueva = document.getElementById('formularioNuevaCuentaPagar');
    var mensajeError = document.getElementById('mensajeErrorCxP');

    cargarCuentas();
    if (selectProveedor) cargarProveedores();

    if (formNueva) {
        formNueva.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarNuevaCuenta();
        });
    }

    function cargarCuentas() {
        fetch('/SP%20Perfect%20Color/cuentaPagar/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(resultado) {
                if (resultado.estado === 'exito') {
                    mostrarCuentas(resultado.datos.cuentas);
                }
            });
    }

    function cargarProveedores() {
        console.log('[CxP] Cargando proveedores...');
        fetch('/SP%20Perfect%20Color/cuentaPagar/obtenerProveedoresAjax')
            .then(function(r) {
                console.log('[CxP] Respuesta HTTP status:', r.status);
                return r.json();
            })
            .then(function(resultado) {
                console.log('[CxP] Respuesta JSON:', resultado);
                if (resultado.estado === 'exito') {
                    selectProveedor.innerHTML = '<option value="">Seleccione un proveedor...</option>';
                    console.log('[CxP] Proveedores recibidos:', resultado.datos.proveedores.length);
                    resultado.datos.proveedores.forEach(function(p) {
                        var op = document.createElement('option');
                        op.value = p.id_proveedor;
                        op.textContent = p.rif + ' - ' + p.nombre_empresa;
                        selectProveedor.appendChild(op);
                    });
                } else {
                    console.warn('[CxP] Error del servidor:', resultado.mensaje);
                }
            })
            .catch(function(error) {
                console.error('[CxP] Error en fetch:', error);
            });
    }

    function guardarNuevaCuenta() {
        var proveedorId = selectProveedor.value;
        var montoTotal = document.getElementById('montoTotalCxP').value;
        var fechaVencimiento = document.getElementById('fechaVencimientoCxP').value;

        mensajeError.classList.add('d-none');

        if (!proveedorId) {
            mensajeError.textContent = 'Debe seleccionar un proveedor';
            mensajeError.classList.remove('d-none');
            return;
        }
        if (!montoTotal || parseFloat(montoTotal) <= 0) {
            mensajeError.textContent = 'Ingrese un monto total valido';
            mensajeError.classList.remove('d-none');
            return;
        }
        if (!fechaVencimiento) {
            mensajeError.textContent = 'Ingrese una fecha de vencimiento';
            mensajeError.classList.remove('d-none');
            return;
        }

        var formData = new FormData();
        formData.append('proveedor_id', proveedorId);
        formData.append('monto_total', montoTotal);
        formData.append('fecha_vencimiento', fechaVencimiento);

        fetch('/SP%20Perfect%20Color/cuentaPagar/guardarManual', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(resultado) {
            if (resultado.estado === 'exito') {
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaCuentaPagar'));
                if (modal) modal.hide();
                formNueva.reset();
                cargarCuentas();
            } else {
                mensajeError.textContent = resultado.mensaje;
                mensajeError.classList.remove('d-none');
            }
        })
        .catch(function() {
            mensajeError.textContent = 'Error de conexion';
            mensajeError.classList.remove('d-none');
        });
    }

    function mostrarCuentas(cuentas) {
        if (!$.fn.DataTable.isDataTable('#tablaCuentasPagar')) {
            var $t = $('#tablaCuentasPagar');
            console.log('[CxP] Columnas en <thead>:', $t.find('thead tr th').length);
            console.log('[CxP] Columnas en columns[]:', 7);
            $t.DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'proveedor_nombre' },
                    { data: 'proveedor_rif' },
                    {
                        data: 'monto_total',
                        render: function(data) {
                            if (data == null) return '';
                            return '$ ' + formatearMoneda(data);
                        }
                    },
                    {
                        data: 'saldo_pendiente',
                        render: function(data) {
                            if (data == null) return '';
                            var cls = parseFloat(data) > 0 ? 'saldo-pendiente-positivo' : 'saldo-pendiente-cero';
                            return '<span class="' + cls + '">$ ' + formatearMoneda(data) + '</span>';
                        }
                    },
                    {
                        data: 'fecha_vencimiento',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'estado',
                        render: function(data) {
                            if (!data) return '';
                            var cap = data.charAt(0).toUpperCase() + data.slice(1);
                            return '<span class="estado-' + data + '">' + cap + '</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (!row) return '';
                            return '<div class="d-flex gap-2">' +
                                '<a href="/SP%20Perfect%20Color/cuentaPagar/ver?id=' + row.id_cuenta_pagar + '" class="btn btn-sm btn-info" title="Ver" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>' +
                                '<button class="btn btn-sm btn-outline-danger btn-eliminar-cxp" data-id="' + row.id_cuenta_pagar + '" title="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>' +
                                '</div>';
                        }
                    }
                ]
            });
        }

        var table = $('#tablaCuentasPagar').DataTable();
        table.clear();

        cuentas.forEach(function(cuenta) {
            table.row.add(cuenta);
        });

        table.draw();
    }

    document.getElementById('tablaCuentasPagar').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-eliminar-cxp');
        if (btn) {
            if (!confirm('Esta seguro de eliminar esta cuenta por pagar?')) return;
            var fd = new FormData();
            fd.append('id', btn.dataset.id);
            fetch('/SP%20Perfect%20Color/cuentaPagar/eliminar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarCuentas(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            })
            .catch(function() { mostrarNotificacion('Error de conexion', 'error'); });
        }
    });

    // Enlazar busqueda manual a DataTables
    if (busquedaCuentas) {
        busquedaCuentas.addEventListener('keyup', function() {
            if ($.fn.DataTable.isDataTable('#tablaCuentasPagar')) {
                $('#tablaCuentasPagar').DataTable().search(this.value).draw();
            }
        });
    }
});
