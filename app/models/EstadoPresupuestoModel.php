<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class EstadoPresupuestoModel extends ModeloBase
{
    private ?int $id_estado_presupuesto = null;
    private ?string $nombre = null;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los estados de presupuesto disponibles
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT id_estado_presupuesto, nombre FROM estado_presupuesto ORDER BY id_estado_presupuesto ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un estado de presupuesto por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id_estado_presupuesto = $id;
        if ($this->id_estado_presupuesto < 1) return false;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT id_estado_presupuesto, nombre FROM estado_presupuesto WHERE id_estado_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_estado_presupuesto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarPorNombre
    // OBJETIVO: Busca un estado de presupuesto por su nombre
    public function buscarPorNombre(string $nombre): array|false
    {
        $this->nombre = trim($nombre);
        if ($this->nombre === '') return false;
        return $this->_ejecutarSelectByNombre();
    }

    private function _ejecutarSelectByNombre(): array|false
    {
        $consulta = "SELECT id_estado_presupuesto, nombre FROM estado_presupuesto WHERE LOWER(nombre) = LOWER(:nombre)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
}
