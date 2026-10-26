<?php
declare(strict_types=1);

namespace App\Traits;

use PDO;

// TRAIT: ReactivacionTrait
// OBJETIVO: Búsqueda única de registros dados de baja (estado = 0) para reactivarlos en lugar de duplicarlos
trait ReactivacionTrait
{
    // FUNCIÓN: buscarInactivoPor
    // OBJETIVO: Devuelve el ID del registro inactivo cuya columna coincide con el valor, o false
    // NOTA: $tabla, $pk y $columna los fija cada modelo (nunca vienen de la petición)
    protected function buscarInactivoPor(string $tabla, string $pk, string $columna, string $valor, bool $ignorarMayusculas = false): int|false
    {
        $valor = trim($valor);
        if ($valor === '') return false;
        return $this->_ejecutarBuscarInactivoPor($tabla, $pk, $columna, $valor, $ignorarMayusculas);
    }

    private function _ejecutarBuscarInactivoPor(string $tabla, string $pk, string $columna, string $valor, bool $ignorarMayusculas): int|false
    {
        $condicion = $ignorarMayusculas ? "LOWER({$columna}) = LOWER(:valor)" : "{$columna} = :valor";
        $stmt = $this->conexion->prepare("SELECT {$pk} FROM {$tabla} WHERE {$condicion} AND estado = 0 LIMIT 1");
        $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id === false ? false : (int)$id;
    }
}
