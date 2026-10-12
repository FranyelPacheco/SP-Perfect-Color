<?php
// VISTA: notaEntregaListView.php
// OBJETIVO: Listado de notas de entrega con DataTable
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0 toolbar-title"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Notas de Entrega (Ventas)</h4>
                <div class="btn-group btn-group-sm shadow-sm" role="group" id="grupoFiltroNotas">
                    <button type="button" class="btn btn-primary active btn-filtro-nota" data-filtro="">Todas</button>
                    <button type="button" class="btn btn-outline-primary btn-filtro-nota" data-filtro="Aprobado"><i class="bi bi-check-circle-fill me-1"></i>Aprobadas</button>
                    <button type="button" class="btn btn-outline-primary btn-filtro-nota" data-filtro="En Espera"><i class="bi bi-clock-fill me-1"></i>En Espera</button>
                    <button type="button" class="btn btn-outline-primary btn-filtro-nota" data-filtro="Rechazado"><i class="bi bi-x-circle-fill me-1"></i>Rechazadas</button>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaNotas" class="form-control" style="width: 220px;" placeholder="Buscar por cliente o cedula...">
                <a href="/SP%20Perfect%20Color/presupuesto/nuevo" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Nueva Venta / Presupuesto
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaNotas" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cédula</th>
                        <th>Total</th>
                        <th>Condición / Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaNotas"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/notaEntrega.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntrega.js'); ?>"></script>
