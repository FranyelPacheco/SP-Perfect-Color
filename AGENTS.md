# Session Summary

## Cuentas por Pagar — DataTable column count fix

### Problem
- DataTable `#tablaCuentasPagar` showed "Incorrect column count" when navigating to Cuentas por Pagar
- Error occurred after database re-import

### Root causes found & fixed
1. **Duplicate `const DATATABLES_SPANISH`**: Both `assets/js/utilidades.js` and `assets/js/cuentaPagar.js` declared the same `const`. Due to load order (cuentaPagar.js loads before utilidades.js), `utilidades.js` threw `SyntaxError: Identifier 'DATATABLES_SPANISH' has already been declared`, preventing proper execution.

2. **No explicit `columns` definition**: DataTable was initialized without explicit column mapping, relying on auto-detection from `<thead>`. Combined with array-based `row.add([...])`, any mismatch in auto-detected column count would cause the error.

### Changes made
- **`assets/js/cuentaPagar.js`**:
  - Added explicit `columns: [...]` array with 7 entries matching the 7 `<th>` elements
  - Changed from array-based `row.add([...7 items...])` to object-based `row.add(cuenta)` using the `data` properties in columns definition
  - Each column uses a `render` function for formatted output (moneda, estado badge, acciones, etc.)
  - Added defensive guards (`if (!data) return '';`) in all render functions to prevent crashes on null/undefined data
  - Retained `const DATATABLES_SPANISH` definition (every feature file defines its own copy)
- **`assets/js/utilidades.js`**: Removed duplicate `const DATATABLES_SPANISH` definition (caused `SyntaxError: Identifier 'DATATABLES_SPANISH' has already been declared` because feature `.js` files load before `utilidades.js` and define their own copy)
- **`app/views/cuentaPagarListView.php`**: Removed placeholder `<tr><td colspan="7">` from `<tbody>` — empty tbody prevents DataTables from mis-counting columns during initialization

### Modules standardized to same DataTables pattern

| Module | View | `<th>` count | JS file | Key features |
|---|---|---|---|---|
| CxC | `cuentaCobrarListView.php` | 8 | `cuentaCobrar.js` | Documento col (NE/Factura), Vencida badge |
| Notas de Entrega | `notaEntregaListView.php` | 7 | `notaEntrega.js` | ID prefixed with `#`, total formatted |
| Presupuestos | `presupuestoListView.php` | 8 | `presupuesto.js` | Approve/Reject buttons in acciones, estado filter column(5) |

Changes applied to each:
- Removed placeholder `<tr><td colspan="N">` from `<tbody>` in all view files
- Added explicit `columns: [...]` with `data` + `render` for every `<th>`
- Changed from array-based `row.add([...])` to object-based `row.add(row)`
- Added defensive guards (`if (!data) return '';`) in all render functions

### Toolbar layout standardized
All four list views now share identical toolbar structure:
```
card > card-body > toolbar [h4 title left | search + button right] > table-responsive > table
```
- Title `<h4>` inside card, left-aligned
- Search input (`form-control`, `width: 250px`) + optional filter + `<button>` inside `div.d-flex.gap-2`, right-aligned
- All "Nuevo" buttons use `<button class="btn btn-success"><i class="bi bi-plus-lg me-2"></i>Nuevo`
  - CxP: modal toggle → `data-bs-toggle="modal"`
  - Notas/Presupuestos: navigation → `onclick="location.href='...'"`
  - CxC: no create action yet (search only)

### Previous issue fixed
- Search bar was not filtering because it used native `<input>`+ event instead of DataTables search API (was using `window.open()` which reloaded the page)
- Fixed by using `$('#tablaCuentasPagar').DataTable().search(this.value).draw()` on keyup

## Módulo Reportes — Simplificado a 2 tipos + Exportación PDF/Excel

### Problema
- Demasiados tipos de reporte (9) saturaban la UI; la mayoría eran redundantes o no usados
- No existía capacidad de exportación PDF/Excel
- La exportación vía fetch no funcionaba para respuestas de archivos binarios

### Cambios realizados
- **`app/models/ReporteModel.php`**: Eliminados 8 métodos no usados (`ingresosPorRango`, `egresosPorRango`, `totalIngresosPorMetodoPago`, `totalEgresosPorMetodoPago`, `cuentasVencidas`, `antiguedadSaldos`, `ventasPorVendedor`, `ventasPorMetodoPago`, `productosMasVendidos`). Mantenidos `ventasPorRango`, `totalVentasPorTipoPago`, `totalVentasPorMetodoPago` y `carteraCxc()`.
- **`app/controllers/reporteController.php`**: Reducido a 5 endpoints: `index`, `ventasAjax`, `carteraCxcAjax`, `exportarPdfAjax`, `exportarExcelAjax`. Todos usan `use function App\Helpers\generarPDF` y `generarExcel`.
- **`app/views/reporteListView.php`**: `<select>` solo tiene "Notas de Entrega" (`ventas`) y "Cuentas por Cobrar Pendientes" (`carteraCxc`). Botones PDF/Excel ocultos hasta generar.
- **`assets/js/reporte.js`**: Simplificado a 2 tipos. `cambiarEncabezado()` limpia cuerpo/resumen/export al cambiar `<select>`. Export usa `window.location.href`.
- **`app/helpers/exportarReporteHelper.php`**: Creado — `generarPDF($tipo, $desde, $hasta)` con Dompdf (A4 horizontal, attachment), `generarExcel($tipo, $desde, $hasta)` con OpenSpout Writer (XLSX, descarga navegador).
- **`composer.json`**: Agregados `dompdf/dompdf ^3.1` y `openspout/openspout ^4.25` a require; agregado `"app/helpers/exportarReporteHelper.php"` a autoload.files.

### Decisiones clave
- Simplificado de 9 a 2 tipos tras considerar el usuario que los demás eran redundantes
- Usado `window.location.href` en lugar de fetch para exportaciones (respuesta de archivo binario)
- PSR-4 no auto-carga archivos de funciones; debe usarse arreglo `"files"` en composer.json

