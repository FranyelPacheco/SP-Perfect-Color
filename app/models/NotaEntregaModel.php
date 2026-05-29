<?php
// Archivo: NotaEntregaModel.php
// Modelo para operaciones con notas de entrega

namespace App\Models;

use App\Core\ConexionBD;

class NotaEntregaModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todas las notas de entrega
    public function listarTodos()
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id 
                     INNER JOIN usuarios u ON ne.usuario_id = u.id 
                     ORDER BY ne.fecha DESC, ne.id DESC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca una nota de entrega por ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.direccion as cliente_direccion,
                            c.telefono as cliente_telefono,
                            u.nombre as usuario_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id 
                     INNER JOIN usuarios u ON ne.usuario_id = u.id 
                     WHERE ne.id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtiene el detalle de una nota de entrega
    public function obtenerDetalle($notaId)
    {
        $consulta = "SELECT ned.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo,
                            i.marca as insumo_marca
                     FROM nota_entrega_detalle ned
                     INNER JOIN insumos i ON ned.insumo_id = i.id
                     WHERE ned.nota_id = :nota_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nota_id', $notaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Crea una nota de entrega y descuenta del inventario
    public function crearNotaEntrega($datos, $detalle)
    {
        try {
            // Iniciar transaccion para asegurar integridad
            $this->conexion->beginTransaction();
            
            // Verificar stock disponible para cada item
            foreach ($detalle as $item) {
                $consultaStock = "SELECT stock_actual FROM insumos WHERE id = :id FOR UPDATE";
                $stmtStock = $this->conexion->prepare($consultaStock);
                $stmtStock->bindParam(':id', $item['insumo_id'], PDO::PARAM_INT);
                $stmtStock->execute();
                $insumo = $stmtStock->fetch();
                
                if (!$insumo || $insumo['stock_actual'] < $item['cantidad']) {
                    throw new PDOException('Stock insuficiente para el insumo ID: ' . $item['insumo_id']);
                }
            }
            
            // Insertar la nota de entrega
            $consulta = "INSERT INTO notas_entrega (cliente_id, usuario_id, fecha, total, estado, presupuesto_id) 
                         VALUES (:cliente_id, :usuario_id, CURDATE(), :total, 'entregado', :presupuesto_id)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $datos['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':presupuesto_id', $datos['presupuesto_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $notaId = $this->conexion->lastInsertId();
            
            // Insertar cada item del detalle
            $consultaDetalle = "INSERT INTO nota_entrega_detalle (nota_id, insumo_id, cantidad, precio_unitario, subtotal) 
                                VALUES (:nota_id, :insumo_id, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);
            
            // Actualizar stock de cada insumo
            $consultaDescontar = "UPDATE insumos SET stock_actual = stock_actual - :cantidad WHERE id = :id";
            $stmtDescontar = $this->conexion->prepare($consultaDescontar);
            
            foreach ($detalle as $item) {
                // Insertar detalle
                $stmtDetalle->bindParam(':nota_id', $notaId, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':insumo_id', $item['insumo_id'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $item['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindParam(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();
                
                // Descontar del inventario
                $stmtDescontar->bindParam(':cantidad', $item['cantidad']);
                $stmtDescontar->bindParam(':id', $item['insumo_id'], PDO::PARAM_INT);
                $stmtDescontar->execute();
            }
            
            // Si viene de un presupuesto, marcarlo como convertido
            if (!empty($datos['presupuesto_id'])) {
                $consultaPresupuesto = "UPDATE presupuestos SET estado = 'convertido' WHERE id = :id";
                $stmtPresupuesto = $this->conexion->prepare($consultaPresupuesto);
                $stmtPresupuesto->bindParam(':id', $datos['presupuesto_id'], PDO::PARAM_INT);
                $stmtPresupuesto->execute();
            }
            
            // Confirmar transaccion
            $this->conexion->commit();
            
            return $notaId;
            
        } catch (PDOException $e) {
            // Revertir todo en caso de error
            $this->conexion->rollback();
            throw $e;
        }
    }

    // Busca notas de entrega por cliente o estado
    public function buscarNotas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id 
                     INNER JOIN usuarios u ON ne.usuario_id = u.id 
                     WHERE c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3 
                     ORDER BY ne.fecha DESC, ne.id DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}