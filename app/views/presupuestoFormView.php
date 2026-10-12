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
                <div class="d-flex gap-1 mb-2 overflow-auto pb-1" id="filtrosTipoInsumo">
                    <button type="button" class="btn btn-sm btn-primary btn-filtro-tipo active" data-tipo="todos">Todos</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-tipo" data-tipo="1"><i class="bi bi-droplet-half me-1"></i>Bases</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-tipo" data-tipo="2"><i class="bi bi-box-seam me-1"></i>Simples</button>
                </div>

                <!-- Sub-filtro por tamaño/fracción para bases -->
                <div class="d-flex gap-1 mb-3 overflow-auto pb-1 d-none" id="filtrosFraccionBase">
                    <button type="button" class="btn btn-sm btn-outline-primary active btn-filtro-fraccion" data-fraccion="todos">Todas</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1">1 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/2">1/2 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/4">1/4 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/8">1/8 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/16">1/16 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/32">1/32 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/64">1/64 Gal</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filtro-fraccion" data-fraccion="1/128">1/128 Gal</button>
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
                    <div class="row g-2">
                        <div class="col-12">
                            <label for="clientePresupuesto" class="form-label fw-bold text-dark mb-1">
                                <i class="bi bi-person-fill text-primary me-1"></i>Cliente <span class="text-danger">*</span>
                            </label>
                            <select id="clientePresupuesto" name="id_cliente" class="form-select" required>
                                <option value="">Seleccione un cliente...</option>
                            </select>
                            <small class="text-muted">El presupuesto es una cotización preliminar. Las condiciones y forma de pago se definen al generar la Nota de Entrega.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjeta Items del Presupuesto -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark fs-6">
                        <i class="bi bi-cart-check-fill text-primary me-2"></i>Items del Presupuesto
                    </h5>
                    <div class="d-flex align-items-center gap-2">
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
                                            <small class="text-muted">Seleccione productos del catálogo a la derecha para agregarlos al presupuesto.</small>
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

<script src="/SP%20Perfect%20Color/assets/js/presupuestoForm.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/presupuestoForm.js'); ?>"></script>
