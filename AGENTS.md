# Session Summary

## Cadena Lineal de Ventas y Cobros (Cero Ciclos: `notas_entrega` $\to$ `cuentas_cobrar` $\to$ `pagos_recibidos`)

### Contexto & Problema de Negocio
- La coexistencia de `cuentas_cobrar.id_nota_entrega` junto con `pagos_recibidos.id_nota_entrega` y `pagos_recibidos.id_cuenta_cobrar` formaba un ciclo/bucle cerrado triangular (`notas_entrega` $\leftrightarrow$ `pagos_recibidos` $\leftrightarrow$ `cuentas_cobrar`).
- Se implementó la **Opción A** contable estándar: unificar todo el flujo de cobros a través de `cuentas_cobrar`.
- El sistema soporta ambas modalidades de venta sin bucles:
  - **A Crédito:** Crea `cuentas_cobrar` con `estado = 'pendiente'` y saldo pendiente. Los pagos posteriores entran a `pagos_recibidos` vinculados a la cuenta.
  - **De Contado:** Crea la cuenta en `cuentas_cobrar` y en la misma transacción la cancela de inmediato (`saldo_pendiente = 0`, `estado = 'pagado'`), registrando su pago completo en `pagos_recibidos` vinculado a esa cuenta por cobrar.

### Cambios realizados en Base de Datos & SQL Dump
1. **Modificación en `pagos_recibidos`:**
   - Eliminada la columna `id_nota_entrega` y su clave foránea `fk_pr_nota_entrega`.
   - `pagos_recibidos` ahora referencia **única y exclusivamente** a `cuentas_cobrar` (`id_cuenta_cobrar`).
2. **Estructura Lineal Limpia:**
   - Cadena de flujo: `notas_entrega` (1) $\to$ (N) `cuentas_cobrar` (1) $\to$ (N) `pagos_recibidos`.
   - Cero ciclos, cero redundancias y sin valores nulos condicionales en las claves de transacción.
3. **Actualizado diagrama SVG (`modelo_relacional.svg`):**
   - Eliminada la flecha cíclica `rel_pagos_recibidos_notas_entrega_id_nota_entrega`.
   - `pagos_recibidos` actualizada a 7 filas.
   - Conectores de `id_tipo_pago` e `id_banco` realineados. Total: 24 tablas y 25 relaciones perfectamente balanceadas.
4. **Dump oficial:** `app/core/sp_perfect_color.sql` exportado con la estructura final.

### Cambios en Código (Modelos y Consultas)
- **`app/models/NotaEntregaModel.php`**:
  - `_crearYPagardeInmediatoContado()`: Al registrar venta de contado, inserta en `cuentas_cobrar` con `saldo_pendiente = 0` y `estado = 'pagado'`, y de inmediato inserta en `pagos_recibidos` asociando `id_cuenta_cobrar`.
  - `_ejecutarSelectAll()`, `_ejecutarSelectById()`, `_ejecutarSearch()`: Consultas actualizadas haciendo `LEFT JOIN cuentas_cobrar cc ON cc.id_nota_entrega = ne.id_nota_entrega LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago`.
- **`app/models/ReporteModel.php`**:
  - Consultas de reportes de ventas y métodos de pago actualizadas con la cadena lineal `notas_entrega` $\to$ `cuentas_cobrar` $\to$ `pagos_recibidos`.

---

## Normalización de Catálogo: Productos, Tipos y Mezclas (Colorimetría)

### Contexto & Problema de Negocio
- **SP Perfect Color** comercializa dos naturalezas de artículos:
  1. **Artículos simples de reventa directa:** Brochas, tirros, lijas, pulituras, pegas, herramientas.
  2. **Insumos y tintes base para formulación de colores:** Tintes concentrados (negro, blanco, colores primarios, químicos) medidos en fracciones de galón ($1/2, 1/4, 1/8, \dots, 1/128$).
  3. **Pinturas preparadas:** Mezclas creadas a solicitud del cliente combinando hasta 6 tintes base.
- Anteriormente la tabla se llamaba `insumos` con stock en `DECIMAL(10,2)` y sin distinción de naturaleza ni soporte para descomposición de mezclas.

### Cambios realizados en Base de Datos & SQL Dump
1. **Creada tabla maestra `tipo_producto`:**
   - `id_tipo_producto` (PK AUTO_INCREMENT), `nombre` (VARCHAR UNIQUE), `descripcion`, `activo`.
   - Seeds: `1 = Base`, `2 = Simple`, `3 = Preparado`.
