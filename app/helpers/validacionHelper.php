<?php
// ARCHIVO: validacionHelper.php
// OBJETIVO: Funciones reutilizables para validar formatos de datos (cédula, RIF, teléfono, etc.)

namespace App\Helpers;

// FUNCIÓN: validarCorreo
// OBJETIVO: Verificar que un correo electrónico tenga una estructura válida
function validarCorreo($correo)
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

// FUNCIÓN: validarRequerido
// OBJETIVO: Verificar que un campo de texto no esté vacío después de eliminar espacios
function validarRequerido($valor)
{
    return !empty(trim($valor));
}

// FUNCIÓN: validarFecha
// OBJETIVO: Verificar que una fecha tenga formato YYYY-MM-DD y sea una fecha real del calendario
function validarFecha($fecha)
{
    $partes = explode('-', $fecha);
    if (count($partes) !== 3) {
        return false;
    }
    return checkdate((int)$partes[1], (int)$partes[2], (int)$partes[0]);
}

// FUNCIÓN: validarClave
// OBJETIVO: Verificar que la contraseña tenga entre 6 y 20 caracteres y no contenga caracteres de inyección SQL / scripts
function validarClave(string $clave, ?string &$error = null): bool
{
    $longitud = strlen($clave);
    if ($longitud < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
        return false;
    }
    if ($longitud > 20) {
        $error = 'La contraseña no puede exceder los 20 caracteres';
        return false;
    }

    // Prohibir caracteres de inyección SQL y XSS: comillas ', ", `, punto y coma ;, barras \, secuencias --, /*, */, <, >
    if (preg_match('/[\'"`;\\\\<>]/', $clave) || str_contains($clave, '--') || str_contains($clave, '/*') || str_contains($clave, '*/')) {
        $error = 'La contraseña contiene caracteres no permitidos (evite comillas, punto y coma, barras invertidas o símbolos < >)';
        return false;
    }

    return true;
}

