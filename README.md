# SP Perfect Color - Sistema de Gestión Administrativa

![PHP 8.2](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![DataTables](https://img.shields.io/badge/DataTables-1.13-000000?style=for-the-badge&logo=datatable&logoColor=white)
![PHPMailer](https://img.shields.io/badge/PHPMailer-7.1-EA4335?style=for-the-badge&logo=gmail&logoColor=white)
![Dompdf](https://img.shields.io/badge/Dompdf-3.1-FF6B6B?style=for-the-badge&logo=adobeacrobatreader&logoColor=white)
![OpenSpout](https://img.shields.io/badge/OpenSpout-4.25-217346?style=for-the-badge&logo=microsoftexcel&logoColor=white)

Sistema integral de gestión administrativa para la empresa **SP Perfect Color** (Barquisimeto, Estado Lara). Especializada en la comercialización de pinturas automotrices, formulación colorimétrica de tintes concentrados, insumos químicos, herramientas de acabado y ferretería técnica.

---

## Índice
1. [Características Principales](#características-principales)
2. [Arquitectura y Buenas Prácticas](#arquitectura-y-buenas-prácticas)
3. [Tecnologías Utilizadas](#tecnologías-utilizadas)
4. [Estructura del Proyecto](#estructura-del-proyecto)
5. [Módulos del Sistema](#módulos-del-sistema)
6. [Flujo de Procesos y Modelo de Datos](#flujo-de-procesos-y-modelo-de-datos)
7. [Requisitos del Entorno](#requisitos-del-entorno)
8. [Guía de Instalación y Configuración](#guía-de-instalación-y-configuración)
9. [Configuración SMTP (PHPMailer)](#configuración-smtp-phpmailer)
10. [Convenciones de Código](#convenciones-de-código)
11. [Equipo y Créditos](#equipo-y-créditos)

---

## Características Principales

* **Autenticación y Seguridad Integral:** Control de acceso basado en roles y permisos por módulo, cifrado seguro con `password_hash()` (Bcrypt), y protección contra ataques de fijación de sesión mediante regeneración de identificadores.
* **Uso de Traits en POO:** Lógica de sesiones, autenticación y permisos encapsulada horizontalmente en `SessionTrait` y centralizada en `AppTrait`.
* **Servicio de Correo SMTP con PHPMailer:** Notificaciones automatizadas, recuperación segura de contraseñas mediante tokens temporales firmados con HMAC SHA-256, avisos de seguridad al modificar contraseñas y bienvenida con credenciales a nuevos usuarios.
* **Módulo Dedicado de "Mi Perfil":** Consulta independiente de datos personales, permisos asignados y cambio de contraseña con validación obligatoria de la clave actual.
* **Formulación Colorimétrica y BOM (Bill of Materials):** Preparación interactiva de pinturas personalizadas combinando hasta 6 tintes base concentrados medidos con precisión decimal (12,4) en fracciones de galón (hasta $1/128$).
* **Cadena Lineal de Ventas y Cobros (Cero Ciclos):** Flujo financiero libre de dependencias cíclicas (`notas_entrega` $\to$ `cuentas_cobrar` $\to$ `pagos_recibidos`).
* **Reactivación Automática de Soft-Delete:** Recuperación transparente de registros inactivos al reingresar claves únicas duplicadas (cédula, RIF, código, correo o nombre).
* **Exportación de Reportes Ejecutivos:** Descarga directa de notas de entrega y cartera de cuentas por cobrar en PDF horizontal (Dompdf) y Excel XLSX (OpenSpout).

---

## Arquitectura y Buenas Prácticas

El sistema está construido bajo un patrón arquitectónico limpio, desacoplado y sin frameworks pesados, aplicando estándares modernos de la comunidad PHP:

```
Petición HTTP
     │
     ▼
[index.php] ───► [frontController] ───► Valida Ruta y Mapeo
                         │
        ┌────────────────┼────────────────┬────────────────┐
        ▼                ▼                ▼                ▼
[SessionTrait]   [perfilController] [notaEntrega...]  [Controladores...]
   (Permisos)            │                │
        │                ▼                ▼
        │         [UsuarioModel]   [Modelos Base] ──► _ejecutarQuery() (PDO)
        │                │                │                   │
        └───────────────►├────────────────┴───────────────────┘
                         ▼
             [Vistas / JSON API (fetch)]
```

### 1. Gestión de Sesiones mediante Traits (`App\Traits`)
Toda la lógica de control de sesiones se encuentra desacoplada en:
- `App\Traits\SessionTrait`: Métodos de autenticación, verificación de módulos (`tienePermiso`), roles (`verificarRolAdmin`), datos de sesión y utilidades de propietario.
- `App\Traits\AppTrait`: Trait agregador maestro que centraliza todos los traits del sistema.
- `App\Helpers\SesionManager`: Fachada/adaptador que hereda de `AppTrait`, permitiendo que los controladores y funciones helpers consuman el trait sin acoplamiento rígido ni rotura de código previo.

### 2. Estándar de Importaciones PSR-12 (`Group Use Declarations`)
Se sustituyeron las listas largas de sentencias `use` individuales por bloques limpios y categorizados:

```php
use PDOException;

use App\Models\{
    NotaEntregaModel,
    PresupuestoModel,
    ClienteModel,
    InventarioModel,
    TipoPagoModel,
    BancoModel
};

use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    verificarRolVendedor
};
```

### 3. Encapsulamiento Estricto en Modelos (`ModeloBase`)
Los 11 modelos del sistema aplican encapsulamiento operativo:
- Los métodos públicos definen la firma, tipado estricto e hidratación de propiedades.
- La ejecución en base de datos delega en métodos privados con prefijo `_ejecutar` (`_ejecutarSelectAll`, `_ejecutarInsert`, etc.), garantizando transaccionalidad y consultas preparadas con PDO.

### 4. Flujo Financiero sin Redundancias Cíclicas
Para garantizar la integridad referencial y cumplir con principios contables:
- **Ventas de Contado:** Crean la cuenta por cobrar en estado cancelado (`saldo_pendiente = 0`, `estado = 'pagado'`) y registran de inmediato el pago en `pagos_recibidos` vinculado a esa cuenta.
- **Ventas a Crédito:** Crean la cuenta por cobrar en estado `pendiente` con su fecha de vencimiento. Los cobros posteriores se abonan a `pagos_recibidos` referenciando dicha cuenta.
- **Resultado:** Cero claves foráneas cíclicas y consistencia absoluta en reportes de ingresos.

---

## Tecnologías Utilizadas

### Backend
- **PHP:** 8.2+ con tipado estricto (`declare(strict_types=1)`).
- **Gestión de Paquetes:** Composer con autoloader PSR-4 (`App\` $\to$ `app/`).
- **Librerías Externas:**
  - `phpmailer/phpmailer: ^7.1` — Envíos de correos vía SMTP.
  - `dompdf/dompdf: ^3.1` — Generación de comprobantes y reportes en PDF.
  - `openspout/openspout: ^4.25` — Exportación de grandes volúmenes a Excel XLSX.

### Base de Datos
- **Motor:** MySQL 8.0+ / MariaDB 10.4+.
- **Driver:** PDO con emulación de consultas preparadas desactivada (`ATTR_EMULATE_PREPARES => false`).
- **Collation:** `utf8mb4_spanish2_ci` uniforme en la base de datos y en las 24 tablas.

### Frontend
- **Framework CSS:** Bootstrap 5.3.3.
- **Iconografía:** Bootstrap Icons 1.11.3.
- **Tablas Dinámicas:** DataTables 1.13 con soporte responsive y lenguaje español unificado (`window.DATATABLES_SPANISH`).
- **Gráficas:** Chart.js 4.4 para visualización de finanzas en el dashboard.
- **JavaScript:** Vanilla JS moderno (ES6+) con peticiones asíncronas vía `fetch` API.

---

## Estructura del Proyecto

```
c:/xampp/htdocs/SP Perfect Color/
├── app/
│   ├── config/
│   │   └── correoConfig.php          # Configuración del servidor SMTP
│   ├── controllers/                  # Controladores procedimentales
│   │   ├── bancoController.php
│   │   ├── clienteController.php
│   │   ├── configPagoController.php  # Módulo combinado (Bancos + Tipos de Pago)
│   │   ├── cuentaCobrarController.php
│   │   ├── cuentaPagarController.php
│   │   ├── dashboardController.php
│   │   ├── frontController.php       # Enrutador principal y metadatos SEO
│   │   ├── inventarioController.php
│   │   ├── loginController.php       # Autenticación y recuperación de clave
│   │   ├── notaEntregaController.php
│   │   ├── perfilController.php      # Gestión de perfil y contraseña propia
│   │   ├── presupuestoController.php
│   │   ├── proveedorController.php
│   │   ├── reporteController.php
│   │   ├── tipoPagoController.php
│   │   └── usuarioController.php     # Gestión de usuarios y roles
│   ├── core/
│   │   ├── conexionBD.php            # Singleton de conexión PDO
│   │   └── sp_perfect_color.sql      # Esquema oficial (24 tablas normalizadas)
│   ├── helpers/                      # Funciones auxiliares globales
│   │   ├── correoHelper.php          # Servicio de correo y plantillas HTML
│   │   ├── exportarReporteHelper.php # Exportadores PDF y Excel
│   │   ├── respuestaHelper.php       # Respuestas estándar JSON
│   │   ├── sesionHelper.php          # Adaptador global para SessionTrait
│   │   └── validacionHelper.php      # Validaciones (RIF, cédula, correo, etc.)
│   ├── models/                       # Modelos de datos con encapsulamiento
│   │   ├── BancoModel.php
│   │   ├── ClienteModel.php
│   │   ├── CuentaCobrarModel.php
│   │   ├── CuentaPagarModel.php
│   │   ├── EstadoPresupuestoModel.php
│   │   ├── InventarioModel.php
│   │   ├── ModeloBase.php            # Clase base con conexión PDO
│   │   ├── ModuloModel.php
│   │   ├── NotaEntregaModel.php
│   │   ├── PresupuestoModel.php
│   │   ├── ProveedorModel.php
│   │   ├── ReporteModel.php
│   │   ├── RolModel.php
│   │   ├── TipoPagoModel.php
│   │   └── UsuarioModel.php
│   ├── traits/                       # Traits de composición POO
│   │   ├── AppTrait.php              # Trait maestro agregador
│   │   └── SessionTrait.php          # Trait centralizado de sesiones y permisos
│   └── views/                        # Vistas Blade-less en PHP / HTML5
│       ├── clienteListView.php
│       ├── configPagoListView.php
│       ├── cuentaCobrarListView.php
│       ├── cuentaPagarListView.php
│       ├── dashboardView.php
│       ├── inventarioListView.php
│       ├── loginView.php
│       ├── notaEntregaFormView.php
│       ├── notaEntregaListView.php
│       ├── perfilView.php            # Vista de perfil de usuario
│       ├── plantillaBase.php         # Layout base con sidebar responsive
│       ├── presupuestoFormView.php   # Formulario con formulador de mezclas
│       ├── presupuestoListView.php
│       ├── proveedorListView.php
│       ├── reporteListView.php
│       ├── restablecerClaveView.php  # Vista de ingreso de nueva clave por token
│       └── usuarioListView.php       # Gestión administrativa de usuarios y roles
├── assets/
│   ├── css/
│   │   └── estiloBase.css            # Estilos corporativos, sidebar y variables
│   ├── js/                           # Scripts modulares por funcionalidad
│   │   ├── cliente.js
│   │   ├── login.js
│   │   ├── perfil.js                 # Manejador AJAX de perfil y seguridad
│   │   ├── presupuestoForm.js        # Lógica de colorimetría y formulación
│   │   ├── reporte.js
│   │   ├── utilidades.js             # Notificaciones, modales y animaciones
│   │   └── ...
│   └── images/
│       └── logo.webp                 # Isotipo corporativo
├── vendor/                           # Paquetes Composer
├── composer.json                     # Definición de dependencias y autoload
├── index.php                         # Punto de entrada de la aplicación
├── AGENTS.md                         # Bitácora técnica y registro de cambios
└── README.md                         # Documentación general del proyecto
```

---

## Módulos del Sistema

| Módulo | Descripción Funcional | Clave URL |
|---|---|:---:|
| **Dashboard** | Resumen ejecutivo con contadores animados, accesos directos, balance del día y gráfico de ingresos vía Chart.js. | `/dashboard` |
| **Clientes** | Registro de clientes con cédula única (V/E), historial y múltiples teléfonos en tabla puente. | `/cliente` |
| **Proveedores** | Directorio de proveedores con validación de RIF (J/V/E/G), teléfonos y asignación de rubros. | `/proveedor` |
| **Inventario** | Control de existencias diferenciando artículos simples de reventa y tintes base concentrados para formulación. | `/inventario` |
| **Presupuestos** | Cotizaciones comerciales con soporte para productos físicos y formulador interactivo de mezclas de color. | `/presupuesto` |
| **Notas de Entrega** | Despacho y facturación de presupuestos aprobados con descuento automático de inventario físico. | `/notaEntrega` |
| **Cuentas por Cobrar** | Gestión de cartera a crédito, control de saldos y registro de pagos parciales o totales. | `/cuentaCobrar` |
| **Cuentas por Pagar** | Control de compromisos con proveedores, fechas de vencimiento y registro de amortizaciones. | `/cuentaPagar` |
| **Config. de Pago** | Administración unificada de entidades bancarias e instrumentos de cobro (Efectivo, Pago Móvil, etc.). | `/configPago` |
| **Usuarios y Roles** | Administración centralizada de cuentas de usuario y asignación granular de módulos permitidos por rol. | `/usuario` |
| **Mi Perfil** | Consulta personal de perfil, edición de datos propios y cambio seguro de contraseña. | `/perfil` |
| **Reportes** | Auditoría y análisis financiero de ventas y cartera pendiente con exportación a PDF y XLSX. | `/reporte` |
| **Autenticación** | Inicio de sesión, cierre seguro y recuperación de contraseñas olvidadas vía correo SMTP. | `/login` |

---

## Flujo de Procesos y Modelo de Datos

### Cadena Comercial y Ciclo de Inventario
```
[Catálogo de Insumos / Tintes Base]
                 │
                 ▼
[Presupuesto: Formulación de Mezcla o Producto Simple]
                 │
           (Aprobación)
                 │
                 ▼
[Nota de Entrega: Descuenta Stock Físico (BOM)]
                 │
        ┌────────┴────────┐
        ▼                 ▼
   (De Contado)      (A Crédito)
        │                 │
  (Cancela CxC)     [Cuenta por Cobrar: Pendiente]
        │                 │
        ▼                 ▼
[Pagos Recibidos: Referencia id_cuenta_cobrar]
```

### Tablas Oficiales en Base de Datos (24 Tablas)
1. `banco` — Catálogo de entidades bancarias.
2. `clientes` — Registro de clientes naturales o jurídicos.
3. `cuentas_cobrar` — Deudas activas de clientes derivadas de notas de entrega.
4. `cuentas_pagar` — Compromisos financieros con proveedores.
5. `estado_presupuesto` — Estados parametrizados (Pendiente, Aprobado, Rechazado).
6. `item_composicion` — Desglose de insumos consumidos por fórmula colorimétrica (BOM).
7. `modulos` — Módulos del sistema para control de acceso.
8. `notas_entrega` — Despacho y cobro directo del presupuesto.
9. `pagos_realizados` — Egresos aplicados a cuentas por pagar.
10. `pagos_recibidos` — Cobros aplicados exclusivamente a cuentas por cobrar.
11. `presupuesto_detalle` — Ítems comerciales que componen una cotización.
12. `presupuestos` — Cabecera de presupuestos cotizados.
13. `producto_proveedor` — Relación N:M de proveedores y productos suministrados.
14. `productos` — Catálogo maestro (stock en precisión decimal 12,4).
15. `proveedores` — Registro de proveedores comerciales.
16. `rol_modulo` — Matriz de permisos de módulos autorizados por rol.
17. `roles` — Roles de usuario (Administrador, Vendedor, etc.).
18. `rubro` — Clasificación de ramos de proveedores.
19. `rubro_proveedor` — Tabla puente de proveedores y sus rubros.
20. `telefono_cliente` — Múltiples números telefónicos por cliente.
21. `telefono_proveedor` — Múltiples números telefónicos por proveedor.
22. `tipo_pago` — Métodos de pago (Efectivo, Pago Móvil, Transferencia, etc.).
23. `tipo_producto` — Naturaleza del producto (1: Base, 2: Simple).
24. `usuarios` — Usuarios autenticables con clave hasheada.

---

## Requisitos del Entorno

* **Servidor Web:** Apache 2.4+ con módulo `mod_rewrite` habilitado.
* **PHP:** Versión 8.2 o superior.
* **Extensiones PHP Requeridas:**
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `gd`
  - `zip`
  - `curl`
* **Base de Datos:** MySQL 8.0+ o MariaDB 10.4+.
* **Gestor de Dependencias:** Composer 2.x.
* **Navegador:** Cualquier navegador moderno con soporte para ES6 (Chrome, Firefox, Edge, Safari).

---

## Guía de Instalación y Configuración

### 1. Clonar el repositorio
Ubica la raíz del servidor web (por ejemplo, en XAMPP `C:\xampp\htdocs\`):
```bash
cd C:\xampp\htdocs
git clone https://github.com/FranyelPacheco/SP-Perfect-Color.git "SP Perfect Color"
cd "SP Perfect Color"
```

### 2. Instalar dependencias con Composer
```bash
composer install
composer dump-autoload
```

### 3. Crear e importar la Base de Datos
1. Abre tu gestor MySQL (phpMyAdmin o MySQL Workbench).
2. Crea una base de datos con cotejamiento `utf8mb4_spanish2_ci`:
   ```sql
   CREATE DATABASE sp_perfect_color CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
   ```
3. Importa el archivo oficial de esquema y seeds:
   ```bash
   mysql -u root -p sp_perfect_color < app/core/sp_perfect_color.sql
   ```

### 4. Configurar la Conexión a Base de Datos
Verifica o edita las credenciales en [`app/core/conexionBD.php`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/app/core/conexionBD.php):
```php
private $host = 'localhost';
private $baseDatos = 'sp_perfect_color';
private $usuario = 'root';
private $clave = '';
```

### 5. Acceso al Sistema
Abre en tu navegador la URL:
```
http://localhost/SP%20Perfect%20Color/login
```

**Credenciales iniciales de prueba:**
- **Usuario:** `admin@perfectcolor.com`
- **Contraseña:** `admin123`

---

## Configuración SMTP (PHPMailer)

El sistema envía correos corporativos utilizando el servidor SMTP configurado en [`app/config/correoConfig.php`](file:///c:/xampp/htdocs/SP%20Perfect%20Color/app/config/correoConfig.php).

### Parámetros de Configuración:
```php
return [
    'smtp_host'       => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port'       => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_auth'       => true,
    'smtp_user'       => getenv('SMTP_USER') ?: 'tu_correo@gmail.com',
    'smtp_pass'       => getenv('SMTP_PASS') ?: 'tu_app_password_16_caracteres',
    'smtp_secure'     => getenv('SMTP_SECURE') ?: 'tls',
    'smtp_from_email' => getenv('SMTP_FROM_EMAIL') ?: 'tu_correo@gmail.com',
    'smtp_from_name'  => getenv('SMTP_FROM_NAME') ?: 'SP Perfect Color',
    'smtp_debug'      => false,
];
```

> **Consejo para Gmail:** Genera una **Contraseña de aplicación** de 16 caracteres desde tu [Cuenta Google > Seguridad > Contraseñas de aplicaciones](https://myaccount.google.com/apppasswords) y asígnala al campo `smtp_pass`.

---

## Convenciones de Código

* **Estandarización PSR:** 
  - PSR-4 para autocarga de clases (`App\` en `app/`).
  - PSR-12 para sintaxis de código e importaciones agrupadas.
* **Nombres de Tablas y Columnas:** Notación snake_case (`id_producto`, `fecha_vencimiento`).
* **Nombres de Clases y Modelos:** Notación PascalCase (`NotaEntregaModel`, `SessionTrait`).
* **Nombres de Controladores y Helpers:** Formato camelCase (`notaEntregaController.php`, `correoHelper.php`).
* **Encabezados de Documentación Obligatorios:**
  ```php
  <?php
  // ARCHIVO: nombreArchivo.php
  // OBJETIVO: Descripción concisa de la responsabilidad del componente
  ```

---

## Equipo y Créditos

* **Franyel Pacheco**
* **Jermaine Gonzalez**
* **Luis Delgado**

* **Tutora Académica:** Ing. Alexis Dorante
* **Tutora Externa:** Lic. Nellyser Sánchez

**Proyecto Socio-Tecnológico — Programa Nacional de Formación en Informática (PNFI)**  
Barquisimeto, Estado Lara, Venezuela — 2026.
