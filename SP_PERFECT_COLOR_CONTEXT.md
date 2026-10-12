# 🎨 SP Perfect Color — Contexto del Sistema & Arquitectura

> **Ruta del Proyecto:** `C:\xampp\htdocs\SP Perfect Color`  
> **Servidor Local:** Apache y MySQL en XAMPP (`http://localhost/SP%20Perfect%20Color/`)  
> **Base de Datos:** MySQL / MariaDB (`sp_perfect_color` en `localhost:3306`, usuario `root`, sin contraseña)

---

## 🏗️ 1. Arquitectura Técnica
- **Patrón:** MVC nativo en PHP 8.2 (sin frameworks pesados como Laravel).
  - `app/controllers/`: Controladores con despacho de acciones.
  - `app/models/`: Modelos con PDO que extienden de `ModeloBase.php`.
  - `app/views/`: Vistas PHP que usan `plantillaBase.php` (sidebar colapsable + navbar superior).
  - `app/traits/`: Reutilización de sesiones (`SessionTrait`) y utilidades globales (`AppTrait`).
- **Frontend:**
  - Bootstrap 5.3.3 + Bootstrap Icons (`bi bi-...`).
  - DataTables con configuración en español.
  - JavaScript nativo modular en `assets/js/[modulo].js` con SweetAlert2 para modales y alertas.
  - Estilos centralizados en `assets/css/estiloBase.css`.

---

## 🎨 2. Sistema de Diseño (Design Tokens en `estiloBase.css`)
Cualquier interfaz, botón o tabla nueva **debe respetar estrictamente estas variables CSS**:
- `--brand-darkest: #0F172A` (Fondo oscuro / Sidebar)
- `--brand-dark: #1E3A5F`
- `--brand-primary: #1D4ED8` (Color principal azul)
- `--brand-light: #3B82F6`
- `--brand-bright: #60A5FA`
- `--brand-bg-light: #EFF6FF`
- `--brand-bg-border: #BFDBFE`
- `--brand-gradient: linear-gradient(135deg, #0F172A 0%, #1D4ED8 100%)`
- `--card-radius: 12px` (Bordes redondeados de tarjetas y modales)
- Tipografía: `'Segoe UI', system-ui, -apple-system, sans-serif`

---

## 🗄️ 3. Módulos y Flujo de Negocio
1. **Ventas:**
   - `Presupuesto` $\rightarrow$ `NotaEntrega` $\rightarrow$ `CuentaCobrar` $\rightarrow$ `PagosRecibidos`.
2. **Compras:**
   - `NotaEntregaCompra` $\rightarrow$ `CuentaPagar` $\rightarrow$ `PagosRealizados` $\rightarrow$ `Proveedores`.
3. **Catálogos e Inventario:**
   - `Inventario` / `Productos` / `Rubros` / `MezclaProducto`.
   - `Bancos` / `Tipos de Pago` / `TasaCambio`.
   - `Usuarios` / `Roles` / `Modulos`.

---

## 🛠️ 4. Herramientas de Validación y Depuración en Vivo
El asistente tiene acceso directo en consola a:
1. **Logs de Apache:** `C:\xampp\apache\logs\error.log` (para revisar errores PHP en caliente).
2. **Consultas directas a la BD:** `C:\xampp\mysql\bin\mysql.exe -u root sp_perfect_color -e "TU CONSULTA"`.
3. **Sintaxis PHP:** `php -l "ruta/archivo.php"`.
4. **Navegador en vivo:** Playwright MCP para abrir `http://localhost/SP%20Perfect%20Color/`, interactuar con vistas y capturar la consola JS.

---

## 📌 5. Reglas Obligatorias para la IA
1. **Cero inventos de diseño:** Usar las clases de `estiloBase.css` y Bootstrap 5 existentes. Prohibido meter Tailwind o estilos que desentonen con la paleta azul/turquesa.
2. **Cero librerías basura (`ponytail`):** La solución más pequeña y limpia posible reutilizando `utilidades.js` y `ModeloBase.php`.
3. **Validación relacional (`db-design-sp`):** Toda columna usada en SQL debe existir realmente en la base de datos `sp_perfect_color`.
