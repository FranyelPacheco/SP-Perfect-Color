<?php
// Archivo: UsuarioModel.php
// Modelo para operaciones con la tabla usuarios

require_once __DIR__ . '/../core/conexionBD.php';

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
        $consulta = "SELECT * FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Busca un usuario por su ID primario
    public function buscarPorId($id)
    {
        $consulta = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Lista todos los usuarios con su nombre de rol
    public function listarTodos()
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON u.rol_id = r.id
                     ORDER BY u.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // Lista todos los roles disponibles
    public function listarRoles()
    {
        $consulta = "SELECT * FROM roles ORDER BY id ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // Inserta un nuevo usuario
    public function insertarUsuario($datos)
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
        $consulta = "UPDATE usuarios SET nombre = :nombre, correo = :correo, rol_id = :rol_id, activo = :activo WHERE id = :id";
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
        $consulta = "UPDATE usuarios SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':id' => $id
        ]);
    }

    // Elimina un usuario protegiendo al último administrador
    public function eliminarUsuario($id)
    {
        $usuario = $this->buscarPorId($id);
        if ($usuario && $usuario['rol_id'] == 1) {
            $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1 AND activo = 1";
            $stmt = $this->conexion->query($consulta);
            $totalAdmins = $stmt->fetch()['total'];

            if ($totalAdmins <= 1) {
                return false;
            }
        }

        $consulta = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Verifica si un correo ya existe, con exclusión opcional por ID
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
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }
}
