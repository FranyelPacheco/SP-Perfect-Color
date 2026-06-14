<!-- Archivo: inventarioListView.php -->
<!-- Vista para la gestion de inventario con Bootstrap 5 -->

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-box-seam-fill me-2 text-primary"></i>Inventario / Insumos</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaInsumos" class="form-control" style="width: 220px;" placeholder="Buscar por codigo, nombre o categoria...">
                <?php if ($_SESSION['usuario_rol'] == 1): ?>
                <button id="btnNuevoInsumo" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
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
                        <th>Marca</th>
                        <th>Categoria</th>
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
<div id="modalInsumo" class="modal" style="display: none;" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalInsumo">Nuevo Insumo</h5>
                <button type="button" id="btnCerrarModalInsumo" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioInsumo">
                <div class="modal-body">
                    <input type="hidden" id="insumoId" name="id" value="">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="codigoInsumo" class="form-label">Codigo</label>
                            <input type="text" id="codigoInsumo" name="codigo" class="form-control" required placeholder="Codigo unico del insumo">
                        </div>
                        <div class="col-md-6">
                            <label for="categoriaInsumo" class="form-label">Categoria</label>
                            <select id="categoriaInsumo" name="categoria" class="form-select">
                                <option value="">Seleccione una categoria</option>
                                <option value="Tintes">Tintes</option>
                                <option value="Quimicos">Quimicos</option>
                                <option value="Pintura Automotriz">Pintura Automotriz</option>
                                <option value="Brillo Directo">Brillo Directo</option>
                                <option value="Acrilico">Acrilico</option>
                                <option value="Fondos Anticorrosivos">Fondos Anticorrosivos</option>
                                <option value="Esmalte">Esmalte</option>
                                <option value="Sintetico">Sintetico</option>
                                <option value="Transparentes">Transparentes</option>
                                <option value="Masillas">Masillas</option>
                                <option value="Lijas">Lijas</option>
                                <option value="Thiner">Thiner</option>
                                <option value="Disolvente">Disolvente</option>
                                <option value="Spray">Spray</option>
                                <option value="Pulitura">Pulitura</option>
                                <option value="Mopas">Mopas</option>
                                <option value="Brochas">Brochas</option>
                                <option value="Herramientas">Herramientas</option>
                                <option value="Ferreteria">Ferreteria</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombreInsumo" class="form-label">Nombre del Insumo</label>
                        <input type="text" id="nombreInsumo" name="nombre" class="form-control" required placeholder="Ingrese el nombre del insumo">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="marcaInsumo" class="form-label">Marca</label>
                            <input type="text" id="marcaInsumo" name="marca" class="form-control" placeholder="Ingrese la marca">
                        </div>
                        <div class="col-md-6">
                            <label for="unidadMedidaInsumo" class="form-label">Unidad de Medida</label>
                            <select id="unidadMedidaInsumo" name="unidad_medida" class="form-select">
                                <option value="">Seleccione...</option>
                                <option value="Unidad">Unidad</option>
                                <option value="Litro">Litro</option>
                                <option value="Galon">Galon</option>
                                <option value="Kilogramo">Kilogramo</option>
                                <option value="Gramo">Gramo</option>
                                <option value="Metro">Metro</option>
                                <option value="Caja">Caja</option>
                                <option value="Paquete">Paquete</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="stockActualInsumo" class="form-label">Stock Actual</label>
                            <input type="number" id="stockActualInsumo" name="stock_actual" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label for="stockMinimoInsumo" class="form-label">Stock Minimo</label>
                            <input type="number" id="stockMinimoInsumo" name="stock_minimo" class="form-control" step="0.01" min="0" value="5">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="precioVentaInsumo" class="form-label">Precio de Venta ($)</label>
                            <input type="number" id="precioVentaInsumo" name="precio_venta" class="form-control" step="0.01" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="precioCompraInsumo" class="form-label">Precio de Compra ($)</label>
                            <input type="number" id="precioCompraInsumo" name="precio_compra" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="proveedorInsumo" class="form-label">Proveedor</label>
                        <select id="proveedorInsumo" name="proveedor_id" class="form-select">
                            <option value="">Seleccione un proveedor...</option>
                        </select>
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
<?php endif; ?>

<script src="/SP%20Perfect%20Color/assets/js/inventario.js"></script>
