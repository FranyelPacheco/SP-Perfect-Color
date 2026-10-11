<?php
// VISTA: notaEntregaCompraFormView.php
// OBJETIVO: Formulario para registrar la recepción de insumos/productos de un proveedor (suma stock)
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0 toolbar-title"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Registrar Nota de Entrega (Compra)</h2>
    <a href="/SP%20Perfect%20Color/notaEntregaCompra" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al Listado
    </a>
</div>

<form id="formNotaCompra">
    <div class="row g-4 mb-4">
        <!-- Datos de Cabecera -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fs-6 fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Datos del Proveedor y Compra</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="idProveedor" class="form-label fw-semibold">Proveedor <span class="text-danger">*</span></label>
                        <select id="idProveedor" name="id_proveedor" class="form-select" required>
                            <option value="">Seleccione un proveedor...</option>
                            <?php foreach ($proveedores as $p): ?>
                                <option value="<?php echo $p['id_proveedor']; ?>">
                                    <?php echo htmlspecialchars($p['rif'] . ' - ' . $p['nombre_empresa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="fechaCompra" class="form-label fw-semibold">Fecha de Recepción <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="fechaCompra" name="fecha" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="condicionPago" class="form-label fw-semibold">Condición de Pago <span class="text-danger">*</span></label>
                        <select id="condicionPago" name="condicion_pago" class="form-select" required>
                            <option value="contado">Contado</option>
                            <option value="credito">Crédito (Genera Cuenta por Pagar)</option>
                        </select>
                    </div>

                    <!-- Campos para crédito -->
                    <div id="campoVencimiento" class="mb-3 d-none">
                        <label for="fechaVencimiento" class="form-label fw-semibold">Fecha de Vencimiento <span class="text-danger">*</span></label>
                        <input type="date" id="fechaVencimiento" name="fecha_vencimiento" class="form-control" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                        <small class="text-muted">Fecha límite pactada para pagar al proveedor.</small>
                    </div>

                    <!-- Campos para contado -->
                    <div id="camposContado" class="mb-3">
                        <div class="mb-2">
                            <label for="idTipoPago" class="form-label fw-semibold">Método de Pago</label>
                            <select id="idTipoPago" name="id_tipo_pago" class="form-select">
                                <?php foreach ($tiposPago as $tp): ?>
                                    <option value="<?php echo $tp['id_tipo_pago']; ?>"><?php echo htmlspecialchars($tp['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="referenciaPago" class="form-label fw-semibold">Referencia / Comprobante</label>
                            <input type="text" id="referenciaPago" name="referencia" class="form-control" placeholder="Ej. REF-48921">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="3" placeholder="Detalles de la entrega, factura del proveedor, etc..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insumos Recibidos (Detalle) -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fs-6 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Insumos y Productos Recibidos</h5>
                </div>
                <div class="card-body">
                    <!-- Selector de nuevo ítem -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label for="selectProducto" class="form-label small fw-semibold">Producto / Insumo</label>
                                <select id="selectProducto" class="form-select form-select-sm">
                                    <option value="">Seleccione un producto...</option>
                                    <?php foreach ($insumos as $ins): ?>
                                        <option value="<?php echo $ins['id_producto']; ?>" 
                                                data-codigo="<?php echo htmlspecialchars($ins['codigo']); ?>"
                                                data-nombre="<?php echo htmlspecialchars($ins['nombre']); ?>"
                                                data-precio="<?php echo htmlspecialchars($ins['precio_compra'] ?? '0.00'); ?>"
                                                data-stock="<?php echo htmlspecialchars($ins['stock_actual']); ?>">
                                            <?php echo htmlspecialchars($ins['codigo'] . ' - ' . $ins['nombre'] . ' (Stock actual: ' . $ins['stock_actual'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="cantidadItem" class="form-label small fw-semibold">Cantidad</label>
                                <input type="number" id="cantidadItem" class="form-control form-control-sm" step="0.0001" min="0.0001" placeholder="Ej. 10">
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="precioItem" class="form-label small fw-semibold">Costo ($)</label>
                                <input type="number" id="precioItem" class="form-control form-control-sm" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="button" id="btnAgregarItem" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Añadir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de ítems añadidos -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="tablaDetalleCompra">
                            <thead class="table-light">
                                <tr>
                                    <th>Insumo / Producto</th>
                                    <th style="width: 120px;" class="text-center">Cantidad</th>
                                    <th style="width: 140px;" class="text-end">Costo Unit. ($)</th>
                                    <th style="width: 140px;" class="text-end">Subtotal ($)</th>
                                    <th style="width: 60px;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoDetalleCompra">
                                <tr id="filaVacia">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                        Añada los insumos recibidos del proveedor usando el formulario superior.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="3" class="text-end">TOTAL DE LA COMPRA:</td>
                                    <td class="text-end text-primary fs-5" id="totalCompraDisplay">$ 0,00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div id="alertaErrorCompra" class="alert alert-danger d-none mt-3"></div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="/SP%20Perfect%20Color/notaEntregaCompra" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success px-4" id="btnGuardarNotaCompra">
                            <i class="bi bi-check-circle-fill me-2"></i>Registrar Recepción y Sumar Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="/SP%20Perfect%20Color/assets/js/notaEntregaCompra.js?v=<?php echo file_exists(__DIR__ . '/../../assets/js/notaEntregaCompra.js') ? filemtime(__DIR__ . '/../../assets/js/notaEntregaCompra.js') : time(); ?>"></script>
