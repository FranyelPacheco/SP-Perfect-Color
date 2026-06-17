<?php
// Archivo: respuestaHelper.php
// Helper para estandarizar las respuestas JSON del servidor

namespace App\Helpers;

function respuestaJson($estado, $mensaje, $datos = null)
{
    // Establecer el tipo de contenido como JSON con no-cache
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Construir la estructura de respuesta estandarizada
    $respuesta = [
        'estado' => $estado,
        'mensaje' => $mensaje,
        'datos' => $datos
    ];
    
    // Enviar la respuesta y finalizar la ejecucion
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}