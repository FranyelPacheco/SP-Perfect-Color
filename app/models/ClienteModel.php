<?php
// Archivo: ClienteModel.php
// Modelo para operaciones con la tabla clientes

namespace App\Models;

use App\Core\ConexionBD;

class ClienteModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todos los clientes
    public function listarTodos()
    {
        $consulta = "SELECT * FROM clientes ORDER BY apellidos ASC, nombres ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un cliente por su cedula
    public function buscarPorCedula($cedula)
    {
        $consulta = "SELECT * FROM clientes WHERE cedula = :cedula LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Busca un cliente por su ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT * FROM clientes WHERE id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Inserta un nuevo cliente
    public function insertarCliente($datos)
    {
        $consulta = "INSERT INTO clientes (cedula, nombres, apellidos, telefono, correo, direccion) 
                     VALUES (:cedula, :nombres, :apellidos, :telefono, :correo, :direccion)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $datos['cedula'], PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    // Actualiza un cliente existente
    public function actualizarCliente($datos)
    {
        $consulta = "UPDATE clientes 
                     SET cedula = :cedula, nombres = :nombres, apellidos = :apellidos, 
                         telefono = :telefono, correo = :correo, direccion = :direccion 
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $datos['cedula'], PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Elimina un cliente por su ID
    public function eliminarCliente($id)
    {
        // Verificar que el cliente no tenga cuentas por cobrar pendientes
        $consulta = "SELECT COUNT(*) as total FROM cuentas_cobrar 
                     WHERE cliente_id = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deudasPendientes = $stmt->fetch()['total'];
        
        if ($deudasPendientes > 0) {
            return false;
        }
        
        $consulta = "DELETE FROM clientes WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Verifica si una cedula ya existe (para otro cliente en edicion)
    public function cedulaExiste($cedula, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE cedula = :cedula";
        
        if ($idExcluir !== null) {
            $consulta .= " AND id != :id";
        }
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        
        if ($idExcluir !== null) {
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetch()['total'] > 0;
    }

    // Busca clientes por nombre, apellido o cedula
    public function buscarClientes($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT * FROM clientes 
                     WHERE nombres LIKE :termino1 
                        OR apellidos LIKE :termino2 
                        OR cedula LIKE :termino3 
                     ORDER BY apellidos ASC, nombres ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $termino, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}