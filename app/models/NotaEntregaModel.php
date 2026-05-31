<?php
// Archivo: NotaEntregaModel.php
// Modelo para operaciones con notas de entrega

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;
use \PDOException;

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
                     WHERE ne.activo = 1
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
                     WHERE ne.id = :id AND ne.activo = 1";
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
        error_log('Intentando crear nota de entrega para cliente ID: ' . ($datos['cliente_id'] ?? 'N/A') . ', total=' . ($datos['total'] ?? 'N/A') . ', tipo_pago=' . ($datos['tipo_pago'] ?? 'N/A'));
        try {
            // Iniciar transaccion para asegurar integridad
            $this->conexion->beginTransaction();
            
            // Verificar stock disponible para cada item
            foreach ($detalle as $item) {
                $consultaStock = "SELECT stock_actual FROM insumos WHERE id = :id AND activo = 1 FOR UPDATE";
                $stmtStock = $this->conexion->prepare($consultaStock);
                $stmtStock->bindValue(':id', $item['insumo_id'], PDO::PARAM_INT);
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
            $stmt->bindValue(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $datos['usuario_id'], PDO::PARAM_INT);
            $stmt->bindValue(':total', $datos['total']);
            $stmt->bindValue(':presupuesto_id', $datos['presupuesto_id'], empty($datos['presupuesto_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
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
                $stmtDetalle->bindValue(':nota_id', $notaId, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':insumo_id', $item['insumo_id'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();
                
                // Descontar del inventario
                $stmtDescontar->bindValue(':cantidad', $item['cantidad']);
                $stmtDescontar->bindValue(':id', $item['insumo_id'], PDO::PARAM_INT);
                $stmtDescontar->execute();
            }
            
            // Si viene de un presupuesto, marcarlo como convertido
            if (!empty($datos['presupuesto_id'])) {
                $consultaPresupuesto = "UPDATE presupuestos SET estado = 'convertido' WHERE id = :id";
                $stmtPresupuesto = $this->conexion->prepare($consultaPresupuesto);
                $stmtPresupuesto->bindValue(':id', $datos['presupuesto_id'], PDO::PARAM_INT);
                $stmtPresupuesto->execute();
            }
            
            // Crear cuenta por cobrar asociada solo si es credito
            if (!empty($datos['tipo_pago']) && $datos['tipo_pago'] === 'credito') {
                error_log('CxC: Insertando cuenta por cobrar para nota_id=' . $notaId . ', total=' . $datos['total']);
                $fechaVen = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : date('Y-m-d', strtotime('+30 days'));
                $consultaCxC = "INSERT INTO cuentas_cobrar (cliente_id, nota_entrega_id, monto_total, saldo_pendiente, fecha_vencimiento, estado, activo) 
                                VALUES (:cliente_id, :nota_id, :monto_total, :saldo_pendiente, :fecha_vencimiento, 'pendiente', 1)";
                $stmtCxC = $this->conexion->prepare($consultaCxC);
                $stmtCxC->bindValue(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
                $stmtCxC->bindValue(':nota_id', $notaId, PDO::PARAM_INT);
                $stmtCxC->bindValue(':monto_total', $datos['total']);
                $stmtCxC->bindValue(':saldo_pendiente', $datos['total']);
                $stmtCxC->bindValue(':fecha_vencimiento', $fechaVen, PDO::PARAM_STR);
                if (!$stmtCxC->execute()) {
                    $errInfo = $stmtCxC->errorInfo();
                    throw new PDOException('Error al insertar en cuentas_cobrar: ' . ($errInfo[2] ?? 'desconocido'));
                }
                error_log('CxC: Cuenta creada exitosamente para nota_id=' . $notaId);
            } else {
                error_log('CxC: No se crea cuenta — tipo_pago=' . ($datos['tipo_pago'] ?? 'vacio'));
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
                     WHERE ne.activo = 1 AND (c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3) 
                     ORDER BY ne.fecha DESC, ne.id DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}