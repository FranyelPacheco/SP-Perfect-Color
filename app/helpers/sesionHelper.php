<?php
// ARCHIVO: sesionHelper.php
// OBJETIVO: Funciones para verificar autenticación, roles y permisos de usuario

namespace App\Helpers;

// FUNCIÓN: verificarAutenticacion
// OBJETIVO: Comprobar que existe una sesión activa con id_usuario; si no, devolver error JSON
function verificarAutenticacion()
{
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Debe iniciar sesion para acceder');
    }
}

// FUNCIÓN: verificarRolAdmin
// OBJETIVO: Verificar que el usuario autenticado tenga rol de Administrador (rol = 1)
// NOTA: Llama internamente a verificarAutenticacion()
function verificarRolAdmin()
{
    verificarAutenticacion();

    if ($_SESSION['usuario_rol'] != 1) {
        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Administrador');
    }
}

// FUNCIÓN: verificarRolVendedor
// OBJETIVO: Verificar que el usuario autenticado tenga rol de Vendedor o Administrador (roles 1 y 2)
function verificarRolVendedor()
{
    verificarAutenticacion();

    if ($_SESSION['usuario_rol'] != 1 && $_SESSION['usuario_rol'] != 2) {
        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Vendedor');
    }
}

// FUNCIÓN: verificarAcceso
// OBJETIVO: Validar que el usuario tenga uno de los roles permitidos; soporta JSON y redirección
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

    $rolUsuario = $_SESSION['usuario_rol'] ?? 0;

    if (in_array($rolUsuario, $rolesPermitidos)) {
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
