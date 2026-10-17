<?php
// VISTA: inventarioListView.php
// OBJETIVO: Lista de insumos con DataTable, alertas de stock bajo y modal CRUD
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-box-seam-fill me-2 text-primary"></i>Gestión de Inventario</h4>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <input type="text" id="busquedaInsumos" class="form-control" style="width: 220px;" placeholder="Buscar por codigo, nombre...">
                <?php if ($_SESSION['usuario_rol'] == 1): ?>
                <button id="btnPrepararMezcla" class="btn btn-primary shadow-sm"><i class="bi bi-palette-fill me-1"></i>Mezcla</button>
                <button id="btnAjusteMerma" class="btn btn-outline-danger"><i class="bi bi-dash-circle-fill me-1"></i>Merma</button>
                <?php endif; ?>
            </div>
        </div>

        <div id="alertasStockBajo" class="alert alert-warning d-none">
            <h6 class="alert-heading mb-2">Alertas de Stock Bajo</h6>
            <div id="contenidoAlertas"></div>
        </div>

        <div class="table-responsive">
            <table id="tablaInsumos" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Rubro</th>
                        <th>Stock</th>
                        <th>P. Venta</th>
                        <th>Proveedor</th>
                        <?php if ($_SESSION['usuario_rol'] == 1): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaInsumos"></tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($_SESSION['usuario_rol'] == 1): ?>
<!-- MODAL PARA EDICION DE INSUMO -->
<div id="modalInsumo" class="modal" style="display: none;" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalInsumo">Editar Insumo</h5>
                <button type="button" id="btnCerrarModalInsumo" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioInsumo">
                <div class="modal-body">
                    <input type="hidden" id="insumoId" name="id" value="">

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="codigoInsumo" class="form-label">Codigo <span class="text-danger">*</span></label>
                            <input type="text" id="codigoInsumo" name="codigo" class="form-control" required placeholder="Codigo unico">
                            <div class="invalid-feedback d-none" id="feedbackCodigoInsumo">Ya existe un insumo con este codigo</div>
                        </div>
                        <div class="col-md-5">
                            <label for="nombreInsumo" class="form-label">Nombre del Insumo / Producto</label>
                            <input type="text" id="nombreInsumo" name="nombre" class="form-control" required placeholder="Ingrese el nombre">
                        </div>
                        <div class="col-md-3">
                            <label for="tipoProductoInsumo" class="form-label">Tipo</label>
                            <select id="tipoProductoInsumo" name="id_tipo_producto" class="form-select" required>
                                <option value="2">Simple (Venta directa)</option>
                                <option value="1">Base (Tinte/Colorimetría)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="unidadMedidaInsumo" class="form-label">Unidad de Medida</label>
                            <select id="unidadMedidaInsumo" name="unidad_medida" class="form-select" required>
                                <option value="Unidad">Unidad</option>
                                <option value="KG">KG</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="stockActualInsumo" class="form-label">Stock Actual</label>
                            <input type="number" id="stockActualInsumo" name="stock_actual" class="form-control bg-light" step="1" min="0" value="0" readonly tabindex="-1">
                            <div class="form-text text-muted small">Solo lectura. Se actualiza vía Compras y Ventas.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="stockMinimoInsumo" class="form-label">Stock Mínimo</label>
                            <input type="number" id="stockMinimoInsumo" name="stock_minimo" class="form-control" step="1" min="0" value="5">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="precioVentaInsumo" class="form-label">Precio de Venta ($)</label>
                            <input type="number" id="precioVentaInsumo" name="precio_venta" class="form-control" step="0.01" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="proveedorInsumo" class="form-label">Proveedor Asociado</label>
                            <select id="proveedorInsumo" name="id_proveedor" class="form-select bg-light" disabled>
                                <option value="">Sin proveedor asociado...</option>
                            </select>
                            <div class="form-text text-muted small">El proveedor se asocia en Compras.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 d-none">
                        <div class="col-md-12">
                            <label for="rubroInsumo" class="form-label">Rubro</label>
                            <select id="rubroInsumo" name="id_rubro" class="form-select" disabled>
                                <option value="">Seleccione un proveedor primero</option>
                            </select>
                        </div>
                    </div>

                    <div id="mensajeErrorInsumo" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnCancelarInsumo" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarInsumo" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA FORMULAR Y PREPARAR PINTURA / MEZCLA -->
