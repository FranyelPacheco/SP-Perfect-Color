<?php
// VISTA: notaEntregaEditView.php
// OBJETIVO: Edición de items de una nota de entrega
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Editar Nota de Entrega #<?php echo $nota['id_nota_entrega']; ?></h2>
    <a href="/SP%20Perfect%20Color/notaEntrega/ver?id=<?php echo $nota['id_nota_entrega']; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<form id="formularioEditarNota">
    <input type="hidden" name="id_nota_entrega" value="<?php echo $nota['id_nota_entrega']; ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-person-fill me-2 text-primary"></i>Datos del Cliente</h5>
        </div>
        <div class="card-body">
            <div class="mb-0">
                <small class="text-muted d-block">Cliente:</small>
                <span class="fw-bold"><?php echo $nota['cliente_nombre'] . ' (' . $nota['cliente_cedula'] . ')'; ?></span>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <small class="text-muted d-block">Tipo de Pago:</small>
                    <span><?php echo ucfirst($nota['condicion_pago'] ?? '-'); ?></span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Metodo de Pago:</small>
                    <span><?php echo $nota['tipo_pago_nombre'] ?? '-'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Items de la Nota</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaItemsEdit" class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Insumo</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaItemsEdit">
                        <tr id="filaVaciaEdit" style="display:none">
                            <td colspan="7" class="text-center text-muted">No hay items agregados</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total:</td>
                            <td id="totalEdit" class="fw-bold">$ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="mensajeErrorEdit" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <button type="button" id="btnAgregarInsumo" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Agregar Insumo</button>
        <div class="d-flex gap-2">
            <a href="/SP%20Perfect%20Color/notaEntrega/ver?id=<?php echo $nota['id_nota_entrega']; ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-floppy me-2"></i>Guardar Cambios</button>
        </div>
    </div>
</form>

<!-- Modal para buscar y agregar insumos -->
<div class="modal fade" id="modalAgregarInsumo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-search me-2"></i>Agregar Insumo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="busquedaInsumoModal" class="form-control form-control-lg" placeholder="Buscar insumo por nombre o codigo...">
                </div>
                <div id="resultadosInsumoModal" class="list-group">
                    <div class="text-muted p-2">Escriba al menos 1 caracter para buscar</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
var notaDetalleExistente = <?php echo json_encode($detalle); ?>;
</script>
<script src="/SP%20Perfect%20Color/assets/js/notaEntregaEdit.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntregaEdit.js'); ?>"></script>
