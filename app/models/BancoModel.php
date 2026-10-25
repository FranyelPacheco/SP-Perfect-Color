<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class BancoModel extends ModeloBase
{
    // === PROPIEDADES PRIVADAS DE LA ENTIDAD ===
    private ?int $id_banco;
    private ?string $nombre;
    private ?int $activo;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los bancos ordenados por nombre
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT id_banco, nombre, estado, estado as activo FROM banco ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un banco por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id_banco = $id;
        if ($this->id_banco < 1) return false;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT id_banco, nombre, estado, estado as activo FROM banco WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_banco, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: validarDatosBanco
    // OBJETIVO: Valida el nombre del banco y sus restricciones de longitud y caracteres
    public function validarDatosBanco(string $nombre): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return ['valido' => false, 'mensaje' => 'El nombre del banco es obligatorio'];
        }
        if (mb_strlen($nombre) > 50) {
            return ['valido' => false, 'mensaje' => 'El nombre del banco no puede superar los 50 caracteres'];
        }
        if (preg_match('/[\'"`;\\\\<>]/', $nombre)) {
            return ['valido' => false, 'mensaje' => 'El nombre del banco contiene caracteres no permitidos'];
        }
        return ['valido' => true, 'nombre' => $nombre];
    }

    // FUNCIÓN: registrar
    // OBJETIVO: Valida y registra o reactiva un banco de forma limpia
    public function registrar(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $val = $this->validarDatosBanco($nombre);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        $nombre = $val['nombre'];
        if ($this->buscarDuplicado('banco', 'nombre', 'id_banco', $nombre)['activo'] ?? false) {
            return ['exito' => false, 'mensaje' => 'Ya existe un banco con este nombre'];
        }
        $inactivoId = $this->buscarInactivoPorNombre($nombre);
        if ($inactivoId) {
            if ($this->actualizar($inactivoId, $nombre, 1)) {
                return ['exito' => true, 'mensaje' => 'Banco reactivado exitosamente'];
            }
            return ['exito' => false, 'mensaje' => 'Error al reactivar el banco'];
        }

        if ($this->insertar($nombre)) {
            return ['exito' => true, 'mensaje' => 'Banco creado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al crear el banco'];
    }

    // FUNCIÓN: modificar
    // OBJETIVO: Valida y actualiza los datos de un banco existente
    public function modificar(int $id, array $datos): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de banco no valido'];
        }
        $nombre = trim($datos['nombre'] ?? '');
        $activo = intval($datos['activo'] ?? 1);

        $val = $this->validarDatosBanco($nombre);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        if ($this->buscarDuplicado('banco', 'nombre', 'id_banco', $val['nombre'], $id, true) !== null) {
            return ['exito' => false, 'mensaje' => 'Ya existe un banco con este nombre'];
        }

        if ($this->actualizar($id, $val['nombre'], $activo)) {
            return ['exito' => true, 'mensaje' => 'Banco actualizado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al actualizar el banco'];
    }

    // FUNCIÓN: darDeBaja
    // OBJETIVO: Soft-delete seguro de un banco
    public function darDeBaja(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de banco no valido'];
        }
        if ($this->eliminar($id)) {
            return ['exito' => true, 'mensaje' => 'Banco eliminado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al eliminar el banco'];
    }

    // FUNCIÓN: cambiarEstado
    // OBJETIVO: Alterna estado activo/inactivo de un banco
    public function cambiarEstado(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de banco no valido'];
        }
        if ($this->toggleActivo($id)) {
            return ['exito' => true, 'mensaje' => 'Estado cambiado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al cambiar el estado'];
    }

    // FUNCIÓN: insertar
    // OBJETIVO: Crea un nuevo banco con estado=1
    public function insertar(string $nombre): bool
    {
        $this->nombre = $nombre;
        if ($this->nombre === '') return false;
        return $this->_ejecutarInsert();
    }

    private function _ejecutarInsert(): bool
    {
        $consulta = "INSERT INTO banco (nombre, estado) VALUES (:nombre, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizar
    // OBJETIVO: Actualiza nombre y estado de un banco existente
    public function actualizar(int $id, string $nombre, int $activo): bool
    {
        $this->id_banco = $id;
        $this->nombre = $nombre;
        $this->activo = $activo;
        if ($this->id_banco < 1 || $this->nombre === '') return false;
        return $this->_ejecutarUpdate();
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE banco SET nombre = :nombre, estado = :activo WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id_banco, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminar
    // OBJETIVO: Soft-delete — marca estado=0 en vez de borrar
    public function eliminar(int $id): bool
    {
        $this->id_banco = $id;
        if ($this->id_banco < 1) return false;
        return $this->_ejecutarDelete();
    }

    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE banco SET estado = 0 WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_banco, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: toggleActivo
    // OBJETIVO: Alterna estado entre 0 y 1 (bit flip)
    public function toggleActivo(int $id): bool
    {
        $this->id_banco = $id;
        if ($this->id_banco < 1) return false;
        return $this->_ejecutarToggleActivo();
    }

    private function _ejecutarToggleActivo(): bool
    {
        $consulta = "UPDATE banco SET estado = 1 - estado WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_banco, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: buscarInactivoPorNombre
    // OBJETIVO: Busca un banco inactivo por nombre exacto para reactivación
    public function buscarInactivoPorNombre(string $nombre): int|false
    {
        $this->nombre = $nombre;
        if ($this->nombre === '') return false;
        return $this->_ejecutarBuscarInactivoPorNombre();
    }

    private function _ejecutarBuscarInactivoPorNombre(): int|false
    {
        $consulta = "SELECT id_banco FROM banco WHERE nombre = :nombre AND estado = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_banco'] : false;
    }
}
