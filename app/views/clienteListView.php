<?php
// VISTA: clienteListView.php
// OBJETIVO: Lista de clientes con DataTable, búsqueda y modal CRUD
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 toolbar-title"><i class="bi bi-people-fill me-2 text-primary"></i>Clientes</h4>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="busquedaClientes" class="form-control" style="width: 220px;" placeholder="Buscar por cedula, nombre o apellido...">
                <button id="btnNuevoCliente" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nuevo</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaClientes" class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Cedula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Telefono</th>
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
<div id="modalCliente" class="modal" style="display: none;" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalCliente">Nuevo Cliente</h5>
                <button type="button" id="btnCerrarModalCliente" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formularioCliente">
                <div class="modal-body">
                    <input type="hidden" id="clienteId" name="id" value="">

                    <div class="mb-3">
                        <label for="cedulaCliente" class="form-label">Cedula</label>
                        <input type="text" id="cedulaCliente" name="cedula" class="form-control" required placeholder="Ingrese la cedula (7-8 digitos)" maxlength="8">
                    </div>

                    <div class="mb-3">
                        <label for="nombresCliente" class="form-label">Nombres</label>
                        <input type="text" id="nombresCliente" name="nombres" class="form-control" required placeholder="Ingrese los nombres">
                    </div>

                    <div class="mb-3">
                        <label for="apellidosCliente" class="form-label">Apellidos</label>
                        <input type="text" id="apellidosCliente" name="apellidos" class="form-control" required placeholder="Ingrese los apellidos">
                    </div>

                    <div class="mb-3">
                        <label for="telefonoCliente" class="form-label">Telefono</label>
                        <input type="text" id="telefonoCliente" name="telefono" class="form-control" placeholder="Ingrese el telefono (11 digitos)" maxlength="11">
                    </div>

                    <div class="mb-3">
                        <label for="correoCliente" class="form-label">Correo Electronico</label>
                        <input type="email" id="correoCliente" name="correo" class="form-control" placeholder="Ingrese el correo electronico">
                    </div>

                    <div class="mb-3">
                        <label for="direccionCliente" class="form-label">Direccion</label>
                        <textarea id="direccionCliente" name="direccion" class="form-control" rows="2" placeholder="Ingrese la direccion"></textarea>
                    </div>

                    <div id="mensajeErrorCliente" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnCancelarCliente" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarCliente" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/SP%20Perfect%20Color/assets/js/cliente.js"></script>
