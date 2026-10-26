<?php
declare(strict_types=1);

namespace App\Traits;

// TRAIT: RespuestaModeloTrait
// OBJETIVO: Formato único de resultado de los modelos (['exito', 'mensaje', 'datos']) que traduce responder() en los controladores
trait RespuestaModeloTrait
{
    // FUNCIÓN: respuestaExito
    // OBJETIVO: Resultado exitoso con mensaje y datos opcionales
    public function respuestaExito(string $mensaje, $datos = null): array
    {
        return $this->_ejecutarRespuesta(true, $mensaje, $datos);
    }

    // FUNCIÓN: respuestaError
    // OBJETIVO: Resultado fallido con mensaje
    public function respuestaError(string $mensaje): array
    {
        return $this->_ejecutarRespuesta(false, $mensaje, null);
    }

    private function _ejecutarRespuesta(bool $exito, string $mensaje, $datos): array
    {
        $respuesta = ['exito' => $exito, 'mensaje' => $mensaje];
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        return $respuesta;
    }
}
