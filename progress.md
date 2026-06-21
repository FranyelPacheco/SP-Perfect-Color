# Progreso del Proyecto: SP Perfect Color

## Base de Datos
- [x] Esquema SQL normalizado con bridge tables (`telefono_cliente`, `telf_proveedor`, `rubro_proveedor`, `insumo_proveedor`)
- [x] Columnas redundantes eliminadas (`clientes.telefono`, `proveedores.telefono`, `proveedores.rubros`, `insumos.proveedor_id`)
- [x] `AUTO_INCREMENT`, `PRIMARY KEY` e índices inline en cada CREATE TABLE
- [x] FOREIGN KEY constraints como ALTER TABLE al final del SQL
- [x] `fecha_vencimiento` eliminada de tabla `insumos`
- [x] `tipo_pago` (enum contado/credito) y `metodo_pago` agregados a `notas_entrega`
- [x] `cuenta_cobrar_id` nullable en `pagos_recibidos` (para contado directo)
- [x] ENUM `notas_entrega.estado` extendido: `pendiente`,`entregado`,`en_espera`

## Modelos
- [x] `buscarPorId()` ya no filtra por `activo = 1` — permite editar/ cambiar contraseña de usuarios inactivos
- [x] Bridge methods y `GROUP_CONCAT` en `ClienteModel`, `ProveedorModel`, `InventarioModel`
- [x] Subqueries vía bridge tables en `NotaEntregaModel`, `CuentaCobrarModel`, `CuentaPagarModel`
- [x] Método `obtenerTotalPagosHoy()` en `CuentaPagarModel` y `CuentaCobrarModel`
- [x] Plazo crédito por defecto: 10 días en `NotaEntregaModel`
- [x] `eliminarCuenta()` (activo=0) en `CuentaCobrarModel` y `CuentaPagarModel`
- [x] `eliminarPresupuesto()` (activo=0) en `PresupuestoModel`
- [x] `ponerEnEspera()` en `NotaEntregaModel`
- [x] `actualizarDetalleNota()` en `NotaEntregaModel` (transacción: restaura stock, reemplaza items, actualiza total)
- [x] `ReporteModel` con `ventasPorRango()`, `ingresosPorRango()`, `egresosPorRango()`
- [x] Métodos de agrupación: `totalVentasPorTipoPago()`, `totalVentasPorMetodoPago()`, `totalIngresosPorMetodoPago()`, `totalEgresosPorMetodoPago()`
- [x] Insert de nota envía `tipo_pago`, `metodo_pago` y `estado` dinámico
- [x] Contado registra ingreso directo en `pagos_recibidos` sin crear cuenta_cobrar

## Controladores
- [x] Guardar/actualizar rutea a bridge tables en `cliente`, `proveedor`, `inventario`
- [x] Dashboard con estadísticas reales (Clientes, Proveedores, Insumos, Alertas Stock, Ingresos/Egresos Hoy)
- [x] Rutas `eliminar` en cuentaCobrar, cuentaPagar y presupuesto
- [x] Rutas `editar` y `actualizar` en notaEntrega (editar items de notas en espera)
- [x] Ruta `reporteController` con `ventasAjax`, `ingresosAjax`, `egresosAjax` (incluye datos agrupados por tipo y método de pago)
- [x] Estado dinámico desde formulario (pendiente/en_espera al crear nota)
- [x] Admin puede cambiar contraseña de otros usuarios desde el modal de edición

