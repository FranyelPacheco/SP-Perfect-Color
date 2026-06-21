<?php
// ARCHIVO: respuestaHelper.php
// OBJETIVO: Estandarizar las respuestas JSON devueltas por los controladores

namespace App\Helpers;

// FUNCIÓN: respuestaJson
// OBJETIVO: Enviar una respuesta JSON estandarizada al cliente y finalizar la ejecución
// NOTA: Incluye cabeceras anti-cache y soporta UTF-8
function respuestaJson($estado, $mensaje, $datos = null)
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $respuesta = [
        'estado' => $estado,
        'mensaje' => $mensaje,
        'datos' => $datos
    ];

    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
