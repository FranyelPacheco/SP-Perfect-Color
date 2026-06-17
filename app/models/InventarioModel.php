<?php

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;

class InventarioModel
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

    public function listarProveedoresActivos()
    {
        return $this->_listarProveedoresActivos();
    }

    public function listarRubrosActivos()
    {
        return $this->_listarRubrosActivos();
    }

    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    public function buscarPorCodigo($codigo)
    {
        return $this->_buscarPorCodigo($codigo);
    }

    public function insertarInsumo($datos)
    {
        return $this->_insertarInsumo($datos);
    }

    public function actualizarInsumo($datos)
    {
        return $this->_actualizarInsumo($datos);
    }

    public function eliminarInsumo($id)
    {
        return $this->_eliminarInsumo($id);
    }

    public function codigoExiste($codigo, $idExcluir = null)
    {
        return $this->_codigoExiste($codigo, $idExcluir);
    }

    public function buscarInsumos($termino)
    {
        return $this->_buscarInsumos($termino);
    }

    public function obtenerAlertasStockBajo()
    {
        return $this->_obtenerAlertasStockBajo();
    }

    public function asignarProveedorAInsumo($insumoId, $proveedorId)
    {
        return $this->_asignarProveedorAInsumo($insumoId, $proveedorId);
    }

    public function eliminarProveedoresDeInsumo($insumoId)
    {
        return $this->_eliminarProveedoresDeInsumo($insumoId);
    }

    private function _listarTodos()
    {
        $consulta = "SELECT i.*, r.nombre as rubro_nombre,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT p.id_proveedor SEPARATOR ',') as proveedores_id
                     FROM insumos i
                     LEFT JOIN rubro r ON i.rubro_id = r.id_rubro
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id_insumo
                     LEFT JOIN proveedores p ON p.id_proveedor = ip.proveedor_id
                     WHERE i.activo = 1
                     GROUP BY i.id_insumo
                     ORDER BY i.nombre ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    private function _listarProveedoresActivos()
    {
        $consulta = "SELECT id_proveedor, nombre_empresa, rif FROM proveedores WHERE activo = 1 ORDER BY nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    private function _listarRubrosActivos()
    {
        $consulta = "SELECT id_rubro, nombre FROM rubro ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT i.*, r.nombre as rubro_nombre,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT p.id_proveedor SEPARATOR ',') as proveedores_id
                     FROM insumos i
                     LEFT JOIN rubro r ON i.rubro_id = r.id_rubro
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id_insumo
                     LEFT JOIN proveedores p ON p.id_proveedor = ip.proveedor_id
                     WHERE i.id_insumo = :id AND i.activo = 1
                     GROUP BY i.id_insumo LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    private function _buscarPorCodigo($codigo)
    {
        $consulta = "SELECT * FROM insumos WHERE codigo = :codigo AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    private function _insertarInsumo($datos)
    {
        $consulta = "INSERT INTO insumos (codigo, nombre, marca, rubro_id, unidad_medida,
                     stock_actual, stock_minimo, precio_venta, precio_compra)
                     VALUES (:codigo, :nombre, :marca, :rubro_id, :unidad_medida,
                     :stock_actual, :stock_minimo, :precio_venta, :precio_compra)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $datos['codigo'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':marca', $datos['marca'], PDO::PARAM_STR);
        $stmt->bindValue(':rubro_id', !empty($datos['rubro_id']) ? $datos['rubro_id'] : null, empty($datos['rubro_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':unidad_medida', $datos['unidad_medida'], PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $datos['stock_actual']);
        $stmt->bindParam(':stock_minimo', $datos['stock_minimo']);
        $stmt->bindParam(':precio_venta', $datos['precio_venta']);
        $stmt->bindParam(':precio_compra', $datos['precio_compra']);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    private function _actualizarInsumo($datos)
    {
        $consulta = "UPDATE insumos 
                     SET codigo = :codigo, nombre = :nombre, marca = :marca, rubro_id = :rubro_id,
                         unidad_medida = :unidad_medida, stock_actual = :stock_actual,
                         stock_minimo = :stock_minimo, precio_venta = :precio_venta,
                         precio_compra = :precio_compra, activo = 1
                     WHERE id_insumo = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $datos['codigo'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':marca', $datos['marca'], PDO::PARAM_STR);
        $stmt->bindValue(':rubro_id', !empty($datos['rubro_id']) ? $datos['rubro_id'] : null, empty($datos['rubro_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':unidad_medida', $datos['unidad_medida'], PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $datos['stock_actual']);
        $stmt->bindParam(':stock_minimo', $datos['stock_minimo']);
        $stmt->bindParam(':precio_venta', $datos['precio_venta']);
        $stmt->bindParam(':precio_compra', $datos['precio_compra']);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function _eliminarInsumo($id)
    {
        $consulta = "UPDATE insumos SET activo = 0 WHERE id_insumo = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function buscarInactivoPorCodigo($codigo)
    {
        if (empty($codigo)) return false;
        return $this->_buscarInactivoPorCodigo($codigo);
    }

    private function _buscarInactivoPorCodigo($codigo)
    {
        $consulta = "SELECT id_insumo FROM insumos WHERE codigo = :codigo AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_insumo'] : false;
    }

    private function _codigoExiste($codigo, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM insumos WHERE codigo = :codigo AND activo = 1";

        if ($idExcluir !== null) {
            $consulta .= " AND id_insumo != :id";
        }

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);

        if ($idExcluir !== null) {
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetch()['total'] > 0;
    }

    private function _buscarInsumos($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT i.*, r.nombre as rubro_nombre,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT p.id_proveedor SEPARATOR ',') as proveedores_id
                     FROM insumos i
                     LEFT JOIN rubro r ON i.rubro_id = r.id_rubro
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id_insumo
                     LEFT JOIN proveedores p ON p.id_proveedor = ip.proveedor_id
                     WHERE i.activo = 1 AND (i.nombre LIKE :termino1
                        OR i.codigo LIKE :termino2
                        OR r.nombre LIKE :termino3)
                     GROUP BY i.id_insumo
                     ORDER BY i.nombre ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $termino, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function _obtenerAlertasStockBajo()
    {
        $consulta = "SELECT i.*, r.nombre as rubro_nombre,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre
                     FROM insumos i
                     LEFT JOIN rubro r ON i.rubro_id = r.id_rubro
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id_insumo
                     LEFT JOIN proveedores p ON p.id_proveedor = ip.proveedor_id
                     WHERE i.activo = 1 AND i.stock_actual <= i.stock_minimo
                     GROUP BY i.id_insumo
                     ORDER BY i.stock_actual ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    private function _asignarProveedorAInsumo($insumoId, $proveedorId)
    {
        $consulta = "INSERT IGNORE INTO insumo_proveedor (insumo_id, proveedor_id) VALUES (:insumo_id, :proveedor_id)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':insumo_id', $insumoId, PDO::PARAM_INT);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _eliminarProveedoresDeInsumo($insumoId)
    {
        $consulta = "DELETE FROM insumo_proveedor WHERE insumo_id = :insumo_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':insumo_id', $insumoId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function actualizarStock($id, $cantidad, $operacion)
    {
        if ($operacion === 'sumar') {
            $consulta = "UPDATE insumos SET stock_actual = stock_actual + :cantidad WHERE id_insumo = :id";
        } else {
            $consulta = "UPDATE insumos SET stock_actual = stock_actual - :cantidad WHERE id_insumo = :id";
        }

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function obtenerProveedoresDeInsumo($insumoId)
    {
        $consulta = "SELECT p.* FROM proveedores p
                     INNER JOIN insumo_proveedor ip ON ip.proveedor_id = p.id_proveedor
                     WHERE ip.insumo_id = :insumo_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':insumo_id', $insumoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
