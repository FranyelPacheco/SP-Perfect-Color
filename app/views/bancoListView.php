<?php
// VISTA: bancoListView.php
// OBJETIVO: Lista de bancos con DataTable y modal para crear/editar
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-bank me-2 text-primary"></i>Bancos</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaBancos" class="form-control input-busqueda" placeholder="Buscar banco...">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBanco"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaBancos" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBanco" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalBanco">Nuevo Banco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioBanco">
                <div class="modal-body">
                    <input type="hidden" id="bancoId" name="id">
                    <div class="mb-3">
                        <label for="nombreBanco" class="form-label">Nombre del Banco</label>
                        <input type="text" id="nombreBanco" name="nombre" class="form-control" required placeholder="Ej: Banco de Venezuela" maxlength="50">
                        <div class="invalid-feedback" id="feedbackNombreBanco"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" id="activoBanco" name="activo" class="form-check-input" value="1" checked>
                        <label for="activoBanco" class="form-check-label">Activo</label>
                    </div>
                    <div id="mensajeErrorBanco" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/banco.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/banco.js'); ?>"></script>
