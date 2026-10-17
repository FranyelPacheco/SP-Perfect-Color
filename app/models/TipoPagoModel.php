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
        $consulta = "SELECT id_tipo_pago, nombre, estado, estado as activo FROM tipo_pago ORDER BY nombre ASC";
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
        $consulta = "SELECT id_tipo_pago, nombre, estado, estado as activo FROM tipo_pago WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: validarDatosTipoPago
    // OBJETIVO: Valida el nombre del tipo de pago
    public function validarDatosTipoPago(string $nombre): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['valido' => false, 'mensaje' => 'El nombre del tipo de pago es obligatorio'];
        }
        if (mb_strlen($nombre) > 50) {
            return ['valido' => false, 'mensaje' => 'El nombre del tipo de pago no puede superar los 50 caracteres'];
        }
        if (preg_match('/[\'"`;\\\\<>]/', $nombre)) {
            return ['valido' => false, 'mensaje' => 'El nombre del tipo de pago contiene caracteres no permitidos'];
        }
        return ['valido' => true, 'nombre' => $nombre];
    }

    // FUNCIÓN: registrar
    // OBJETIVO: Valida y registra o reactiva un tipo de pago
    public function registrar(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $val = $this->validarDatosTipoPago($nombre);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        $nombre = $val['nombre'];
        $inactivoId = $this->buscarInactivoPorNombre($nombre);
        if ($inactivoId) {
            if ($this->actualizar($inactivoId, $nombre, 1)) {
                return ['exito' => true, 'mensaje' => 'Tipo de pago reactivado exitosamente'];
            }
            return ['exito' => false, 'mensaje' => 'Error al reactivar el tipo de pago'];
        }

        if ($this->insertar($nombre)) {
            return ['exito' => true, 'mensaje' => 'Tipo de pago creado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al crear el tipo de pago'];
    }

    // FUNCIÓN: modificar
    // OBJETIVO: Valida y actualiza los datos de un tipo de pago
    public function modificar(int $id, array $datos): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de tipo de pago no valido'];
        }
        $nombre = trim($datos['nombre'] ?? '');
        $activo = intval($datos['activo'] ?? 1);

        $val = $this->validarDatosTipoPago($nombre);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        if ($this->actualizar($id, $val['nombre'], $activo)) {
            return ['exito' => true, 'mensaje' => 'Tipo de pago actualizado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al actualizar el tipo de pago'];
    }

    // FUNCIÓN: darDeBaja
    // OBJETIVO: Soft-delete de un tipo de pago
    public function darDeBaja(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de tipo de pago no valido'];
        }
        if ($this->eliminar($id)) {
            return ['exito' => true, 'mensaje' => 'Tipo de pago eliminado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al eliminar el tipo de pago'];
    }

    // FUNCIÓN: cambiarEstado
    // OBJETIVO: Alterna estado activo/inactivo
    public function cambiarEstado(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de tipo de pago no valido'];
        }
        if ($this->toggleActivo($id)) {
            return ['exito' => true, 'mensaje' => 'Estado cambiado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al cambiar el estado'];
    }

    // FUNCIÓN: insertar
    // OBJETIVO: Crea un nuevo tipo de pago con estado=1
    public function insertar(string $nombre): bool
    {
        $this->nombre = $nombre;
        if ($this->nombre === '') return false;
        return $this->_ejecutarInsert();
    }

    private function _ejecutarInsert(): bool
    {
        $consulta = "INSERT INTO tipo_pago (nombre, estado) VALUES (:nombre, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizar
    // OBJETIVO: Actualiza nombre y estado de un tipo de pago existente
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
        $consulta = "UPDATE tipo_pago SET nombre = :nombre, estado = :activo WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminar
    // OBJETIVO: Soft-delete — marca estado=0 en vez de borrar
    public function eliminar(int $id): bool
    {
        $this->id_tipo_pago = $id;
        if ($this->id_tipo_pago < 1) return false;
        return $this->_ejecutarDelete();
    }

    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE tipo_pago SET estado = 0 WHERE id_tipo_pago = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_tipo_pago, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: toggleActivo
    // OBJETIVO: Alterna estado entre 0 y 1 (bit flip)
    public function toggleActivo(int $id): bool
    {
        $this->id_tipo_pago = $id;
        if ($this->id_tipo_pago < 1) return false;
        return $this->_ejecutarToggleActivo();
    }

    private function _ejecutarToggleActivo(): bool
    {
        $consulta = "UPDATE tipo_pago SET estado = 1 - estado WHERE id_tipo_pago = :id";
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
        $consulta = "SELECT id_tipo_pago FROM tipo_pago WHERE nombre = :nombre AND estado = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_tipo_pago'] : false;
    }
}
