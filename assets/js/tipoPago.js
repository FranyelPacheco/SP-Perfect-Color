
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalTipoPago');
    var formulario = document.getElementById('formularioTipoPago');
    var tipoPagoId = document.getElementById('tipoPagoId');
    var nombreTipoPago = document.getElementById('nombreTipoPago');
    var activoTipoPago = document.getElementById('activoTipoPago');
    var mensajeError = document.getElementById('mensajeErrorTipoPago');
    var tituloModal = document.getElementById('tituloModalTipoPago');

    var validacionNombre = validarUnico({
        input: nombreTipoPago,
        feedback: document.getElementById('feedbackNombreTipoPago'),
        entidad: 'tipoPago', campo: 'nombre',
        getId: function() { return tipoPagoId.value || null; },
        boton: formulario.querySelector('[type="submit"]')
    });
    modal.addEventListener('hidden.bs.modal', validacionNombre.limpiar);
    modal.addEventListener('shown.bs.modal', function() { if (nombreTipoPago.value) validacionNombre.revalidar(); });

    cargarTiposPago();

    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarTipoPago();
        });
    }

    alHacerClick('.btn-editar-tipo-pago', function(e) {
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        if (!isNaN(id)) abrirModalEditar(id);
    });

    alHacerClick('.btn-toggle-tipo-pago', function(e) {
        var id = parseInt($(this).attr('data-id') || $(this).data('id'));
        toggleTipoPagoEstado(id);
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
        api.get('tipoPago/listarAjax')
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
                    { data: 'nombre', render: $.fn.dataTable.render.text() },
                    { data: 'activo', render: function(d) { return d == 1 ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-secondary">No</span>'; } },
                    { data: null, render: function(d, t, row) {
                        var activo = row.activo == 1;
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-tipo-pago" data-id="' + row.id_tipo_pago + '"><i class="bi bi-pencil-square"></i></button>' +
                            (activo
                                ? '<button class="btn btn-sm btn-danger btn-toggle-tipo-pago" data-id="' + row.id_tipo_pago + '" data-activo="1" title="Deshabilitar"><i class="bi bi-toggle-off"></i></button>'
                                : '<button class="btn btn-sm btn-success btn-toggle-tipo-pago" data-id="' + row.id_tipo_pago + '" data-activo="0" title="Habilitar"><i class="bi bi-toggle-on"></i></button>') +
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
        api.get('tipoPago/obtener?id=' + id)
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
            .catch(function() { mostrarError('Error de conexión'); });
    }

    function toggleTipoPagoEstado(id) {
        var fd = new FormData();
        fd.append('id', id);
        fetch('/SP%20Perfect%20Color/tipoPago/toggleActivo', { method: 'POST', body: fd })
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
