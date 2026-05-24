# Progress & Architecture Guide: SP-Perfect-Color (Refactorización a Front Controller)

## 📌 Contexto del Proyecto
Este es el proyecto de grado para la universidad. El objetivo actual es refactorizar la arquitectura de enrutamiento y controladores para alinearse ESTRICTAMENTE con la estructura de Front Controller requerida por la cátedra.

---

## 🛠️ Reglas Arquitectónicas Estrictas (¡PROHIBIDO ROMPER!)
1. **El Index Principal:** El `index.php` en la raíz debe actuar únicamente como el punto de entrada que inicializa el sistema y delega al `frontController.php`.
2. **El Único Controlador con Clase:** El archivo `app/controllers/frontController.php` (o la ruta exacta en nuestro proyecto) es el **ÚNICO** archivo que puede contener una clase (`class FrontController`) y un método `__construct()`.
3. **Controladores Procedimentales:** Todos los demás controladores específicos (ej. `loginController.php`, `usuarioController.php`, etc.) **NO PUEDEN TENER CLASES**. Deben ser archivos puramente procedimentales (lógica secuencial, funciones directas, requerimiento de vistas, etc.), tal como la referencia estructurada de la profesora.

---

## 📋 Lista de Tareas (Todo List)

### Fase 1: Análisis y Preparación
- [x] Analizar la estructura actual de carpetas en `SP-Perfect-Color` y compararla con el repositorio de referencia.
- [x] Identificar la ubicación exacta de la carpeta de controladores y el archivo `index.php` actual.

### Fase 2: Implementación del Núcleo (Core)
- [x] **Configurar `index.php` raíz:** Adaptar el punto de entrada para que invoque al Front Controller de manera similar al modelo de referencia.
- [x] **Crear/Modificar `frontController.php`:** Implementar la clase controladora principal, su constructor y el método de enrutamiento/despacho que capture las variables de la URL (ej. `?url=...` o `?op=...`) para cargar los controladores procedimentales.

### Fase 3: Refactorización de Controladores Específicos
- [x] Identificar el primer controlador a refactorizar (ej. Home o Login). → **Piloto: `loginController.php`**
- [x] Eliminar cualquier declaración de `class XController` en dicho archivo. → **`loginController.php`**
- [x] Transformar la lógica interna en código procedimental que reciba las peticiones del Front Controller, interactúe con los modelos y cargue (`require`/`include`) la vista correspondiente. → **`loginController.php`**
- [ ] Repetir el proceso con el resto de los controladores del sistema.
  - [x] `loginController.php`
  - [x] `dashboardController.php`
  - [x] `clienteController.php`
  - [x] `proveedorController.php`
  - [x] `usuarioController.php`
  - [x] `inventarioController.php`
  - [x] `cajaController.php`
  - [x] `facturaController.php`
  - [x] `presupuestoController.php`
  - [x] `notaEntregaController.php`
  - [x] `cuentaCobrarController.php`
  - [x] `cuentaPagarController.php`

### Fase 4: Pruebas y Verificación
- [ ] Verificar que el enrutamiento funcione correctamente desde la URL.
- [ ] Asegurar que no existan errores de "Class not found" o duplicidad de nombres.
- [ ] Validar que se mantenga la consistencia de estilos y datos entre el Front Controller y los scripts procedimentales.

---

## 🚨 Notas para Cursor (AI Instructions)
- **NO** crees clases en ningún archivo dentro de la carpeta de controladores, a excepción de `frontController.php`.
- Mantén el código simple, limpio y enfocado en la inclusión de archivos mediante flujo secuencial PHP.
- Cada vez que termines una tarea, actualiza este archivo `progress.md` marcando con una `[x]` el ítem correspondiente.