<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class TipoPagoModel extends ModeloBase
{
    // === PROPIEDADES PRIVADAS DE LA ENTIDAD ===
    private ?int $id_tipo_pago;
    private ?string $nombre;
    private ?int $activo;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los tipos de pago ordenados por nombre
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT id_tipo_pago, nombre, activo FROM tipo_pago ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un tipo de pago por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id_tipo_pago = $id;
        if ($this->id_tipo_pago < 1) return false;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT id_tipo_pago, nombre, activo FROM tipo_pago WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: insertar
    // OBJETIVO: Crea un nuevo tipo de pago con activo=1
    public function insertar(string $nombre): bool
    {
        $this->nombre = $nombre;
        if ($this->nombre === '') return false;
        return $this->_ejecutarInsert();
    }

    private function _ejecutarInsert(): bool
    {
        $consulta = "INSERT INTO tipo_pago (nombre, activo) VALUES (:nombre, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizar
    // OBJETIVO: Actualiza nombre y activo de un tipo de pago existente
    public function actualizar(int $id, string $nombre, int $activo): bool
    {
        $this->id_tipo_pago = $id;
        $this->nombre = $nombre;
        $this->activo = $activo;
        if ($this->id_tipo_pago < 1 || $this->nombre === '') return false;
        return $this->_ejecutarUpdate();
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE tipo_pago SET nombre = :nombre, activo = :activo WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminar
    // OBJETIVO: Soft-delete — marca activo=0 en vez de borrar
    public function eliminar(int $id): bool
    {
        $this->id_tipo_pago = $id;
        if ($this->id_tipo_pago < 1) return false;
        return $this->_ejecutarDelete();
    }

    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE tipo_pago SET activo = 0 WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: toggleActivo
    // OBJETIVO: Alterna activo entre 0 y 1 (bit flip)
    public function toggleActivo(int $id): bool
    {
        $this->id_tipo_pago = $id;
        if ($this->id_tipo_pago < 1) return false;
        return $this->_ejecutarToggleActivo();
    }

    private function _ejecutarToggleActivo(): bool
    {
        $consulta = "UPDATE tipo_pago SET activo = 1 - activo WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: buscarInactivoPorNombre
    // OBJETIVO: Busca un tipo de pago inactivo por nombre exacto para reactivación
    public function buscarInactivoPorNombre(string $nombre): int|false
    {
        $this->nombre = $nombre;
        if ($this->nombre === '') return false;
        return $this->_ejecutarBuscarInactivoPorNombre();
    }

    private function _ejecutarBuscarInactivoPorNombre(): int|false
    {
        $consulta = "SELECT id_tipo_pago FROM tipo_pago WHERE nombre = :nombre AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_tipo_pago'] : false;
    }
}