## Vistas
- [x] Multi-rubro dinámico en `proveedorListView.php`
- [x] Estandarización de DataTables en CxP, CxC, Notas de Entrega, Presupuestos
- [x] `<thead>` faltante restaurado en `presupuestoListView.php` y `notaEntregaListView.php`
- [x] Sidebar con detección de página activa
- [x] Títulos dinámicos y SEO head completo
- [x] Login con fondo animado, gradiente, shapes decorativas, glassmorphism
- [x] Input de correo con icono `bi-envelope-fill` y contraseña con `bi-lock-fill`
- [x] Toggle de visibilidad de contraseña (`bi-eye-slash-fill` / `bi-eye-fill`)
- [x] Botón de login con spinner de carga y texto dinámico ("Ingresando...")
- [x] Error de login con animación `shake` y flex layout
- [x] Cache-busting en login.js para evitar caché del navegador
- [x] Dashboard con 6 stat cards
- [x] Toolbar unificado en list views
- [x] Logo webp integrado en sidebar, navbar mobile, login y favicon
- [x] Logo sidebar con fondo blanco y padding para visibilidad
- [x] Botones "Volver"/"Cancelar" con `btn-outline-secondary`
- [x] Botones de formulario con iconos
- [x] "Quitar" items con `btn-outline-danger` + `bi-trash`
- [x] Botones Aprobar/Rechazar con iconos
- [x] Vencimiento a 10 días auto-completado
- [x] Moneda en dólares ($) en todo el sistema
- [x] Badges de estado estilo pill en DataTables (pendiente, aprobado, rechazado, convertido, etc.)
- [x] Badge de estado en detalle de presupuesto usa clases CSS propias
- [x] Badge de estado en detalle de nota usa clases CSS propias
- [x] Columna Estado en DataTable de notas de entrega
- [x] Columna Pago (tipo + método) en DataTable de notas de entrega
- [x] Botones "Poner en Espera" / "Marcar como Entregado" en DataTable de notas
- [x] Botón "Editar Items" en DataTable y detalle para notas en_espera
- [x] Vista `notaEntregaEditView.php` para editar items de nota en espera
- [x] Selector de Método de Pago en formularios de nota (contado)
- [x] Dos botones en formulario: "Crear Nota" (pendiente) y "Poner en Espera" (en_espera)
- [x] Vista `reporteListView.php` con filtros (tipo + rango fechas + generar)
- [x] Resumen de reporte (total registros + monto total)
- [x] Desglose en tarjetas separadas: Por Tipo de Pago y Por Método de Pago
- [x] Ventas: desglosa contado vs crédito y métodos de contado
- [x] Ingresos: separa contado directo vs crédito cobrado
- [x] Egresos: desglosa por método de pago

## Frontend / CSS
- [x] Paleta azul + rojo (`#0F172A`, `#1D4ED8`, `#DC2626`)
- [x] Variables CSS, sidebar con gradiente, cards con `border-radius: 12px`
- [x] Botones rediseñados con gradientes, sombras, `translateY(-2px)`
- [x] `btn-info`, `btn-secondary` y `btn-outline-secondary` con estilos propios
- [x] `btn-lg` con `border-radius: 10px`
- [x] Tablas con cabecera gradiente y color explícito en celdas
- [x] DataTable empty state visible
- [x] Formularios con `border: 2px` y focus ring
- [x] Modales con cabecera gradiente
- [x] Notificaciones toast animadas
- [x] Stat cards con colores variados
- [x] Footer de formularios con border-top divisor
- [x] Scrollbar personalizada, responsive y print styles
- [x] Estilos badge para estados (`estado-pendiente`, `estado-aprobado`, `estado-rechazado`, `estado-convertido`, `estado-entregado`, `estado-en_espera`, `estado-pagado`, `estado-moroso`)

## SEO
- [x] `frontController.php`: mapa de títulos/descripciones por controlador
- [x] `plantillaBase.php`: title dinámico, meta description, robots, canonical, OG, Twitter Cards, JSON-LD
- [x] `loginView.php` y `error404View.php`: mismo set completo
- [x] `robots.txt`: `Disallow: /`
- [x] `.htaccess`: `Options -Indexes`, `ErrorDocument 404`, headers de seguridad

## Logo
- [x] Logo webp en `assets/images/logo.webp`
- [x] Integrado en sidebar desktop/offcanvas, navbar mobile, login y favicon
- [x] Sidebar con fondo blanco, padding, box-shadow para mejor visibilidad
- [x] Archivos `logo.png` y `logo.svg` eliminados

## Login
- [x] Diseño renovado con glassmorphism, sombra profunda, wave divider SVG en header
- [x] 4 shapes decorativas flotando con animaciones CSS keyframes
- [x] Logo en contenedor cuadrado con hover scale(1.05)
- [x] Input groups con iconos, focus-within para borde unificado
- [x] Toggle de contraseña con icono interactivo
- [x] Botón con spinner de carga y estado disabled durante envío
- [x] Error de login con animación shake X
- [x] Cache-busting (`?v=filemtime`) en script login.js
- [x] URL de fetch absoluta para evitar errores de ruteo
- [x] Responsive para móviles (<=480px)

## Módulo de Reportes
- [x] `ReporteModel` con consultas por rango de fechas
- [x] `reporteController.php` con rutas AJAX
- [x] `reporteListView.php` con filtros y tabla
- [x] `assets/js/reporte.js` con lógica de generación
- [x] Enlace en sidebar (ambos menús)
- [x] Tres tipos: Ventas, Ingresos, Egresos
- [x] Desglose por tipo de pago (contado/crédito) y por método de pago
- [x] IDs para títulos de tarjetas (`tituloDesgloseTipo`, `tituloDesgloseMetodo`) para actualización dinámica desde JS
- [x] Bugfix: `formatearMonedaLocal` convertía strings de MySQL directamente con `.toFixed()`, corregido con `parseFloat()`

