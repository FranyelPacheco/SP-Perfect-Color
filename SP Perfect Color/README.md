# SP Perfect Color - Sistema de Gestión Administrativa

Sistema de información para la gestión integral de procesos administrativos de la empresa **SP Perfect Color**, ubicada en Barquisimeto, Estado Lara. La empresa se especializa en la comercialización de pintura automotriz, tintes, químicos, herramientas y productos de ferretería.

---

## Tecnologías Utilizadas

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5.3, DataTables 1.13
- **Backend:** PHP 8.2
- **Base de Datos:** MySQL (PDO)
- **Servidor:** Apache (XAMPP)
- **Librerías:** AJAX (fetch API), Composer (autoload PSR-4)
- **Sin frameworks**

---

## Estructura del Proyecto

```
raiz/
├── app/
│   ├── controllers/       # Controladores (lógica de negocio)
│   ├── models/            # Modelos (acceso a base de datos)
│   ├── views/             # Vistas (interfaz de usuario)
│   ├── helpers/           # Funciones auxiliares (validación, sesiones, respuestas)
│   └── core/              # Configuración central (conexión BD + SQL)
│       └── sp_perfect_color.sql   # Esquema completo de la BD
├── assets/
│   ├── css/               # Hojas de estilo (estiloBase.css)
│   ├── js/                # Scripts JavaScript por módulo
│   └── images/            # Logo webp, imágenes
├── vendor/                # Dependencias de Composer
├── composer.json          # Configuración de Composer
├── index.php              # Punto de entrada (front controller)
├── .htaccess              # Configuración de Apache (seguridad + rewrite)
├── robots.txt             # Bloqueo de rastreo (sistema interno)
├── progress.md            # Estado actual del proyecto
└── README.md              # Este archivo
```

---

## Convenciones de Código

### Nombrado de Archivos

| Tipo | Formato | Ejemplo |
|------|---------|---------|
| Controlador | `nombreController.php` | `clienteController.php` |
| Vista | `nombreView.php` | `clienteListView.php` |
| Modelo | `NombreModel.php` | `ClienteModel.php` |
| Helper | `nombreHelper.php` | `validacionHelper.php` |
| JavaScript | `nombre.js` | `cliente.js` |
| CSS | `nombre.css` | `estiloBase.css` |

### Nombrado de Funciones

Todas las funciones usan el formato `verboSustantivo()`:

- `validarCedula()` - Validar datos
- `obtenerClientes()` - Obtener registros
- `calcularTotal()` - Calcular valores
- `mostrarAlerta()` - Mostrar interfaz
- `enviarFormulario()` - Enviar datos

---

## Requisitos del Sistema

### Software Necesario

- **XAMPP** (Apache + MySQL + PHP 8.0 o superior)
- **Composer** (para autoload)
- Navegador web moderno (Chrome, Firefox, Edge, Opera)

### Extensiones PHP requeridas

- `pdo_mysql`
- `mbstring`
- `curl`
- `openssl`

---

## Instalación

### Paso 1: Clonar el repositorio

```bash
cd C:\xampp\htdocs
git clone [URL_DEL_REPOSITORIO] "SP Perfect Color"
```

### Paso 2: Instalar dependencias con Composer

```bash
cd "SP Perfect Color"
composer install
composer dump-autoload
```

### Paso 3: Configurar la base de datos

1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Crear una base de datos nueva llamada `sp_perfect_color` (utf8_general_ci)
3. Seleccionar la base de datos `sp_perfect_color`
4. Ir a la pestaña **Importar**
5. Seleccionar el archivo `app/core/sp_perfect_color.sql`
6. Asegurarse de que el charset sea `utf-8`
7. Hacer clic en **Continuar**

> **Importante:** El SQL incluye `DROP TABLE IF EXISTS` al inicio, por lo que se puede reimportar sin errores. Si usas phpMyAdmin, verifica que no haya saltos de línea extraños al copiar/pegar — usa siempre el archivo `.sql` directamente.

### Paso 4: Configurar la conexión

Editar `app/core/ConexionBD.php` si es necesario:

```php
private $host = 'localhost';
private $baseDatos = 'sp_perfect_color';
private $usuario = 'root';
private $clave = '';
```

### Paso 5: Verificar permisos de escritura (logs)

Asegúrate de que PHP pueda escribir en los logs si es necesario:

```bash
mkdir C:\xampp\php\logs 2>nul
```

