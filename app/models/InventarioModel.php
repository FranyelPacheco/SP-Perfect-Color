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
        $consulta = "SELECT i.*,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT p.id SEPARATOR ',') as proveedores_id
                     FROM insumos i
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id
                     LEFT JOIN proveedores p ON p.id = ip.proveedor_id
                     WHERE i.activo = 1
                     GROUP BY i.id
                     ORDER BY i.nombre ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    public function listarProveedoresActivos()
    {
        $consulta = "SELECT id, nombre_empresa, rif FROM proveedores WHERE activo = 1 ORDER BY nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $consulta = "SELECT i.*,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT p.id SEPARATOR ',') as proveedores_id
                     FROM insumos i
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id
                     LEFT JOIN proveedores p ON p.id = ip.proveedor_id
                     WHERE i.id = :id AND i.activo = 1
                     GROUP BY i.id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function buscarPorCodigo($codigo)
    {
        $consulta = "SELECT * FROM insumos WHERE codigo = :codigo AND activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function insertarInsumo($datos)
    {
        $consulta = "INSERT INTO insumos (codigo, nombre, marca, categoria, unidad_medida, 
                     stock_actual, stock_minimo, precio_venta, precio_compra) 
                     VALUES (:codigo, :nombre, :marca, :categoria, :unidad_medida, 
                     :stock_actual, :stock_minimo, :precio_venta, :precio_compra)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $datos['codigo'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':marca', $datos['marca'], PDO::PARAM_STR);
        $stmt->bindParam(':categoria', $datos['categoria'], PDO::PARAM_STR);
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

    public function actualizarInsumo($datos)
    {
        $consulta = "UPDATE insumos 
                     SET codigo = :codigo, nombre = :nombre, marca = :marca, categoria = :categoria, 
                         unidad_medida = :unidad_medida, stock_actual = :stock_actual, 
                         stock_minimo = :stock_minimo, precio_venta = :precio_venta, 
                         precio_compra = :precio_compra
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $datos['codigo'], PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $stmt->bindParam(':marca', $datos['marca'], PDO::PARAM_STR);
        $stmt->bindParam(':categoria', $datos['categoria'], PDO::PARAM_STR);
        $stmt->bindParam(':unidad_medida', $datos['unidad_medida'], PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $datos['stock_actual']);
        $stmt->bindParam(':stock_minimo', $datos['stock_minimo']);
        $stmt->bindParam(':precio_venta', $datos['precio_venta']);
        $stmt->bindParam(':precio_compra', $datos['precio_compra']);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function eliminarInsumo($id)
    {
        $consulta = "UPDATE insumos SET activo = 0 WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function codigoExiste($codigo, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM insumos WHERE codigo = :codigo AND activo = 1";

        if ($idExcluir !== null) {
            $consulta .= " AND id != :id";
        }

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);

        if ($idExcluir !== null) {
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetch()['total'] > 0;
    }

    public function buscarInsumos($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT i.*,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT p.id SEPARATOR ',') as proveedores_id
                     FROM insumos i
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id
                     LEFT JOIN proveedores p ON p.id = ip.proveedor_id
                     WHERE i.activo = 1 AND (i.nombre LIKE :termino1 
                        OR i.codigo LIKE :termino2 
                        OR i.categoria LIKE :termino3)
                     GROUP BY i.id
                     ORDER BY i.nombre ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $termino, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerAlertasStockBajo()
    {
        $consulta = "SELECT i.*,
                            GROUP_CONCAT(DISTINCT p.nombre_empresa SEPARATOR ', ') as proveedores_nombre
                     FROM insumos i
                     LEFT JOIN insumo_proveedor ip ON ip.insumo_id = i.id
                     LEFT JOIN proveedores p ON p.id = ip.proveedor_id
                     WHERE i.activo = 1 AND i.stock_actual <= i.stock_minimo
                     GROUP BY i.id
                     ORDER BY i.stock_actual ASC";
        $stmt = $this->conexion->query($consulta);

        return $stmt->fetchAll();
    }

    private function actualizarStock($id, $cantidad, $operacion)
    {
        if ($operacion === 'sumar') {
            $consulta = "UPDATE insumos SET stock_actual = stock_actual + :cantidad WHERE id = :id";
        } else {
            $consulta = "UPDATE insumos SET stock_actual = stock_actual - :cantidad WHERE id = :id";
        }

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Gestion de la relacion insumo-proveedor
    private function obtenerProveedoresDeInsumo($insumoId)
    {
        $consulta = "SELECT p.* FROM proveedores p
                     INNER JOIN insumo_proveedor ip ON ip.proveedor_id = p.id
                     WHERE ip.insumo_id = :insumo_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':insumo_id', $insumoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function asignarProveedorAInsumo($insumoId, $proveedorId)
    {
        $consulta = "INSERT IGNORE INTO insumo_proveedor (insumo_id, proveedor_id) VALUES (:insumo_id, :proveedor_id)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':insumo_id', $insumoId, PDO::PARAM_INT);
        $stmt->bindParam(':proveedor_id', $proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminarProveedoresDeInsumo($insumoId)
    {
        $consulta = "DELETE FROM insumo_proveedor WHERE insumo_id = :insumo_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':insumo_id', $insumoId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
