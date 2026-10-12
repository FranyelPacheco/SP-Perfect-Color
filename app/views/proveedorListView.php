<?php
// VISTA: proveedorListView.php
// OBJETIVO: Listado y gestión de proveedores con modal de formulario
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-truck me-2 text-primary"></i>Proveedores</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaProveedores" class="form-control" style="width: 220px;" placeholder="Buscar por nombre de empresa o RIF...">
                <?php if ($_SESSION['usuario_rol'] == 1): ?>
                <button id="btnNuevoProveedor" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaProveedores" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>RIF</th>
                        <th>Nombre de Empresa</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Rubros</th>
                        <?php if ($_SESSION['usuario_rol'] == 1): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaProveedores"></tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($_SESSION['usuario_rol'] == 1): ?>
<div id="modalProveedor" class="modal" style="display: none;" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalProveedor">Nuevo Proveedor</h5>
                <button type="button" id="btnCerrarModalProveedor" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioProveedor">
                <div class="modal-body">
                    <input type="hidden" id="proveedorId" name="id" value="">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">RIF / Documento <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select id="tipoRifProveedor" name="tipo_rif" class="form-select" style="max-width: 85px;">
                                    <option value="J" selected>J</option>
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                    <option value="G">G</option>
                                </select>
                                <input type="text" id="rifProveedor" name="rif_num" class="form-control" required placeholder="1-9 dígitos" maxlength="9">
                            </div>
                            <input type="hidden" id="rifCompletoProveedor" name="rif" value="">
                            <div id="feedbackRifProveedor" class="invalid-feedback d-none">Este RIF ya existe en el sistema</div>
                        </div>
                        <div class="col-md-6">
                            <label for="nombreEmpresaProveedor" class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                            <input type="text" id="nombreEmpresaProveedor" name="nombre_empresa" class="form-control" required placeholder="Ingrese el nombre de la empresa">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="direccionProveedor" class="form-label">Dirección</label>
                        <textarea id="direccionProveedor" name="direccion" class="form-control" rows="2" placeholder="Ingrese la dirección"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="correoProveedor" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" id="correoProveedor" name="correo" class="form-control" required placeholder="correo@ejemplo.com" pattern="^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$" title="Ingrese un correo electrónico válido">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Teléfono Principal <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="btnToggleTel2Proveedor">
                                <i class="bi bi-plus-circle me-1"></i>Añadir segundo teléfono
                            </button>
                        </div>
                        <div class="input-group">
                            <select id="operadoraProveedor" name="operadora" class="form-select" style="max-width: 95px;">
                                <option value="0412" selected>0412</option>
                                <option value="0414">0414</option>
                                <option value="0424">0424</option>
                                <option value="0416">0416</option>
                                <option value="0426">0426</option>
                                <option value="0251">0251</option>
                            </select>
                            <input type="text" id="telefonoNumeroProveedor" name="telefono_num" class="form-control" placeholder="7 dígitos" maxlength="7" pattern="[0-9]{7}">
                        </div>
                        <input type="hidden" id="telefonoProveedor" name="telefono" value="">
                    </div>

                    <div class="mb-3 d-none" id="seccionTel2Proveedor">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-muted mb-0 small">Segundo Teléfono (Opcional)</label>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" id="btnQuitarTel2Proveedor">
                                <i class="bi bi-trash me-1"></i>Quitar
                            </button>
                        </div>
                        <div class="input-group">
                            <select id="operadoraProveedor2" name="operadora2" class="form-select" style="max-width: 95px;">
                                <option value="0412">0412</option>
                                <option value="0414">0414</option>
                                <option value="0424" selected>0424</option>
                                <option value="0416">0416</option>
                                <option value="0426">0426</option>
                                <option value="0251">0251</option>
                            </select>
                            <input type="text" id="telefonoNumeroProveedor2" name="telefono_num2" class="form-control" placeholder="7 dígitos" maxlength="7" pattern="[0-9]{7}">
                        </div>
                        <input type="hidden" id="telefonoProveedor2" name="telefono2" value="">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rubros</label>
                        <div id="rubrosContainer">
                            <div class="input-group mb-2 rubro-item">
                                <select name="rubros[]" class="form-select" disabled><option value="">Cargando rubros...</option></select>
                                <button class="btn btn-outline-danger btn-remove-rubro" type="button" disabled><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                        <button type="button" id="btnAgregarRubro" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Agregar Rubro</button>
                    </div>

                    <div id="mensajeErrorProveedor" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnCancelarProveedor" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="submit" id="btnGuardarProveedor" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/SP%20Perfect%20Color/assets/js/proveedor.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/proveedor.js'); ?>"></script>
