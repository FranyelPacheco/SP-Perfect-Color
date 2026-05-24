# SP Perfect Color - Sistema de Gestión Administrativa

Sistema de información para la gestión integral de procesos administrativos de la empresa **SP Perfect Color**, ubicada en Barquisimeto, Estado Lara. La empresa se especializa en la comercialización de pintura automotriz, tintes, químicos, herramientas y productos de ferretería.

---

## Tecnologías Utilizadas

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
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
│   └── core/              # Configuración central (conexión BD)
├── assets/
│   ├── css/               # Hojas de estilo
│   ├── js/                # Scripts JavaScript
│   └── img/               # Imágenes
├── vendor/                # Dependencias de Composer
├── composer.json          # Configuración de Composer
├── index.php              # Punto de entrada (front controller)
└── .htaccess              # Configuración de Apache
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

### Comentarios

- Comentarios en la línea anterior al código que explican
- Sin emojis, iconos ni decoraciones
- Describen el propósito o la regla de negocio aplicada

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

### Paso 3: Crear la base de datos

1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Ejecutar el script SQL proporcionado en `database.sql`
3. La base de datos se llama `sp_perfect_color`

### Paso 4: Configurar la conexión

Editar `app/core/conexionBD.php` si es necesario:

```php
private $host = 'localhost';
private $baseDatos = 'sp_perfect_color';
private $usuario = 'root';
private $clave = '';
```

### Paso 5: Acceder al sistema

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
- Cambio de clave

### 2. Clientes (RF02)
- Registro de clientes con cédula única
- Búsqueda por cédula, nombre o apellido
- Edición y eliminación de registros

### 3. Proveedores (RF05)
- Registro de proveedores con RIF único
- Administración de datos de contacto
- Solo el Administrador puede modificar registros

### 4. Inventario de Insumos (RF01)
- Control de stock de productos
- Alertas de stock bajo (por debajo del mínimo)
- Registro de precios de venta y compra
- Categorización de productos

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

### 7. Facturación (RF03)
- Emisión de facturas con número automático
- Métodos de pago: Efectivo, Punto de Venta, Pago Móvil, Crédito
- Facturación a crédito genera cuenta por cobrar automáticamente
- Asociación a caja abierta

### 8. Caja
- Apertura de caja con monto inicial
- Cierre de caja con resumen de ventas por método de pago
- Reporte de cierre diario
- Historial de cajas

### 9. Cuentas por Cobrar
- Registro automático al facturar a crédito
- Registro de pagos parciales o totales
- Historial de pagos por cuenta
- Vencimiento a 15 días

### 10. Cuentas por Pagar
- Registro de deudas con proveedores
- Registro de pagos realizados (solo Administrador)
- Control de saldos pendientes

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

### Tablas Principales

- `usuarios` - Usuarios del sistema con roles
- `roles` - Roles disponibles (Administrador, Vendedor)
- `clientes` - Registro de clientes
- `proveedores` - Registro de proveedores
- `insumos` - Inventario de productos
- `presupuestos` / `presupuesto_detalle` - Presupuestos y sus items
- `notas_entrega` / `nota_entrega_detalle` - Notas de entrega y sus items
- `facturas` / `factura_detalle` - Facturas y sus items
- `caja` - Registro de apertura y cierre de caja
- `cuentas_cobrar` / `pagos_recibidos` - Cuentas por cobrar y sus pagos
- `cuentas_pagar` / `pagos_realizados` - Cuentas por pagar y sus pagos

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
```

---

Este README.md incluye toda la información necesaria para que cualquier persona pueda entender, instalar y ejecutar el proyecto. Crea el archivo en la raíz de tu proyecto y súbelo a GitHub.
