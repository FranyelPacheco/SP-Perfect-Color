<?php
// ARCHIVO: SessionTrait.php
// OBJETIVO: Trait reutilizable para la gestión centralizada de sesiones, permisos y autenticación

namespace App\Traits;

use function App\Helpers\respuestaJson;

trait SessionTrait
{
    // FUNCIÓN: iniciarSesion
    // OBJETIVO: Regenerar ID de sesión y almacenar datos de usuario en $_SESSION
    public function iniciarSesion(array $usuario): void
    {
        $this->_ejecutarIniciarSesion($usuario);
    }

    private function _ejecutarIniciarSesion(array $usuario): void
    {
        $this->_ejecutarAsegurarSesion();
        session_regenerate_id(true);

        $_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $_SESSION['usuario_foto'] = $usuario['foto'] ?? '';
        $_SESSION['usuario_rol'] = (int)$usuario['id_rol'];
        $_SESSION['usuario_rol_nombre'] = $usuario['rol_nombre'] ?? '';
        $_SESSION['usuario_modulos'] = $usuario['rol_modulos'] ?? '';
    }

    // FUNCIÓN: cerrarSesion
    // OBJETIVO: Destruir la sesión activa y limpiar variables
    public function cerrarSesion(): void
    {
        $this->_ejecutarCerrarSesion();
    }

    private function _ejecutarCerrarSesion(): void
    {
        $this->_ejecutarAsegurarSesion();
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    // FUNCIÓN: estaAutenticado
    // OBJETIVO: Determina si existe una sesión de usuario válida
    public function estaAutenticado(): bool
    {
        return $this->_ejecutarEstaAutenticado();
    }

    private function _ejecutarEstaAutenticado(): bool
    {
        return !empty($_SESSION['id_usuario']);
    }

    // FUNCIÓN: obtenerIdUsuario
    // OBJETIVO: Obtener el ID del usuario actual en sesión
    public function obtenerIdUsuario(): ?int
    {
        return $this->_ejecutarObtenerEntero('id_usuario');
    }

    // FUNCIÓN: obtenerRolUsuario
    // OBJETIVO: Obtener el ID del rol del usuario en sesión
    public function obtenerRolUsuario(): ?int
    {
        return $this->_ejecutarObtenerEntero('usuario_rol');
    }

    private function _ejecutarObtenerEntero(string $clave): ?int
    {
        return isset($_SESSION[$clave]) ? (int)$_SESSION[$clave] : null;
    }

    // FUNCIÓN: tienePermiso
    // OBJETIVO: Comprobar si el usuario en sesión tiene acceso a un módulo específico
    public function tienePermiso(string $modulo): bool
    {
        return $this->_ejecutarTienePermiso($modulo);
    }

    private function _ejecutarTienePermiso(string $modulo): bool
    {
        if (!$this->estaAutenticado()) {
            return false;
        }
        if ($this->obtenerRolUsuario() === 1) {
            return true; // Administrador cuenta con acceso total
        }

        $modulos = $_SESSION['usuario_modulos'] ?? [];
        if (is_string($modulos)) {
            $modulos = array_filter(array_map('trim', explode(',', $modulos)));
        }
        return in_array(strtolower($modulo), array_map('strtolower', $modulos), true);
    }

    // FUNCIÓN: verificarAutenticacion
    // OBJETIVO: Exigir sesión activa; si no hay, devuelve JSON de error o redirige al login
    public function verificarAutenticacion(): void
    {
        $this->_ejecutarVerificarAutenticacion();
    }

    private function _ejecutarVerificarAutenticacion(): void
    {
        if ($this->estaAutenticado()) {
            return;
        }
        if ($this->_ejecutarEsPeticionAjax()) {
            respuestaJson('error', 'Debe iniciar sesion para acceder');
        }
        header('Location: /SP%20Perfect%20Color/login');
        exit;
    }

    // FUNCIÓN: verificarPermiso
    // OBJETIVO: Exigir acceso al módulo; si no tiene, redirige o responde JSON
    public function verificarPermiso(string $modulo): bool
    {
        return $this->_ejecutarVerificarPermiso($modulo);
    }

    private function _ejecutarVerificarPermiso(string $modulo): bool
    {
        $this->verificarAutenticacion();
        if ($this->tienePermiso($modulo)) {
            return true;
        }
        $this->_ejecutarDenegar('[ACCESO DENEGADO A MODULO]', 'intento acceder al modulo: ' . $modulo, 'Acceso denegado. No tiene permisos para este modulo');
    }

    // FUNCIÓN: verificarRolAdmin
    // OBJETIVO: Verificar privilegios de administrador o permiso del módulo de la URL actual
    public function verificarRolAdmin(): void
    {
        $this->_ejecutarVerificarRolAdmin();
    }

    private function _ejecutarVerificarRolAdmin(): void
    {
        $this->verificarAutenticacion();
        if ($this->obtenerRolUsuario() === 1 || $this->_ejecutarTienePermisoModuloActual()) {
            return;
        }
        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Administrador');
    }

    // FUNCIÓN: verificarSoloAdmin
    // OBJETIVO: Exigir rol Administrador (rol 1) aunque el usuario tenga permiso del módulo; para acciones que modifican datos sensibles
    public function verificarSoloAdmin(): void
    {
        $this->verificarAutenticacion();
        if ($this->obtenerRolUsuario() === 1) {
            return;
        }
        respuestaJson('error', 'Acceso denegado. Solo el Administrador puede realizar esta acción');
    }

    // FUNCIÓN: verificarRolVendedor
    // OBJETIVO: Verificar privilegios de vendedor, administrador o módulos de venta
    public function verificarRolVendedor(): void
    {
        $this->_ejecutarVerificarRolVendedor();
    }

    private function _ejecutarVerificarRolVendedor(): void
    {
        $this->verificarAutenticacion();
        $rol = (int)$this->obtenerRolUsuario();
        if ($rol === 1 || $rol === 2 || $this->tienePermiso('presupuesto') || $this->tienePermiso('notaEntrega')) {
            return;
        }
        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de ventas');
    }

    // FUNCIÓN: verificarAcceso
    // OBJETIVO: Validar roles permitidos o permiso explícito al módulo de la URL actual
    public function verificarAcceso(array $rolesPermitidos): bool
    {
        return $this->_ejecutarVerificarAcceso($rolesPermitidos);
    }

    private function _ejecutarVerificarAcceso(array $rolesPermitidos): bool
    {
        $this->verificarAutenticacion();
        $rol = (int)$this->obtenerRolUsuario();
        if ($rol === 1 || in_array($rol, $rolesPermitidos, true) || $this->_ejecutarTienePermisoModuloActual()) {
            return true;
        }
        $this->_ejecutarDenegar('[ACCESO DENEGADO]', 'Rol: ' . $rol . ' intento acceder a: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'), 'Acceso denegado. No tiene permisos para esta accion');
    }