## Módulo de Reportes — Simplificado a 2 tipos + Exportación PDF/Excel
- [x] Limpiado `ReporteModel`: eliminados métodos redundantes (`ingresosPorRango`, `egresosPorRango`, `cuentasVencidas`, `antiguedadSaldos`, `ventasPorVendedor`, `ventasPorMetodoPago`, `productosMasVendidos`)
- [x] Limpiado `reporteController.php`: solo 5 endpoints (`index`, `ventasAjax`, `carteraCxcAjax`, `exportarPdfAjax`, `exportarExcelAjax`)
- [x] `reporteListView.php`: `<select>` con solo "Notas de Entrega" y "Cuentas por Cobrar Pendientes"
- [x] `reporte.js`: simplificado a 2 tipos, `cambiarEncabezado()` limpia cuerpo/resumen/botones al cambiar tipo
- [x] Botones export con `window.location.href` para descarga binaria
- [x] `app/helpers/exportarReporteHelper.php`: `generarPDF()` (Dompdf A4 horizontal) y `generarExcel()` (OpenSpout XLSX)
- [x] `composer.json`: agregado `dompdf/dompdf ^3.1`, `openspout/openspout ^4.25`, autoload.files con el helper
- [x] Resumen dinámico: etiquetas cambian según tipo (ventas → Notas de Entrega / CxC → Cuentas por Cobrar)

## Base de Datos — Collation español
- [x] `ALTER DATABASE sp_perfect_color` → `utf8mb4_spanish2_ci`
- [x] `ALTER TABLE` de todas las tablas → `utf8mb4_spanish2_ci`
- [x] `sp_perfect_color.sql` actualizado: todas las ocurrencias `utf8mb4_unicode_ci` → `utf8mb4_spanish2_ci`
- [x] `conexionBD.php`: agregado `SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish2_ci'`

## Módulo Config. de Pago — Bancos + Tipos de Pago unificados
- [x] `configPagoController.php` creado — renderiza vista combinada
- [x] `configPagoListView.php` — dos cards lado a lado (Bancos + Tipos de Pago)
- [x] `bancoController.php` / `tipoPagoController.php` redirigen `index` a `configPago`
- [x] Sidebar reemplazó ambos por "Config. de Pago"
- [x] `frontController.php`: `configPago` en `$titulosPagina`

## DATATABLES_SPANISH globalizado
- [x] `utilidades.js`: define `window.DATATABLES_SPANISH`
- [x] 10 feature JS: `language: window.DATATABLES_SPANISH`
- [x] `plantillaBase.php`: cache buster `?v=filemtime` en utilidades.js

## Git + GitHub
- [x] Repositorio inicializado y subido a `https://github.com/FranyelPacheco/SP-Perfect-Color`
- [x] Rama `main`, commit "optimización y mejora de BD"
- [x] `vendor/` incluido (quitado de `.gitignore`)

## Reactivación de registros soft-delete
- [x] 6 modelos: agregado `_buscarInactivoPor*` (cedula, RIF, codigo, correo, nombre)
- [x] `_actualizar` en Cliente/Proveedor/Inventario ahora setea `activo = 1`
- [x] 6 controladores: `guardar` reactiva registro inactivo antes de INSERT
- [x] Evita error UNIQUE KEY al reinsertar después de eliminar

## Rubro — ciclo eliminado: insumo hereda rubro del proveedor
- [x] `InventarioModel._obtenerRubrosPorProveedor()` — query que trae rubros desde `rubro_proveedor` para un proveedor
- [x] `inventarioController.obtenerRubrosPorProveedorAjax` — endpoint para frontend
- [x] Al seleccionar proveedor en el insumo: si tiene 1 rubro → auto-selecciona y bloquea; si tiene varios → filtra; si tiene 0 → muestra todos
- [x] Al editar insumo: carga rubros del proveedor guardado y selecciona el rubro correcto
- [x] Formulario reorganizado: Proveedor y Rubro están lado a lado
- [x] `rubro_id` eliminado de tabla `insumos` (rubro ahora va por proveedor)

