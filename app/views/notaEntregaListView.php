<!-- Archivo: notaEntregaListView.php -->
<!-- Vista para la lista de notas de entrega -->

<div class="modulo-nota-entrega">
    <div class="modulo-header">
        <h2>Notas de Entrega</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaNotas" placeholder="Buscar por cliente o cedula..." class="input-busqueda">
            <a href="/SP%20Perfect%20Color/notaEntrega/nueva" class="btn-primario">Nueva Nota de Entrega</a>
        </div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaNotas" class="tabla-datos">
                <thead>
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
                <tbody id="cuerpoTablaNotas">
                    <tr><td colspan="7" style="text-align: center;">Cargando notas de entrega...</td></tr>
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
    width: 250px;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/notaEntrega.js"></script>