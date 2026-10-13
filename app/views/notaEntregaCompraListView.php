<?php
// VISTA: notaEntregaCompraListView.php
// OBJETIVO: Listado de notas de entrega de compras a proveedores con DataTables
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Recepción de Compras</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaNotasCompra" class="form-control" style="width: 240px;" placeholder="Buscar por proveedor o RIF...">
                <?php if (\App\Helpers\tienePermiso('notaEntregaCompra')): ?>
                <a href="/SP%20Perfect%20Color/notaEntregaCompra/crear" class="btn btn-success" title="Registrar Compra" data-bs-toggle="tooltip">
                    <i class="bi bi-plus-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaNotasCompra" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>N° Nota</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>RIF</th>
                        <th>Total</th>
                        <th>Condición</th>
                        <th>Estado de Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaNotasCompra"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/notaEntregaCompra.js?v=<?php echo file_exists(__DIR__ . '/../../assets/js/notaEntregaCompra.js') ? filemtime(__DIR__ . '/../../assets/js/notaEntregaCompra.js') : time(); ?>"></script>
