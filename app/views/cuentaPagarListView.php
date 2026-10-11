<?php
// VISTA: cuentaPagarListView.php
// OBJETIVO: Lista de cuentas por pagar con DataTable y modal para nueva cuenta
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-credit-card-2-back-fill me-2 text-primary"></i>Cuentas por Pagar</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaCuentasPagar" class="form-control" style="width: 220px;" placeholder="Buscar por proveedor o RIF...">
                <a href="/SP%20Perfect%20Color/notaEntregaCompra/crear" class="btn btn-primary">
                    <i class="bi bi-cart-plus-fill me-2"></i>Registrar Compra
                </a>
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

<script src="/SP%20Perfect%20Color/assets/js/cuentaPagar.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cuentaPagar.js'); ?>"></script>
