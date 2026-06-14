<!-- Archivo: notaEntregaListView.php -->
<!-- Vista para la lista de notas de entrega con Bootstrap 5 -->

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Notas de Entrega</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaNotas" class="form-control" style="width: 220px;" placeholder="Buscar por cliente o cedula...">
                <button type="button" class="btn btn-primary" onclick="location.href='/SP%20Perfect%20Color/notaEntrega/nueva'">
                    <i class="bi bi-plus-lg me-2"></i>Nuevo
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaNotas" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cedula</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Vendedor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaNotas"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/notaEntrega.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntrega.js'); ?>"></script>