### Cambio collation BD a utf8mb4_spanish2_ci
- Ejecutado `ALTER DATABASE` + `ALTER TABLE` en las 17 tablas
- Actualizado `sp_perfect_color.sql` reemplazando todas las ocurrencias `utf8mb4_unicode_ci` → `utf8mb4_spanish2_ci`
- Agregado `$this->conexion->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish2_ci'")` en el constructor de `ConexionBD.php`

---

## Sesión actual — 4 cambios de funcionalidad + encapsulamiento

### 1. Presupuesto DataTable — mostrar ID
- **`assets/js/presupuesto.js`**: Columna 0 cambiada de `data: 'id'` a `data: 'id_presupuesto'` (la API devuelve `id_presupuesto`, no `id`, por lo que la columna se mostraba vacía)

### 2. RIF — solo J/V/E/G + máximo 9 dígitos
- **`app/helpers/validacionHelper.php`**: Regex actualizada de `/^[JGVEP]-\d{8,9}$/` → `/^[JVEG]-\d{1,9}$/` (quitado `P`, min 1 dígito, max 9)
- **`assets/js/proveedor.js`**: Auto-format solo acepta J/V/E/G; validación de envío con misma regex; limite de 9 dígitos vía JS
- **`app/views/proveedorListView.php`**: `maxlength="11"` en input RIF

### 3. Pago móvil/Transferencia — banco+ref obligatorio + Tarjeta Crédito eliminada
- **`app/core/sp_perfect_color.sql`**: Eliminado seed `(5, 'Tarjeta Credito', 1)` (ya borrada de BD por usuario)
- **`app/views/notaEntregaFormView.php`**: Detección por ID (`val === 2 || val === 3`) en vez de texto; `required` dinámico en banco/referencia; labels sin "(opcional)"
- **`assets/js/notaEntregaForm.js`**: Validación JS: si tipo_pago es 2 o 3, banco y referencia obligatorios antes de enviar
- **`app/controllers/notaEntregaController.php`**: Validación PHP: si tipo_pago 2 o 3, `banco_id` y `referencia` obligatorios
- **`app/views/cuentaCobrarVerView.php`**: Detección por ID; `required` dinámico; validación JS con mensajes específicos
- **`app/controllers/cuentaCobrarController.php`**: Validación PHP: si tipo_pago 2 o 3, `banco_id` y `referencia` obligatorios
- **`app/views/cuentaPagarVerView.php`**: Mismos cambios que cuentaCobrarVerView
- **`app/controllers/cuentaPagarController.php`**: Mismos cambios que cuentaCobrarController

### 4. Encapsulamiento — 11 models (método público → privado)
Patrón aplicado a cada modelo: métodos públicos con operaciones DB ahora delegan a implementaciones privadas con prefijo `_`.

| Modelo | Métodos encapsulados |
|--------|---------------------|
| `ClienteModel` | 10 métodos (`listarTodos`, `buscarPorId`, `insertarCliente`, `actualizarCliente`, `insertarTelefono`, `eliminarTelefonos`, `eliminarCliente`, `cedulaExiste`, `buscarClientes`) |
| `ProveedorModel` | 11 métodos (CRUD + `rifExiste`, `buscarProveedores`, teléfonos, rubros) |
| `InventarioModel` | 13 métodos (CRUD + stock, proveedores, rubros) |
| `PresupuestoModel` | 7 métodos (CRUD + detalle, estados) |
| `NotaEntregaModel` | 7 métodos (CRUD + detalle, estados) |
| `CuentaCobrarModel` | 9 métodos (CRUD + pagos, tipos_pago, bancos) |
| `CuentaPagarModel` | 9 métodos (CRUD + pagos, proveedores, tipos_pago, bancos) |
| `UsuarioModel` | 9 métodos (CRUD + correo, roles, clave) |
| `ReporteModel` | 4 métodos (ventas, carteraCxc, totales) |
| `TipoPagoModel` | 5 métodos (CRUD) |
| `BancoModel` | 5 métodos (CRUD) |

Los helpers y controllers son procedurales (funciones/if-else sin clases), el patrón público→privado no aplica. Se verificaron los 4 helpers y 14 controllers — no faltan archivos.

### 5. Bancos + Tipos de Pago unificados en un solo módulo "Config. de Pago"
- **`app/controllers/configPagoController.php`**: Creado — solo renderiza la vista combinada (`index`)
- **`app/views/configPagoListView.php`**: Creada — dos cards lado a lado (Bancos + Tipos de Pago), cada una con su DataTable y su modal, carga `banco.js` + `tipoPago.js`
- **`app/controllers/bancoController.php`**: `index` redirige a `configPago` (AJAX endpoints siguen igual)
- **`app/controllers/tipoPagoController.php`**: `index` redirige a `configPago` (AJAX endpoints siguen igual)
- **`app/views/plantillaBase.php`**: Sidebar (móvil y desktop): reemplazados "Bancos" y "Tipos de Pago" por un solo "Config. de Pago"
- **`app/controllers/frontController.php`**: `$titulosPagina` — agregado `configPago`, quitados `banco`/`tipoPago`
- **`assets/js/utilidades.js`**: Agregado `window.DATATABLES_SPANISH` (disponible globalmente)
- **10 JS feature files**: Eliminado `const DATATABLES_SPANISH` de todos (banco, tipoPago, cliente, proveedor, inventario, presupuesto, notaEntrega, cuentaCobrar, cuentaPagar, usuario)

### 6. Nota de Entrega — tipo_pago obligatorio + botón Nuevo eliminado + view huérfana
- **`app/views/notaEntregaListView.php`**: Botón "Nuevo" eliminado (notas solo se crean desde presupuesto)
- **`app/views/notaEntregaFormView.php`**: `<select required>` en tipoPago; `toggleCondicionPago()` togglea `required` dinámico
- **`assets/js/notaEntregaForm.js`**: Validación JS: si condicion_pago es contado, tipo_pago obligatorio
- **`app/controllers/notaEntregaController.php`**: Validación PHP idem; ruta `nueva` eliminada
- **`app/views/notaEntregaDirectaView.php`**: Archivo eliminado (ya no usado)

