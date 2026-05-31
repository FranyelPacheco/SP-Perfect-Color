<!-- Archivo: presupuestoFormView.php -->
<!-- Vista para crear un nuevo presupuesto con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nuevo Presupuesto</h2>
    <a href="../presupuesto" class="btn btn-secondary">Volver a la lista</a>
</div>

<form id="formularioPresupuesto">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="clientePresupuesto" class="form-label">Cliente</label>
                <select id="clientePresupuesto" name="cliente_id" class="form-select" required>
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Agregar Insumos</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="busquedaInsumoPresupuesto" class="form-label">Buscar Insumo</label>
                <input type="text" id="busquedaInsumoPresupuesto" class="form-control" placeholder="Buscar por nombre o codigo...">
            </div>
            <div id="listaInsumosDisponibles" class="list-group" style="max-height: 250px; overflow-y: auto;">
                <div class="list-group-item text-center text-muted">Cargando insumos disponibles...</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Items del Presupuesto</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaItemsPresupuesto" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Codigo</th>
                            <th>Insumo</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaItems">
                        <tr id="filaVacia">
                            <td colspan="6" class="text-center text-muted">No hay items agregados</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total:</td>
                            <td id="totalPresupuesto" class="fw-bold">Bs. 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Observaciones</h5>
        </div>
        <div class="card-body">
            <div class="mb-0">
                <textarea id="observacionesPresupuesto" name="observaciones" class="form-control" rows="3" placeholder="Observaciones adicionales..."></textarea>
            </div>
        </div>
    </div>

    <div id="mensajeErrorPresupuesto" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-end gap-2">
        <a href="../presupuesto" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">Guardar Presupuesto</button>
    </div>
</form>

<script src="/SP%20Perfect%20Color/assets/js/presupuestoForm.js"></script>
