<!-- Archivo: facturaListView.php -->
<!-- Vista para la lista de facturas -->

<div class="modulo-factura">
    <div class="modulo-header">
        <h2>Facturacion</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaFacturas" placeholder="Buscar por cliente, cedula o Nro. factura..." class="input-busqueda">
            <a href="/SP%20Perfect%20Color/factura/nueva" class="btn-primario">Nueva Factura</a>
        </div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaFacturas" class="tabla-datos">
                <thead>
                    <tr>
                        <th>Nro. Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Metodo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaFacturas">
                    <tr><td colspan="7" style="text-align: center;">Cargando facturas...</td></tr>
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
    flex-wrap: wrap;
}

.input-busqueda {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 280px;
}

.estado-pagado {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-pendiente {
    background: #fff3e0;
    color: #e65100;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-anulado {
    background: #ffebee;
    color: #c62828;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/factura.js"></script>