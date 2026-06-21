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
                        <th>Contacto</th>
                        <th>Telefono</th>
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
                            <label for="rifProveedor" class="form-label">RIF</label>
                            <input type="text" id="rifProveedor" name="rif" class="form-control" required placeholder="Ej: J-123456789" maxlength="11">
                        </div>
                        <div class="col-md-6">
                            <label for="nombreEmpresaProveedor" class="form-label">Nombre de la Empresa</label>
                            <input type="text" id="nombreEmpresaProveedor" name="nombre_empresa" class="form-control" required placeholder="Ingrese el nombre de la empresa">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="direccionProveedor" class="form-label">Direccion</label>
                        <textarea id="direccionProveedor" name="direccion" class="form-control" rows="2" placeholder="Ingrese la direccion"></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="contactoProveedor" class="form-label">Contacto</label>
                            <input type="text" id="contactoProveedor" name="contacto" class="form-control" placeholder="Nombre del contacto">
                        </div>
                        <div class="col-md-4">
                            <label for="telefonoProveedor" class="form-label">Telefono</label>
                            <input type="text" id="telefonoProveedor" name="telefono" class="form-control" placeholder="11 digitos" maxlength="11">
                        </div>
                        <div class="col-md-4">
                            <label for="correoProveedor" class="form-label">Correo</label>
                            <input type="email" id="correoProveedor" name="correo" class="form-control" placeholder="correo@ejemplo.com">
                        </div>
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
                    <button type="button" id="btnCancelarProveedor" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarProveedor" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="/SP%20Perfect%20Color/assets/js/proveedor.js"></script>
