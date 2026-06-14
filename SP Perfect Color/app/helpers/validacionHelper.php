<?php
// Archivo: validacionHelper.php
// Helper con funciones de validacion reutilizables

namespace App\Helpers;

// Verifica que una cedula venezolana tenga formato valido (7 a 8 digitos)
function validarCedula($cedula)
{
    return preg_match('/^\d{7,8}$/', $cedula);
}

// Verifica que un RIF tenga formato valido (letra + 8 a 9 digitos)
function validarRIF($rif)
{
    return preg_match('/^[JGVEP]-\d{8,9}$/', strtoupper($rif));
}

// Verifica que un telefono venezolano tenga formato valido
function validarTelefono($telefono)
{
    return preg_match('/^\d{11}$/', $telefono);
}

// Verifica que un correo electronico tenga formato valido
function validarCorreo($correo)
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

// Verifica que un campo de texto no este vacio
function validarRequerido($valor)
{
    return !empty(trim($valor));
}

// Verifica que un valor sea un numero decimal positivo
function validarDecimalPositivo($valor)
{
    return is_numeric($valor) && $valor > 0;
}

// Verifica que una fecha tenga formato valido (YYYY-MM-DD)
function validarFecha($fecha)
{
    $partes = explode('-', $fecha);
    if (count($partes) !== 3) {
        return false;
    }
    return checkdate((int)$partes[1], (int)$partes[2], (int)$partes[0]);
}

// Verifica la longitud maxima de un texto
function validarLongitudMaxima($valor, $maximo)
{
    return strlen(trim($valor)) <= $maximo;
}