<?php
// VISTA: notaEntregaCompraFormView.php
// OBJETIVO: Formulario para registrar la recepción de insumos/productos de un proveedor (suma stock)
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0 toolbar-title"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Registrar Compra</h2>
    <a href="/SP%20Perfect%20Color/notaEntregaCompra" class="btn btn-outline-secondary" title="Volver" data-bs-toggle="tooltip">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<form id="formNotaCompra">
    <div class="row g-4 mb-4">
        <!-- Datos de Cabecera -->
        <div class="col-12 col-xl-4">
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

                    <!-- Campos para crédito (Fecha de vencimiento + Abono inicial opcional) -->
                    <div id="campoVencimiento" class="mb-3 d-none p-3 bg-light rounded border">
                        <div class="mb-3">
                            <label for="fechaVencimiento" class="form-label fw-semibold small">Fecha de Vencimiento <span class="text-danger">*</span></label>
                            <input type="date" id="fechaVencimiento" name="fecha_vencimiento" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                            <small class="text-muted" style="font-size: 0.75rem;">Fecha límite pactada para pagar al proveedor.</small>
                        </div>

                        <div class="border-top pt-2 mt-2">
                            <label for="montoInicialCompra" class="form-label fw-semibold small text-primary mb-1">
                                <i class="bi bi-cash-coin me-1"></i>Pago / Abono Inicial ($) <span class="text-muted fw-normal">(Opcional)</span>
                            </label>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text">$</span>
                                <input type="text" id="montoInicialCompra" name="monto_inicial" class="form-control" value="0.00" placeholder="0.00" maxlength="10" autocomplete="off">
                            </div>
                            <div class="d-flex justify-content-between align-items-center small mb-2">
                                <span class="text-muted" id="montoInicialCompraBsDisplay">Equiv: Bs. 0,00</span>
                                <span>Saldo CxP: <strong class="text-danger" id="saldoPendienteCompraDisplay">$ 0,00</strong> <small class="text-muted" id="saldoPendienteCompraBsDisplay">(Bs. 0,00)</small></span>
                            </div>

                            <!-- Campos de pago del abono inicial (visibles si monto_inicial > 0) -->
                            <div id="contenedorMetodoInicialCompra" style="display: none;" class="mt-2 pt-2 border-top">
                                <div class="mb-2">
                                    <label for="idTipoPagoInicialCompra" class="form-label small fw-semibold mb-1">Método del Abono Inicial <span class="text-danger">*</span></label>
                                    <select id="idTipoPagoInicialCompra" name="id_tipo_pago_inicial" class="form-select form-select-sm">
                                        <?php foreach ($tiposPago as $tp): ?>
                                            <option value="<?php echo $tp['id_tipo_pago']; ?>"><?php echo htmlspecialchars($tp['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2" id="contenedorBancoInicialCompra" style="display: none;">
                                    <label for="idBancoInicialCompra" class="form-label small fw-semibold mb-1">Banco Emisor / Cuenta <span class="text-danger">*</span></label>
                                    <select id="idBancoInicialCompra" name="id_banco_inicial" class="form-select form-select-sm">
                                        <option value="">Seleccione banco...</option>
                                        <?php foreach ($bancos as $b): ?>
                                            <option value="<?php echo $b['id_banco']; ?>"><?php echo htmlspecialchars($b['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-1" id="contenedorRefInicialCompra" style="display: none;">
                                    <label for="referenciaInicialCompra" class="form-label small fw-semibold mb-1">Referencia (Últimos 4 dígitos) <span class="text-danger">*</span></label>
                                    <input type="text" id="referenciaInicialCompra" name="referencia_inicial" class="form-control form-control-sm" placeholder="Ej: 4821" maxlength="20">
                                </div>
                            </div>
                        </div>
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
                        <div class="mb-2" id="contenedorBancoCompra" style="display: none;">
                            <label for="idBanco" class="form-label fw-semibold">Banco Emisor / Cuenta</label>
                            <select id="idBanco" name="id_banco" class="form-select">
                                <option value="">Seleccione banco...</option>
                                <?php foreach ($bancos as $b): ?>
                                    <option value="<?php echo $b['id_banco']; ?>"><?php echo htmlspecialchars($b['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2" id="contenedorReferenciaCompra" style="display: none;">
                            <label for="referenciaPago" class="form-label fw-semibold">Referencia (Últimos 4 dígitos)</label>
                            <input type="text" id="referenciaPago" name="referencia" class="form-control" placeholder="Ej: 4821 (últimos 4 dígitos)" maxlength="20">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="3" maxlength="250" placeholder="Detalles de la entrega, factura del proveedor, etc..."></textarea>
                        <small id="contadorObservacionesCompra" class="text-muted d-block mt-1">0 / 250 caracteres (máx. 50 palabras)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insumos Recibidos (Detalle) -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0 fs-6 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Productos Recibidos</h5>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($tasaCambioGlobal) && $tasaCambioGlobal > 0): ?>
                            <span class="badge bg-info bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <i class="bi bi-currency-exchange me-1"></i>Tasa BCV: Bs. <?php echo number_format((float)$tasaCambioGlobal, 2, ',', '.'); ?> / $
                            </span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRapidoInsumo">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo Producto
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Selector de nuevo ítem con búsqueda integrada -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label for="inputBuscarProductoCompra" class="form-label small fw-semibold">Producto <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" id="inputBuscarProductoCompra" class="form-control form-control-sm" placeholder="Escriba código o nombre del producto..." autocomplete="off">
                                        <button class="btn btn-outline-secondary d-none" type="button" id="btnLimpiarProductoCompra" title="Limpiar"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                    <input type="hidden" id="selectProducto" value="">
                                    <div class="dropdown-menu w-100 p-0 shadow border buscador-dropdown-lista" id="listaSugerenciasProductoCompra" style="max-height: 260px; overflow-y: auto; z-index: 1055;"></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="cantidadItem" class="form-label small fw-semibold">Cantidad (Entero)</label>
                                <input type="number" id="cantidadItem" class="form-control form-control-sm" step="1" min="1" placeholder="Ej: 10">
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
                                    <th>Producto</th>
                                    <th style="width: 120px;" class="text-center">Cantidad</th>
                                    <th style="width: 140px;" class="text-end">Costo Unit. ($)</th>
                                    <th style="width: 150px;" class="text-end">Subtotal ($)</th>
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
                                    <td class="text-end">
                                        <div class="text-primary fs-5" id="totalCompraDisplay">$ 0,00</div>
                                        <div class="text-muted small fw-normal" id="totalCompraBsDisplay">Bs. 0,00</div>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div id="alertaErrorCompra" class="alert alert-danger d-none mt-3"></div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="/SP%20Perfect%20Color/notaEntregaCompra" class="btn btn-secondary" title="Volver" data-bs-toggle="tooltip"><i class="bi bi-arrow-left"></i></a>
                        <button type="submit" class="btn btn-success px-4" id="btnGuardarNotaCompra">
                            <i class="bi bi-check-circle-fill me-1"></i>Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Rápido para Crear Nuevo Producto -->
<div class="modal fade" id="modalRapidoInsumo" tabindex="-1" aria-labelledby="modalRapidoInsumoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalRapidoInsumoLabel">
                    <i class="bi bi-box-seam me-2"></i>Nuevo Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioRapidoInsumo">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="rapidoCodigo" class="form-label small fw-semibold">Código <span class="text-danger">*</span></label>
                            <input type="text" id="rapidoCodigo" name="codigo" class="form-control form-control-sm" required placeholder="Ej: INS-001">
                        </div>
                        <div class="col-md-7">
                            <label for="rapidoTipo" class="form-label small fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select id="rapidoTipo" name="id_tipo_producto" class="form-select form-select-sm" required>
                                <option value="2">Simple (Venta directa / Herramienta)</option>
                                <option value="1">Base (Tinte concentrado 1 Galón)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="rapidoNombre" class="form-label small fw-semibold">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" id="rapidoNombre" name="nombre" class="form-control form-control-sm" required placeholder="Ej: Solvente Limpiador 1L">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="rapidoUnidad" class="form-label small fw-semibold">Unidad de Medida <span class="text-danger">*</span></label>
                            <select id="rapidoUnidad" name="unidad_medida" class="form-select form-select-sm" required>
                                <option value="Unidad">Unidad / Galón</option>
                                <option value="KG">KG</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="rapidoStockMinimo" class="form-label small fw-semibold">Stock Mínimo</label>
                            <input type="number" id="rapidoStockMinimo" name="stock_minimo" class="form-control form-control-sm" min="0" value="5">
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label for="rapidoPrecioVenta" class="form-label small fw-semibold">Precio de Venta ($) <span class="text-danger">*</span></label>
                            <input type="number" id="rapidoPrecioVenta" name="precio_venta" class="form-control form-control-sm" step="0.01" min="0" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rapidoPrecioCompra" class="form-label small fw-semibold">Costo Estimado ($)</label>
                            <input type="number" id="rapidoPrecioCompra" name="precio_compra" class="form-control form-control-sm" step="0.01" min="0" value="0.00">
                        </div>
                    </div>

                    <div id="alertaErrorRapidoInsumo" class="alert alert-danger d-none mt-3 mb-0 small"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnGuardarRapidoInsumo">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.insumosCompraCatalogo = <?php echo json_encode($insumos ?? []); ?>;
</script>
<script src="/SP%20Perfect%20Color/assets/js/notaEntregaCompra.js?v=<?php echo file_exists(__DIR__ . '/../../assets/js/notaEntregaCompra.js') ? filemtime(__DIR__ . '/../../assets/js/notaEntregaCompra.js') : time(); ?>"></script>
