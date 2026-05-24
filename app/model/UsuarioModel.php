<?php
namespace App\Model;

require_once __DIR__ . '/../core/conexionBD.php';

use App\Core\ConexionBD;
use PDO;

class UsuarioModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    public function buscarPorCorreo($correo)
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre 
                     FROM usuarios u 
                     LEFT JOIN roles r ON u.rol_id = r.id 
                     WHERE u.correo = :correo 
                     LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function buscarPorId($id)
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre 
                     FROM usuarios u 
                     LEFT JOIN roles r ON u.rol_id = r.id 
                     WHERE u.id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function listarTodos()
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre 
                     FROM usuarios u 
                     LEFT JOIN roles r ON u.rol_id = r.id 
                     ORDER BY u.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    public function listarRoles()
    {
        $consulta = "SELECT * FROM roles ORDER BY id ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    public function insertar($datos)
    {
        $consulta = "INSERT INTO usuarios (nombre, correo, password_hash, rol_id, activo) 
                     VALUES (:nombre, :correo, :password_hash, :rol_id, :activo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $datos['password_hash'], PDO::PARAM_STR);
        $stmt->bindParam(':rol_id', $datos['rol_id'], PDO::PARAM_INT);
        $stmt->bindParam(':activo', $datos['activo'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function actualizar($datos)
    {
        if (empty($datos['password_hash'])) {
            $consulta = "UPDATE usuarios 
                         SET nombre = :nombre, correo = :correo, rol_id = :rol_id, activo = :activo 
                         WHERE id = :id";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
            $stmt->bindParam(':rol_id', $datos['rol_id'], PDO::PARAM_INT);
            $stmt->bindParam(':activo', $datos['activo'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        } else {
            $consulta = "UPDATE usuarios 
                         SET nombre = :nombre, correo = :correo, password_hash = :password_hash, rol_id = :rol_id, activo = :activo 
                         WHERE id = :id";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $datos['password_hash'], PDO::PARAM_STR);
            $stmt->bindParam(':rol_id', $datos['rol_id'], PDO::PARAM_INT);
            $stmt->bindParam(':activo', $datos['activo'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        }
        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $consulta = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function correoExiste($correo, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE correo = :correo";
        if ($idExcluir !== null) {
            $consulta .= " AND id != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch()['total'] > 0;
    }
} 