# Progress & Architecture Guide: SP-Perfect-Color (Refactorización a Front Controller)

## 📌 Contexto del Proyecto
Este es el proyecto de grado para la universidad. El objetivo actual es refactorizar la arquitectura de enrutamiento y controladores para alinearse ESTRICTAMENTE con la estructura de Front Controller requerida por la cátedra.

---

## 🛠️ Reglas Arquitectónicas Estrictas (¡PROHIBIDO ROMPER!)
1. **El Index Principal:** El `index.php` en la raíz debe actuar únicamente como el punto de entrada que inicializa el sistema y delega al `frontController.php`.
2. **El Único Controlador con Clase:** El archivo `app/controllers/frontController.php` es el **ÚNICO** archivo que puede contener una clase (`class FrontController`) y un método `__construct()`.
3. **Controladores Procedimentales:** Todos los demás controladores específicos **NO PUEDEN TENER CLASES**. Deben ser archivos puramente procedimentales (lógica secuencial, funciones directas, requerimiento de vistas, etc.).

---

## 📋 Lista de Tareas (Todo List)

### Fase 1: Análisis y Preparación
- [x] Analizar la estructura actual de carpetas en `SP-Perfect-Color`.
- [x] Identificar la ubicación de la carpeta de controladores y el archivo `index.php`.

### Fase 2: Implementation del Front Controller
- [x] Crear el archivo `index.php` centralizado en la raíz.
- [x] Desarrollar la clase `FrontController` dinámica encargada de leer la URL.

### Fase 3: Controladores Procedimentales y Limpieza de Módulos
- [x] `loginController.php`
- [x] `dashboardController.php`
- [x] `clienteController.php`
- [x] `proveedorController.php`
- [x] `usuarioController.php`
- [x] `inventarioController.php`
- [x] `presupuestoController.php`
- [x] `notaEntregaController.php`
- [x] `cuentaCobrarController.php`
- [x] `cuentaPagarController.php`
- [x] Modificar y ajustar la pestaña inferior de `<div class="info-usuario">` en la vista de la plantilla/sidebar.

### 🗑️ Depuración de Módulos (Fuera de Alcance)
- [x] Eliminar por completo el módulo de **Caja** (Identificar y borrar su Controlador, Modelo, carpeta de Vistas y enlaces en los menús).
- [x] Eliminar por completo el módulo de **Facturación** (Identificar y borrar su Controlador, Modelo, carpeta de Vistas y enlaces en los menús).

### 👥 Chequeo: Módulo de Usuarios (Enfoque Unificado)
- [x] Auditar 'usuarioController.php' (Verificar que sea 100% procedimental y no tenga clases).
- [ ] Asegurar que el enlace del perfil/usuarios en la plantilla apunte siempre a `usuario` o `usuario/index`.
- [ ] **Controlador Unificado:** Configurar `usuarioController.php` para que cargue siempre `usuarioListView.php` inyectando las credenciales de sesión en JS.
- [ ] **Interfaz Compartida Inteligente (`usuarioListView.php` + `usuario.js`):**
    - Si el JS detecta que el usuario es **Administrador**, muestra la tabla global de CRUD con normalidad.
    - Si el JS detecta que es **Vendedor**, oculta por completo la tabla general y los botones de creación, transformando el modal de edición en un formulario estático e incrustado para actualizar únicamente su propio Nombre, Correo y Clave.

### Fase 4: Pruebas y Verificación
- [ ] Verificar que el enrutamiento funcione correctamente desde la URL tras la remoción de módulos.
- [ ] Asegurar que no existan errores de "Archivo no encontrado" o redirecciones muertas.
- [ ] Validar la consistencia de datos en las vistas procedimentales restantes.

---

## 🚨 Notas para la IA (AI Instructions)
- **NO** crees clases en ningún archivo dentro de la carpeta `app/controllers/`, excepto en `frontController.php`.
- Mantén la consistencia de las rutas dinámicas implementadas en el sistema.