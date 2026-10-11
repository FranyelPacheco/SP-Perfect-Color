<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class EstadoVentaModel extends ModeloBase
{
    private ?int $id_estado_venta = null;
    private ?string $nombre = null;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los estados de venta disponibles
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT id_estado_venta, nombre 
                     FROM estado_venta 
                     WHERE estado = 1 
                     ORDER BY id_estado_venta ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un estado de venta por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id_estado_venta = $id;
        if ($this->id_estado_venta < 1) return false;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT id_estado_venta, nombre 
                     FROM estado_venta 
                     WHERE id_estado_venta = :id AND estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_estado_venta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarPorNombre
    // OBJETIVO: Busca un estado de venta por su nombre
    public function buscarPorNombre(string $nombre): array|false
    {
        $this->nombre = trim($nombre);
        if ($this->nombre === '') return false;
        return $this->_ejecutarSelectByNombre();
    }

    private function _ejecutarSelectByNombre(): array|false
    {
        $consulta = "SELECT id_estado_venta, nombre 
                     FROM estado_venta 
                     WHERE LOWER(nombre) = LOWER(:nombre) AND estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
}
