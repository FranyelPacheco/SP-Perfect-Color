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