### Paso 6: Acceder al sistema

```
http://localhost/SP%20Perfect%20Color/login
```

**Credenciales por defecto:**
- Correo: `admin@perfectcolor.com`
- Clave: `admin123`

---

## Módulos del Sistema

### 1. Autenticación y Usuarios
- Inicio de sesión con roles (Administrador / Vendedor)
- CRUD de usuarios (solo Administrador)
- Activación/desactivación de usuarios
- Cambio de clave (admin puede cambiar clave de cualquier usuario; vendedor solo su propia)
- Login con diseño glassmorphism, iconos en inputs, toggle de contraseña, spinner de carga y animación shake en errores

### 2. Clientes
- Registro de clientes con cédula única
- Múltiples teléfonos por cliente (bridge table)
- Búsqueda por cédula, nombre o apellido
- Edición y eliminación de registros

### 3. Proveedores
- Registro de proveedores con RIF único
- Múltiples teléfonos y rubros por proveedor (bridge tables)
- Solo el Administrador puede modificar registros

### 4. Inventario de Insumos
- Control de stock de productos
- Alertas de stock bajo (por debajo del mínimo)
- Registro de precios de venta y compra
- Múltiples proveedores por insumo (bridge table)

### 5. Presupuestos
- Creación de presupuestos con múltiples items
- Cálculo automático de totales
- Cambio de estados: Pendiente, Aprobado, Rechazado, Convertido
- Búsqueda y filtrado por estado

### 6. Notas de Entrega
- Creación desde presupuestos aprobados
- Creación directa con selección de items
- Descuento automático de inventario
- Validación de stock disponible
- Estados: Pendiente, Entregado, En Espera (con botones de cambio rápido en tabla y detalle)
- Edición de items cuando la nota está en espera (agregar/quitar insumos, actualiza stock)
- Pago a crédito genera CxC con vencimiento a 10 días por defecto
- Pago a contado registra ingreso automático en `pagos_recibidos`
- Método de pago seleccionable (Efectivo, Transferencia, Pago Móvil, Punto de Venta, Divisas)
- Botón "Poner en Espera" al crear la nota directamente

### 7. Cuentas por Cobrar
- Registro automático al crear nota a crédito
- Registro de pagos parciales o totales
- Historial de pagos por cuenta
- Eliminación lógica de cuentas
- Vencimiento a 10 días por defecto
- Dashboard muestra Ingresos del día

### 8. Cuentas por Pagar
- Registro de deudas con proveedores
- Registro de pagos realizados (solo Administrador)
- Control de saldos pendientes
- Eliminación lógica de cuentas
- Dashboard muestra Egresos del día

### 9. Presupuestos
- Creación con múltiples items
- Cálculo automático de totales
- Estados: Pendiente, Aprobado, Rechazado, Convertido
- Aprobación/Rechazo desde la tabla listado
- Eliminación lógica de presupuestos
- Filtro por estado en el listado
- Badge de estado estilizado en detalle

### 10. Reportes
- Reporte de Ventas (notas de entrega) por rango de fechas
- Reporte de Ingresos (pagos recibidos + contado directo) por rango de fechas
- Reporte de Egresos (pagos realizados) por rango de fechas
- Resumen con total de registros y monto acumulado
- **Desglose por Tipo de Pago**: Ventas separa contado vs crédito; Ingresos separa contado directo vs crédito cobrado
- **Desglose por Método de Pago**: Muestra desglose de Efectivo, Transferencia, Pago Móvil, Punto de Venta, Divisas
- Enlace en el sidebar (accesible para todos los roles)

---

## Roles del Sistema

### Administrador
- Acceso total a todos los módulos
- Gestión de usuarios
- Modificación de proveedores e insumos
- Registro de pagos a proveedores

### Vendedor
- Gestión de clientes
- Visualización de inventario y proveedores
- Creación de presupuestos, notas de entrega y facturas
- Gestión de caja
- Registro de pagos de clientes

---

## Paleta de Colores (UI)

El sistema usa una paleta azul + rojo definida en CSS:

| Variable | Color | Uso |
|----------|-------|-----|
| `--brand-darkest` | `#0F172A` | Sidebar, fondos oscuros |
| `--brand-primary` | `#1D4ED8` | Botones primarios, enlaces |
| `--brand-light` | `#3B82F6` | Hovers, bordes |
| `--brand-red` | `#DC2626` | Peligro, egresos, alertas |
| `--brand-gradient` | `0F172A → 1D4ED8` | Headers, login, botones, tablas |

