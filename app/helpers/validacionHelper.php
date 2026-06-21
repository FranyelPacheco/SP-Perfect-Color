<?php
// ARCHIVO: validacionHelper.php
// OBJETIVO: Funciones reutilizables para validar formatos de datos (cédula, RIF, teléfono, etc.)

namespace App\Helpers;

// FUNCIÓN: validarCedula
// OBJETIVO: Verificar que una cédula venezolana tenga entre 7 y 8 dígitos numéricos
function validarCedula($cedula)
{
    return preg_match('/^\d{7,8}$/', $cedula);
}

// FUNCIÓN: validarRIF
// OBJETIVO: Verificar que un RIF tenga formato J/V/E/G seguido de guión y 1 a 9 dígitos
function validarRIF($rif)
{
    return preg_match('/^[JVEG]-\d{1,9}$/', strtoupper($rif));
}

// FUNCIÓN: validarTelefono
// OBJETIVO: Verificar que un teléfono tenga exactamente 11 dígitos (formato venezolano)
function validarTelefono($telefono)
{
    return preg_match('/^\d{11}$/', $telefono);
}

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

// FUNCIÓN: validarDecimalPositivo
// OBJETIVO: Verificar que un valor sea numérico y estrictamente mayor a cero
function validarDecimalPositivo($valor)
{
    return is_numeric($valor) && $valor > 0;
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

// FUNCIÓN: validarLongitudMaxima
// OBJETIVO: Verificar que la longitud de un texto no supere un límite dado
function validarLongitudMaxima($valor, $maximo)
{
    return strlen(trim($valor)) <= $maximo;
}
