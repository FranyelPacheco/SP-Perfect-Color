<!-- Archivo: cuentaPagarListView.php -->
<!-- Vista para la lista de cuentas por pagar con Bootstrap 5 -->

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-credit-card-2-back-fill me-2 text-primary"></i>Cuentas por Pagar</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaCuentasPagar" class="form-control" style="width: 220px;" placeholder="Buscar por proveedor o RIF...">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaCuentaPagar">
                    <i class="bi bi-plus-lg me-2"></i>Nuevo
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaCuentasPagar" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>RIF</th>
                        <th>Monto Total</th>
                        <th>Saldo Pendiente</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaCuentasPagar"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Cuenta por Pagar -->
<div class="modal fade" id="modalNuevaCuentaPagar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Cuenta por Pagar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioNuevaCuentaPagar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="proveedorCxP" class="form-label">Proveedor</label>
                        <select id="proveedorCxP" class="form-select" required>
                            <option value="">Cargando proveedores...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="montoTotalCxP" class="form-label">Monto Total ($)</label>
                        <input type="number" id="montoTotalCxP" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="fechaVencimientoCxP" class="form-label">Fecha de Vencimiento</label>
                        <input type="date" id="fechaVencimientoCxP" class="form-control" required>
                    </div>
                    <div id="mensajeErrorCxP" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Guardar Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/cuentaPagar.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cuentaPagar.js'); ?>"></script>
