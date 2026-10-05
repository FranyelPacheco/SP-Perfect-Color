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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $_SESSION['usuario_rol'] = (int)$usuario['id_rol'];
        $_SESSION['usuario_rol_nombre'] = $usuario['rol_nombre'] ?? '';
        $_SESSION['usuario_modulos'] = $usuario['rol_modulos'] ?? '';
    }

    // FUNCIÓN: cerrarSesion
    // OBJETIVO: Destruir la sesión activa y limpiar variables
    public function cerrarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    // FUNCIÓN: estaAutenticado
    // OBJETIVO: Determina si existe una sesión de usuario válida
    public function estaAutenticado(): bool
    {
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }

    // FUNCIÓN: obtenerIdUsuario
    // OBJETIVO: Obtener el ID del usuario actual en sesión
    public function obtenerIdUsuario(): ?int
    {
        return isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;
    }

    // FUNCIÓN: obtenerRolUsuario
    // OBJETIVO: Obtener el ID del rol del usuario en sesión
    public function obtenerRolUsuario(): ?int
    {
        return isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : null;
    }

    // FUNCIÓN: obtenerNombreUsuario
    // OBJETIVO: Obtener el nombre del usuario en sesión
    public function obtenerNombreUsuario(): ?string
    {
        return $_SESSION['usuario_nombre'] ?? null;
    }

    // FUNCIÓN: obtenerCorreoUsuario
    // OBJETIVO: Obtener el correo del usuario en sesión
    public function obtenerCorreoUsuario(): ?string
    {
        return $_SESSION['usuario_correo'] ?? null;
    }

    // FUNCIÓN: tienePermiso
    // OBJETIVO: Comprobar si el usuario en sesión tiene acceso a un módulo específico
    public function tienePermiso(string $modulo): bool
    {
        if (!$this->estaAutenticado()) {
            return false;
        }

        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($rol === 1) {
            return true; // Administrador cuenta con acceso total
        }

        $modulos = $_SESSION['usuario_modulos'] ?? [];
        if (is_string($modulos)) {
            $modulos = array_filter(array_map('trim', explode(',', $modulos)));
        }

        $modulosLower = array_map('strtolower', $modulos);
        $moduloLower = strtolower($modulo);

        return in_array($moduloLower, $modulosLower, true);
    }

    // FUNCIÓN: verificarAutenticacion
    // OBJETIVO: Exigir sesión activa; si no hay, devuelve JSON de error o redirige al login
    public function verificarAutenticacion(): void
    {
        if (!$this->estaAutenticado()) {
            $esJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($esJson) {
                respuestaJson('error', 'Debe iniciar sesion para acceder');
            }
            header('Location: /SP%20Perfect%20Color/login');
            exit;
        }
    }

    // FUNCIÓN: verificarPermiso
    // OBJETIVO: Exigir acceso al módulo actual; si no tiene, redirige o responde JSON
    public function verificarPermiso(string $modulo): bool
    {
        $this->verificarAutenticacion();

        if ($this->tienePermiso($modulo)) {
            return true;
        }

        error_log('[ACCESO DENEGADO A MODULO] Usuario: ' . ($_SESSION['usuario_nombre'] ?? 'N/A') .
                  ' (ID: ' . ($_SESSION['id_usuario'] ?? 'N/A') .
                  ') intento acceder al modulo: ' . $modulo);

        $esJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($esJson) {
            respuestaJson('error', 'Acceso denegado. No tiene permisos para este modulo');
        }

        header('Location: /SP%20Perfect%20Color/dashboard');
        exit;
    }

    // FUNCIÓN: verificarRolAdmin
    // OBJETIVO: Verificar privilegios de administrador o permiso de módulo
    public function verificarRolAdmin(): void
    {
        $this->verificarAutenticacion();

        if ((int)($_SESSION['usuario_rol'] ?? 0) === 1) {
            return;
        }

        $url = $_GET['url'] ?? '';
        $partes = explode('/', trim($url, '/'));
        $moduloActual = !empty($partes[0]) ? $partes[0] : '';
        if (!empty($moduloActual) && $this->tienePermiso($moduloActual)) {
            return;
        }

        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Administrador');
    }

    // FUNCIÓN: verificarRolVendedor
    // OBJETIVO: Verificar privilegios de vendedor, administrador o módulos de venta
    public function verificarRolVendedor(): void
    {
        $this->verificarAutenticacion();

        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($rol === 1 || $rol === 2 || $this->tienePermiso('presupuesto') || $this->tienePermiso('notaEntrega')) {
            return;
        }

        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de ventas');
    }

    // FUNCIÓN: verificarAcceso
    // OBJETIVO: Validar roles permitidos o permiso explícito al módulo
    public function verificarAcceso(array $rolesPermitidos): bool
    {
        $this->verificarAutenticacion();

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);

        if ($rolUsuario === 1 || in_array($rolUsuario, $rolesPermitidos, true)) {
            return true;
        }

        $url = $_GET['url'] ?? '';
        $partes = explode('/', trim($url, '/'));
        $moduloActual = !empty($partes[0]) ? $partes[0] : '';
        if (!empty($moduloActual) && $this->tienePermiso($moduloActual)) {
            return true;
        }

        error_log('[ACCESO DENEGADO] Usuario: ' . ($_SESSION['usuario_nombre'] ?? 'N/A') .
                  ' (ID: ' . ($_SESSION['id_usuario'] ?? 'N/A') .
                  ', Rol: ' . $rolUsuario .
                  ') intento acceder a: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

        $esJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($esJson) {
            respuestaJson('error', 'Acceso denegado. No tiene permisos para esta accion');
        }

        header('Location: /SP%20Perfect%20Color/dashboard');
        exit;
    }

    // FUNCIÓN: verificarPropietario
    // OBJETIVO: Comprobar que el ID solicitado coincida con el usuario en sesión
    public function verificarPropietario($idSolicitado): void
    {
        $idSesion = $this->obtenerIdUsuario();
        if ($idSesion === null || (int)$idSolicitado !== (int)$idSesion) {
            respuestaJson('error', 'Acceso denegado. Solo puedes acceder a tu propio perfil');
        }
    }
}
