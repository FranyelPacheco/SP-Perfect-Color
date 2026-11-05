$(function() {
    const $modal = $('#modalTipoPago');
    const $formulario = $('#formularioTipoPago');
    const $tipoPagoId = $('#tipoPagoId');
    const $nombreTipoPago = $('#nombreTipoPago');
    const $activoTipoPago = $('#activoTipoPago');
    const $mensajeError = $('#mensajeErrorTipoPago');

    const validacionNombre = validarUnico({
        input: $nombreTipoPago,
        feedback: $('#feedbackNombreTipoPago'),
        entidad: 'tipoPago', campo: 'nombre',
        getId: function() { return $tipoPagoId.val() || null; },
        boton: $formulario.find('[type="submit"]')
    });
    $modal.on('hidden.bs.modal', validacionNombre.limpiar);
    $modal.on('shown.bs.modal', function() { if ($nombreTipoPago.val()) validacionNombre.revalidar(); });

    vincularBusqueda('#busquedaTiposPago', '#tablaTiposPago');
    cargarTiposPago();

    $formulario.on('submit', function(e) {
        e.preventDefault();
        guardarTipoPago();
    });

    alHacerClick('.btn-editar-tipo-pago', function() {
        const id = parseInt($(this).attr('data-id'));
        if (!isNaN(id)) abrirModalEditar(id);
    });

    alHacerClick('.btn-toggle-tipo-pago', function() {
        toggleTipoPagoEstado(parseInt($(this).attr('data-id')));
    });

    $modal.on('hidden.bs.modal', function() {
        $formulario[0].reset();
        $tipoPagoId.val('');
        $activoTipoPago.prop('checked', true);
        $mensajeError.addClass('d-none');
    });

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
                        const activo = row.activo == 1;
                        return '<button class="btn btn-sm btn-warning btn-editar-tipo-pago" data-id="' + row.id_tipo_pago + '"><i class="bi bi-pencil-square"></i></button>' +
                            (activo
                                ? '<button class="btn btn-sm btn-danger btn-toggle-tipo-pago" data-id="' + row.id_tipo_pago + '" data-activo="1" title="Deshabilitar"><i class="bi bi-toggle-off"></i></button>'
                                : '<button class="btn btn-sm btn-success btn-toggle-tipo-pago" data-id="' + row.id_tipo_pago + '" data-activo="0" title="Habilitar"><i class="bi bi-toggle-on"></i></button>');
                    }}
                ]
            });
        }
        const table = $('#tablaTiposPago').DataTable();
        table.clear();
        $.each(tipos, function(i, t) { table.row.add(t); });
        table.draw();
    }

    function abrirModalEditar(id) {
        api.get('tipoPago/obtener', { id: id })
            .then(function(res) {
                if (res.estado === 'exito') {
                    $('#tituloModalTipoPago').text('Editar Tipo de Pago');
                    $tipoPagoId.val(res.datos.id_tipo_pago);
                    $nombreTipoPago.val(res.datos.nombre);
                    $activoTipoPago.prop('checked', res.datos.activo == 1);
                    $mensajeError.addClass('d-none');
                    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
                } else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function guardarTipoPago() {
        const id = $tipoPagoId.val();
        const esEdicion = id !== '';
        if (!$nombreTipoPago.val().trim()) { mostrarError('El nombre es obligatorio'); return; }
        const fd = new FormData($formulario[0]);
        if (esEdicion) fd.set('id', id);
        fd.set('activo', $activoTipoPago.is(':checked') ? '1' : '0');

        api.post(esEdicion ? 'tipoPago/actualizar' : 'tipoPago/guardar', fd)
            .then(function(res) {
                if (res.estado === 'exito') {
                    bootstrap.Modal.getInstance($modal[0]).hide();
                    cargarTiposPago();
                    mostrarNotificacion(res.mensaje, 'exito');
                } else { mostrarError(res.mensaje); }
            })
            .catch(function() { mostrarError('No se pudo conectar con el servidor. Intente de nuevo.'); });
    }

    function toggleTipoPagoEstado(id) {
        api.post('tipoPago/toggleActivo', { id: id })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarTiposPago(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function mostrarError(msg) {
        $mensajeError.text(msg).removeClass('d-none');
    }
});
