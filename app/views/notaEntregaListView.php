<!-- Archivo: notaEntregaListView.php -->
<!-- Vista para la lista de notas de entrega con Bootstrap 5 -->

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Notas de Entrega</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaNotas" class="form-control" style="width: 250px;" placeholder="Buscar por cliente o cedula...">
                <button type="button" class="btn btn-success" onclick="location.href='/SP%20Perfect%20Color/notaEntrega/nueva'">
                    <i class="bi bi-plus-lg me-2"></i>Nuevo
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaNotas" class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cedula</th>
                        <th>Total</th>
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
