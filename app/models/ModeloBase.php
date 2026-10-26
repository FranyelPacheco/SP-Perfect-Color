<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ConexionBD;
use App\Traits\ReactivacionTrait;
use App\Traits\RespuestaModeloTrait;
use App\Traits\ValidacionTrait;
use PDO;

abstract class ModeloBase
{
    use ValidacionTrait;
    use RespuestaModeloTrait;
    use ReactivacionTrait;

    protected PDO $conexion;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD vía ConexionBD singleton
    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // FUNCIÓN: buscarDuplicado
    // OBJETIVO: Busca un valor en una columna única; devuelve el estado de la fila coincidente (1 activo, 0 inactivo) o null
    // NOTA: $tabla, $columna y $pk deben venir de una lista blanca, nunca de la petición
    private function _ejecutarBuscarDuplicado(string $tabla, string $columna, string $pk, string $valor, ?int $idExcluir = null, bool $ignorarEstado = false): ?array
    {
        $sql = "SELECT $pk AS id, estado FROM $tabla WHERE LOWER($columna) = LOWER(:valor)"
            . ($idExcluir !== null ? " AND $pk != :id" : '')
            . " ORDER BY estado DESC LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':valor', $valor);
        if ($idExcluir !== null) {
            $stmt->bindValue(':id', $idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            return null;
        }
        return ['id' => (int)$fila['id'], 'activo' => $ignorarEstado || (int)$fila['estado'] === 1];
    }

    // FUNCIÓN: verificarUnico
    // OBJETIVO: Punto público de validación en vivo para campos únicos
    public function verificarUnico(string $tabla, string $columna, string $pk, string $valor, ?int $idExcluir = null, bool $ignorarEstado = false): ?array
    {
        return $this->_ejecutarBuscarDuplicado($tabla, $columna, $pk, $valor, $idExcluir, $ignorarEstado);
    }
}
