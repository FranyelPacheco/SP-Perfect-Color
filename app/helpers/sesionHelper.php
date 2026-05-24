<?php
// Archivo: sesionHelper.php
// Helper para manejo de sesiones y verificacion de autenticacion

// Verifica que el usuario haya iniciado sesion
function verificarAutenticacion()
{
    if (!isset($_SESSION['usuario_id'])) {
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

// Obtiene el ID del usuario en sesion
function obtenerUsuarioId()
{
    return $_SESSION['usuario_id'] ?? null;
}

// Obtiene el rol del usuario en sesion
function obtenerUsuarioRol()
{
    return $_SESSION['usuario_rol'] ?? null;
}