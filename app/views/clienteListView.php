<?php
// VISTA: clienteListView.php
// OBJETIVO: Lista de clientes con DataTable, búsqueda y modal CRUD
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-people-fill me-2 text-primary"></i>Clientes</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaClientes" class="form-control input-busqueda" placeholder="Buscar por cédula, nombre o apellido...">
                <button id="btnNuevoCliente" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaClientes" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaClientes"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar cliente (Bootstrap 5) -->
<div id="modalCliente" class="modal fade" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalCliente">Nuevo Cliente</h5>
                <button type="button" id="btnCerrarModalCliente" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formularioCliente">
                <div class="modal-body">
                    <input type="hidden" id="clienteId" name="id" value="">

                    <div class="mb-3">
                        <label class="form-label">Cédula / Documento <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <select id="tipoDocCliente" name="tipo_doc" class="form-select" style="max-width: 85px;">
                                <option value="V" selected>V</option>
                                <option value="E">E</option>
                                <option value="J">J</option>
                            </select>
                            <input type="text" id="cedulaCliente" name="cedula_num" class="form-control" required placeholder="Número de documento" maxlength="9">
                            <div id="feedbackCedulaCliente" class="invalid-feedback"><i class="bi bi-exclamation-circle-fill me-1"></i>Cliente ya registrado</div>
                        </div>
                        <input type="hidden" id="cedulaCompletaCliente" name="cedula" value="">
                    </div>

                    <div class="mb-3">
                        <label for="nombresCliente" class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" id="nombresCliente" name="nombres" class="form-control" required maxlength="40" placeholder="Ingrese los nombres" pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$" title="Solo letras y espacios (máx. 40 caracteres)">
                    </div>

                    <div class="mb-3">
                        <label for="apellidosCliente" class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" id="apellidosCliente" name="apellidos" class="form-control" required maxlength="40" placeholder="Ingrese los apellidos" pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$" title="Solo letras y espacios (máx. 40 caracteres)">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Teléfono Principal <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="btnToggleTel2Cliente">
                                <i class="bi bi-plus-circle me-1"></i>Añadir segundo teléfono
                            </button>
                        </div>
                        <div class="input-group">
                            <select id="operadoraCliente" name="operadora" class="form-select" style="max-width: 105px;">
                                <option value="0412" selected>0412</option>
                                <option value="0414">0414</option>
                                <option value="0424">0424</option>
                                <option value="0416">0416</option>
                                <option value="0426">0426</option>
                                <option value="0251">0251</option>
                            </select>
                            <input type="text" id="telefonoNumeroCliente" name="telefono_num" class="form-control" required placeholder="7 dígitos (ej: 1234567)" maxlength="7" pattern="[0-9]{7}">
                        </div>
                        <input type="hidden" id="telefonoCliente" name="telefono" value="">
                    </div>

                    <div class="mb-3 d-none" id="seccionTel2Cliente">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-muted mb-0 small">Segundo Teléfono (Opcional)</label>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" id="btnQuitarTel2Cliente">
                                <i class="bi bi-trash me-1"></i>Quitar
                            </button>
                        </div>
                        <div class="input-group">
                            <select id="operadoraCliente2" name="operadora2" class="form-select" style="max-width: 105px;">
                                <option value="0412">0412</option>
                                <option value="0414">0414</option>
                                <option value="0424" selected>0424</option>
                                <option value="0416">0416</option>
                                <option value="0426">0426</option>
                                <option value="0251">0251</option>
                            </select>
                            <input type="text" id="telefonoNumeroCliente2" name="telefono_num2" class="form-control" placeholder="7 dígitos (ej: 7654321)" maxlength="7" pattern="[0-9]{7}">
                        </div>
                        <input type="hidden" id="telefonoCliente2" name="telefono2" value="">
                    </div>

                    <div class="mb-3">
                        <label for="correoCliente" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" id="correoCliente" name="correo" class="form-control" required maxlength="100" placeholder="correo@ejemplo.com" pattern="^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$" title="Ingrese un correo electrónico válido">
                        <div id="feedbackCorreoCliente" class="invalid-feedback"><i class="bi bi-exclamation-circle-fill me-1"></i>Correo ya registrado</div>
                    </div>

                    <div class="mb-3">
                        <label for="direccionCliente" class="form-label">Dirección</label>
                        <textarea id="direccionCliente" name="direccion" class="form-control" maxlength="250" rows="2" placeholder="Ingrese la dirección"></textarea>
                    </div>

                    <div id="mensajeErrorCliente" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnCancelarCliente" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancelar</button>
                    <button type="submit" id="btnGuardarCliente" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/cliente.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/cliente.js'); ?>"></script>
