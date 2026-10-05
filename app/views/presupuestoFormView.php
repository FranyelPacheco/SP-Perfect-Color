<?php
// VISTA: presupuestoFormView.php
// OBJETIVO: Formulario para crear un nuevo presupuesto con UI/UX moderna en 2 columnas
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>Nuevo Presupuesto</h4>
        <small class="text-muted">Seleccione el cliente y agregue los insumos del catálogo interactivo</small>
    </div>
    <a href="/SP%20Perfect%20Color/presupuesto" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver a la Lista
    </a>
</div>

<form id="formularioPresupuesto">
    <div class="presupuesto-grid mb-4">
        <!-- COLUMNA IZQUIERDA: CATALOGO Y BUSQUEDA DE INSUMOS -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark fs-6">
                    <i class="bi bi-box-seam text-primary me-2"></i>Catálogo de Insumos
                </h5>
                <span id="contadorInsumos" class="badge bg-light text-muted border">0 disponibles</span>
            </div>
            <div class="card-body p-3">
                <!-- Buscador -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="busquedaInsumoPresupuesto" class="form-control border-start-0" placeholder="Buscar por código, nombre o rubro...">
                        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda" title="Limpiar"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>

                <!-- Filtros rapidos por tipo -->
                <div class="d-flex gap-1 mb-3 overflow-auto pb-1" id="filtrosTipoInsumo">
                    <button type="button" class="btn btn-sm btn-primary btn-filtro-tipo active" data-tipo="todos">Todos</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-tipo" data-tipo="1"><i class="bi bi-droplet-half me-1"></i>Bases</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-tipo" data-tipo="2"><i class="bi bi-box-seam me-1"></i>Simples</button>
                </div>

                <!-- Lista de insumos con scroll -->
                <div id="listaInsumosDisponibles" class="catalogo-scroll-container">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <div>Cargando insumos disponibles...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: DATOS DEL CLIENTE, ITEMS Y TOTAL -->
        <div class="d-flex flex-column gap-3">
            <!-- Tarjeta Cliente -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <label for="clientePresupuesto" class="form-label fw-bold text-dark mb-1">
                        <i class="bi bi-person-fill text-primary me-1"></i>Cliente <span class="text-danger">*</span>
                    </label>
                    <select id="clientePresupuesto" name="id_cliente" class="form-select" required>
                        <option value="">Seleccione un cliente...</option>
                    </select>
                </div>
            </div>

            <!-- Tarjeta Items del Presupuesto -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark fs-6">
                        <i class="bi bi-cart-check-fill text-primary me-2"></i>Items del Presupuesto
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-success shadow-sm" id="btnModalPrepararMezcla">
                            <i class="bi bi-palette-fill me-1"></i>Preparar Mezcla
                        </button>
                        <span id="badgeCantidadItems" class="badge bg-primary rounded-pill">0 items</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table id="tablaItemsPresupuesto" class="table tabla-items-presupuesto align-middle">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th class="text-center" style="width: 100px;">Cantidad</th>
                                    <th class="text-end" style="width: 120px;">Precio ($)</th>
                                    <th class="text-end" style="width: 110px;">Subtotal</th>
                                    <th class="text-center" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaItems">
                                <tr id="filaVacia">
                                    <td colspan="5" class="p-0">
                                        <div class="items-empty-state">
                                            <i class="bi bi-basket text-muted"></i>
                                            <p class="mb-1 fw-semibold text-secondary">Aún no hay items en el presupuesto</p>
                                            <small class="text-muted">Agregue productos del catálogo o use <strong>Preparar Mezcla</strong> para crear formulaciones.</small>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tarjeta Resumen y Observaciones -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="observacionesPresupuesto" class="form-label fw-semibold text-secondary small mb-1">
                                <i class="bi bi-chat-dots me-1"></i>Observaciones (Opcional)
                            </label>
                            <textarea id="observacionesPresupuesto" name="observaciones" class="form-control form-control-sm" rows="3" placeholder="Notas, especificaciones del color o detalles de entrega..."></textarea>
                        </div>
                        <div class="col-md-5">
                            <div class="caja-total-card h-100 d-flex flex-column justify-content-center">
                                <span class="caja-total-label"><i class="bi bi-receipt me-1"></i>Total Estimado</span>
                                <span id="totalPresupuesto" class="caja-total-monto">$ 0,00</span>
                                <small class="text-white-50 mt-1" id="resumenConteoItems">0 items agregados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensaje de error y Botones -->
            <div id="mensajeErrorPresupuesto" class="alert alert-danger d-none mb-0"></div>

            <div class="d-flex justify-content-end gap-2 mt-1">
                <a href="/SP%20Perfect%20Color/presupuesto" class="btn btn-light border px-4">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm" id="btnGuardarPresupuesto">
                    <i class="bi bi-check-circle-fill me-2"></i>Crear Presupuesto
                </button>
            </div>
        </div>
    </div>
