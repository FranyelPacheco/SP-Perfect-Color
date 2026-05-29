<?php
// Archivo: PresupuestoModel.php
// Modelo para operaciones con presupuestos

namespace App\Models;

use App\Core\ConexionBD;

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
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.cliente_id = c.id 
                     INNER JOIN usuarios u ON p.usuario_id = u.id 
                     ORDER BY p.fecha DESC, p.id DESC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca un presupuesto por ID con todos sus datos
    public function buscarPorId($id)
    {
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.cliente_id = c.id 
                     INNER JOIN usuarios u ON p.usuario_id = u.id 
                     WHERE p.id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtiene el detalle de un presupuesto
    public function obtenerDetalle($presupuestoId)
    {
        $consulta = "SELECT pd.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo,
                            i.marca as insumo_marca
                     FROM presupuesto_detalle pd
                     INNER JOIN insumos i ON pd.insumo_id = i.id
                     WHERE pd.presupuesto_id = :presupuesto_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':presupuesto_id', $presupuestoId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Inserta un nuevo presupuesto con su detalle usando transaccion
    public function insertarPresupuesto($datos, $detalle)
    {
        try {
            // Iniciar transaccion
            $this->conexion->beginTransaction();
            
            // Insertar el presupuesto principal
            $consulta = "INSERT INTO presupuestos (cliente_id, usuario_id, fecha, total, estado, observaciones) 
                         VALUES (:cliente_id, :usuario_id, CURDATE(), :total, 'pendiente', :observaciones)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $datos['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':observaciones', $datos['observaciones'], PDO::PARAM_STR);
            $stmt->execute();
            
            // Obtener el ID del presupuesto insertado
            $presupuestoId = $this->conexion->lastInsertId();
            
            // Insertar cada item del detalle
            $consultaDetalle = "INSERT INTO presupuesto_detalle (presupuesto_id, insumo_id, cantidad, precio_unitario, subtotal) 
                                VALUES (:presupuesto_id, :insumo_id, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);
            
            foreach ($detalle as $item) {
                $stmtDetalle->bindParam(':presupuesto_id', $presupuestoId, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':insumo_id', $item['insumo_id'], PDO::PARAM_INT);
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
        $consulta = "UPDATE presupuestos SET estado = :estado WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Busca presupuestos por cliente o estado
    public function buscarPresupuestos($termino, $estado = '')
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
        
        $where = '';
        if (!empty($condiciones)) {
            $where = 'WHERE ' . implode(' AND ', $condiciones);
        }
        
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.cliente_id = c.id 
                     INNER JOIN usuarios u ON p.usuario_id = u.id 
                     {$where}
                     ORDER BY p.fecha DESC, p.id DESC";
        
        $stmt = $this->conexion->prepare($consulta);
        
        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}