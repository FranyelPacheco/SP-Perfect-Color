<?php
// VISTA: tipoPagoListView.php
// OBJETIVO: Listado y gestión de tipos de pago con modal
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-credit-card me-2 text-primary"></i>Tipos de Pago</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaTiposPago" class="form-control input-busqueda" placeholder="Buscar tipo de pago...">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTipoPago"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaTiposPago" class="table table-hover table-striped mb-0">
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

<div class="modal fade" id="modalTipoPago" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalTipoPago">Nuevo Tipo de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioTipoPago">
                <div class="modal-body">
                    <input type="hidden" id="tipoPagoId" name="id">
                    <div class="mb-3">
                        <label for="nombreTipoPago" class="form-label">Nombre del Tipo de Pago</label>
                        <input type="text" id="nombreTipoPago" name="nombre" class="form-control" required placeholder="Ej: Transferencia, Efectivo, etc.">
                        <div class="invalid-feedback" id="feedbackNombreTipoPago"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" id="activoTipoPago" name="activo" class="form-check-input" value="1" checked>
                        <label for="activoTipoPago" class="form-check-label">Activo</label>
                    </div>
                    <div id="mensajeErrorTipoPago" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/tipoPago.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tipoPago.js'); ?>"></script>
