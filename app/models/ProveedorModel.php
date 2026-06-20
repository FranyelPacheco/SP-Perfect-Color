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
        return $this->_listarTodos();
    }

    private function _listarTodos()
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.activo = 1
                     GROUP BY p.id_proveedor
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    private function buscarPorRIF($rif)
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.rif = :rif AND p.activo = 1
                     GROUP BY p.id_proveedor LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $rif, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.id_proveedor = :id AND p.activo = 1
                     GROUP BY p.id_proveedor LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function insertarProveedor($datos)
    {
        return $this->_insertarProveedor($datos);
    }

    private function _insertarProveedor($datos)
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
        return $this->_actualizarProveedor($datos);
    }

    private function _actualizarProveedor($datos)
    {
        $consulta = "UPDATE proveedores 
                     SET rif = :rif, nombre_empresa = :nombre_empresa, direccion = :direccion, 
                         contacto = :contacto, correo = :correo, activo = 1 
                     WHERE id_proveedor = :id";
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
        return $this->_eliminarProveedor($id);
    }

    private function _eliminarProveedor($id)
    {
        $consulta = "SELECT COUNT(*) as total FROM cuentas_pagar 
                     WHERE id_proveedor = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deudasPendientes = $stmt->fetch()['total'];

        if ($deudasPendientes > 0) {
            return false;
        }

        $consulta = "SELECT COUNT(*) as total FROM insumo_proveedor WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $insumosAsociados = $stmt->fetch()['total'];

        if ($insumosAsociados > 0) {
            return false;
        }

        $consulta = "UPDATE proveedores SET activo = 0 WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function rifExiste($rif, $idExcluir = null)
    {
        return $this->_rifExiste($rif, $idExcluir);
    }

    public function buscarInactivoPorRIF($rif)
    {
        if (empty($rif)) return false;
        return $this->_buscarInactivoPorRIF($rif);
    }

    private function _buscarInactivoPorRIF($rif)
    {
        $consulta = "SELECT id_proveedor FROM proveedores WHERE rif = :rif AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $rif, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_proveedor'] : false;
    }

    private function _rifExiste($rif, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM proveedores WHERE rif = :rif AND activo = 1";

        if ($idExcluir !== null) {
            $consulta .= " AND id_proveedor != :id";
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
        return $this->_buscarProveedores($termino);
    }

    private function _buscarProveedores($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.activo = 1 AND (p.nombre_empresa LIKE :termino1 
                        OR p.rif LIKE :termino2)
                     GROUP BY p.id_proveedor
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function obtenerTelefonos($proveedorId)
    {
        $consulta = "SELECT * FROM telf_proveedor WHERE id_proveedor = :id_proveedor ORDER BY id_telf_proveedor ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertarTelefono($proveedorId, $telefono, $tipo = null)
    {
        return $this->_insertarTelefono($proveedorId, $telefono, $tipo);
    }

    private function _insertarTelefono($proveedorId, $telefono, $tipo = null)
    {
        $consulta = "INSERT INTO telf_proveedor (id_proveedor, telefono, tipo) VALUES (:id_proveedor, :telefono, :tipo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function eliminarTelefonos($proveedorId)
    {
        return $this->_eliminarTelefonos($proveedorId);
    }

    private function _eliminarTelefonos($proveedorId)
    {
        $consulta = "DELETE FROM telf_proveedor WHERE id_proveedor = :id_proveedor";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function obtenerRubros($proveedorId)
    {
        $consulta = "SELECT rp.*, r.nombre 
                     FROM rubro_proveedor rp
                     INNER JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE rp.id_proveedor = :id_proveedor 
                     ORDER BY rp.id_rubro_proveedor ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertarRubro($proveedorId, $rubroId)
    {
        return $this->_insertarRubro($proveedorId, $rubroId);
    }

    private function _insertarRubro($proveedorId, $rubroId)
    {
        $consulta = "INSERT INTO rubro_proveedor (id_proveedor, id_rubro) VALUES (:id_proveedor, :id_rubro)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':id_rubro', $rubroId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminarRubros($proveedorId)
    {
        return $this->_eliminarRubros($proveedorId);
    }

    private function _eliminarRubros($proveedorId)
    {
        $consulta = "DELETE FROM rubro_proveedor WHERE id_proveedor = :id_proveedor";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
