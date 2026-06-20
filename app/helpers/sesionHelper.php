<?php
// Archivo: sesionHelper.php
// Helper para manejo de sesiones y verificacion de autenticacion

namespace App\Helpers;

// Verifica que el usuario haya iniciado sesion
function verificarAutenticacion()
{
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Debe iniciar sesion para acceder');
    }
}

// Verifica que el usuario tenga rol de Administrador
function verificarRolAdmin()
{
    verificarAutenticacion();
    
    if ($_SESSION['usuario_rol'] != 1) {
        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Administrador');
    }
}

// Verifica que el usuario tenga rol de Vendedor o Administrador
function verificarRolVendedor()
{
    verificarAutenticacion();
    
    if ($_SESSION['usuario_rol'] != 1 && $_SESSION['usuario_rol'] != 2) {
        respuestaJson('error', 'Acceso denegado. Se requieren privilegios de Vendedor');
    }
}

// Verifica que el usuario tenga uno de los roles permitidos
// Gestiona tanto respuestas JSON como redirecciones de pagina
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

// Obtiene el ID del usuario en sesion
function obtenerUsuarioId()
{
    return $_SESSION['id_usuario'] ?? null;
}

// Obtiene el rol del usuario en sesion
function obtenerUsuarioRol()
{
    return $_SESSION['usuario_rol'] ?? null;
}