---

## Sesión — Git + DATATABLES_SPANISH + Config. Pago + Reactivación soft-delete

### 1. Git init + push a GitHub
- Inicializado repositorio en `https://github.com/FranyelPacheco/SP-Perfect-Color`
- Rama `main`, commit "optimización y mejora de BD"
- `vendor/` quitado de `.gitignore` e incluido en el repo

### 2. DATATABLES_SPANISH globalizado (fix ReferenceError)
- **`assets/js/utilidades.js`**: define `window.DATATABLES_SPANISH = {...}`
- **10 feature JS**: `language: DATATABLES_SPANISH` → `language: window.DATATABLES_SPANISH`
- **`app/views/plantillaBase.php`**: cache buster `?v=filemtime` en script tag de utilidades.js

### 3. Bancos + Tipos de Pago → Config. de Pago
- **`app/controllers/configPagoController.php`**: Creado — renderiza vista combinada
- **`app/views/configPagoListView.php`**: Creada — dos cards lado a lado (Bancos + Tipos de Pago)
- **`bancoController.php` / `tipoPagoController.php`**: `index` redirige a `configPago`
- **`plantillaBase.php`**: Sidebar reemplazó "Bancos" y "Tipos de Pago" por "Config. de Pago"
- **`frontController.php`**: `configPago` en `$titulosPagina`

### 4. Reactivación de registros soft-delete (6 entidades)
**Problema:** PHP filtra por `activo=1` pero MySQL UNIQUE KEY rechaza el INSERT si existe una fila inactiva con el mismo valor único.

**Solución:** Antes de INSERT, se busca un registro inactivo por su campo único. Si existe, se UPDATE con `activo=1` + datos nuevos.

| Entidad | Campo único | Modelo método | Controller cambio |
|---------|-------------|---------------|-------------------|
| Cliente | cedula | `buscarInactivoPorCedula()` | `guardar`: reactiva antes de insertar |
| Proveedor | rif | `buscarInactivoPorRIF()` | `guardar`: reactiva + reasigna teléfonos/rubros |
| Insumo | codigo | `buscarInactivoPorCodigo()` | `guardar`: reactiva + reasigna proveedores |
| Usuario | correo | `buscarInactivoPorCorreo()` | `guardar`: reactiva + actualiza clave |
| Banco | nombre | `buscarInactivoPorNombre()` | `guardar`: reactiva vía `actualizar(..., 1)` |
| TipoPago | nombre | `buscarInactivoPorNombre()` | `guardar`: reactiva vía `actualizar(..., 1)` |

**Models:** `_actualizar` en Cliente/Proveedor/Inventario ahora setean `activo = 1` en el UPDATE.
**Controllers:** Los 6 `guardar` verifican `buscarInactivoPor*` antes de `insertar*`.

---

## Sesión — FK/PK renaming (26 columnas en 13 tablas)

### Problema
Las FK tenían nombres inconsistentes con las PKs que referenciaban. Ej: `cuentas_cobrar.cliente_id` referenciaba `clientes.id_cliente`. Todas las FK debían llamarse igual que su PK (`id_nombre`).

### Cambios realizados

**SQL (`app/core/sp_perfect_color.sql`):** 26 FK columns renombradas en CREATE TABLE, INSERT, INDEX, y FOREIGN KEY constraints:

| Tabla | Old FK | New FK |
|-------|--------|--------|
| `cuentas_cobrar` | `cliente_id`, `nota_entrega_id` | `id_cliente`, `id_nota_entrega` |
| `cuentas_pagar` | `proveedor_id` | `id_proveedor` |
| `insumo_proveedor` | `insumo_id`, `proveedor_id` | `id_insumo`, `id_proveedor` |
| `notas_entrega` | `cliente_id`, `usuario_id`, `tipo_pago_id`, `presupuesto_id` | `id_cliente`, `id_usuario`, `id_tipo_pago`, `id_presupuesto` |
| `nota_entrega_detalle` | `nota_id`, `presupuesto_detalle_id` | `id_nota_entrega`, `id_presupuesto_detalle` |
| `pagos_realizados` | `cuenta_pagar_id`, `tipo_pago_id`, `banco_id` | `id_cuenta_pagar`, `id_tipo_pago`, `id_banco` |
| `pagos_recibidos` | `cuenta_cobrar_id`, `tipo_pago_id`, `banco_id` | `id_cuenta_cobrar`, `id_tipo_pago`, `id_banco` |
| `presupuestos` | `cliente_id`, `usuario_id` | `id_cliente`, `id_usuario` |
| `presupuesto_detalle` | `presupuesto_id`, `insumo_id` | `id_presupuesto`, `id_insumo` |
| `rubro_proveedor` | `proveedor_id`, `rubro_id` | `id_proveedor`, `id_rubro` |
| `telefono_cliente` | `cliente_id` | `id_cliente` |
| `telf_proveedor` | `proveedor_id` | `id_proveedor` |
| `usuarios` | `rol_id` | `id_rol` |

**Código (PHP/JS):** 33 archivos modificados con PowerShell (bulk replaceAll):

- **8 Models:** ClienteModel, ProveedorModel, InventarioModel, PresupuestoModel, NotaEntregaModel, CuentaCobrarModel, CuentaPagarModel, UsuarioModel — SQL queries, bind params, array keys
- **8 Controllers:** cliente, proveedor, inventario, presupuesto, notaEntrega, cuentaCobrar, cuentaPagar, usuario — `$_POST`/`$_GET` keys, `$_SESSION` keys
- **1 Helper:** `sesionHelper.php` — `$_SESSION['usuario_id']` → `$_SESSION['id_usuario']`
- **6 Views:** form `name` attributes (`name="cliente_id"` → `name="id_cliente"`), PHP echo de columnas
- **7 JS:** `notaEntregaForm.js`, `notaEntregaEdit.js`, `presupuestoForm.js`, `inventario.js`, `cuentaCobrar.js`, `cuentaPagar.js`, `usuario.js` — fetch/FormData keys, DataTable column data
- **1 View:** `loginController.php`, `frontController.php` — `$_SESSION['usuario_id']` → `$_SESSION['id_usuario']`

