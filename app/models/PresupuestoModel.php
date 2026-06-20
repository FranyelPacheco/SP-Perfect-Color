<?php
// Archivo: PresupuestoModel.php
// Modelo para operaciones con presupuestos

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;
use \PDOException;

class PresupuestoModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todos los presupuestos con datos del cliente
    public function listarTodos()
    {
        return $this->_listarTodos();
    }

    private function _listarTodos()
    {
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     WHERE p.activo = 1
                     ORDER BY p.fecha DESC, p.id_presupuesto DESC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un presupuesto por ID con todos sus datos
    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     WHERE p.id_presupuesto = :id AND p.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtiene el detalle de un presupuesto
    public function obtenerDetalle($presupuestoId)
    {
        return $this->_obtenerDetalle($presupuestoId);
    }

    private function _obtenerDetalle($presupuestoId)
    {
        $consulta = "SELECT pd.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo,
                            i.marca as insumo_marca
                     FROM presupuesto_detalle pd
                     INNER JOIN insumos i ON pd.id_insumo = i.id_insumo
                     WHERE pd.id_presupuesto = :id_presupuesto";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_presupuesto', $presupuestoId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Inserta un nuevo presupuesto con su detalle usando transaccion
    public function insertarPresupuesto($datos, $detalle)
    {
        return $this->_insertarPresupuesto($datos, $detalle);
    }

    private function _insertarPresupuesto($datos, $detalle)
    {
        try {
            // Iniciar transaccion
            $this->conexion->beginTransaction();
            
            // Insertar el presupuesto principal
            $consulta = "INSERT INTO presupuestos (id_cliente, id_usuario, fecha, total, estado, observaciones) 
                         VALUES (:id_cliente, :id_usuario, NOW(), :total, 'pendiente', :observaciones)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':id_cliente', $datos['id_cliente'], PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $datos['id_usuario'], PDO::PARAM_INT);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':observaciones', $datos['observaciones'], PDO::PARAM_STR);
            $stmt->execute();
            
            // Obtener el ID del presupuesto insertado
            $presupuestoId = $this->conexion->lastInsertId();
            
            // Insertar cada item del detalle
            $consultaDetalle = "INSERT INTO presupuesto_detalle (id_presupuesto, id_insumo, cantidad, precio_unitario, subtotal) 
                                VALUES (:id_presupuesto, :id_insumo, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);
            
            foreach ($detalle as $item) {
                $stmtDetalle->bindParam(':id_presupuesto', $presupuestoId, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':id_insumo', $item['id_insumo'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $item['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindParam(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();
            }
            
            // Confirmar transaccion
            $this->conexion->commit();
            
            return $presupuestoId;
            
        } catch (PDOException $e) {
            // Revertir en caso de error
            $this->conexion->rollback();
            throw $e;
        }
    }

    // Cambia el estado de un presupuesto
    public function cambiarEstado($id, $estado)
    {
        return $this->_cambiarEstado($id, $estado);
    }

    private function _cambiarEstado($id, $estado)
    {
        $consulta = "UPDATE presupuestos SET estado = :estado WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Eliminacion logica de un presupuesto
    public function eliminarPresupuesto($id)
    {
        return $this->_eliminarPresupuesto($id);
    }

    private function _eliminarPresupuesto($id)
    {
        $consulta = "UPDATE presupuestos SET activo = 0 WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Busca presupuestos por cliente o estado
    public function buscarPresupuestos($termino, $estado = '')
    {
        return $this->_buscarPresupuestos($termino, $estado);
    }

    private function _buscarPresupuestos($termino, $estado = '')
    {
        $condiciones = [];
        $parametros = [];
        
        if (!empty($termino)) {
            $condiciones[] = "(c.nombres LIKE :termino1 OR c.apellidos LIKE :termino2 OR c.cedula LIKE :termino3)";
            $terminoLike = '%' . $termino . '%';
            $parametros[':termino1'] = $terminoLike;
            $parametros[':termino2'] = $terminoLike;
            $parametros[':termino3'] = $terminoLike;
        }
        
        if (!empty($estado)) {
            $condiciones[] = "p.estado = :estado";
            $parametros[':estado'] = $estado;
        }
        
        $condiciones[] = "p.activo = 1";
        
        $where = '';
        if (!empty($condiciones)) {
            $where = 'WHERE ' . implode(' AND ', $condiciones);
        }
        
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     {$where}
                     ORDER BY p.fecha DESC, p.id_presupuesto DESC";
        
        $stmt = $this->conexion->prepare($consulta);
        
        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
