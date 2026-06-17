<?php
// Archivo: UsuarioModel.php
// Modelo para operaciones con la tabla usuarios

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;

class UsuarioModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Busca un usuario por su correo electrónico
    public function buscarPorCorreo($correo)
    {
        return $this->_buscarPorCorreo($correo);
    }

    private function _buscarPorCorreo($correo)
    {
        $consulta = "SELECT * FROM usuarios WHERE correo = :correo AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Busca un usuario por su ID primario
    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT * FROM usuarios WHERE id_usuario = :id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Lista todos los usuarios con su nombre de rol
    public function listarTodos()
    {
        return $this->_listarTodos();
    }

    private function _listarTodos()
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON u.rol_id = r.id_rol
                     WHERE u.activo = 1
                     ORDER BY u.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // Lista todos los roles disponibles
    public function listarRoles()
    {
        return $this->_listarRoles();
    }

    private function _listarRoles()
    {
        $consulta = "SELECT * FROM roles ORDER BY id_rol ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // Inserta un nuevo usuario
    public function insertarUsuario($datos)
    {
        return $this->_insertarUsuario($datos);
    }

    private function _insertarUsuario($datos)
    {
        $consulta = "INSERT INTO usuarios (nombre, correo, password_hash, rol_id, activo)
                     VALUES (:nombre, :correo, :password_hash, :rol_id, :activo)";
        $stmt = $this->conexion->prepare($consulta);
        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':correo' => $datos['correo'],
            ':password_hash' => $datos['password_hash'],
            ':rol_id' => $datos['rol_id'],
            ':activo' => $datos['activo']
        ]);
    }

    // Actualiza los datos básicos de un usuario existente
    public function actualizarUsuario($datos)
    {
        return $this->_actualizarUsuario($datos);
    }

    private function _actualizarUsuario($datos)
    {
        $consulta = "UPDATE usuarios SET nombre = :nombre, correo = :correo, rol_id = :rol_id, activo = :activo WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        return $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':correo' => $datos['correo'],
            ':rol_id' => $datos['rol_id'],
            ':activo' => $datos['activo'],
            ':id' => $datos['id']
        ]);
    }

    // Actualiza exclusivamente la clave de un usuario
    public function actualizarClave($id, $passwordHash)
    {
        return $this->_actualizarClave($id, $passwordHash);
    }

    private function _actualizarClave($id, $passwordHash)
    {
        $consulta = "UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id' => $id
        ]);
    }

    // Elimina un usuario protegiendo al último administrador
    public function eliminarUsuario($id)
    {
        return $this->_eliminarUsuario($id);
    }

    private function _eliminarUsuario($id)
    {
        $usuario = $this->_buscarPorId($id);
        if ($usuario && $usuario['rol_id'] == 1) {
            $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1 AND activo = 1";
            $stmt = $this->conexion->query($consulta);
            $totalAdmins = $stmt->fetch()['total'];

            if ($totalAdmins <= 1) {
                return false;
            }
        }

        $consulta = "UPDATE usuarios SET activo = 0 WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Verifica si un correo ya existe, con exclusión opcional por ID
    public function correoExiste($correo, $idExcluir = null)
    {
        return $this->_correoExiste($correo, $idExcluir);
    }

    public function buscarInactivoPorCorreo($correo)
    {
        if (empty($correo)) return false;
        return $this->_buscarInactivoPorCorreo($correo);
    }

    private function _buscarInactivoPorCorreo($correo)
    {
        $consulta = "SELECT id_usuario FROM usuarios WHERE correo = :correo AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_usuario'] : false;
    }

    private function _correoExiste($correo, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE correo = :correo AND activo = 1";

        if ($idExcluir !== null) {
            $consulta .= " AND id_usuario != :id";
        }

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);

        if ($idExcluir !== null) {
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        }

        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }
}
