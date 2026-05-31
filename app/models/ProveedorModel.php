<?php
// Archivo: ProveedorModel.php
// Modelo para operaciones con la tabla proveedores

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;

class ProveedorModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todos los proveedores
    public function listarTodos()
    {
        $consulta = "SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un proveedor por su RIF
    public function buscarPorRIF($rif)
    {
        $consulta = "SELECT * FROM proveedores WHERE rif = :rif AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $rif, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Busca un proveedor por su ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT * FROM proveedores WHERE id = :id AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Inserta un nuevo proveedor
    public function insertarProveedor($datos)
    {
        $consulta = "INSERT INTO proveedores (rif, nombre_empresa, direccion, contacto, telefono, correo, rubros) 
                     VALUES (:rif, :nombre_empresa, :direccion, :contacto, :telefono, :correo, :rubros)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $datos['rif'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $datos['nombre_empresa'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':contacto', $datos['contacto'], PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':rubros', $datos['rubros'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    // Actualiza un proveedor existente
    public function actualizarProveedor($datos)
    {
        $consulta = "UPDATE proveedores 
                     SET rif = :rif, nombre_empresa = :nombre_empresa, direccion = :direccion, 
                         contacto = :contacto, telefono = :telefono, correo = :correo, rubros = :rubros 
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $datos['rif'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $datos['nombre_empresa'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':contacto', $datos['contacto'], PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':rubros', $datos['rubros'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Elimina un proveedor por su ID
    public function eliminarProveedor($id)
    {
        // Verificar que el proveedor no tenga cuentas por pagar pendientes
        $consulta = "SELECT COUNT(*) as total FROM cuentas_pagar 
                     WHERE proveedor_id = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deudasPendientes = $stmt->fetch()['total'];
        
        if ($deudasPendientes > 0) {
            return false;
        }
        
        // Verificar que no tenga insumos asociados
        $consulta = "SELECT COUNT(*) as total FROM insumos WHERE proveedor_id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $insumosAsociados = $stmt->fetch()['total'];
        
        if ($insumosAsociados > 0) {
            return false;
        }
        
        $consulta = "UPDATE proveedores SET activo = 0 WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Verifica si un RIF ya existe (para otro proveedor en edicion)
    public function rifExiste($rif, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM proveedores WHERE rif = :rif AND activo = 1";
        
        if ($idExcluir !== null) {
            $consulta .= " AND id != :id";
        }
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $rif, PDO::PARAM_STR);
        
        if ($idExcluir !== null) {
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetch()['total'] > 0;
    }

    // Busca proveedores por nombre de empresa o RIF
    public function buscarProveedores($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT * FROM proveedores 
                     WHERE activo = 1 AND (nombre_empresa LIKE :termino1 
                        OR rif LIKE :termino2) 
                     ORDER BY nombre_empresa ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}