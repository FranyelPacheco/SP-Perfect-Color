<?php
// ARCHIVO: envHelper.php
// OBJETIVO: Leer variables de entorno desde el archivo .env de la raíz (sin dependencias)

// FUNCIÓN: env
// OBJETIVO: Devolver el valor de una variable del .env (o de getenv) o el valor por defecto
function env(string $clave, $defecto = null)
{
    static $variables = null;
    if ($variables === null) {
        $variables = [];
        $ruta = __DIR__ . '/../../.env';
        if (is_readable($ruta)) {
            foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
                $linea = trim($linea);
                if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
                [$k, $v] = explode('=', $linea, 2);
                $variables[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }
    $valor = $variables[$clave] ?? getenv($clave);
    return ($valor === false || $valor === null) ? $defecto : $valor;
}