## FK/PK renaming — todas las FK ahora usan `id_nombre` (26 columnas)
- [x] **SQL (`sp_perfect_color.sql`):** CREATE TABLE, INSERT, KEY y FK constraints renombrados
- [x] **Models (8):** Cliente, Proveedor, Inventario, Presupuesto, NotaEntrega, CuentaCobrar, CuentaPagar, Usuario
- [x] **Controllers (14):** arrays, POST/GET actualizados (`$_POST['cliente_id']`→`$_POST['id_cliente']`, etc.)
- [x] **Helpers (2):** `sesionHelper.php` (`$_SESSION['usuario_id']`→`$_SESSION['id_usuario']`)
- [x] **Views (6):** form name attributes actualizados
- [x] **JS (7):** referencias AJAX y objeto DataTable actualizadas

## Mejoras UI/UX (Junio 2026)
- [x] **Login:** Gradiente quitado del `card-header`, fondo sólido (`--brand-dark`)
- [x] **Sidebar replegable:** Botón toggle que colapsa/expande sidebar (solo iconos visible en colapsado)
- [x] **Counter Animation:** `animarContador()` en `utilidades.js` — números animados en stat-cards del dashboard
- [x] **Gráfica ingresos:** Chart.js vía CDN — barras verticales con ingresos diarios últimos 7 días
- [x] **Bancos/TiposPago separados:** 2 módulos independientes; sidebar muestra "Config. de Pago" con submenú hover (Bancos / Tipos de Pago)

## Auditoría de encapsulamiento y seguridad (Junio 2026)

### 🔴 Crítico — Corregido
- [x] **LFI en `frontController.php`:** `$this->controlador` ahora se sanitiza con `preg_match('/^[a-z]+$/', ...)`. Si no coincide, cae a `'login'` por defecto.
- [x] **`UsuarioModel._buscarPorId()`:** Agregado `AND activo = 1` — ya no retorna usuarios desactivados.

### 🟠 Encapsulamiento — Corregido
- [x] **Dead code eliminado (7 métodos):** `ClienteModel::buscarPorCedula()`, `ClienteModel::obtenerTelefonos()`, `ProveedorModel::buscarPorRIF()`, `ProveedorModel::obtenerTelefonos()`, `ProveedorModel::obtenerRubros()`, `InventarioModel::actualizarStock()`, `InventarioModel::obtenerProveedoresDeInsumo()`
- [x] **SQL directo reemplazado por models (3 controllers):** `cuentaCobrarController`, `cuentaPagarController`, `notaEntregaController` — endpoints `obtenerDatosPagoAjax`, `obtenerTiposPagoAjax`, `obtenerBancosAjax` ahora usan `TipoPagoModel` y `BancoModel` en vez de `ConexionBD::query()`.
- [x] **Session checks manuales reemplazados (2 controllers):** `notaEntregaController` (5 endpoints) y `presupuestoController` (2 endpoints) ahora usan `verificarAutenticacion()`.
- [x] **Validación agregada a `CuentaPagarModel`:** `buscarPorId()`, `obtenerPagos()`, `registrarPago()`, `eliminarCuenta()`, `buscarCuentas()` ahora validan parámetros igual que `CuentaCobrarModel`.
- [x] **Validación duplicada extraída en `ReporteModel`:** Las 4 copias del bloque `if (empty($desde) || empty($hasta))` se reemplazaron por `_validarRangoFechas()`.

### 🟡 Seguridad — Corregido
- [x] **XSS en `usuarioController.php`:** `$_SESSION['usuario_rol']` y `$_SESSION['id_usuario']` ahora se escapan con `json_encode()`.
- [x] **Info sensible eliminada de errores:** `notaEntregaController` ya no envía `$e->getFile():$e->getLine()` al cliente. Los errores se loguean con `error_log()` y se devuelve mensaje genérico. Igual en `cuentaCobrarController` y `cuentaPagarController`.
- [x] **Validación de fechas + whitelist en `reporteController`:** Los endpoints `ventasAjax`, `carteraCxcAjax`, `exportarPdfAjax`, `exportarExcelAjax` ahora validan formato fecha con `validarFecha()` y el parámetro `tipo` contra whitelist `['ventas', 'carteraCxc']`.

### 🔵 Mejores prácticas — Corregido
- [x] **`verificarPropietario()` movido de `usuarioController.php` a `sesionHelper.php`:** Elimina riesgo de "Cannot redeclare" si el controller se incluye 2 veces.
- [x] **Dashboard optimizado:** `ClienteModel`, `ProveedorModel`, `InventarioModel` ahora tienen `contarTodos()` con `SELECT COUNT(*)`. DashboardController usa estos en vez de cargar todos los registros y hacer `count()` en PHP.
- [x] **Debug log eliminado:** `error_log('[CxP] Proveedores encontrados')` removido de `cuentaPagarController`.
- [x] **Inventario guardar:** Agregado soporte para `id_rubro` en los datos enviados (consistente con `actualizar`).