**Ejecutado con:** Script PowerShell `(Get-Content).Replace()` sobre 70 archivos, 14 reemplazos en orden específico (largo→corto para evitar colisiones: `presupuesto_detalle_id` antes que `presupuesto_id`, etc.)

### Verificación
- `grep` confirmó 0 ocurrencias de los 14 patrones viejos (`cliente_id`, `proveedor_id`, etc.) en PHP y JS
- SQL FK constraints revisados manualmente — todos referencian `id_*` correctamente

---

## Sesión — 5 mejoras UI/UX

### 1. Login — gradiente quitado, fondo sólido
- **`assets/css/estiloBase.css`**: `.login-card .card-header` cambió de `var(--brand-gradient)` a `var(--brand-dark)` (azul sólido `#1E3A5F`)

### 2. Sidebar replegable (collapsible)
- **`assets/css/estiloBase.css`**: Clase `.sidebar.collapsed` con `width: 70px`, oculta textos, iconos centrados. Botón toggle en `.sidebar-brand` con icono `bi-arrow-bar-left`/`bi-arrow-bar-right`
- **`app/views/plantillaBase.php`**: Botón toggle dentro del sidebar; JS togglea clase `.collapsed` y cambia icono

### 3. Counter Animation en Dashboard
- **`assets/js/utilidades.js`**: `animarContador(elemento, valorFinal, duracion)` — anima de 0→valor usando `requestAnimationFrame`. Soporta formato moneda (`data-moneda="1"`)
- **`app/views/dashboardView.php`**: Todos los `.stat-value` tienen `data-valor` con el valor real; arrancan en 0. El DOMContentLoaded en utilidades.js los anima automáticamente

### 4. Gráfica de Ingresos (Chart.js)
- **`app/models/CuentaCobrarModel.php`**: Nuevo `obtenerPagosPorDia(7)` — SELECT agrupado por fecha últimos N días
- **`app/controllers/dashboardController.php`**: Pasa `$ingresosPorDia` a la vista
- **`app/views/dashboardView.php`**: `<canvas id="graficoIngresos">` dentro de card; script inline pasa `json_encode($ingresosPorDia)`
- **`assets/js/utilidades.js`**: `inicializarGraficoIngresos()` — Chart.js barras verticales con 7 días, rellena con 0 los días sin datos
- **`app/views/plantillaBase.php`**: CDN Chart.js agregado antes del cierre `</body>`

### 5. Bancos/TiposPago → 2 módulos separados + dropdown hover
- **`app/controllers/bancoController.php`**: `index` ahora renderiza `bancoListView.php` (antes redirigía a configPago)
- **`app/controllers/tipoPagoController.php`**: `index` renderiza `tipoPagoListView.php` (antes redirigía)
- **`app/controllers/frontController.php`**: Agregados `'banco'` y `'tipoPago'` a `$titulosPagina`
- **`app/views/plantillaBase.php`**: Sidebar desktop: `<li class="nav-item dropdown-hover">` con submenú hover (Bancos / Tipos de Pago). Sidebar móvil: sublista anidada dentro del mismo `<li>`
- **`assets/css/estiloBase.css`**: Estilos para `.dropdown-hover` (posición absoluta a la derecha del sidebar, visible en hover, sombra, animación)

---

## Sesión — Normalización BD: registro_cliente + estado_presupuesto (ENUM a tabla maestra)

### 1. `fecha_registro` movida a tabla `registro_cliente`
- **BD**: Creada tabla `registro_cliente` (`id_registro_cliente` PK AUTO_INCREMENT, `id_cliente` INT FK, `fecha_registro` DATETIME). Eliminada columna `fecha_registro` de la tabla `clientes`.
- **`app/models/ClienteModel.php`**:
  - `_ejecutarSelectAll`, `_ejecutarSelectById`, `_ejecutarSearch`: Incluyen `LEFT JOIN registro_cliente rc ON rc.id_cliente = c.id_cliente` seleccionando `rc.fecha_registro`. Las vistas reciben la fecha sin romper compatibilidad.
  - `_ejecutarInsert`: Inserta automáticamente en `registro_cliente` tras insertar el cliente nuevo.
  - `_ejecutarUpdate`: Asegura existencia del registro de fecha en `registro_cliente` si el cliente fue reactivado.

### 2. ENUMs > 2 opciones: `presupuestos.estado` → `estado_presupuesto`
- **Auditoría de ENUMs en la BD**:
  - `presupuestos.estado` (4 opciones: `pendiente`, `aprobado`, `rechazado`, `convertido`) → Convertida en tabla maestra.
  - `cuentas_cobrar.estado` → Normalizada a 2 opciones (`'pendiente'`, `'pagado'`).
  - `cuentas_pagar.estado`, `notas_entrega.condicion_pago`, `insumos.tipo_inventario` (2 opciones) y `notas_entrega.estado` (1 opción) se preservaron como ENUM.
- **BD**:
  - Creada tabla `estado_presupuesto` (`id_estado_presupuesto` PK AUTO_INCREMENT, `nombre` VARCHAR(50) UNIQUE) con seeds: `1 = pendiente`, `2 = aprobado`, `3 = rechazado`, `4 = convertido`.
  - En `presupuestos`: eliminada columna `estado`, agregada columna `id_estado_presupuesto` INT con FK `fk_presupuesto_estado` a `estado_presupuesto(id_estado_presupuesto)`.
- **`app/models/EstadoPresupuestoModel.php`**: Creado nuevo modelo con `listarTodos()`, `buscarPorId()`, `buscarPorNombre()`.
- **`app/models/PresupuestoModel.php`**:
  - `_ejecutarSelectAll`, `_ejecutarSelectById`, `_ejecutarSearch`: Incorporan `INNER JOIN estado_presupuesto ep ON p.id_estado_presupuesto = ep.id_estado_presupuesto` y devuelven `ep.nombre as estado`, garantizando total compatibilidad con vistas y DataTables JS.
  - `cambiarEstado(int $id, string|int $estado)`: Mapea transparentemente strings (`'pendiente'`, `'aprobado'`, etc.) al ID correspondiente y actualiza `id_estado_presupuesto`.
  - `_ejecutarInsert`: Inserta con `id_estado_presupuesto = 1` ('pendiente').