2. **Renombrada tabla `insumos` $\rightarrow$ `productos`:**
   - PK `id_insumo` $\rightarrow$ `id_producto`.
   - Agregada columna `id_tipo_producto` (FK a `tipo_producto`, default 2).
   - Precisión ampliada: `stock_actual` y `stock_minimo` de `DECIMAL(10,2)` a `DECIMAL(12,4)` para soportar con exactitud fracciones de galón (hasta $1/128 = 0.0078125$).
3. **Renombrada tabla `insumo_proveedor` $\rightarrow$ `producto_proveedor`:**
   - PK `id_insumo_proveedor` $\rightarrow$ `id_producto_proveedor`, FK `id_insumo` $\rightarrow$ `id_producto`.
4. **Cadena Lineal BOM sin ciclos (`presupuestos` $\to$ `presupuesto_detalle` $\to$ `item_composicion` $\to$ `productos`):**
   - **`presupuesto_detalle`:** Desacoplado del inventario físico. Representa el ítem comercial que adquiere el cliente (`id_presupuesto_detalle`, `id_presupuesto`, `descripcion`, `cantidad`, `precio_unitario`, `subtotal`). Se eliminó `id_producto` evitando ciclos relacionales o valores NULL.
   - **`item_composicion`:** Tabla de composición (BOM - Bill of Materials). Representa los insumos físicos consumidos del inventario (`id_item_composicion`, `id_presupuesto_detalle`, `id_producto`, `fraccion_128`, `cantidad_consumida`). Tanto productos de reventa (1 insumo, `fraccion_128 = 0`) como fórmulas preparadas (1 a N bases concentradas, `fraccion_128 > 0`) se registran con el mismo esquema uniforme.
5. **Eliminada tabla redundante `nota_entrega_detalle` (Opción 2 - Cero ciclos):**
   - Se eliminó el rombo/ciclo entre `presupuestos`, `presupuesto_detalle`, `nota_entrega_detalle` y `notas_entrega`.
   - `notas_entrega` registra el despacho/cobro directo del presupuesto (`id_presupuesto`).
   - El detalle de los ítems y consumos de inventario se lee directamente de `presupuesto_detalle` e `item_composicion`.
6. **Actualizado dump oficial:** `app/core/sp_perfect_color.sql` reexportado en UTF-8 con exactamente 24 tablas limpias sin redundancias ni ciclos.

### Cambios en Código (Modelos, Controladores y Vistas)
- **`app/models/InventarioModel.php`**: Actualizado a `productos`, `producto_proveedor`, soporte de `id_tipo_producto`, `listarTiposProducto()`, `listarBases()` y aliases de compatibilidad (`id_producto as id_insumo`).
- **`app/models/PresupuestoModel.php`**: Consultas actualizadas a `id_producto` y JOIN con `productos`.
- **`app/models/NotaEntregaModel.php`**: Consultas de detalle y descuentos de stock actualizados a `id_producto` y `productos`.
- **`app/controllers/inventarioController.php`**: Maneja `id_tipo_producto` en `guardar`, `actualizar` y retorna `tipos_producto` en `listarAjax`.
- **`app/views/inventarioListView.php`**: Agregada columna "Tipo" a la tabla y campo `<select id="tipoProductoInsumo">` en el modal.
- **`assets/js/inventario.js`**: Soporte en DataTables para mostrar badge de tipo (Base, Simple, Preparado) y carga/edición del tipo en el modal.

---

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

---

### 8. Limpieza de Acentos en BD & Corrección Integral de Botones en DataTables

#### Contexto & Solicitud del Usuario
- **"No pongas nombres con acentos"**: Eliminar tildes/acentos de todos los datos en la base de datos (clientes, proveedores, productos, presupuestos, notas de entrega, roles, módulos y usuarios).
- **"revisa los botones del datatable porque no me quieren agarrar"**: Los botones de acción en las tablas (editar, eliminar, aprobar, rechazar, toggle) no respondían consistentemente al hacer clic.

#### Causas Raíz Identificadas & Solucionadas
1. **Rutas Relativas en llamadas AJAX (`fetch`)**:
   - En `inventario.js`, `cliente.js`, `proveedor.js`, `presupuesto.js` y `usuario.js`, las llamadas AJAX se realizaban con rutas relativas (`inventario/obtener`, `cliente/eliminar`, `inventario/listarRubrosAjax` desde `/proveedor/`).
   - Al navegar a subrutas o tener trailing slashes en la URL, las rutas relativas producían errores 404 (ej. `/proveedor/inventario/listarRubrosAjax`).
   - **Solución**: Se normalizaron todas las peticiones a rutas canónicas absolutas con prefijo `/SP%20Perfect%20Color/...`.
