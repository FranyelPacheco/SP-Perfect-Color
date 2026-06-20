
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalBanco');
    var formulario = document.getElementById('formularioBanco');
    var bancoId = document.getElementById('bancoId');
    var nombreBanco = document.getElementById('nombreBanco');
    var activoBanco = document.getElementById('activoBanco');
    var mensajeError = document.getElementById('mensajeErrorBanco');
    var tituloModal = document.getElementById('tituloModalBanco');

    cargarBancos();

    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarBanco();
        });
    }

    document.getElementById('tablaBancos').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-editar-banco');
        if (btn) { abrirModalEditar(parseInt(btn.dataset.id)); return; }
        btn = e.target.closest('.btn-toggle-banco');
        if (btn) {
            var id = parseInt(btn.dataset.id);
            var activo = parseInt(btn.dataset.activo);
            var titulo = activo ? 'Deshabilitar' : 'Habilitar';
            var msg = activo ? 'Esta seguro de deshabilitar este banco?' : 'Esta seguro de habilitar este banco?';
            confirmarConModal(titulo, msg, function() {
                toggleBancoEstado(id);
            });
            return;
        }
    });

    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            formulario.reset();
            bancoId.value = '';
            activoBanco.checked = true;
            mensajeError.classList.add('d-none');
        });
    }

    function cargarBancos() {
        fetch('/SP%20Perfect%20Color/banco/listarAjax')
            .then(function(r) { return r.json(); })
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
                    { data: 'nombre' },
                    { data: 'activo', render: function(d) { return d == 1 ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-secondary">No</span>'; } },
                    { data: null, render: function(d, t, row) {
                        var activo = row.activo == 1;
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-banco" data-id="' + row.id_banco + '"><i class="bi bi-pencil-square"></i></button>' +
                            (activo
                                ? '<button class="btn btn-sm btn-danger btn-toggle-banco" data-id="' + row.id_banco + '" data-activo="1" title="Deshabilitar"><i class="bi bi-toggle-off"></i></button>'
                                : '<button class="btn btn-sm btn-success btn-toggle-banco" data-id="' + row.id_banco + '" data-activo="0" title="Habilitar"><i class="bi bi-toggle-on"></i></button>') +
                            '</div>';
                    }}
                ]
            });
        }
        var table = $('#tablaBancos').DataTable();
        table.clear();
        bancos.forEach(function(b) { table.row.add(b); });
        table.draw();
    }

    function abrirModalEditar(id) {
        fetch('/SP%20Perfect%20Color/banco/obtener?id=' + id)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    tituloModal.textContent = 'Editar Banco';
                    bancoId.value = res.datos.id_banco;
                    nombreBanco.value = res.datos.nombre;
                    activoBanco.checked = res.datos.activo == 1;
                    mensajeError.classList.add('d-none');
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                } else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function guardarBanco() {
        var id = bancoId.value;
        var esEdicion = id !== '';
        var url = esEdicion ? '/SP%20Perfect%20Color/banco/actualizar' : '/SP%20Perfect%20Color/banco/guardar';
        var fd = new FormData(formulario);
        if (!fd.get('nombre').trim()) { mostrarError('El nombre es obligatorio'); return; }
        if (esEdicion) fd.set('id', id);
        fd.set('activo', activoBanco.checked ? '1' : '0');

        fetch(url, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') {
                    bootstrap.Modal.getInstance(modal).hide();
                    cargarBancos();
                    mostrarNotificacion(res.mensaje, 'exito');
                } else { mostrarError(res.mensaje); }
            })
            .catch(function() { mostrarError('Error de conexion'); });
    }

    function toggleBancoEstado(id) {
        var fd = new FormData();
        fd.append('id', id);
        fetch('/SP%20Perfect%20Color/banco/toggleActivo', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.estado === 'exito') { mostrarNotificacion(res.mensaje, 'exito'); cargarBancos(); }
                else { mostrarNotificacion(res.mensaje, 'error'); }
            });
    }

    function mostrarError(msg) {
        mensajeError.textContent = msg;
        mensajeError.classList.remove('d-none');
    }
});