- **`app/models/NotaEntregaModel.php`**:
  - `_cambiarEstadoPresupuesto`: Actualiza `id_estado_presupuesto = 4` ('convertido').
- **`app/core/sp_perfect_color.sql`**: Dump y esquema actualizado con las nuevas tablas, datos, índices, auto-increments y restricciones foráneas.

---

## Sesión — Renombrado telefono_proveedor + Eliminación notas_entrega.estado + Auditoría subtotal

### 1. Renombrado `telf_proveedor` → `telefono_proveedor`
- **BD**:
  - `RENAME TABLE telf_proveedor TO telefono_proveedor;`
  - `ALTER TABLE telefono_proveedor CHANGE id_telf_proveedor id_telefono_proveedor INT(11) NOT NULL AUTO_INCREMENT;`
  - Clave foránea actualizada: `fk_telefono_proveedor_proveedor` apuntando a `proveedores(id_proveedor)`.
- **`app/models/ProveedorModel.php`**: Reemplazadas todas las consultas SQL (`_ejecutarSelectAll`, `_ejecutarSelectById`, `_ejecutarSearch`, `_ejecutarInsertTelefono`, `_ejecutarDeleteTelefonos`) para apuntar a `telefono_proveedor`.
- **`app/models/CuentaPagarModel.php`**: Subconsulta en `_ejecutarSelectById` actualizada para usar `telefono_proveedor`.
- **`app/core/sp_perfect_color.sql`**: Tabla, campos, volcado, índices, auto_increment y constraints actualizados a `telefono_proveedor` e `id_telefono_proveedor`.

### 2. Eliminación de columna redundante `notas_entrega.estado`
- **BD**: `ALTER TABLE notas_entrega DROP COLUMN estado;`
- **`app/models/NotaEntregaModel.php`**:
  - Eliminada propiedad `$estado`.
  - Eliminado parámetro `$estado` de `crearNotaEntrega()`.
  - Eliminado método público `cambiarEstado()` y privado `_ejecutarUpdateEstado()`.
  - Removido campo `:estado` del `INSERT INTO notas_entrega`.
- **`app/controllers/notaEntregaController.php`**: Removida variable `$estadoNota` y su argumento en la invocación de `crearNotaEntrega()`.
- **`assets/js/notaEntregaForm.js`**: Removido `formData.append('estado', 'entregado');`.
- **`app/views/notaEntregaListView.php`**: Eliminado `<th>Estado</th>` (conteo reducido a 8 columnas).
- **`assets/js/notaEntrega.js`**: Eliminada columna `{ data: 'estado', ... }` del arreglo `columns` de DataTables (conteo reducido a 8 columnas exactas).
- **`app/views/notaEntregaVerView.php`**: Removido bloque visual que mostraba el estado de la nota de entrega.
- **`app/core/sp_perfect_color.sql`**: Removida columna `estado` del DDL y de los INSERTs de `notas_entrega`.

### 3. Auditoría de campo `*.subtotal`
- Se evaluaron las columnas `subtotal` en `presupuesto_detalle` y `nota_entrega_detalle`.
- Aunque conceptualmente es un atributo derivable (`cantidad * precio_unitario`), se mantiene intacto en base de datos y modelos ya que:
  1. Es estándar en sistemas de facturación y presupuestos para preservar inmutabilidad histórica frente a cambios en redondeo.
  2. Su presencia es esperada por múltiples componentes de formulario (`notaEntregaEdit.js`, `notaEntregaForm.js`, `presupuestoForm.js`), controladores y vistas de detalle.
  3. No afecta negativamente a los reportes ni al desempeño, garantizando que el sistema siga funcionando con total regularidad.

### 4. Diagrama `modelo_relacional.svg`
- Diagrama regenerado desde la base de datos viva:
  - Refleja `telefono_proveedor` con `id_telefono_proveedor`.
  - Refleja `notas_entrega` con sus 9 columnas actuales (sin `estado`), reajustando altura del nodo y conectores.
  - **Optimización de conectores y enrutamiento ortogonal**:
    - Eliminadas al 100% las líneas colineales superpuestas (`0` colisiones colineales).
    - Entradas escalonadas (*staggered pins*) en `clientes.id_cliente` (desfases de $\pm 4\text{px}$) y `proveedores.id_proveedor` (desfases de $\pm 5\text{px}$), evitando que múltiples relaciones compartan el mismo trazo horizontal de aproximación.
    - Separación de corredores verticales con holguras de $25\text{px}$ a $55\text{px}$.
    - Desacoplamiento del cruce de `pagos_realizados` y `pagos_recibidos` hacia `tipo_pago` y `banco` (separados por canal independiente de $20\text{px}$).
    - Cruces perpendiculares reducidos al mínimo topológico inevitable (de 8 a 6 intersecciones a 90°).

---

## Sesión — Generación del Modelo Entidad-Relación (`diagrama_mer.svg`)

### Generación de `diagrama_mer.svg` (Notación Chen)
- Generado en formato SVG puro (`diagrama_mer.svg`, $4200 \times 2700\text{px}$) con fondo blanco, trazos ortogonales a 90° ("cuadradas") y tipografía monospace nítida.
- **Entidades representadas (18 entidades)**:
  - **Fuertes (rectángulo simple)**: `roles`, `usuarios`, `clientes`, `presupuestos`, `estado_presupuesto`, `notas_entrega`, `cuentas_cobrar`, `tipo_pago`, `banco`, `insumos`, `proveedores`, `cuentas_pagar`, `rubro`.
  - **Débiles (doble rectángulo)**: `telefono_cliente`, `registro_cliente`, `telefono_proveedor`, `pagos_recibidos`, `pagos_realizados`.
