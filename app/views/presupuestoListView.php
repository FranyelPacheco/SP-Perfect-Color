<!-- Archivo: presupuestoListView.php -->
<!-- Vista para la lista de presupuestos -->

<div class="modulo-presupuesto">
    <div class="modulo-header">
        <h2>Gestion de Presupuestos</h2>
        <div class="modulo-acciones">
            <input type="text" id="busquedaPresupuestos" placeholder="Buscar por cliente o cedula..." class="input-busqueda">
            <select id="filtroEstadoPresupuesto" class="filtro-select">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendientes</option>
                <option value="aprobado">Aprobados</option>
                <option value="rechazado">Rechazados</option>
                <option value="convertido">Convertidos</option>
            </select>
            <a href="presupuesto/nuevo" class="btn-primario">Nuevo Presupuesto</a>
        </div>
    </div>
    
    <div class="modulo-body">
        <div class="tabla-contenedor">
            <table id="tablaPresupuestos" class="tabla-datos">
                <thead>
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
                <tbody id="cuerpoTablaPresupuestos">
                    <tr><td colspan="8" style="text-align: center;">Cargando presupuestos...</td></tr>
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

.filtro-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.estado-pendiente {
    background: #fff3e0;
    color: #e65100;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-aprobado {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-rechazado {
    background: #ffebee;
    color: #c62828;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.estado-convertido {
    background: #e3f2fd;
    color: #1565c0;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<script src="/SP%20Perfect%20Color/assets/js/presupuesto.js"></script>