<div class="modal fade" id="modalPrepararMezcla" tabindex="-1" aria-labelledby="modalPrepararMezclaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalPrepararMezclaLabel">
                    <i class="bi bi-palette-fill me-2"></i>Nueva Mezcla
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioMezcla">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="mezclaNombre" class="form-label fw-semibold text-dark">Nombre / Identificación del Color <span class="text-danger">*</span></label>
                            <input type="text" id="mezclaNombre" class="form-control" placeholder="Ej: Azul Perla Fiesta" maxlength="40" required>
                        </div>
                        <div class="col-md-4">
                            <label for="mezclaPresentacion" class="form-label fw-semibold text-dark">Presentación <span class="text-danger">*</span></label>
                            <select id="mezclaPresentacion" class="form-select" required>
                                <option value="1 Galón" data-factor="1.0" selected>1 Galón (1 gal)</option>
                                <option value="1/2 Galón" data-factor="0.5">1/2 Galón (0.50 gal)</option>
                                <option value="1/4 Galón" data-factor="0.25">1/4 Galón (0.25 gal)</option>
                                <option value="1/8 Galón" data-factor="0.125">1/8 Galón (0.125 gal)</option>
                                <option value="1/16 Galón" data-factor="0.0625">1/16 Galón (0.0625 gal)</option>
                                <option value="1/32 Galón" data-factor="0.03125">1/32 Galón (0.03125 gal)</option>
                                <option value="1/64 Galón" data-factor="0.015625">1/64 Galón (0.0156 gal)</option>
                                <option value="1/128 Galón" data-factor="0.0078125">1/128 Galón (0.0078 gal)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="mezclaCantidad" class="form-label fw-semibold text-dark">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" id="mezclaCantidad" class="form-control" min="1" step="1" value="1" required>
                        </div>
                    </div>

                    <div class="card bg-light border mb-3">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark small">
                                <i class="bi bi-droplet-half text-primary me-1"></i>Tintes Base (Máx. 6)
                            </span>
                            <span id="contadorBasesMezcla" class="badge bg-light text-secondary border">0/6 bases</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-mezcla-wrapper">
                                <table class="table table-sm align-middle mb-2" id="tablaBasesMezcla">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="width: 45%;">Tinte Base Concentrado</th>
                                            <th style="width: 30%;">Medida (Fracción de Galón)</th>
                                            <th style="width: 18%;" class="text-end">Consumo Unit.</th>
                                            <th style="width: 7%;" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoBasesMezcla">
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarFilaBase" title="Agregar base" data-bs-toggle="tooltip">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <div class="text-end">
                                    <div id="indicadorBalanceFormula" class="fw-semibold small text-muted">Volumen: 0.0000 / 1.0000 gal</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label for="mezclaPrecioVenta" class="form-label fw-semibold text-dark mb-1">Precio de Venta por Envase ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">$</span>
                                <input type="number" id="mezclaPrecioVenta" class="form-control" min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border text-end">
                                <div class="text-muted small">Subtotal Estimado Total:</div>
                                <div class="fs-4 fw-bold text-primary" id="mezclaSubtotalEstimado">$ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <div id="mensajeErrorMezcla" class="alert alert-danger d-none mt-3 mb-0"></div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarMezcla">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA AJUSTE POR MERMA / DAÑO -->
<div class="modal fade" id="modalAjusteMerma" tabindex="-1" aria-labelledby="modalAjusteMermaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalAjusteMermaLabel">
                    <i class="bi bi-dash-circle-fill me-2"></i>Ajuste por Merma
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioAjusteMerma">
                <div class="modal-body p-4">
                    <div class="mb-3 position-relative" id="contenedorBuscadorAjuste">
                        <label for="inputBuscarAjusteProducto" class="form-label fw-semibold text-dark">Producto / Insumo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="inputBuscarAjusteProducto" class="form-control" placeholder="Haga clic o escriba código / nombre del producto..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary d-none" id="btnLimpiarAjusteProducto" title="Limpiar selección"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <input type="hidden" id="ajusteProducto" value="" required>
                        <div class="dropdown-menu w-100 buscador-dropdown-lista" id="listaSugerenciasAjusteProducto"></div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Stock actual disponible:</span>
                        <span class="fw-bold fs-5 text-dark" id="ajusteStockDisponible">-</span>
                    </div>

                    <div class="mb-3">
                        <label for="ajusteCantidad" class="form-label fw-semibold text-dark">Cantidad a Descontar <span class="text-danger">*</span></label>
                        <input type="number" id="ajusteCantidad" class="form-control" min="1" step="1" placeholder="1" required>
                        <div class="form-text">Esta cantidad se restará inmediatamente de las existencias (solo números enteros).</div>
                    </div>

                    <div class="mb-3">
                        <label for="ajusteMotivo" class="form-label fw-semibold text-dark">Motivo del Ajuste <span class="text-danger">*</span></label>
                        <select id="ajusteMotivo" class="form-select mb-2" required>
                            <option value="Dañado en almacén">Dañado / Caída en almacén</option>
                            <option value="Vencido o deteriorado">Vencido o químicamente deteriorado</option>
                            <option value="Evaporación / Fuga">Evaporación o derrame de envase</option>
                            <option value="Defecto de fábrica">Defecto de fábrica detectado</option>
                            <option value="Otro">Otro motivo justificado</option>
                        </select>
                        <textarea id="ajusteObservacion" class="form-control" rows="2" maxlength="200" placeholder="Detalle adicional u observación sobre la pérdida..." required></textarea>
                        <small id="contadorAjusteObservacion" class="text-muted d-block mt-1">0 / 200 caracteres (máx. 40 palabras)</small>
                    </div>

                    <div id="mensajeErrorAjuste" class="alert alert-danger d-none mb-0"></div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnGuardarAjuste">
                        <i class="bi bi-dash-lg me-1"></i>descontar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/SP%20Perfect%20Color/assets/js/inventario.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/inventario.js'); ?>"></script>