- **Relaciones (21 rombos ampliados y legibles)**:
  - Rombo simple para relaciones estándar (`tienen`, `registra`, `clasifica`, `solicita`, `procede`, `genera`, `forma`, `destino`, `posee`, `pertenece`, `efectua`).
  - Rombo doble para relaciones identificadoras hacia entidades débiles (`tiene`, `registrado`, `posee`, `abona`).
  - Dimensiones de rombos ampliadas ($w = 110\text{--}185\text{px}, h = 48\text{--}66\text{px}$) para albergar con total holgura los títulos y subtítulos sin que los vértices toquen las letras.
  - **4 Tablas Relacionales/Asociativas con atributos propios y subetiquetas explícitas**:
    1. `contiene` (`presupuesto_detalle`): <ins>`id`</ins>, `cantidad`, `precio_unitario`, `subtotal`.
    2. `despacha` (`nota_entrega_detalle`): <ins>`id`</ins>, `cantidad`, `precio_unitario`, `subtotal`.
    3. `suministran` (`insumo_proveedor`): <ins>`id`</ins>.
    4. `maneja_rubro` (`rubro_proveedor`): <ins>`id`</ins>.
    - Título y subtítulo en alta legibilidad (`font-size: 12.5px` en negrita para el verbo y `9.5px` para el nombre de la tabla).
- **Simetría Geométrica y Reacomodo Estético (98 elipses correspondientes a las 22 tablas del MR)**:
  - Dispersión rediseñada bajo estricta simetría bilateral, radial y por cuadrantes (abanicos regulares, pares equidistantes respecto a los ejes de cada entidad).
  - Radios estandarizados proporcionalmente al texto para uniformidad visual.
- **Verificación algorítmica de geometría y despeje**:
  - `0` colisiones entre formas (las 98 elipses, 18 rectángulos y 21 rombos guardan márgenes de seguridad completos).
  - `0` intersecciones entre líneas de relación y elipses de atributos.
  - `0` cortes entre líneas de relación y rectángulos de entidades.
  - `0` superposiciones colineales de líneas entre relaciones distintas.
  - Cardinalidades (1:1, 1:N, N:1, N:M) señaladas de forma limpia en los extremos de conexión.

---

## Sesión — Mejoras Responsive + Módulo Dinámico de Roles y Permisos (22 Tablas Preservadas)

### 1. Mejoras Responsive y UI/UX
- **Menú Lateral Móvil (Aside / Offcanvas)**:
  - **Problema corregido**: El aside móvil (`.offcanvas.sidebar-offcanvas`) heredaba el color azul predeterminado de Bootstrap (`#0d6efd`), perdiendo contraste sobre el fondo oscuro, y el texto quedaba pegado a los iconos sin separación.
  - **Cambios**:
    - `app/views/plantillaBase.php`: Se agregó la clase `.sidebar` al offcanvas (`<div class="offcanvas sidebar sidebar-offcanvas ...">`) y separación explícita `me-2` en los iconos.
    - `assets/css/estiloBase.css`: Se unificaron los selectores (`.sidebar .nav-link, .sidebar-offcanvas .nav-link`) con texto blanco semitransparente (`rgba(255,255,255,0.75)` y `#fff` en hover/active), `gap: 0.75rem` e iconos con ancho fijo (`width: 1.35rem`).
    - Submenú *Config. de Pago* en móvil estilizado con sangría y fondo sutil integrado.
- **Espaciado y Prevención de Colisión de Botones**:
  - `assets/css/estiloBase.css`:
    - Regla global `.table td .btn + .btn, .table td .btn + a.btn, .table td a.btn + .btn, .table td a.btn + a.btn { margin-left: 0.35rem; }`.
    - Alineación vertical centrada `.table td { vertical-align: middle; }` y `white-space: nowrap;` en la última columna (`Acciones`) para evitar quiebres y amontonamiento.
    - En pantallas pequeñas (`@media (max-width: 576px)`): las barras de herramientas y buscadores con ancho fijo (`width: 220px/250px`) pasan a `width: 100%` con distribución en columna y botones expandibles sin desborde.
    - Adaptación responsive de controles DataTables (`dataTables_length`, `dataTables_filter`, `dataTables_paginate`) para dispositivos móviles (`@media (max-width: 768px)`).

### 2. Módulo de Roles y Permisos Dinámicos (Sin Tabla Intermedia)
- **Base de Datos (Se mantienen exactamente las 22 tablas)**:
  - `ALTER TABLE roles ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER nombre;`
  - `ALTER TABLE roles ADD COLUMN modulos TEXT NULL AFTER activo;`
  - Dump `app/core/sp_perfect_color.sql` actualizado con las nuevas columnas y seeds:
    - Rol 1 (Administrador): `activo = 1`, acceso total a todos los módulos.
    - Rol 2 (Vendedor): `activo = 1`, acceso a `dashboard,cliente,presupuesto,notaEntrega,reporte`.
- **Backend**:
  - `app/models/RolModel.php`: Creado con arquitectura encapsulada (métodos públicos delegando a privados `_`): `listarTodos()`, `listarActivos()`, `buscarPorId()`, `insertarRol()`, `actualizarRol()`, `toggleActivo()` (con bloqueo de protección para Administrador ID 1), `nombreExiste()`, `buscarInactivoPorNombre()`.
  - `app/models/UsuarioModel.php`: `_ejecutarSelectByCorreo()` ahora recupera `rol_activo` y `rol_modulos`. `listarRoles()` retorna roles con `activo = 1`.
  - `app/helpers/sesionHelper.php`:
    - `tienePermiso(string $modulo): bool`: Verifica si el usuario es Admin o si el módulo está en su lista autorizada.
    - `verificarPermiso(string $modulo)`: Bloqueo dinámico por módulo.
    - `verificarRolAdmin()` y `verificarAcceso()` ahora consultan `tienePermiso($moduloActual)` según la URL, permitiendo que nuevos roles creados por el Administrador accedan a los módulos autorizados sin romper ningún controlador existente.
  - `app/controllers/loginController.php`: Valida que el rol asignado al usuario esté activo antes de iniciar sesión. Carga `$_SESSION['usuario_modulos']`.
  - `app/controllers/usuarioController.php`: Incorpora endpoints AJAX para roles: `listarRolesAjax`, `obtenerRol`, `guardarRol`, `actualizarRol` y `toggleRol`.