</form>

<!-- MODAL PARA FORMULAR Y PREPARAR PINTURA / MEZCLA -->
<div class="modal fade" id="modalPrepararMezcla" tabindex="-1" aria-labelledby="modalPrepararMezclaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalPrepararMezclaLabel">
                    <i class="bi bi-palette-fill me-2"></i>Preparar Pintura (Fórmula y Mezcla de Tintes)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioMezcla">
                <div class="modal-body p-4">
                    <!-- Fila 1: Nombre del Color y Presentación -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="mezclaNombre" class="form-label fw-semibold text-dark">Nombre / Identificación del Color <span class="text-danger">*</span></label>
                            <input type="text" id="mezclaNombre" class="form-control" placeholder="Ej: Azul Marino Metalizado #102" required>
                        </div>
                        <div class="col-md-4">
                            <label for="mezclaPresentacion" class="form-label fw-semibold text-dark">Tamaño a Vender <span class="text-danger">*</span></label>
                            <select id="mezclaPresentacion" class="form-select" required>
                                <option value="1 Galón" data-factor="1.0">1 Galón (1 gal)</option>
                                <option value="1/2 Galón" data-factor="0.5">1/2 Galón (0.50 gal)</option>
                                <option value="1/4 Galón" data-factor="0.25" selected>1/4 Galón (0.25 gal)</option>
                                <option value="1/8 Galón" data-factor="0.125">1/8 Galón (0.125 gal)</option>
                                <option value="1/16 Galón" data-factor="0.0625">1/16 Galón (0.0625 gal)</option>
                                <option value="1/32 Galón" data-factor="0.03125">1/32 Galón (0.03125 gal)</option>
                                <option value="1/64 Galón" data-factor="0.015625">1/64 Galón (0.0156 gal)</option>
                                <option value="1/128 Galón" data-factor="0.0078125">1/128 Galón (0.0078 gal)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="mezclaCantidad" class="form-label fw-semibold text-dark">Cantidad Envases <span class="text-danger">*</span></label>
                            <input type="number" id="mezclaCantidad" class="form-control" min="1" step="1" value="1" required>
                        </div>
                    </div>

                    <!-- Fila 2: Formulación BOM (Tintes Base) -->
                    <div class="card bg-light border mb-3">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark small">
                                <i class="bi bi-droplet-half text-primary me-1"></i>Composición de Tintes Base (Máximo 6 bases concentradas)
                            </span>
                            <span id="contadorBasesMezcla" class="badge bg-light text-secondary border">0/6 bases</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
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
                                        <!-- Filas dinámicas agregadas por JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarFilaBase">
                                    <i class="bi bi-plus-circle me-1"></i>Agregar Tinte Base
                                </button>
                                <small class="text-muted" id="totalGalonesMezcla">Consumo total fórmula: 0.0000 gal</small>
                            </div>
                        </div>
                    </div>

                    <!-- Fila 3: Precio y Totales -->
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label for="mezclaPrecioVenta" class="form-label fw-semibold text-dark mb-1">Precio de Venta por Envase ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">$</span>
                                <input type="number" id="mezclaPrecioVenta" class="form-control" min="0.01" step="0.01" placeholder="0.00" required>
                            </div>
                            <small class="text-muted" id="ayudaPrecioMezcla">Costo estimado bases: $ 0.00</small>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border text-end">
                                <div class="text-muted small">Subtotal Renglón Mezcla:</div>
                                <div class="fs-4 fw-bold text-primary" id="mezclaSubtotalEstimado">$ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <div id="mensajeErrorMezcla" class="alert alert-danger d-none mt-3 mb-0"></div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarMezcla">
                        <i class="bi bi-check-lg me-1"></i>Agregar Mezcla al Presupuesto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/presupuestoForm.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/presupuestoForm.js'); ?>"></script>
