<?php
// Archivo: InventarioModel.php
// Modelo para operaciones con la tabla insumos

namespace App\Models;

use App\Core\ConexionBD;

class InventarioModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todos los insumos con datos del proveedor
    public function listarTodos()
    {
        $consulta = "SELECT i.*, p.nombre_empresa as proveedor_nombre 
                     FROM insumos i 
                     LEFT JOIN proveedores p ON i.proveedor_id = p.id 
                     ORDER BY i.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Lista proveedores para el select del formulario
    public function listarProveedoresActivos()
    {
        $consulta = "SELECT id, nombre_empresa, rif FROM proveedores ORDER BY nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un insumo por su ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT i.*, p.nombre_empresa as proveedor_nombre 
                     FROM insumos i 
                     LEFT JOIN proveedores p ON i.proveedor_id = p.id 
                     WHERE i.id = :id LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Busca un insumo por su codigo
    public function buscarPorCodigo($codigo)
    {
        $consulta = "SELECT * FROM insumos WHERE codigo = :codigo LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Inserta un nuevo insumo
    public function insertarInsumo($datos)
    {
        $consulta = "INSERT INTO insumos (codigo, nombre, marca, categoria, unidad_medida, 
                     stock_actual, stock_minimo, precio_venta, precio_compra, fecha_vencimiento, proveedor_id) 
                     VALUES (:codigo, :nombre, :marca, :categoria, :unidad_medida, 
                     :stock_actual, :stock_minimo, :precio_venta, :precio_compra, :fecha_vencimiento, :proveedor_id)";
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
        $stmt->bindParam(':fecha_vencimiento', $datos['fecha_vencimiento'], PDO::PARAM_STR);
        $stmt->bindParam(':proveedor_id', $datos['proveedor_id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Actualiza un insumo existente
    public function actualizarInsumo($datos)
    {
        $consulta = "UPDATE insumos 
                     SET codigo = :codigo, nombre = :nombre, marca = :marca, categoria = :categoria, 
                         unidad_medida = :unidad_medida, stock_actual = :stock_actual, 
                         stock_minimo = :stock_minimo, precio_venta = :precio_venta, 
                         precio_compra = :precio_compra, fecha_vencimiento = :fecha_vencimiento, 
                         proveedor_id = :proveedor_id 
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
        $stmt->bindParam(':fecha_vencimiento', $datos['fecha_vencimiento'], PDO::PARAM_STR);
        $stmt->bindParam(':proveedor_id', $datos['proveedor_id'], PDO::PARAM_INT);
        $stmt->bindParam(':id', $datos['id'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Elimina un insumo
    public function eliminarInsumo($id)
    {
        $consulta = "DELETE FROM insumos WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Verifica si un codigo ya existe
    public function codigoExiste($codigo, $idExcluir = null)
    {
        $consulta = "SELECT COUNT(*) as total FROM insumos WHERE codigo = :codigo";
        
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

    // Busca insumos por nombre, codigo o categoria
    public function buscarInsumos($termino)
    {
        $termino = '%' . $termino . '%';
        $consulta = "SELECT i.*, p.nombre_empresa as proveedor_nombre 
                     FROM insumos i 
                     LEFT JOIN proveedores p ON i.proveedor_id = p.id 
                     WHERE i.nombre LIKE :termino1 
                        OR i.codigo LIKE :termino2 
                        OR i.categoria LIKE :termino3 
                     ORDER BY i.nombre ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $termino, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Obtiene los insumos con stock bajo (por debajo del minimo)
    public function obtenerAlertasStockBajo()
    {
        $consulta = "SELECT i.*, p.nombre_empresa as proveedor_nombre 
                     FROM insumos i 
                     LEFT JOIN proveedores p ON i.proveedor_id = p.id 
                     WHERE i.stock_actual <= i.stock_minimo 
                     ORDER BY i.stock_actual ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Actualiza el stock de un insumo (suma o resta)
    public function actualizarStock($id, $cantidad, $operacion)
    {
        // operacion: 'sumar' o 'restar'
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
}