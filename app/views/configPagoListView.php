<?php
// VISTA: configPagoListView.php
// OBJETIVO: Vista combinada de Bancos y Tipos de Pago con DataTables y modales
?>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 mb-0"><i class="bi bi-bank me-2 text-primary"></i>Bancos</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalBanco"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tablaBancos" class="table table-hover mb-0">
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
    </div>

    <div class="col-md-6 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
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
    </div>
</div>

<div class="modal fade" id="modalBanco" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalBanco">Nuevo Banco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioBanco">
                <div class="modal-body">
                    <input type="hidden" id="bancoId" name="id">
                    <div class="mb-3">
                        <label for="nombreBanco" class="form-label">Nombre del Banco</label>
                        <input type="text" id="nombreBanco" name="nombre" class="form-control" required placeholder="Ej: Banco de Venezuela">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" id="activoBanco" name="activo" class="form-check-input" value="1" checked>
                        <label for="activoBanco" class="form-check-label">Activo</label>
                    </div>
                    <div id="mensajeErrorBanco" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
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

<script src="/SP%20Perfect%20Color/assets/js/banco.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/banco.js'); ?>"></script>
<script src="/SP%20Perfect%20Color/assets/js/tipoPago.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/tipoPago.js'); ?>"></script>