    // FUNCIÓN: verificarPropietario
    // OBJETIVO: Comprobar que el ID solicitado coincida con el usuario en sesión
    public function verificarPropietario($idSolicitado): void
    {
        $this->_ejecutarVerificarPropietario((int)$idSolicitado);
    }

    private function _ejecutarVerificarPropietario(int $idSolicitado): void
    {
        $idSesion = $this->obtenerIdUsuario();
        if ($idSesion === null || $idSolicitado !== $idSesion) {
            respuestaJson('error', 'Acceso denegado. Solo puedes acceder a tu propio perfil');
        }
    }

    private function _ejecutarAsegurarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function _ejecutarEsPeticionAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    // El módulo sale del primer segmento de la URL (controlador)
    private function _ejecutarTienePermisoModuloActual(): bool
    {
        $partes = explode('/', trim($_GET['url'] ?? '', '/'));
        $moduloActual = $partes[0] ?? '';
        return $moduloActual !== '' && $this->tienePermiso($moduloActual);
    }

    // Registra el intento en el log y responde: JSON si es AJAX, si no redirige al dashboard
    private function _ejecutarDenegar(string $etiqueta, string $detalle, string $mensajeJson): never
    {
        error_log($etiqueta . ' Usuario: ' . ($_SESSION['usuario_nombre'] ?? 'N/A') . ' (ID: ' . ($_SESSION['id_usuario'] ?? 'N/A') . ') ' . $detalle);
        if ($this->_ejecutarEsPeticionAjax()) {
            respuestaJson('error', $mensajeJson);
        }
        header('Location: /SP%20Perfect%20Color/dashboard');
        exit;
    }
}
