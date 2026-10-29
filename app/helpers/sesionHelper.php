<?php
// ARCHIVO: sesionHelper.php
// OBJETIVO: Funciones globales para verificar autenticación, roles y permisos delegando en SessionTrait

namespace App\Helpers;

use App\Traits\AppTrait;

// CLASE: SesionManager
// OBJETIVO: Contenedor de servicio que implementa AppTrait para las funciones del helper
class SesionManager
{
    use AppTrait;
}

// FUNCIÓN: obtenerSesionManager
// OBJETIVO: Retorna una instancia única del gestor de sesiones con SessionTrait
function obtenerSesionManager(): SesionManager
{
    static $instancia = null;
    if ($instancia === null) {
        $instancia = new SesionManager();
    }
    return $instancia;
}

// FUNCIÓN: verificarAutenticacion
// OBJETIVO: Comprobar que existe una sesión activa con id_usuario; si no, devolver error JSON o redirigir al login
function verificarAutenticacion()
{
    obtenerSesionManager()->verificarAutenticacion();
}

// FUNCIÓN: tienePermiso
// OBJETIVO: Determina si el usuario autenticado tiene acceso a un módulo específico
function tienePermiso(string $modulo): bool
{
    return obtenerSesionManager()->tienePermiso($modulo);
}

// FUNCIÓN: verificarPermiso
// OBJETIVO: Valida el permiso para el módulo actual; soporta JSON y redirección HTTP
function verificarPermiso(string $modulo)
{
    return obtenerSesionManager()->verificarPermiso($modulo);
}

// FUNCIÓN: verificarRolAdmin
// OBJETIVO: Verificar que el usuario tenga rol de Administrador o permiso en el módulo solicitado
function verificarRolAdmin()
{
    obtenerSesionManager()->verificarRolAdmin();
}

// FUNCIÓN: verificarSoloAdmin
// OBJETIVO: Verificar que el usuario sea Administrador (rol 1), sin aceptar permisos de módulo
function verificarSoloAdmin()
{
    obtenerSesionManager()->verificarSoloAdmin();
}

// FUNCIÓN: verificarRolVendedor
// OBJETIVO: Verificar que el usuario tenga rol de Vendedor, Administrador o acceso a ventas
function verificarRolVendedor()
{
    obtenerSesionManager()->verificarRolVendedor();
}

// FUNCIÓN: verificarAcceso
// OBJETIVO: Validar que el usuario tenga uno de los roles permitidos o permiso al módulo actual
function verificarAcceso($rolesPermitidos)
{
    return obtenerSesionManager()->verificarAcceso($rolesPermitidos);
}

// FUNCIÓN: verificarPropietario
// OBJETIVO: Comprobar que el ID recibido coincida con el usuario en sesión
function verificarPropietario($idSolicitado)
{
    obtenerSesionManager()->verificarPropietario($idSolicitado);
}