2. **Interferencia de Eventos por Íconos y Tooltips Bootstrap**:
   - Al hacer clic sobre los íconos `<i class="bi ...">` o si el Tooltip de Bootstrap 5 permanecía activo, el evento de clic no se propagaba adecuadamente al botón o retenía el foco.
   - **Solución**:
     - Se añadió regla CSS en `assets/css/estiloBase.css` con `pointer-events: none;` para todos los elementos hijos dentro de los botones de acción en tablas.
     - En todos los scripts se implementó `var tip = bootstrap.Tooltip.getInstance(this); if (tip) tip.hide();` al ejecutar un clic.
3. **Delegación de Eventos en DataTables**:
   - Se migró la delegación de eventos nativa a la delegación canónica con jQuery `$(document).on('click', '.btn-...', function() { ... })`. Esto garantiza que los botones sigan respondiendo tras paginaciones, búsquedas o redibujados de tabla.
4. **Estandarización de `columns: [...]` en DataTables**:
   - Se adaptaron `cliente.js`, `inventario.js` y `proveedor.js` al patrón de definición explícita de `columns: [...]` y llenado con `table.row.add(objeto)` para evitar advertencias de desajuste de columnas.
5. **Cache-Busting Dinámico en Scripts de Vistas**:
   - En `clienteListView.php`, `inventarioListView.php`, `proveedorListView.php` y `presupuestoListView.php` se implementó `?v=<?php echo filemtime(...); ?>` para forzar a los navegadores a cargar los archivos JS actualizados.

#### Limpieza de Acentos en BD & Actualización de Dump
- Se ejecutó un proceso de saneamiento en MySQL para sustituir todas las vocales con tildes (`á, é, í, ó, ú, Á, É, Í, Ó, Ú`) en todas las columnas de texto de las 24 tablas.
- Se re-exportó el volcado SQL oficial `app/core/sp_perfect_color.sql` en UTF-8 con collation `utf8mb4_spanish2_ci`.

---

### 9. Desacoplamiento de Preparados (Mezclas) hacia Presupuestos & Sincronización del SVG

#### Cambios Realizados en Base de Datos & SQL Dump
1. **Limpieza de `tipo_producto`**:
   - Eliminado seed `3 = Preparado`. El catálogo de inventario solo admite artículos que existen con stock físico: `1 = Base` (materia prima/tintes) y `2 = Simple` (reventa/ferretería).
   - Los productos asignados a tipo 3 se reasignaron a tipo 2.
2. **Reestructuración de `mezcla_detalle`**:
   - Se eliminó la autorreferencia extraña a `productos` (`id_producto`).
   - Columna `id_producto` reemplazada por `id_presupuesto_detalle` (FK a `presupuesto_detalle(id_presupuesto_detalle)` con `ON DELETE CASCADE ON UPDATE CASCADE`).
   - Se mantiene `id_producto_base` (FK a `productos(id_producto)`).
3. **Flexibilización de `presupuesto_detalle`**:
   - Columna `id_producto` ajustada a `NULL` permitido para cuando una línea del presupuesto sea una pintura formulada en sitio.
   - `PresupuestoModel.php` y `NotaEntregaModel.php` actualizados con `LEFT JOIN productos` y `COALESCE` para que no se oculten líneas de preparados.
4. **Dump oficial actualizado**: `app/core/sp_perfect_color.sql` reexportado en UTF-8 con todas las restricciones referenciales.

---

## Sesión — Reversión de Opción 2 (Restauración de Presupuesto Detalle)

### Contexto & Acción
- A solicitud del usuario ("deshaz los cambios que te acabo de pedir"), se revirtieron completamente las modificaciones de la Opción 2.
- El proyecto y la base de datos volvieron a la arquitectura previa estable con `presupuesto_detalle`.

### Cambios Revertidos & Estado Actual
1. **Base de Datos MySQL (`sp_perfect_color`):**
   - Restaurada tabla `presupuesto_detalle` con FKs a `presupuestos` y `productos` (`id_producto NULL` permitido).
   - Restaurada tabla `mezcla_detalle` vinculada a `presupuesto_detalle` (`id_presupuesto_detalle` FK) y a `productos` (`id_producto_base` FK).
   - Restaurada tabla `nota_entrega_detalle` con FK a `presupuesto_detalle` (`id_presupuesto_detalle`).
   - Eliminadas tablas `presupuesto_producto` y `presupuesto_preparado`.
   - Re-exportado dump oficial `app/core/sp_perfect_color.sql` en UTF-8.
