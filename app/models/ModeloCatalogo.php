<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

// OBJETIVO: Lógica común de los catálogos simples (nombre único + borrado lógico): Banco y TipoPago
abstract class ModeloCatalogo extends ModeloBase
{
    protected string $tabla;
    protected string $pk;
    protected string $etiqueta;
    protected string $etiquetaArticulo = 'el';

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los registros del catálogo ordenados por nombre
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        return $this->conexion->query("SELECT {$this->pk}, nombre, estado, estado as activo FROM {$this->tabla} ORDER BY nombre ASC")->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un registro por su ID
    public function buscarPorId(int $id): array|false
    {
        if ($id < 1) return false;
        return $this->_ejecutarSelectById($id);
    }

    private function _ejecutarSelectById(int $id): array|false
    {
        $stmt = $this->conexion->prepare("SELECT {$this->pk}, nombre, estado, estado as activo FROM {$this->tabla} WHERE {$this->pk} = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    protected function validarNombre(string $nombre): array
    {
        $nombre = trim($nombre);
        $error = $this->validarTextoSeguro($nombre, 50, "El nombre del {$this->etiqueta}");
        if ($error !== null) {
            return ['valido' => false, 'mensaje' => $error];
        }
        return ['valido' => true, 'nombre' => $nombre];
    }

    public function registrar(array $datos): array
    {
        $val = $this->validarNombre($datos['nombre'] ?? '');
        if (!$val['valido']) {
            return $this->respuestaError($val['mensaje']);
        }

        $nombre = $val['nombre'];
        if ($this->verificarUnico($this->tabla, 'nombre', $this->pk, $nombre)['activo'] ?? false) {
            return $this->respuestaError("Ya existe un {$this->etiqueta} con este nombre");
        }

        $inactivoId = $this->buscarInactivoPorNombre($nombre);
        if ($inactivoId) {
            return $this->actualizar($inactivoId, $nombre, 1)
                ? $this->respuestaExito(ucfirst($this->etiqueta) . ' reactivado exitosamente')
                : $this->respuestaError("Error al reactivar {$this->etiquetaArticulo} {$this->etiqueta}");
        }

        return $this->insertar($nombre)
            ? $this->respuestaExito(ucfirst($this->etiqueta) . ' creado exitosamente')
            : $this->respuestaError("Error al crear {$this->etiquetaArticulo} {$this->etiqueta}");
    }

    public function modificar(int $id, array $datos): array
    {
        if ($id < 1) {
            return $this->respuestaError("ID de {$this->etiqueta} no valido");
        }
        $val = $this->validarNombre($datos['nombre'] ?? '');
        if (!$val['valido']) {
            return $this->respuestaError($val['mensaje']);
        }
        if ($this->verificarUnico($this->tabla, 'nombre', $this->pk, $val['nombre'], $id, true) !== null) {
            return $this->respuestaError("Ya existe un {$this->etiqueta} con este nombre");
        }

        return $this->actualizar($id, $val['nombre'], intval($datos['activo'] ?? 1))
            ? $this->respuestaExito(ucfirst($this->etiqueta) . ' actualizado exitosamente')
            : $this->respuestaError("Error al actualizar {$this->etiquetaArticulo} {$this->etiqueta}");
    }

    public function darDeBaja(int $id): array
    {
        if ($id < 1) {
            return $this->respuestaError("ID de {$this->etiqueta} no valido");
        }
        return $this->eliminar($id)
            ? $this->respuestaExito(ucfirst($this->etiqueta) . ' eliminado exitosamente')
            : $this->respuestaError("Error al eliminar {$this->etiquetaArticulo} {$this->etiqueta}");
    }

    public function cambiarEstado(int $id): array
    {
        if ($id < 1) {
            return $this->respuestaError("ID de {$this->etiqueta} no valido");
        }
        return $this->toggleActivo($id)
            ? $this->respuestaExito('Estado cambiado exitosamente')
            : $this->respuestaError('Error al cambiar el estado');
    }

    // FUNCIÓN: insertar
    // OBJETIVO: Crea un registro con estado=1
    public function insertar(string $nombre): bool
    {
        if ($nombre === '') return false;
        return $this->_ejecutarInsert($nombre);
    }

    private function _ejecutarInsert(string $nombre): bool
    {
        return $this->conexion->prepare("INSERT INTO {$this->tabla} (nombre, estado) VALUES (:nombre, 1)")->execute([':nombre' => $nombre]);
    }

    // FUNCIÓN: actualizar
    // OBJETIVO: Actualiza nombre y estado de un registro existente
    public function actualizar(int $id, string $nombre, int $activo): bool
    {
        if ($id < 1 || $nombre === '') return false;
        return $this->_ejecutarUpdate($id, $nombre, $activo);
    }

    private function _ejecutarUpdate(int $id, string $nombre, int $activo): bool
    {
        return $this->conexion->prepare("UPDATE {$this->tabla} SET nombre = :nombre, estado = :activo WHERE {$this->pk} = :id")
            ->execute([':nombre' => $nombre, ':activo' => $activo, ':id' => $id]);
    }

    // FUNCIÓN: eliminar
    // OBJETIVO: Soft-delete: marca estado=0 en vez de borrar
    public function eliminar(int $id): bool
    {
        if ($id < 1) return false;
        return $this->_ejecutarDelete($id);
    }

    private function _ejecutarDelete(int $id): bool
    {
        return $this->conexion->prepare("UPDATE {$this->tabla} SET estado = 0 WHERE {$this->pk} = :id")->execute([':id' => $id]);
    }

    // FUNCIÓN: toggleActivo
    // OBJETIVO: Alterna el estado entre 0 y 1
    public function toggleActivo(int $id): bool
    {
        if ($id < 1) return false;
        return $this->_ejecutarToggleActivo($id);
    }

    private function _ejecutarToggleActivo(int $id): bool
    {
        return $this->conexion->prepare("UPDATE {$this->tabla} SET estado = 1 - estado WHERE {$this->pk} = :id")->execute([':id' => $id]);
    }

    // FUNCIÓN: buscarInactivoPorNombre
    // OBJETIVO: Busca un registro inactivo por nombre exacto para reactivarlo
    public function buscarInactivoPorNombre(string $nombre): int|false
    {
        return $this->buscarInactivoPor($this->tabla, $this->pk, 'nombre', $nombre);
    }

}