- **Frontend / Vistas**:
  - `app/views/plantillaBase.php`: Enlaces del sidebar (desktop y móvil) condicionados con `\App\Helpers\tienePermiso('modulo')`. Si un nuevo rol tiene acceso a Inventario o Proveedores, aparecerán dinámicamente en su menú.
  - `app/views/usuarioListView.php`: Interfaz con pestañas Nav-Pills (**Usuarios** y **Roles y Permisos**).
    - Modal de rol con selección de nombre, estado y matriz visual de checkboxes con los 11 módulos del sistema (incluyendo advertencia de seguridad para el módulo `usuario`).
  - `assets/js/usuario.js`: Soporte para DataTables `#tablaRoles`, creación y edición de roles, guardado AJAX y toggle de habilitación/deshabilitación con modal de confirmación.- **Correcciones Específicas de la Sesión**:
  - **Offcanvas móvil**: Se eliminó la clase `.sidebar` del contenedor `#offcanvasSidebar` (la cual creaba un div residual de 260px desplazando el contenido) y se aplicaron reglas con `-webkit-text-fill-color: #ffffff !important` y `color: #ffffff !important` asegurando letras 100% blancas sobre el fondo oscuro en móviles. Se incorporó además cache buster `?v=filemtime` en los estilos.
  - **DataTables Responsive Anti-deformación**: Se añadieron bibliotecas CDN oficiales de DataTables Responsive (`responsive.bootstrap5.min.js`, `responsive.bootstrap5.min.css`) y estilos con `overflow-x: auto !important`, `min-width: 680px/820px` y `white-space: nowrap !important` en `estiloBase.css`. Las tablas ya no se comprimen ni parten textos verticalmente en pantallas estrechas.
  - **Toolbars Móviles**: En pantallas `<576px`, los contenedores de búsqueda y botón nuevo pasan a `flex-direction: column` con ancho al 100%, eliminando amontonamientos y colisiones.
  - **Modal de confirmación**: Se modificó `confirmarConModal` en `assets/js/utilidades.js` para usar `.innerHTML`, permitiendo el renderizado correcto de avisos y textos de advertencia en HTML.
  - **Acceso a Usuarios y Roles**: Se agregó enlace directo en los menús de navegación móvil y de escritorio bajo Reportes condicionado por `tienePermiso('usuario')`.

---

## Sesión — Correcciones y Mejoras Progresivas del Sistema (4 Fases Probadas)

### 1. Fase 1: Enrutamiento, Títulos y Estado Activo del Sidebar (UI/UX)
- **`app/controllers/frontController.php`**: Mapeo canónico `$mapeoControladores` para asociar URLs en minúsculas al controlador correcto (`notaentrega` -> `notaEntrega`, etc.). Búsqueda de `$titulosPagina` normalizada con `strtolower` para que las pestañas del navegador muestren el título exacto del módulo en vez del título genérico.
- **`app/views/plantillaBase.php`**: Creada variable `$ctrlLower = strtolower($controlador ?? '');`. Normalizadas las clases `.active` tanto en el menú móvil como en el desktop. Agregado resaltado para el dropdown "Config. de Pago" al visitar `banco`, `tipoPago` o `configPago`.
- **`app/controllers/usuarioController.php` & `app/views/usuarioListView.php`**: Eliminado el `echo "<script>..."` previo al `<!DOCTYPE html>`. Las variables globales JS se trasladaron limpiamente al inicio de `usuarioListView.php`.

### 2. Fase 2: Autenticación, Manejo de Sesiones y Permisos RBAC
- **`app/helpers/sesionHelper.php`**:
  - `verificarAutenticacion()` ahora redirige a `/SP%20Perfect%20Color/login` en solicitudes HTTP ordinarias y devuelve JSON solo si es AJAX.
  - `tienePermiso()` normaliza a minúsculas (`strtolower`) los módulos autorizados, evitando falsos negativos por diferencias de casing.
- **`app/controllers/loginController.php`**: Se añadió `session_regenerate_id(true);` tras validar exitosamente las credenciales contra Session Fixation.
- **`app/controllers/clienteController.php`**: Protegidas todas las acciones con `verificarPermiso('cliente')` y `eliminar` con `verificarRolAdmin()`.
- **`app/controllers/presupuestoController.php`**: Protegidas todas las acciones con `verificarPermiso('presupuesto')`.
- **`app/controllers/reporteController.php`**: Protegidas las 5 acciones con `verificarPermiso('reporte')`.

### 3. Fase 3: Lógica Contable y Negocio (Sincronización CxC y Redondeo)
- **`app/models/NotaEntregaModel.php`**: En `_ejecutarActualizarDetalle()`, si la nota editada tiene una cuenta por cobrar vinculada (`cuentas_cobrar`), se sincroniza automáticamente su `monto_total` y se actualiza el `saldo_pendiente` sumando la diferencia (`$nuevoTotal - $totalViejo`), actualizando el estado según corresponda.
- **`app/models/CuentaCobrarModel.php` & `app/models/CuentaPagarModel.php`**: En `_ejecutarRegistrarPago()`, se aplica `round($nuevoSaldo, 2)` con umbral épsilon `<= 0.001` para fijar el saldo en 0 exacto y estado `'pagado'`, eliminando residuos infinitesimales de punto flotante en PHP.

### 4. Fase 4: Exportación de Reportes y Validación/Seguridad en Vistas
- **`app/helpers/exportarReporteHelper.php`**: En reportes de ventas, sustituida la columna inexistente `'Estado'` por `'Condicion'` (`$fila['condicion_pago']`) tanto en PDF como en Excel, y sanitizadas las salidas del PDF con `htmlspecialchars()`.
- **`app/views/presupuestoFormView.php`**: Agregada la etiqueta de apertura `<thead>` faltante en `#tablaItemsPresupuesto`.
- **`app/views/notaEntregaVerView.php` & `app/views/presupuestoVerView.php`**: Escapados los datos dinámicos con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.

