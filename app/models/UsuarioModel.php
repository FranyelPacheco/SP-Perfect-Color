<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class UsuarioModel extends ModeloBase
{
    private int $id_usuario;
    private string $nombre;
    private string $correo;
    private string $password_hash;
    private int $id_rol;
    private int $activo;
    private int $id;
    private ?int $idExcluir;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: buscarPorCorreo
    // OBJETIVO: Busca un usuario activo por su correo electrónico
    // NOTA: Usado en el login para autenticación
    public function buscarPorCorreo(string $correo): array|false
    {
        $this->correo = $correo;
        return $this->_ejecutarSelectByCorreo();
    }

    // FUNCIÓN: _ejecutarSelectByCorreo
    // OBJETIVO: Ejecuta la consulta de búsqueda por correo
    private function _ejecutarSelectByCorreo(): array|false
    {
        $consulta = "SELECT * FROM usuarios WHERE correo = :correo AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un usuario activo por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la consulta de búsqueda por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT * FROM usuarios WHERE id_usuario = :id AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los usuarios activos con el nombre del rol asociado
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista usuarios con JOIN a roles
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON u.id_rol = r.id_rol
                     WHERE u.activo = 1
                     ORDER BY u.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarRoles
    // OBJETIVO: Retorna todos los roles disponibles en el sistema
    public function listarRoles(): array
    {
        return $this->_ejecutarSelectRoles();
    }

    // FUNCIÓN: _ejecutarSelectRoles
    // OBJETIVO: Ejecuta la consulta de roles ordenados por ID
    private function _ejecutarSelectRoles(): array
    {
        $consulta = "SELECT * FROM roles ORDER BY id_rol ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: insertarUsuario
    // OBJETIVO: Inserta un nuevo usuario con nombre, correo, contraseña hash y rol
    public function insertarUsuario(string $nombre, string $correo, string $passwordHash, int $idRol, int $activo = 1): bool
    {
        $this->nombre = $nombre;
        $this->correo = $correo;
        $this->password_hash = $passwordHash;
        $this->id_rol = $idRol;
        $this->activo = $activo;
        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta el INSERT del usuario en la tabla usuarios
    private function _ejecutarInsert(): bool
    {
        $consulta = "INSERT INTO usuarios (nombre, correo, password_hash, id_rol, activo)
                     VALUES (:nombre, :correo, :password_hash, :id_rol, :activo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':id_rol', $this->id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizarUsuario
    // OBJETIVO: Actualiza los datos de un usuario (nombre, correo, rol, activo)
    // NOTA: No actualiza la contraseña; usar actualizarClave para eso
    public function actualizarUsuario(int $id, string $nombre, string $correo, int $idRol, int $activo): bool
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->correo = $correo;
        $this->id_rol = $idRol;
        $this->activo = $activo;
        return $this->_ejecutarUpdate();
    }

    // FUNCIÓN: _ejecutarUpdate
    // OBJETIVO: Ejecuta el UPDATE de datos generales del usuario
    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE usuarios SET nombre = :nombre, correo = :correo, id_rol = :id_rol, activo = :activo WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':id_rol', $this->id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizarClave
    // OBJETIVO: Actualiza únicamente la contraseña de un usuario
    public function actualizarClave(int $id, string $passwordHash): bool
    {
        $this->id = $id;
        $this->password_hash = $passwordHash;
        return $this->_ejecutarUpdateClave();
    }

    // FUNCIÓN: _ejecutarUpdateClave
    // OBJETIVO: Ejecuta el UPDATE exclusivo del campo password_hash
    private function _ejecutarUpdateClave(): bool
    {
        $consulta = "UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminarUsuario
    // OBJETIVO: Desactiva un usuario (soft delete)
    // NOTA: No permite eliminar el último administrador activo (rol = 1)
    public function eliminarUsuario(int $id): bool
    {
        $this->id = $id;
        $usuario = $this->buscarPorId($id);
        if ($usuario && $usuario['id_rol'] == 1) {
            $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 1 AND activo = 1";
            $stmt = $this->conexion->query($consulta);
            $totalAdmins = $stmt->fetch()['total'];
            if ($totalAdmins <= 1) return false;
        }
        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el UPDATE que marca el usuario como inactivo
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE usuarios SET activo = 0 WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: correoExiste
    // OBJETIVO: Verifica si un correo ya está registrado entre usuarios activos
    public function correoExiste(string $correo, ?int $idExcluir = null): bool
    {
        $this->correo = $correo;
        $this->idExcluir = $idExcluir;
        return $this->_ejecutarCheckCorreo();
    }

    // FUNCIÓN: _ejecutarCheckCorreo
    // OBJETIVO: Ejecuta la consulta COUNT para verificar unicidad del correo
    private function _ejecutarCheckCorreo(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE correo = :correo AND activo = 1";
        if ($this->idExcluir !== null) {
            $consulta .= " AND id_usuario != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        if ($this->idExcluir !== null) {
            $stmt->bindParam(':id', $this->idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }

    // FUNCIÓN: buscarInactivoPorCorreo
    // OBJETIVO: Busca un usuario inactivo por correo para reactivación
    // NOTA: Usado antes de insertar para evitar duplicados por soft delete
    public function buscarInactivoPorCorreo(string $correo): int|false
    {
        if (empty($correo)) return false;
        $this->correo = $correo;
        return $this->_ejecutarBuscarInactivo();
    }

    // FUNCIÓN: _ejecutarBuscarInactivo
    // OBJETIVO: Retorna el ID de un usuario inactivo con el correo dado, o false si no existe
    private function _ejecutarBuscarInactivo(): int|false
    {
        $consulta = "SELECT id_usuario FROM usuarios WHERE correo = :correo AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_usuario'] : false;
    }
}