2. **Código PHP:**
   - `PresupuestoModel.php`: Revertidos `_ejecutarInsert()` y `_ejecutarSelectDetalle()` a `presupuesto_detalle`.
   - `NotaEntregaModel.php`: Revertidos `_ejecutarSelectDetalle()`, `_validarStock()`, `_insertarDetalleYDescontarStock()`, `_ejecutarActualizarDetalle()` y `_ejecutarTopProductos()` a `presupuesto_detalle`.
   - `notaEntregaController.php`: Revertido a `id_presupuesto_detalle`.
3. **Diagrama Relacional SVG (`modelo_relacional.svg`):**
   - Restaurado a la distribución previa: `presupuesto_detalle` (`x=450, y=380`), `estado_presupuesto` (`x=450, y=560`), `mezcla_detalle` (`x=760, y=720`), con conexiones ortogonales y balance de tags 100% verificado.

---

## Sesión: Correcciones en BD/SVG, Inventario y Presupuesto (Preparación de Mezclas)

### 1. Correcciones en BD y SVG (`modelo_relacional.svg`)
- **Separación visual de `usuarios` y `modulos`:**
  - `table_modulos` desplazada verticalmente a $Y=275$ ($X=1030, Y=275, W=230, H=110$).
  - Despejado completamente el solapamiento con `table_usuarios` (que finaliza en $Y=244.5$), dejando 30.5 px libres arriba y 35 px libres abajo hacia `table_productos`.
  - Relación `rel_rol_modulo_modulos_id_modulo` alineada ortogonalmente a la nueva coordenada $Y=309.25$.
- **Representación de `id_cliente` en Notas de Entrega y Cuentas por Cobrar:**
  - En `table_notas_entrega`: Agregadas columnas `id_cliente` (INT 11 FK) e `id_usuario` (INT 11 FK), renombrada columna `fecha_emision` $\to$ `fecha` (DATETIME).
  - En `table_cuentas_cobrar`: Agregada columna `id_cliente` (INT 11 FK).
  - Relaciones conectadas y alineadas en el SVG.
  - Balance estricto de etiquetas `<g>` verificado: 75 aperturas y 75 cierres (`Final depth: 0`).
- **Garantía en el código backend:**
  - `notaEntregaController.php`: Al guardar la nota, `$clienteId` se obtiene y valida directamente del presupuesto asociado (`$presupuesto['id_cliente']`).
  - `NotaEntregaModel.php`: `_crearCuentaCobrar()` garantiza que `id_cliente` se tome de la nota de entrega.

### 2. Correcciones en Módulo Inventario
- **Quitar opción "Preparado":**
  - En [`app/views/inventarioListView.php`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/app/views/inventarioListView.php), eliminada opción `Preparado` del `<select id="tipoProductoInsumo">`. El catálogo ahora solo permite registrar `Base` (ID 1) o `Simple` (ID 2).
  - En [`app/controllers/inventarioController.php`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/app/controllers/inventarioController.php), validado que `id_tipo_producto` solo sea 1 o 2.
- **Stock en números enteros:**
  - En la vista: `step="1"` y `min="0"` en inputs `stockActualInsumo` y `stockMinimoInsumo`.
  - En [`assets/js/inventario.js`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/assets/js/inventario.js): interceptor `keydown` y limpieza `input` para restringir entrada a caracteres estrictamente numéricos (sin `.`, `,`, `e`, `-`). Validación en `guardarInsumo` que sean enteros $\ge 0$.
  - En el controlador: validación PHP con `floor($stock) == $stock` para asegurar enteros.
- **Validación de unidades de medida:**
  - Si Tipo es **Base**: unidad de medida bloqueada/fijada a **Galón** (`Galon`).
  - Si Tipo es **Simple**: selector dinámico restringido a **Unidad** o **KG**.
  - Validación espejo en `inventarioController.php`.

### 3. Correcciones en Módulo Presupuesto (Preparación de Pinturas y Mezclas)
- **Quitar preparados del catálogo interactivo:**
  - En [`app/views/presupuestoFormView.php`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/app/views/presupuestoFormView.php): eliminado botón de filtro "Preparados" (solo quedan "Todos", "Bases" y "Simples").
  - En [`assets/js/presupuestoForm.js`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/assets/js/presupuestoForm.js): exclusión de productos tipo 3 al cargar insumos disponibles.
