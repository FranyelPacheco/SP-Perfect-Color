<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class RolModel extends ModeloBase
{
    private int $id_rol;
    private string $nombre;
    private int $activo;
    private array $modulosArray = [];
    private ?int $idExcluir;

    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Retorna todos los roles con su estado, módulos (desde rol_modulo) y total de usuarios activos asociados
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT r.id_rol, r.nombre, r.activo, r.created_at,
                            (SELECT GROUP_CONCAT(m.codigo ORDER BY m.id_modulo SEPARATOR ',') 
                             FROM rol_modulo rm 
                             JOIN modulos m ON rm.id_modulo = m.id_modulo 
                             WHERE rm.id_rol = r.id_rol) as modulos,
                            (SELECT COUNT(*) FROM usuarios u WHERE u.id_rol = r.id_rol AND u.activo = 1) as total_usuarios
                     FROM roles r 
                     WHERE r.activo = 1
                     ORDER BY r.id_rol ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarActivos
    // OBJETIVO: Retorna únicamente los roles activos para poblar el <select> en usuarios
    public function listarActivos(): array
    {
        return $this->_ejecutarSelectActivos();
    }

    private function _ejecutarSelectActivos(): array
    {
        $consulta = "SELECT r.id_rol, r.nombre,
                            (SELECT GROUP_CONCAT(m.codigo ORDER BY m.id_modulo SEPARATOR ',') 
                             FROM rol_modulo rm 
                             JOIN modulos m ON rm.id_modulo = m.id_modulo 
                             WHERE rm.id_rol = r.id_rol) as modulos
                     FROM roles r 
                     WHERE r.activo = 1 
                     ORDER BY r.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un rol por su clave primaria e incluye sus módulos asignados
    public function buscarPorId(int $id): array|false
    {
        $this->id_rol = $id;
        return $this->_ejecutarSelectById();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT r.id_rol, r.nombre, r.activo, r.created_at,
                            (SELECT GROUP_CONCAT(m.codigo ORDER BY m.id_modulo SEPARATOR ',') 
                             FROM rol_modulo rm 
                             JOIN modulos m ON rm.id_modulo = m.id_modulo 
                             WHERE rm.id_rol = r.id_rol) as modulos
                     FROM roles r 
                     WHERE r.id_rol = :id 
                     LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_rol, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: insertarRol
    // OBJETIVO: Registra un nuevo rol y asocia sus módulos en la tabla relacional rol_modulo
    public function insertarRol(string $nombre, array|string $modulos, int $activo = 1): bool
    {
        $this->nombre = trim($nombre);
        $this->modulosArray = is_array($modulos) 
            ? array_values(array_filter(array_map('trim', $modulos))) 
            : array_values(array_filter(array_map('trim', explode(',', $modulos))));
        $this->activo = $activo;
        return $this->_ejecutarInsert();
    }

    private function _ejecutarInsert(): bool
    {
        $this->conexion->beginTransaction();
        try {
            $consulta = "INSERT INTO roles (nombre, activo) VALUES (:nombre, :activo)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
            $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
            $stmt->execute();
            $this->id_rol = (int)$this->conexion->lastInsertId();

            $this->_sincronizarModulos();

            $this->conexion->commit();
            return true;
        } catch (\Throwable $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    // FUNCIÓN: actualizarRol
    // OBJETIVO: Actualiza el nombre, estado y sincroniza los módulos de un rol (protege Administrador)
    public function actualizarRol(int $id, string $nombre, int $activo, array|string $modulos): bool
    {
        $this->id_rol = $id;
        $this->nombre = trim($nombre);
        $this->activo = ($id === 1) ? 1 : $activo; // Administrador siempre activo
        $this->modulosArray = is_array($modulos) 
            ? array_values(array_filter(array_map('trim', $modulos))) 
            : array_values(array_filter(array_map('trim', explode(',', $modulos))));
        return $this->_ejecutarUpdate();
    }

    private function _ejecutarUpdate(): bool
    {
        $this->conexion->beginTransaction();
        try {
            $consulta = "UPDATE roles SET nombre = :nombre, activo = :activo WHERE id_rol = :id";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
            $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
            $stmt->bindParam(':id', $this->id_rol, PDO::PARAM_INT);
            $stmt->execute();

            $this->_sincronizarModulos();

            $this->conexion->commit();
            return true;
        } catch (\Throwable $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    // FUNCIÓN: _sincronizarModulos
    // OBJETIVO: Sincroniza las asignaciones de módulos en la tabla rol_modulo para el rol actual
    private function _sincronizarModulos(): void
    {
        // 1. Eliminar asignaciones previas
        $delConsulta = "DELETE FROM rol_modulo WHERE id_rol = :id";
        $stmtDel = $this->conexion->prepare($delConsulta);
        $stmtDel->bindParam(':id', $this->id_rol, PDO::PARAM_INT);
        $stmtDel->execute();

        if (empty($this->modulosArray)) {
            return;
        }

        // 2. Buscar IDs correspondientes a los códigos de módulo seleccionados
        $placeholders = implode(',', array_fill(0, count($this->modulosArray), '?'));
        $modConsulta = "SELECT id_modulo FROM modulos WHERE codigo IN ($placeholders)";
        $stmtMod = $this->conexion->prepare($modConsulta);
        $stmtMod->execute($this->modulosArray);
        $idsModulos = $stmtMod->fetchAll(PDO::FETCH_COLUMN);

        // 3. Insertar nuevas asignaciones en rol_modulo
        if (!empty($idsModulos)) {
            $insConsulta = "INSERT INTO rol_modulo (id_rol, id_modulo) VALUES (:id_rol, :id_modulo)";
            $stmtIns = $this->conexion->prepare($insConsulta);
            foreach ($idsModulos as $idMod) {
                $stmtIns->execute([
                    ':id_rol' => $this->id_rol,
                    ':id_modulo' => (int)$idMod
                ]);
            }
        }
    }

    // FUNCIÓN: toggleActivo
    // OBJETIVO: Cambia el estado activo/inactivo de un rol (id_rol = 1 no se puede deshabilitar)
    public function toggleActivo(int $id, int $activo): bool
    {
        if ($id === 1) {
            return false; // El rol Administrador no puede ser deshabilitado
        }
        $this->id_rol = $id;
        $this->activo = $activo ? 1 : 0;
        return $this->_ejecutarToggleActivo();
    }

    private function _ejecutarToggleActivo(): bool
    {
        $consulta = "UPDATE roles SET activo = :activo WHERE id_rol = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id_rol, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: nombreExiste
    // OBJETIVO: Verifica si ya existe un rol con el mismo nombre
    public function nombreExiste(string $nombre, ?int $idExcluir = null): bool
    {
        $this->nombre = trim($nombre);
        $this->idExcluir = $idExcluir;
        return $this->_ejecutarCheckNombre();
    }

    private function _ejecutarCheckNombre(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM roles WHERE LOWER(nombre) = LOWER(:nombre) AND activo = 1";
        if ($this->idExcluir !== null) {
            $consulta .= " AND id_rol != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        if ($this->idExcluir !== null) {
            $stmt->bindParam(':id', $this->idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        $resultado = $stmt->fetch();
        return ($resultado['total'] ?? 0) > 0;
    }

    // FUNCIÓN: buscarInactivoPorNombre
    // OBJETIVO: Busca si existe un rol inactivo con el nombre dado para reactivación
    public function buscarInactivoPorNombre(string $nombre): int|false
    {
        $this->nombre = trim($nombre);
        return $this->_ejecutarBuscarInactivo();
    }

    private function _ejecutarBuscarInactivo(): int|false
    {
        $consulta = "SELECT id_rol FROM roles WHERE LOWER(nombre) = LOWER(:nombre) AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_rol'] : false;
    }

    // FUNCIÓN: eliminarRol
    // OBJETIVO: Eliminación lógica (soft-delete) de un rol (marca activo = 0)
    // NOTA: Protege el rol Administrador (id_rol = 1) y valida que no tenga usuarios activos asignados
    public function eliminarRol(int $id): bool
    {
        if ($id === 1) {
            return false;
        }
        $this->id_rol = $id;
        return $this->_ejecutarDelete();
    }

    private function _ejecutarDelete(): bool
    {
        $consultaCheck = "SELECT COUNT(*) as total FROM usuarios WHERE id_rol = :id AND activo = 1";
        $stmtCheck = $this->conexion->prepare($consultaCheck);
        $stmtCheck->bindParam(':id', $this->id_rol, PDO::PARAM_INT);
        $stmtCheck->execute();
        $conteo = $stmtCheck->fetch();
        if (($conteo['total'] ?? 0) > 0) {
            throw new \PDOException('No se puede eliminar este rol porque tiene ' . $conteo['total'] . ' usuario(s) activo(s) asignado(s). Reasigne los usuarios a otro rol primero.');
        }

        $consulta = "UPDATE roles SET activo = 0 WHERE id_rol = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id_rol, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
