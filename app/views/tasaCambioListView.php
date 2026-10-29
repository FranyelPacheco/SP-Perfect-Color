<?php
// VISTA: tasaCambioListView.php
// OBJETIVO: Listado y administración de tasas de cambio diarias (USD/Bs.) sin opción de eliminación
?>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center <?php echo !empty($tasaHoy) ? 'bg-success text-white' : 'bg-warning text-dark'; ?>" style="width: 50px; height: 50px; font-size: 1.4rem;">
                    <i class="bi <?php echo !empty($tasaHoy) ? 'bi-check-circle-fill' : 'bi-clock-history'; ?>"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Tasa de Hoy (<?php echo date('d/m/Y'); ?>)</span>
                    <h4 class="mb-0 fw-bold mt-1">
                        <?php if (!empty($tasaHoy)): ?>
                            <span class="text-success"><?php echo number_format((float)$tasaHoy['tasa'], 4, ',', '.'); ?> Bs. / $</span>
                        <?php else: ?>
                            <span class="text-warning">Pendiente de registro</span>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width: 50px; height: 50px; font-size: 1.4rem;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Última Tasa Registrada</span>
                    <h4 class="mb-0 fw-bold mt-1">
                        <?php if (!empty($ultimaTasa)): ?>
                            <span class="text-primary"><?php echo number_format((float)$ultimaTasa['tasa'], 4, ',', '.'); ?> Bs. / $</span>
                            <small class="text-muted fs-6 fw-normal ms-1">(<?php echo date('d/m/Y', strtotime($ultimaTasa['fecha'])); ?>)</small>
                        <?php else: ?>
                            <span class="text-muted">Ninguna</span>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-currency-exchange me-2 text-primary"></i>Tasas de Cambio</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaTasas" class="form-control input-busqueda" placeholder="Buscar por fecha...">
                <button type="button" class="btn btn-success" id="btnNuevaTasa" data-bs-toggle="modal" data-bs-target="#modalTasaCambio" title="Registrar Tasa" data-bs-toggle="tooltip">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaTasas" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tasa (Bs / $)</th>
                        <th>Registrada el</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaTasas"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Registrar / Editar Tasa de Cambio (Sin Eliminar) -->
<div class="modal fade" id="modalTasaCambio" tabindex="-1" data-bs-backdrop="static" aria-labelledby="modalTasaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTasaTitulo">Registrar Tasa de Cambio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioTasaCambio">
                <div class="modal-body">
                    <input type="hidden" id="idTasaCambio" name="id_tasa_cambio" value="">
                    
                    <div class="mb-3">
                        <label for="fechaTasa" class="form-label fw-semibold">Fecha de Vigencia <span class="text-danger">*</span></label>
                        <input type="date" id="fechaTasa" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        <div id="feedbackFechaTasa" class="invalid-feedback d-none">Ya existe una tasa registrada para esta fecha</div>
                        <small class="text-muted">Fecha del día a la que aplica esta tasa oficial.</small>
                    </div>

                    <div class="mb-3">
                        <label for="valorTasa" class="form-label fw-semibold">Valor de la Tasa (Bs. por cada 1 $) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Bs.</span>
                            <input type="text" id="valorTasa" name="tasa" class="form-control" placeholder="Ej. 45.50" maxlength="9" required autocomplete="off">
                        </div>
                        <small class="text-muted">Ingrese solo números con hasta 2 decimales (máx. 6 dígitos enteros, ej: 45.50).</small>
                    </div>

                    <div id="mensajeErrorTasa" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarTasa">
                        <i class="bi bi-check-lg me-1"></i><span id="textoBotonGuardar">Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/tasaCambio.js?v=<?php echo file_exists(__DIR__ . '/../../assets/js/tasaCambio.js') ? filemtime(__DIR__ . '/../../assets/js/tasaCambio.js') : time(); ?>"></script>
