
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalTipoPago');
    var formulario = document.getElementById('formularioTipoPago');
    var tipoPagoId = document.getElementById('tipoPagoId');
    var nombreTipoPago = document.getElementById('nombreTipoPago');
    var activoTipoPago = document.getElementById('activoTipoPago');
    var mensajeError = document.getElementById('mensajeErrorTipoPago');
    var tituloModal = document.getElementById('tituloModalTipoPago');

    cargarTiposPago();

    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarTipoPago();
        });
    }

    document.getElementById('tablaTiposPago').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-editar-tipo-pago');
        if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
        btn = e.target.closest('.btn-eliminar-tipo-pago');
        if (btn) {
            if (!confirm('Esta seguro de eliminar este tipo de pago?')) return;
            eliminarTipoPago(parseInt(btn.dataset.id));
        }
    });

    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            formulario.reset();
            tipoPagoId.value = '';
            activoTipoPago.checked = true;
            mensajeError.classList.add('d-none');
        });
    }

    function cargarTiposPago() {
        fetch('/SP%20Perfect%20Color/tipoPago/listarAjax')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') mostrarTiposPago(res.datos.tipos_pago);
            });
    }

    function mostrarTiposPago(tipos) {
        if (!$.fn.DataTable.isDataTable('#tablaTiposPago')) {
            $('#tablaTiposPago').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'nombre' },
                    { data: 'activo', render: function(d) { return d == 1 ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-secondary">No</span>'; } },
                    { data: null, render: function(d, t, row) {
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-tipo-pago" data-id="' + row.id_tipo_pago + '"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger btn-eliminar-tipo-pago" data-id="' + row.id_tipo_pago + '"><i class="bi bi-trash"></i></button>' +
                            '</div>';
                    }}
                ]
            });
        }
        var table = $('#tablaTiposPago').DataTable();
        table.clear();
        tipos.forEach(function(t) { table.row.add(t); });
        table.draw();
    }

    function abrirModalEditar(id) {
        fetch('/SP%20Perfect%20Color/tipoPago/obtener?id=' + id)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    tituloModal.textContent = 'Editar Tipo de Pago';
                    tipoPagoId.value = res.datos.id_tipo_pago;
                    nombreTipoPago.value = res.datos.nombre;
                    activoTipoPago.checked = res.datos.activo == 1;
                    mensajeError.classList.add('d-none');
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                } else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function guardarTipoPago() {
        var id = tipoPagoId.value;
        var esEdicion = id !== '';
        var url = esEdicion ? '/SP%20Perfect%20Color/tipoPago/actualizar' : '/SP%20Perfect%20Color/tipoPago/guardar';
        var fd = new FormData(formulario);
        if (!fd.get('nombre').trim()) { mostrarError('El nombre es obligatorio'); return; }
        if (esEdicion) fd.set('id', id);
        fd.set('activo', activoTipoPago.checked ? '1' : '0');

        fetch(url, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    bootstrap.Modal.getInstance(modal).hide();
                    cargarTiposPago();
                    mostrarNotificacion(res.mensaje, 'exito');
                } else { mostrarError(res.mensaje); }
            })
            .catch(function() { mostrarError('Error de conexion'); });
    }

    function eliminarTipoPago(id) {
        var fd = new FormData();
        fd.append('id', id);
        fetch('/SP%20Perfect%20Color/tipoPago/eliminar', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarTiposPago(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function mostrarError(msg) {
        mensajeError.textContent = msg;
        mensajeError.classList.remove('d-none');
    }
});
