<?php

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

    public function listarTodos()
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT rp.nombre SEPARATOR ', ') as rubros
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.proveedor_id = p.id
                     LEFT JOIN rubro_proveedor rp ON rp.proveedor_id = p.id
                     WHERE p.activo = 1
                     GROUP BY p.id
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    private function buscarPorRIF($rif)
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT rp.nombre SEPARATOR ', ') as rubros
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.proveedor_id = p.id
                     LEFT JOIN rubro_proveedor rp ON rp.proveedor_id = p.id
                     WHERE p.rif = :rif AND p.activo = 1
                     GROUP BY p.id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $rif, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function buscarPorId($id)
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT rp.nombre SEPARATOR ', ') as rubros
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.proveedor_id = p.id
                     LEFT JOIN rubro_proveedor rp ON rp.proveedor_id = p.id
                     WHERE p.id = :id AND p.activo = 1
                     GROUP BY p.id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function insertarProveedor($datos)
    {
        $consulta = "INSERT INTO proveedores (rif, nombre_empresa, direccion, contacto, correo) 
                     VALUES (:rif, :nombre_empresa, :direccion, :contacto, :correo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $datos['rif'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $datos['nombre_empresa'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':contacto', $datos['contacto'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function actualizarProveedor($datos)
    {
        $consulta = "UPDATE proveedores 
                     SET rif = :rif, nombre_empresa = :nombre_empresa, direccion = :direccion, 
                         contacto = :contacto, correo = :correo 
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $datos['rif'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $datos['nombre_empresa'], PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $datos['direccion'], PDO::PARAM_STR);
        $stmt->bindParam(':contacto', $datos['contacto'], PDO::PARAM_STR);
        $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function eliminarProveedor($id)
    {
        $consulta = "SELECT COUNT(*) as total FROM cuentas_pagar 
                     WHERE proveedor_id = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deudasPendientes = $stmt->fetch()['total'];

        if ($deudasPendientes > 0) {
            return false;
        }

        $consulta = "SELECT COUNT(*) as total FROM insumo_proveedor WHERE proveedor_id = :id";
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

    public function buscarProveedores($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT rp.nombre SEPARATOR ', ') as rubros
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.proveedor_id = p.id
                     LEFT JOIN rubro_proveedor rp ON rp.proveedor_id = p.id
                     WHERE p.activo = 1 AND (p.nombre_empresa LIKE :termino1 
                        OR p.rif LIKE :termino2)
                     GROUP BY p.id
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Gestion de telefonos del proveedor
    private function obtenerTelefonos($proveedorId)
    {
        $consulta = "SELECT * FROM telf_proveedor WHERE proveedor_id = :proveedor_id ORDER BY id ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertarTelefono($proveedorId, $telefono, $tipo = null)
    {
        $consulta = "INSERT INTO telf_proveedor (proveedor_id, telefono, tipo) VALUES (:proveedor_id, :telefono, :tipo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function eliminarTelefonos($proveedorId)
    {
        $consulta = "DELETE FROM telf_proveedor WHERE proveedor_id = :proveedor_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Gestion de rubros del proveedor
    private function obtenerRubros($proveedorId)
    {
        $consulta = "SELECT * FROM rubro_proveedor WHERE proveedor_id = :proveedor_id ORDER BY id ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertarRubro($proveedorId, $nombre)
    {
        $consulta = "INSERT INTO rubro_proveedor (proveedor_id, nombre) VALUES (:proveedor_id, :nombre)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function eliminarRubros($proveedorId)
    {
        $consulta = "DELETE FROM rubro_proveedor WHERE proveedor_id = :proveedor_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
