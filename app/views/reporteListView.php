<?php
// VISTA: reporteListView.php
// OBJETIVO: Panel de reportes con selección de tipo y exportación PDF/Excel
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Reportes</h2>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="tipoReporte" class="form-label">Tipo de Reporte</label>
                <select id="tipoReporte" class="form-select">
                    <option value="ventas">Notas de Entrega</option>
                    <option value="carteraCxc">Cuentas por Cobrar Pendientes</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="fechaDesde" class="form-label">Desde</label>
                <input type="date" id="fechaDesde" class="form-control" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="col-md-3">
                <label for="fechaHasta" class="form-label">Hasta</label>
                <input type="date" id="fechaHasta" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <button id="btnGenerarReporte" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Generar Reporte</button>
                <div class="d-flex gap-2 mt-2">
                    <button id="btnPDF" class="btn btn-danger w-100" style="display:none;">
                        <i class="bi bi-filetype-pdf me-1"></i>PDF
                    </button>
                    <button id="btnExcel" class="btn btn-success w-100" style="display:none;">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="resumenReporte" class="row g-3 mb-4" style="display:none">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <small class="text-muted" id="labelResumen1">Total Registros</small>
            <span id="totalRegistros" class="fs-3 fw-bold">0</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <small class="text-muted" id="labelResumen2">Monto Total</small>
            <span id="montoTotal" class="fs-3 fw-bold text-primary">$ 0.00</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaReporte" class="table table-hover mb-0">
                <thead>
                    <tr id="encabezadoReporte">
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Metodo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody id="cuerpoReporte">
                    <tr><td colspan="5" class="text-center text-muted">Seleccione un reporte y presione Generar</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/reporte.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/reporte.js'); ?>"></script>