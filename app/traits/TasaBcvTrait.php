<?php
declare(strict_types=1);

namespace App\Traits;

// TRAIT: TasaBcvTrait
// OBJETIVO: Fragmento SQL único para obtener la tasa Bs/$ vigente en la fecha de un documento
trait TasaBcvTrait
{
    // FUNCIÓN: sqlTasaBcv
    // OBJETIVO: Devuelve "COALESCE(...) as <alias>" con la tasa del día del documento, o la última registrada, o 0
    // NOTA: $expresionFecha y $alias vienen del propio modelo (nunca de la petición)
    public function sqlTasaBcv(string $expresionFecha, string $alias = 'tasa_bcv'): string
    {
        return $this->_ejecutarSqlTasaBcv($expresionFecha, $alias);
    }

    private function _ejecutarSqlTasaBcv(string $expresionFecha, string $alias): string
    {
        return "COALESCE(
                    (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE({$expresionFecha}) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                    (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                    0
                ) as {$alias}";
    }
}
