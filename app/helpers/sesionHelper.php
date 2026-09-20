<?php
// ARCHIVO: sesionHelper.php
// OBJETIVO: Funciones para verificar autenticación, roles y permisos de usuario

namespace App\Helpers;

// FUNCIÓN: verificarAutenticacion
// OBJETIVO: Comprobar que existe una sesión activa con id_usuario; si no, devolver error JSON o redirigir al login
function verificarAutenticacion()
{
    if (!isset($_SESSION['id_usuario'])) {
        $esJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($esJson) {
            respuestaJson('error', 'Debe iniciar sesion para acceder');
        }
        header('Location: /SP%20Perfect%20Color/login');
        exit;
    }
}

// FUNCIÓN: tienePermiso
// OBJETIVO: Determina si el usuario autenticado tiene acceso a un módulo específico
function tienePermiso(string $modulo): bool
{
    if (!isset($_SESSION['id_usuario'])) {
        return false;
    }

    $rol = (int)($_SESSION['usuario_rol'] ?? 0);
    if ($rol === 1) {
        return true; // Administrador cuenta con acceso irrestricto
    }

    $modulos = $_SESSION['usuario_modulos'] ?? [];
    if (is_string($modulos)) {
        $modulos = array_filter(array_map('trim', explode(',', $modulos)));
    }

    $modulosLower = array_map('strtolower', $modulos);
    $moduloLower = strtolower($modulo);

    return in_array($moduloLower, $modulosLower, true);
}

// FUNCIÓN: verificarPermiso
// OBJETIVO: Valida el permiso para el módulo actual; soporta JSON y redirección HTTP
function verificarPermiso(string $modulo)
{
    if (!isset($_SESSION['id_usuario'])) {
        $esJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($esJson) {
            respuestaJson('error', 'Debe iniciar sesion para acceder');
        }
        header('Location: /SP%20Perfect%20Color/login');
        exit;
    }

    if (tienePermiso($modulo)) {
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
// OBJETIVO: Verificar que el usuario tenga rol de Administrador o permiso en el módulo solicitado
function verificarRolAdmin()
{
    verificarAutenticacion();

    if ((int)($_SESSION['usuario_rol'] ?? 0) === 1) {
        return;
    }

    $url = $_GET['url'] ?? '';
    $partes = explode('/', trim($url, '/'));
    $moduloActual = !empty($partes[0]) ? $partes[0] : '';
    if (!empty($moduloActual) && tienePermiso($moduloActual)) {
        return;
    }

    respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Administrador');
}

// FUNCIÓN: verificarRolVendedor
// OBJETIVO: Verificar que el usuario tenga rol de Vendedor, Administrador o acceso a ventas
function verificarRolVendedor()
{
    verificarAutenticacion();

    $rol = (int)($_SESSION['usuario_rol'] ?? 0);
    if ($rol === 1 || $rol === 2 || tienePermiso('presupuesto') || tienePermiso('notaEntrega')) {
        return;
    }

    respuestaJson('error', 'Acceso denegado. Se requieren privilegios de ventas');
}

// FUNCIÓN: verificarAcceso
// OBJETIVO: Validar que el usuario tenga uno de los roles permitidos o permiso al módulo actual
// NOTA: Si la petición no es AJAX redirige al login o dashboard; si es AJAX devuelve JSON
function verificarAcceso($rolesPermitidos)
{
    if (!isset($_SESSION['id_usuario'])) {
        $esJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($esJson) {
            respuestaJson('error', 'Debe iniciar sesion para acceder');
        }
        header('Location: /SP%20Perfect%20Color/login');
        exit;
    }

    $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);

    if ($rolUsuario === 1 || in_array($rolUsuario, $rolesPermitidos)) {
        return true;
    }

    // Verificar si el rol del usuario tiene permiso explícito para el módulo en la URL
    $url = $_GET['url'] ?? '';
    $partes = explode('/', trim($url, '/'));
    $moduloActual = !empty($partes[0]) ? $partes[0] : '';
    if (!empty($moduloActual) && tienePermiso($moduloActual)) {
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

// FUNCIÓN: obtenerUsuarioId
// OBJETIVO: Devolver el ID del usuario actual en sesión, o null si no existe
function obtenerUsuarioId()
{
    return $_SESSION['id_usuario'] ?? null;
}

// FUNCIÓN: obtenerUsuarioRol
// OBJETIVO: Devolver el rol del usuario actual en sesión, o null si no existe
function obtenerUsuarioRol()
{
    return $_SESSION['usuario_rol'] ?? null;
}

// FUNCIÓN: verificarPropietario
// OBJETIVO: Comprobar que el ID recibido coincida con el usuario en sesión
function verificarPropietario($idSolicitado)
{
    $idSesion = $_SESSION['id_usuario'] ?? null;
    if ($idSesion === null || (int)$idSolicitado !== (int)$idSesion) {
        respuestaJson('error', 'Acceso denegado. Solo puedes acceder a tu propio perfil');
    }
}
