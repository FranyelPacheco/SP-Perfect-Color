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

    // Busca un usuario por su correo electronico
    public function buscarPorCorreo($correo)
    {
        $consulta = "SELECT * FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Lista todos los usuarios con su rol
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
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $datos['password_hash'], PDO::PARAM_STR);
        $stmt->bindParam(':rol_id', $datos['rol_id'], PDO::PARAM_INT);
        $stmt->bindParam(':activo', $datos['activo'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Busca un usuario por su ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT u.*, r.nombre as rol_nombre 
                     FROM usuarios u 
                     INNER JOIN roles r ON u.rol_id = r.id 
                     WHERE u.id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Actualiza un usuario existente (sin cambiar clave)
    public function actualizarUsuario($datos)
    {
        $consulta = "UPDATE usuarios 
                     SET nombre = :nombre, correo = :correo, rol_id = :rol_id, activo = :activo 
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':rol_id', $datos['rol_id'], PDO::PARAM_INT);
        $stmt->bindParam(':activo', $datos['activo'], PDO::PARAM_INT);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Actualiza la clave de un usuario
    public function actualizarClave($id, $passwordHash)
    {
        $consulta = "UPDATE usuarios SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Elimina un usuario por su ID
    public function eliminarUsuario($id)
    {
        // Verificar que no se elimine al unico administrador
        $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1 AND activo = 1";
        $stmt = $this->conexion->query($consulta);
        $totalAdmins = $stmt->fetch()['total'];
        
        // Obtener el rol del usuario a eliminar
        $usuario = $this->buscarPorId($id);
        
        // Si es el unico administrador y se intenta eliminar, no permitir
        if ($totalAdmins <= 1 && $usuario['rol_id'] == 1) {
            return false;
        }
        
        $consulta = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Activa o desactiva un usuario
    public function cambiarEstado($id, $estado)
    {
        // Verificar que no se desactive al unico administrador activo
        if ($estado == 0) {
            $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1 AND activo = 1";
            $stmt = $this->conexion->query($consulta);
            $totalAdmins = $stmt->fetch()['total'];
            
            $usuario = $this->buscarPorId($id);
            
            if ($totalAdmins <= 1 && $usuario['rol_id'] == 1) {
                return false;
            }
        }
        
        $consulta = "UPDATE usuarios SET activo = :activo WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':activo', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Verifica si un correo ya existe en otro usuario
    public function correoExiste($correo, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE correo = :correo";
        
        // Si se proporciona un ID, excluirlo de la busqueda (para edicion)
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