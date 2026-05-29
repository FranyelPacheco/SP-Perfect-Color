# Estado del Proyecto: SP Perfect Color (PNF Informática - UNES)

## RoadMap de Refactorización y Mejora
- [x] **Fase 1: Infraestructura (Composer & Autoload)**
    - [x] Configurar namespace `App\` en `app/` (Controllers, Models, Helpers, Core).
    - [x] Implementar `vendor/autoload.php` en `index.php`.
    - [x] Eliminar `require_once` manuales de rutas físicas.
- [ ] **Fase 2: Interfaz Profesional (Bootstrap & CSS)**
    - [ ] Adaptar `views/plantillaBase.php` (o el archivo raíz que gestiona el layout).
    - [ ] Integrar Bootstrap 5 vía CDN.
    - [ ] Migrar componentes de `assets/css/estiloBase.css` a estructura Bootstrap.
- [ ] **Fase 3: Modernización de Datos (DataTables & Borrado Lógico)**
    - [ ] Modificar tablas SQL (`ALTER TABLE`) añadiendo `activo TINYINT(1) DEFAULT 1`.
    - [ ] Actualizar `Models/` para filtrar siempre por `activo = 1`.
    - [ ] Integrar DataTables en `views/` para reemplazar paginación manual.
- [ ] **Fase 4: Encapsulamiento (POO)**
    - [ ] Refactorizar clases para seguir principios de encapsulamiento.
