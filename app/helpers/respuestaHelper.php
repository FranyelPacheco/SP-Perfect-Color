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

// FUNCIÓN: soloPost
// OBJETIVO: Cortar la petición con error JSON si no es POST
function soloPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
}

// FUNCIÓN: responder
// OBJETIVO: Traducir un resultado ['exito' => bool, 'mensaje' => string] del modelo a respuesta JSON
function responder(array $resultado, $datos = null): void
{
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $datos);
}

// FUNCIÓN: responderYContinuar
// OBJETIVO: Enviar la respuesta JSON al navegador, cerrar la conexión y dejar que el script siga (tareas lentas como enviar correos)
function responderYContinuar($estado, $mensaje, $datos = null): void
{
    ignore_user_abort(true);
    set_time_limit(60);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $cuerpo = json_encode(['estado' => $estado, 'mensaje' => $mensaje, 'datos' => $datos], JSON_UNESCAPED_UNICODE);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Connection: close');
    header('Content-Length: ' . strlen($cuerpo));
    echo $cuerpo;
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}