### 5. Eliminación Lógica de Roles (Soft-Delete)
- **`app/models/RolModel.php`**:
  - `_ejecutarSelectAll()`: Agregado filtro `WHERE r.activo = 1` para que los roles con borrado lógico ya no se muestren en la interfaz (igual al comportamiento de usuarios, clientes, etc.).
  - `eliminarRol(int $id)` y `_ejecutarDelete()`:
    - Protege el rol Administrador (`id_rol = 1`).
    - Valida que no tenga usuarios activos asignados (`SELECT COUNT(*) FROM usuarios WHERE id_rol = :id AND activo = 1`), arrojando mensaje explicativo si los tiene.
    - Aplica borrado lógico (`UPDATE roles SET activo = 0 WHERE id_rol = :id`), manteniendo la integridad referencial y las claves foráneas en la base de datos sin borrar filas físicas.
  - `_ejecutarCheckNombre()`: Agregado `AND activo = 1` para no generar conflicto de unicidad con roles inactivos.
  - `buscarInactivoPorNombre()`: Permite detectar y reactivar un rol previamente desactivado en caso de volverlo a registrar.
- **`app/controllers/usuarioController.php`**: Endpoint AJAX `eliminarRol` con verificación de permisos de usuario (`tienePermiso('usuario')`), protección contra el rol raíz 1 y captura de excepciones.
- **`assets/js/usuario.js`**:
  - Botón rojo con ícono de papelera (`.btn-eliminar-rol`) en cada fila de rol excepto Administrador (que muestra badge "Raíz").
  - Función `eliminarRol()`: valida si el rol tiene usuarios activos asignados mostrando advertencia explicativa; si no tiene usuarios, solicita confirmación con modal descriptivo indicando que el rol será desactivado y no volverá a mostrarse, enviando la petición a `usuario/eliminarRol` y refrescando la tabla.
- **`assets/js/utilidades.js`**: Guard en `confirmarConModal` para verificar `typeof callback === 'function'` antes de invocarlo.

### 6. Normalización de Roles y Módulos a 24 Tablas (1FN / Relación N:M)
- **Base de Datos**:
  - Creada tabla maestra `modulos` (`id_modulo` PK AUTO_INCREMENT, `codigo` VARCHAR(50) UNIQUE NOT NULL, `nombre` VARCHAR(50) NOT NULL, `descripcion` VARCHAR(255) NULL) con 12 módulos.
  - Creada tabla intermedia `rol_modulo` (`id_rol_modulo` PK AUTO_INCREMENT, `id_rol` INT(11) FK, `id_modulo` INT(11) FK, UNIQUE `uk_rol_modulo`, con `ON DELETE CASCADE ON UPDATE CASCADE`).
  - Eliminada columna no atómica `modulos TEXT` de la tabla `roles` (cumplimiento estricto de la Primera Forma Normal - 1FN).
  - Migrados los permisos existentes sin pérdida de información (19 asignaciones en `rol_modulo`).
  - Actualizado `app/core/sp_perfect_color.sql` con la nueva definición de 24 tablas, sus volcados, índices y restricciones foráneas.
- **Modelos**:
  - `app/models/ModuloModel.php`: Creado modelo con encapsulamiento privado/público (`listarTodos`, `buscarPorId`, `buscarPorCodigo`, `obtenerIdsPorCodigos`).
  - `app/models/RolModel.php`: Actualizado para gestionar inserciones y actualizaciones atómicas en `rol_modulo` mediante transacciones PDO con `_sincronizarModulos()`, y consultas con subconsulta `GROUP_CONCAT(m.codigo)` para máxima compatibilidad y cero fricción hacia el frontend.
  - `app/models/UsuarioModel.php`: `buscarPorCorreo` obtiene `rol_modulos` a través de la unión relacional de `rol_modulo` y `modulos`.
- **Sincronización Total de Diagramas (24 Tablas)**:
  - `modelo_relacional.svg`: Incorporadas las tablas `modulos` y `rol_modulo`, ajustada la tabla `roles` a 4 columnas sin `modulos`, y trazadas las 2 relaciones foráneas `fk_rol_modulo_rol` y `fk_rol_modulo_modulo`. Paridad 100% (24 tablas y todas sus columnas auditadas contra la BD).
  - `diagrama_mer.svg`: Incorporada la entidad `modulos` con sus 4 atributos, la relación asociativa `posee_modulo (rol_modulo)` con cardinalidades N:M, actualizados los atributos de `Roles` a 4 elipses, e incorporados los atributos de la tabla intermedia `rol_modulo` (`id_rol_modulo` PK subrayada, `id_rol (FK)` punteada, `id_modulo (FK)` punteada). Paridad total: 24 tablas de la BD con sus 24 claves primarias y atributos completos representados en el diagrama MER.

### 7. Recreación del Diagrama de Clases UML (diagrama_clases.svg)
- **Eliminación de Acoplamiento a BD**: Removido `# conexion: PDO` de los atributos de todos los modelos.
- **Sincronización Total de Atributos y Métodos (16 Clases)**:
  - `FrontController`: Atributos tipados (`controlador: string`, `metodo: string`, `parametros: array`, `mapeoControladores: array`, `titulosPagina: array`) y métodos de despacho.
  - 14 Modelos: Auditados al 100% mediante introspección PHP, incorporando métodos de búsqueda AJAX (`buscarClientes`, `buscarProveedores`, etc.), filtros, getters de reportes y eliminación de métodos/atributos desactualizados (como `estado` en notas de entrega).
  - `ConexionBD`: Patrón Singleton con 7 atributos y métodos de conexión PDO.
- **Trazado de Arquitectura en 3 Niveles (2980 x 1380 px)**:
  - Nivel 1: `FrontController` en la parte superior con buses ortogonales hacia los 14 modelos.
  - Nivel 2: 14 modelos distribuidos en un único nivel horizontal simétrico y cajas con ancho expandido a 186px sin solapamientos.
  - Nivel 3: `ConexionBD` en la parte inferior recibiendo las conexiones de acceso a base de datos desde los 14 modelos.