- **Formulación y mezcla de pinturas en "Items del Presupuesto":**
  - Agregado botón destacado `<button id="btnModalPrepararMezcla" class="btn btn-sm btn-success"><i class="bi bi-palette-fill me-1"></i>Preparar Mezcla</button>`.
  - Creado modal `#modalPrepararMezcla` para formulación interactiva:
    - **Nombre/Identificación del color** (ej: "Azul Marino Metalizado #102").
    - **Presentación/Tamaño comercial a vender**: 1 Galón, 1/2 Galón, 1/4 Galón, 1/8 Galón, 1/16 Galón, 1/32 Galón, 1/64 Galón, 1/128 Galón.
    - **Cantidad de envases a vender**.
    - **Tabla dinámica de tintes base**: permite seleccionar hasta 6 bases concentradas con sus fracciones ($1/2, 1/4, 1/8, \dots, 1/128$ o fracción personalizada de 128avos) y calcula el consumo unitario en galones.
    - **Cálculo de costos y precio de venta**: suma costos de las bases y permite establecer el precio de venta unitario por envase.
  - Al agregar la mezcla: se inserta en `itemsPresupuesto` con badge `[Preparado]`, desglose de bases y su subtotal.
  - Envío al backend con array `mezclas` estructurado para registrar en `presupuesto_detalle` e `item_composicion`.
  - En [`PresupuestoModel.php`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/app/models/PresupuestoModel.php): ajustada `cantidad_consumida = 1.0000` en productos simples para evitar multiplicación al cuadrado al descargar stock en notas de entrega.

---

## Sesión: SessionTrait, PHPMailer (SMTP), Perfil de Usuario y Cambio Seguro de Contraseña

### 1. Trait de Sesiones (`SessionTrait`) y Helper Adaptador
- **`app/traits/SessionTrait.php`**: Creado trait `App\Traits\SessionTrait` para encapsular la gestión centralizada de sesiones, autenticación y verificación de roles/permisos.
- **`app/helpers/sesionHelper.php`**: Implementa la clase `SesionManager` que usa `SessionTrait`, y todas las funciones procedurales (`verificarAutenticacion`, `tienePermiso`, `verificarPermiso`, etc.) delegan en la instancia del trait para garantizar 100% de compatibilidad retrospectiva.
- **`app/controllers/loginController.php`**: Refactorizado para usar `obtenerSesionManager()->iniciarSesion($usuario)` y `cerrarSesion()`.

### 2. PHPMailer y Servicio SMTP
- **`composer.json`**: Agregado `phpmailer/phpmailer: ^7.1` y `"app/helpers/correoHelper.php"` en `autoload.files`.
- **`app/config/correoConfig.php`**: Configuración de credenciales y servidor SMTP con soporte de variables de entorno.
- **`app/helpers/correoHelper.php`**: Funciones `enviarCorreo()`, `plantillaBaseCorreo()` y `enviarRecuperacionClave()` con diseño responsive corporativo de SP Perfect Color.
- **Flujo de Recuperación en Login**:
  - `loginView.php`: Agregado enlace y modal `modalRecuperarClave`.
  - `login.js`: Envío asíncrono con fetch a `login/recuperarClave`.
  - `loginController.php`: Endpoints `recuperarClave`, `restablecer` y `guardarNuevaClave` con tokens temporales firmados con HMAC SHA-256.
  - `restablecerClaveView.php`: Vista independiente para ingreso de nueva contraseña.

### 3. Módulo Dedicado de "Mi Perfil" (`/perfil`) y Cambio Seguro de Contraseña
- **`app/controllers/perfilController.php`**: Creado controlador para la gestión de perfil y cambio de clave.
  - `actualizarDatos`: Actualiza nombre y correo de la cuenta activa.
  - `cambiarClave`: Solicita y valida la **contraseña actual** mediante `password_verify()` antes de autorizar la nueva contraseña.
- **`app/views/perfilView.php`**: Vista con tarjeta informativa de usuario (avatar, rol, módulos con acceso, estado) y formularios separados para datos personales y contraseña.
- **`assets/js/perfil.js`**: Validación en cliente y peticiones asíncronas vía fetch con feedback visual.
- **`app/models/UsuarioModel.php`**: Agregado `buscarConRolPorId()` y su método privado encapsulado `_ejecutarSelectConRolById()`.
- **`app/controllers/frontController.php`**: Mapeada la ruta `perfil` en controladores y títulos de página.
- **`app/views/plantillaBase.php`**: Enlaces del usuario en el sidebar (móvil y desktop) actualizados a `/SP%20Perfect%20Color/perfil`.
- **`app/controllers/usuarioController.php`**: Usuarios sin permisos para gestionar usuarios son redirigidos automáticamente a `/perfil`.





