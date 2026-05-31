<!-- Archivo: cuentaCobrarListView.php -->
<!-- Vista para la lista de cuentas por cobrar con Bootstrap 5 -->

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Cuentas por Cobrar</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaCuentas" class="form-control" style="width: 250px;" placeholder="Buscar por cliente o cedula...">
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaCuentas" class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Cedula</th>
                        <th>Documento</th>
                        <th>Monto Total</th>
                        <th>Saldo Pendiente</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaCuentas"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/cuentaCobrar.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cuentaCobrar.js'); ?>"></script>
