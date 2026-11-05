$(function() {
    const $modal = $('#modalBanco');
    const $formulario = $('#formularioBanco');
    const $bancoId = $('#bancoId');
    const $nombreBanco = $('#nombreBanco');
    const $activoBanco = $('#activoBanco');
    const $mensajeError = $('#mensajeErrorBanco');

    $nombreBanco.on('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-]/g, '').substring(0, 50);
    });

    const validacionNombre = validarUnico({
        input: $nombreBanco,
        feedback: $('#feedbackNombreBanco'),
        entidad: 'banco', campo: 'nombre',
        getId: function() { return $bancoId.val() || null; },
        boton: $formulario.find('[type="submit"]')
    });
    $modal.on('hidden.bs.modal', validacionNombre.limpiar);
    $modal.on('shown.bs.modal', function() { if ($nombreBanco.val()) validacionNombre.revalidar(); });

    vincularBusqueda('#busquedaBancos', '#tablaBancos');
    cargarBancos();

    $formulario.on('submit', function(e) {
        e.preventDefault();
        guardarBanco();
    });

    alHacerClick('.btn-editar-banco', function() {
        const id = parseInt($(this).attr('data-id'));
        if (!isNaN(id)) abrirModalEditar(id);
    });

    alHacerClick('.btn-toggle-banco', function() {
        toggleBancoEstado(parseInt($(this).attr('data-id')));
    });

    $modal.on('hidden.bs.modal', function() {
        $formulario[0].reset();
        $bancoId.val('');
        $activoBanco.prop('checked', true);
        $mensajeError.addClass('d-none');
    });

    function cargarBancos() {
        api.get('banco/listarAjax')
            .then(function(res) {
                if (res.estado === 'exito') mostrarBancos(res.datos.bancos);
            });
    }

    function mostrarBancos(bancos) {
        if (!$.fn.DataTable.isDataTable('#tablaBancos')) {
            $('#tablaBancos').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: [
                    { data: 'nombre', render: $.fn.dataTable.render.text() },
                    { data: 'activo', render: function(d) { return d == 1 ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>'; } },
                    { data: null, render: function(d, t, row) {
                        const activo = row.activo == 1;
                        return '<button class="btn btn-sm btn-warning btn-editar-banco" data-id="' + row.id_banco + '"><i class="bi bi-pencil-square"></i></button>' +
                            (activo
                                ? '<button class="btn btn-sm btn-danger btn-toggle-banco" data-id="' + row.id_banco + '" data-activo="1" title="Deshabilitar"><i class="bi bi-toggle-off"></i></button>'
                                : '<button class="btn btn-sm btn-success btn-toggle-banco" data-id="' + row.id_banco + '" data-activo="0" title="Habilitar"><i class="bi bi-toggle-on"></i></button>');
                    }}
                ]
            });
        }
        const table = $('#tablaBancos').DataTable();
        table.clear();
        $.each(bancos, function(i, b) { table.row.add(b); });
        table.draw();
    }

    function abrirModalEditar(id) {
        api.get('banco/obtener', { id: id })
            .then(function(res) {
                if (res.estado === 'exito') {
                    $('#tituloModalBanco').text('Editar Banco');
                    $bancoId.val(res.datos.id_banco);
                    $nombreBanco.val(res.datos.nombre);
                    $activoBanco.prop('checked', res.datos.activo == 1);
                    $mensajeError.addClass('d-none');
                    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
                } else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function guardarBanco() {
        const id = $bancoId.val();
        const esEdicion = id !== '';
        if (!$nombreBanco.val().trim()) { mostrarError('El nombre es obligatorio'); return; }
        const fd = new FormData($formulario[0]);
        if (esEdicion) fd.set('id', id);
        fd.set('activo', $activoBanco.is(':checked') ? '1' : '0');

        api.post(esEdicion ? 'banco/actualizar' : 'banco/guardar', fd)
            .then(function(res) {
                if (res.estado === 'exito') {
                    bootstrap.Modal.getInstance($modal[0]).hide();
                    cargarBancos();
                    mostrarNotificacion(res.mensaje, 'exito');
                } else { mostrarError(res.mensaje); }
            })
            .catch(function() { mostrarError('No se pudo conectar con el servidor. Intente de nuevo.'); });
    }

    function toggleBancoEstado(id) {
        api.post('banco/toggleActivo', { id: id })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarBancos(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function mostrarError(msg) {
        $mensajeError.text(msg).removeClass('d-none');
    }
});
