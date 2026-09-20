<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ModuloModel extends ModeloBase
{
    private ?int $id_modulo = null;
    private ?string $codigo = null;
    private ?string $nombre = null;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD mediante ModeloBase
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los módulos registrados en el sistema
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT id_modulo, codigo, nombre, descripcion FROM modulos ORDER BY id_modulo ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un módulo por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id_modulo = $id;
        if ($this->id_modulo < 1) return false;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT id_modulo, codigo, nombre, descripcion FROM modulos WHERE id_modulo = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_modulo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarPorCodigo
    // OBJETIVO: Busca un módulo por su código canónico (ej: 'cliente', 'presupuesto')
    public function buscarPorCodigo(string $codigo): array|false
    {
        $this->codigo = trim($codigo);
        if ($this->codigo === '') return false;
        return $this->_ejecutarSelectByCodigo();
    }

    private function _ejecutarSelectByCodigo(): array|false
    {
        $consulta = "SELECT id_modulo, codigo, nombre, descripcion FROM modulos WHERE codigo = :codigo LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: obtenerIdsPorCodigos
    // OBJETIVO: Dado un arreglo de códigos de módulos, devuelve sus IDs correspondientes
    public function obtenerIdsPorCodigos(array $codigos): array
    {
        if (empty($codigos)) return [];
        $placeholders = implode(',', array_fill(0, count($codigos), '?'));
        $consulta = "SELECT codigo, id_modulo FROM modulos WHERE codigo IN ($placeholders)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute(array_values($codigos));
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
