<?php
// Archivo: ClienteModel.php
// Modelo para operaciones con la tabla clientes

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;

class ClienteModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todos los clientes con sus telefonos
    public function listarTodos()
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.cliente_id = c.id
                     WHERE c.activo = 1
                     GROUP BY c.id
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un cliente por su cedula
    private function buscarPorCedula($cedula)
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.cliente_id = c.id
                     WHERE c.cedula = :cedula AND c.activo = 1
                     GROUP BY c.id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Busca un cliente por su ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.cliente_id = c.id
                     WHERE c.id = :id AND c.activo = 1
                     GROUP BY c.id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Inserta un nuevo cliente y retorna el ID
    public function insertarCliente($datos)
    {
        $consulta = "INSERT INTO clientes (cedula, nombres, apellidos, correo, direccion) 
                     VALUES (:cedula, :nombres, :apellidos, :correo, :direccion)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $datos['cedula'], PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    // Actualiza un cliente existente
    public function actualizarCliente($datos)
    {
        $consulta = "UPDATE clientes 
                     SET cedula = :cedula, nombres = :nombres, apellidos = :apellidos, 
                         correo = :correo, direccion = :direccion 
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $datos['cedula'], PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Gestion de telefonos del cliente
    private function obtenerTelefonos($clienteId)
    {
        $consulta = "SELECT * FROM telefono_cliente WHERE cliente_id = :cliente_id ORDER BY id ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertarTelefono($clienteId, $telefono, $tipo = null)
    {
        $consulta = "INSERT INTO telefono_cliente (cliente_id, telefono, tipo) VALUES (:cliente_id, :telefono, :tipo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function eliminarTelefonos($clienteId)
    {
        $consulta = "DELETE FROM telefono_cliente WHERE cliente_id = :cliente_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
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
        
        $consulta = "UPDATE clientes SET activo = 0 WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Verifica si una cedula ya existe (para otro cliente en edicion)
    public function cedulaExiste($cedula, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE cedula = :cedula AND activo = 1";
        
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
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.cliente_id = c.id
                     WHERE c.activo = 1 AND (c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3) 
                     GROUP BY c.id
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $termino, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}