### 🟣 Bugs encontrados y corregidos
- [x] `_buscarPorId()` en UsuarioModel retornaba usuarios inactivos (corregido con `AND activo = 1`)

## Refactor models — Herencia + propiedades privadas + patrón público→privado (Junio 2026)

### Nuevo archivo
- [x] **`app/models/ModeloBase.php`**: Clase abstracta con `protected PDO $conexion;` vía `ConexionBD::obtenerInstancia()`. Todos los models ahora la extienden.

### 11 models refactorizados al patrón:
```php
class XxxModel extends ModeloBase
{
    // Propiedades privadas de la entidad
    private ?int $id;
    private ?string $nombre;
    // ...

    public function __construct() { parent::__construct(); }

    // Público: asigna params, valida, delega
    public function buscarPorId(int $id): array|false {
        $this->id = $id;
        if ($this->id < 1) return false;
        return $this->_executeSelectById();
    }

    // Privado: ejecuta SQL usando $this->propiedades
    private function _executeSelectById(): array|false { /* ... */ }
}
```

| Modelo | Props privadas | Métodos públicos → `_execute*` privados |
|--------|---------------|----------------------------------------|
| `BancoModel` | id_banco, nombre, activo | 7 → 7 |
| `TipoPagoModel` | id_tipo_pago, nombre, activo | 7 → 7 |
| `ClienteModel` | id, cedula, nombres, apellidos, correo, direccion + 4 aux | 11 → 11 |
| `ProveedorModel` | id, rif, nombreEmpresa, direccion, contacto, correo + 4 aux | 13 → 13 |
| `InventarioModel` | id, codigo, nombre, marca, idRubro, unidadMedida, stockActual, stockMinimo, precioVenta, precioCompra + 3 aux | 15 → 15 |
| `UsuarioModel` | id, nombre, correo, passwordHash, idRol, activo + 2 aux | 10 → 10 |
| `PresupuestoModel` | id, idCliente, idUsuario, total, observaciones, estado + 3 aux | 7 → 7 |
| `NotaEntregaModel` | id, idCliente, idUsuario, total, idPresupuesto, estado, condicionPago, idTipoPago, idBanco, referencia, fechaVencimiento, detalle + 2 aux | 7 → 7 (+ 5 helpers privados) |
| `CuentaCobrarModel` | id, idCliente, idNotaEntrega, montoTotal, saldoPendiente, fechaVencimiento, estado + 7 aux | 8 → 8 |
| `CuentaPagarModel` | id, idProveedor, montoTotal, saldoPendiente, fechaVencimiento, estado + 5 aux | 7 → 7 |
| `ReporteModel` | desde, hasta | 4 → 4 (+ _validarRangoFechas privado) |

### Cambios adicionales en NotaEntregaModel
- [x] **`ponerEnEspera()` eliminado** — código muerto (0 referencias externas)
- [x] **5 helpers privados extraídos** de `crearNotaEntrega()`: `_validarStock()`, `_insertarDetalleYDescontarStock()`, `_cambiarEstadoPresupuesto()`, `_crearCuentaCobrar()`, `_registrarPagoContado()`
- [x] **Firma `crearNotaEntrega` corregida**: `array $detalle` movido antes de los parámetros opcionales (elimina deprecation PHP 8.0+ de optional param before required)
- [x] **Controller `notaEntregaController` actualizado**: llamada a `crearNotaEntrega()` pasa `$detalle` en la posición correcta

### Verificación
- [x] `php -l` pasa en los 12 archivos (ModeloBase + 11 models)
- [x] Las firmas de métodos públicos no cambiaron — controllers no requieren modificación

## Pendiente
- [ ] **Probar toda la app post-refactor:** login, CRUD completo, reportes, exportaciones
- [ ] **Re-importar BD:** Ejecutar `sp_perfect_color.sql` actualizado en phpMyAdmin
- [ ] Probar reactivación soft-delete: eliminar → crear mismo valor único → verificar activo=1
- [ ] Implementar CSRF token en todos los endpoints POST (prioridad media)
- [ ] Implementar rate limiting en login (prioridad media)
- [ ] Extraer SQL duplicado (SELECT JOINs repetidos 3-4x por modelo) a métodos helper privados
- [ ] Extraer duplicación 70% en `exportarReporteHelper.php` (generarPDF/generarExcel comparten lógica)
- [ ] Extraer reactivación soft-delete copiada en 6 controllers a helper centralizado
- [ ] Unificar tipo `created_at` en BD (timestamp vs datetime)