Los stat cards del dashboard usan 6 colores de acento: teal, blue, purple, orange, green, red.

---

## Flujo de Trabajo Principal

1. **Abrir Caja** (obligatorio para facturar)
2. **Registrar Cliente** (si es nuevo)
3. **Crear Presupuesto** (opcional)
4. **Aprobar Presupuesto** (si se creó)
5. **Crear Nota de Entrega** (desde presupuesto o directa)
6. **Crear Factura** (seleccionando método de pago)
7. **Cerrar Caja** (al final de la jornada)
8. **Gestionar Cuentas por Cobrar** (si hay ventas a crédito)

---

## Respuestas del Servidor (JSON)

Todas las peticiones AJAX retornan una estructura estandarizada:

```json
{
    "estado": "exito" | "error",
    "mensaje": "Descripción legible para el usuario",
    "datos": {} | [] | null
}
```

---

## Base de Datos

El esquema completo está en `app/core/sp_perfect_color.sql`.

### Bridge Tables (relaciones muchos a muchos)

- `telefono_cliente` — Múltiples teléfonos por cliente
- `telf_proveedor` — Múltiples teléfonos por proveedor
- `rubro_proveedor` — Múltiples rubros por proveedor
- `insumo_proveedor` — Múltiples proveedores por insumo

### Tablas Principales

- `usuarios` — Usuarios del sistema con roles
- `roles` — Roles disponibles (Administrador, Vendedor)
- `clientes` — Registro de clientes
- `proveedores` — Registro de proveedores
- `insumos` — Inventario de productos (unidad, categoría)
- `presupuestos` / `presupuesto_detalle` — Presupuestos y sus items
- `notas_entrega` / `nota_entrega_detalle` — Notas de entrega y sus items
- `facturas` / `factura_detalle` — Facturas y sus items
- `caja` — Registro de apertura y cierre de caja
- `cuentas_cobrar` / `pagos_recibidos` — Cuentas por cobrar y sus pagos
- `cuentas_pagar` / `pagos_realizados` — Cuentas por pagar y sus pagos

---

## Reglas de Negocio Implementadas

- Cédula única por cliente
- RIF único por proveedor
- Código único por insumo
- Correo único por usuario
- Stock no puede ser negativo
- No se puede eliminar el último administrador activo
- No se puede facturar sin caja abierta
- Las ventas a crédito generan cuentas por cobrar automáticamente
- Las notas de entrega descuentan el inventario automáticamente
- Los presupuestos aprobados pueden convertirse en notas de entrega
- Pagos parciales o totales en cuentas por cobrar/pagar
- Dashboard muestra ingresos y egresos del día en tiempo real
- Moneda en dólares ($) en todo el sistema
- Plazo crédito por defecto: 10 días
- Notas de entrega en estado "en espera" pueden editar sus items (agregar/quitar insumos)
- Pago a contado registra ingreso inmediato en el dashboard
- Eliminación lógica (activo=0) en CxC, CxP y Presupuestos
- Reportes de ventas, ingresos y egresos por rango de fechas con desglose por tipo de pago y método de pago
- Admin puede cambiar contraseña de cualquier usuario (activo o inactivo)
- Login con URL absoluta y cache-busting en JS para evitar errores de sesión/caché

---

## SEO

Por ser un sistema interno de administración:

- Todas las páginas tienen `<meta robots content="noindex, nofollow">`
- `robots.txt` con `Disallow: /`
- Headers de seguridad vía `.htaccess` (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- Títulos y descripciones dinámicos por controlador
- Open Graph y Twitter Cards configurados
- JSON-LD con datos de la organización

---

## Autores

- **Franyel Pacheco** - CI: 28.679.228
- **Javier Nieto** - CI: 31.692.516
- **Jermaine Gonzalez** - CI: 31.929.716
- **Luis Delgado** - CI: 31.973.245
- **Sebastián Valera** - CI: 33.125.328

**Tutora:** Ing. Paola Ruggiero

**Tutora Externa:** Lic. Nellyser Sánchez

**Sección:** IN2123

**Proyecto Socio-Tecnológico - PNF en Informática**

**Abril - Noviembre 2026**

---

## Licencia

Este proyecto es desarrollado como parte del programa de formación académica y es propiedad de sus autores.
