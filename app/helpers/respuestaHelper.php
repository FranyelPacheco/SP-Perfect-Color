<?php
// Archivo: respuestaHelper.php
// Helper para estandarizar las respuestas JSON del servidor

function respuestaJson($estado, $mensaje, $datos = null)
{
    // Establecer el tipo de contenido como JSON
    header('Content-Type: application/json; charset=utf-8');
    
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