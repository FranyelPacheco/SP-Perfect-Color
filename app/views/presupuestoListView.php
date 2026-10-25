<?php
// VISTA: presupuestoListView.php
// OBJETIVO: Listado de presupuestos con DataTable
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0 toolbar-title"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>Presupuestos</h4>
                <div class="btn-group btn-group-sm shadow-sm" role="group" id="grupoFiltroPresupuestos">
                    <button type="button" class="btn btn-primary active btn-filtro-presupuesto" data-filtro="">Todos</button>
                    <button type="button" class="btn btn-outline-primary btn-filtro-presupuesto" data-filtro="En Espera"><i class="bi bi-clock-fill me-1"></i>En Espera</button>
                    <button type="button" class="btn btn-outline-primary btn-filtro-presupuesto" data-filtro="Aprobado"><i class="bi bi-check-circle-fill me-1"></i>Aprobados</button>
                    <button type="button" class="btn btn-outline-primary btn-filtro-presupuesto" data-filtro="Rechazado"><i class="bi bi-x-circle-fill me-1"></i>Rechazados</button>
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaPresupuestos" class="form-control" style="width: 220px;" placeholder="Buscar por cliente o cédula...">
                <button type="button" class="btn btn-primary" onclick="location.href='/SP%20Perfect%20Color/presupuesto/nuevo'" >
                    <i class="bi bi-plus-lg me-2"></i>Nuevo
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaPresupuestos" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cédula</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaPresupuestos"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/presupuesto.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/presupuesto.js'); ?>"></script>
