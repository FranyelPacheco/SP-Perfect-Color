<?php
// ARCHIVO: validacionHelper.php
// OBJETIVO: Funciones globales de validación delegando en ValidacionTrait (mismas reglas que usan los modelos)

namespace App\Helpers;

use App\Traits\ValidacionTrait;

// CLASE: ValidadorManager
// OBJETIVO: Contenedor de servicio que implementa ValidacionTrait para las funciones del helper
class ValidadorManager
{
    use ValidacionTrait;
}

// FUNCIÓN: obtenerValidador
// OBJETIVO: Retorna una instancia única del validador
function obtenerValidador(): ValidadorManager
{
    static $instancia = null;
    if ($instancia === null) {
        $instancia = new ValidadorManager();
    }
    return $instancia;
}

// FUNCIÓN: validarCorreo
// OBJETIVO: Verificar que un correo electrónico tenga una estructura válida
function validarCorreo($correo)
{
    return obtenerValidador()->validarCorreo($correo);
}

// FUNCIÓN: validarRequerido
// OBJETIVO: Verificar que un campo de texto no esté vacío después de eliminar espacios
function validarRequerido($valor)
{
    return obtenerValidador()->validarRequerido($valor);
}

// FUNCIÓN: validarFecha
// OBJETIVO: Verificar que una fecha tenga formato YYYY-MM-DD y sea una fecha real del calendario
function validarFecha($fecha)
{
    return obtenerValidador()->validarFecha($fecha);
}

// FUNCIÓN: validarClave
// OBJETIVO: Verificar que la contraseña tenga entre 6 y 20 caracteres y no contenga caracteres de inyección
function validarClave(string $clave, ?string &$error = null): bool
{
    return obtenerValidador()->validarClave($clave, $error);
}

// FUNCIÓN: validarTextoSeguro
// OBJETIVO: Validar un texto libre (longitud y caracteres peligrosos); devuelve el mensaje de error o null
function validarTextoSeguro(string $valor, int $maximo, string $etiqueta, bool $obligatorio = true): ?string
{
    return obtenerValidador()->validarTextoSeguro($valor, $maximo, $etiqueta, $obligatorio);
}
