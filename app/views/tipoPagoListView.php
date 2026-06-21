<?php
// VISTA: tipoPagoListView.php
// OBJETIVO: Listado y gestión de tipos de pago con modal
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-credit-card me-2 text-primary"></i>Tipos de Pago</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTipoPago"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaTiposPago" class="table table-hover mb-0">
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

<div class="modal fade" id="modalTipoPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalTipoPago">Nuevo Tipo de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioTipoPago">
                <div class="modal-body">
                    <input type="hidden" id="tipoPagoId" name="id">
                    <div class="mb-3">
                        <label for="nombreTipoPago" class="form-label">Nombre del Tipo de Pago</label>
                        <input type="text" id="nombreTipoPago" name="nombre" class="form-control" required placeholder="Ej: Transferencia, Efectivo, etc.">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" id="activoTipoPago" name="activo" class="form-check-input" value="1" checked>
                        <label for="activoTipoPago" class="form-check-label">Activo</label>
                    </div>
                    <div id="mensajeErrorTipoPago" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/tipoPago.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tipoPago.js'); ?>"></script>
