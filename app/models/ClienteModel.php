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
        return $this->_listarTodos();
    }

    private function _listarTodos()
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.activo = 1
                     GROUP BY c.id_cliente
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un cliente por su cedula
    private function buscarPorCedula($cedula)
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.cedula = :cedula AND c.activo = 1
                     GROUP BY c.id_cliente LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Busca un cliente por su ID
    public function buscarPorId($id)
    {
        if (intval($id) < 1) return false;
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.id_cliente = :id AND c.activo = 1
                     GROUP BY c.id_cliente LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Inserta un nuevo cliente y retorna el ID
    public function insertarCliente($datos)
    {
        if (empty($datos['cedula']) || empty($datos['nombres']) || empty($datos['apellidos'])) return false;
        return $this->_insertarCliente($datos);
    }

    private function _insertarCliente($datos)
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
        if (intval($datos['id'] ?? 0) < 1) return false;
        if (empty($datos['cedula']) || empty($datos['nombres']) || empty($datos['apellidos'])) return false;
        return $this->_actualizarCliente($datos);
    }

    private function _actualizarCliente($datos)
    {
        $consulta = "UPDATE clientes 
                     SET cedula = :cedula, nombres = :nombres, apellidos = :apellidos, 
                         correo = :correo, direccion = :direccion, activo = 1 
                     WHERE id_cliente = :id";
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
        $consulta = "SELECT * FROM telefono_cliente WHERE id_cliente = :id_cliente ORDER BY id_telefono_cliente ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_cliente', $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertarTelefono($clienteId, $telefono, $tipo = null)
    {
        if (intval($clienteId) < 1) return false;
        if (empty($telefono)) return false;
        return $this->_insertarTelefono($clienteId, $telefono, $tipo);
    }

    private function _insertarTelefono($clienteId, $telefono, $tipo = null)
    {
        $consulta = "INSERT INTO telefono_cliente (id_cliente, telefono, tipo) VALUES (:id_cliente, :telefono, :tipo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_cliente', $clienteId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function eliminarTelefonos($clienteId)
    {
        if (intval($clienteId) < 1) return false;
        return $this->_eliminarTelefonos($clienteId);
    }

    private function _eliminarTelefonos($clienteId)
    {
        $consulta = "DELETE FROM telefono_cliente WHERE id_cliente = :id_cliente";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_cliente', $clienteId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina un cliente por su ID
    public function eliminarCliente($id)
    {
        if (intval($id) < 1) return false;
        return $this->_eliminarCliente($id);
    }

    private function _eliminarCliente($id)
    {
        // Verificar que el cliente no tenga cuentas por cobrar pendientes
        $consulta = "SELECT COUNT(*) as total FROM cuentas_cobrar 
                     WHERE id_cliente = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deudasPendientes = $stmt->fetch()['total'];
        
        if ($deudasPendientes > 0) {
            return false;
        }
        
        $consulta = "UPDATE clientes SET activo = 0 WHERE id_cliente = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Verifica si una cedula ya existe (para otro cliente en edicion)
    public function cedulaExiste($cedula, $idExcluir = null)
    {
        if (empty($cedula)) return false;
        return $this->_cedulaExiste($cedula, $idExcluir);
    }

    public function buscarInactivoPorCedula($cedula)
    {
        if (empty($cedula)) return false;
        return $this->_buscarInactivoPorCedula($cedula);
    }

    private function _buscarInactivoPorCedula($cedula)
    {
        $consulta = "SELECT id_cliente FROM clientes WHERE cedula = :cedula AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_cliente'] : false;
    }

    private function _cedulaExiste($cedula, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE cedula = :cedula AND activo = 1";
        
        if ($idExcluir !== null) {
            $consulta .= " AND id_cliente != :id";
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
        if (empty(trim($termino))) return [];
        return $this->_buscarClientes($termino);
    }

    private function _buscarClientes($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.activo = 1 AND (c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3) 
                     GROUP BY c.id_cliente
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $termino, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
