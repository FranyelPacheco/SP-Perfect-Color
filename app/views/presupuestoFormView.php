<!-- Archivo: presupuestoFormView.php -->
<!-- Vista para crear un nuevo presupuesto con Bootstrap 5 -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nuevo Presupuesto</h2>
    <a href="../presupuesto" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form id="formularioPresupuesto">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-fill me-2"></i>Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <div class="mb-0">
                <label for="clientePresupuesto" class="form-label">Cliente</label>
                <select id="clientePresupuesto" name="id_cliente" class="form-select" required>
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Agregar Insumos</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="busquedaInsumoPresupuesto" class="form-label">Buscar Insumo</label>
                <input type="text" id="busquedaInsumoPresupuesto" class="form-control" placeholder="Buscar por nombre o codigo...">
            </div>
            <div id="listaInsumosDisponibles" class="list-group insumo-list" style="max-height: 260px; overflow-y: auto;">
                <div class="text-center text-muted py-3">Cargando insumos disponibles...</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Items del Presupuesto</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaItemsPresupuesto" class="table table-hover mb-0">
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
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total:</td>
                            <td id="totalPresupuesto" class="fw-bold">$ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-chat-dots me-2 text-primary"></i>Observaciones</h5>
        </div>
        <div class="card-body">
            <div class="mb-0">
                <textarea id="observacionesPresupuesto" name="observaciones" class="form-control" rows="3" placeholder="Observaciones adicionales..."></textarea>
            </div>
        </div>
    </div>

    <div id="mensajeErrorPresupuesto" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-end gap-2">
        <a href="../presupuesto" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-floppy me-2"></i>Guardar Presupuesto</button>
    </div>
</form>

<script src="/SP%20Perfect%20Color/assets/js/presupuestoForm.js"></script>
