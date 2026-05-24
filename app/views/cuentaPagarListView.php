<!-- Archivo: cuentaPagarListView.php -->
<!-- Vista para la lista de cuentas por pagar -->

<div class="modulo-cuenta-pagar">
    <div class="modulo-header">
        <h2>Cuentas por Pagar</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaCuentasPagar" placeholder="Buscar por proveedor o RIF..." class="input-busqueda">
        </div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaCuentasPagar" class="tabla-datos">
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
                <tbody id="cuerpoTablaCuentasPagar">
                    <tr><td colspan="7" style="text-align: center;">Cargando cuentas...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.modulo-acciones {
    display: flex;
    gap: 10px;
    align-items: center;
}

.input-busqueda {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 280px;
}

.estado-pendiente {
    background: #fff3e0;
    color: #e65100;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-pagado {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.saldo-pendiente-positivo {
    color: #c62828;
    font-weight: 600;
}

.saldo-pendiente-cero {
    color: #2e7d32;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/cuentaPagar.js"></script>