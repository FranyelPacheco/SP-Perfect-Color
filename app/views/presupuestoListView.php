<!-- Archivo: presupuestoListView.php -->
<!-- Vista para la lista de presupuestos con Bootstrap 5 -->

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Presupuestos</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaPresupuestos" class="form-control" style="width: 250px;" placeholder="Buscar por cliente o cedula...">
                <select id="filtroEstadoPresupuesto" class="form-select" style="width: auto;">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="aprobado">Aprobados</option>
                    <option value="rechazado">Rechazados</option>
                    <option value="convertido">Convertidos</option>
                </select>
                <button type="button" class="btn btn-success" onclick="location.href='presupuesto/nuevo'">
                    <i class="bi bi-plus-lg me-2"></i>Nuevo
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaPresupuestos" class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cedula</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Vendedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaPresupuestos"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/presupuesto.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/presupuesto.js'); ?>"></script>
