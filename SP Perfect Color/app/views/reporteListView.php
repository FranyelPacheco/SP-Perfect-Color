<!-- Archivo: reporteListView.php -->
<!-- Vista de reportes con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Reportes</h2>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="tipoReporte" class="form-label">Tipo de Reporte</label>
                <select id="tipoReporte" class="form-select">
                    <option value="ventas">Ventas (Notas de Entrega)</option>
                    <option value="ingresos">Ingresos Cobrados</option>
                    <option value="egresos">Egresos Pagados</option>
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
            </div>
        </div>
    </div>
</div>

<div id="resumenReporte" class="row g-3 mb-4" style="display:none">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <small class="text-muted">Total Registros</small>
            <span id="totalRegistros" class="fs-3 fw-bold">0</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <small class="text-muted">Monto Total</small>
            <span id="montoTotal" class="fs-3 fw-bold text-primary">$ 0.00</span>
        </div>
    </div>
</div>

<div id="desglosePago" class="row g-3 mb-4" style="display:none">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header"><h6 class="mb-0" id="tituloDesgloseTipo"><i class="bi bi-pie-chart me-1 text-primary"></i>Por Tipo de Pago</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Tipo</th><th>Cantidad</th><th>Total</th></tr></thead>
                    <tbody id="cuerpoDesgloseTipo"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header"><h6 class="mb-0" id="tituloDesgloseMetodo"><i class="bi bi-wallet2 me-1 text-primary"></i>Por Metodo de Pago</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Metodo</th><th>Cantidad</th><th>Total</th></tr></thead>
                    <tbody id="cuerpoDesgloseMetodo"></tbody>
                </table>
            </div>
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
                        <th>Cliente / Proveedor</th>
                        <th>Monto</th>
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
