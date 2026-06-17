<?php

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

    public function listarTodos()
    {
        return $this->_listarTodos();
    }

    private function _listarTodos()
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre,
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id_cliente
                     INNER JOIN usuarios u ON ne.usuario_id = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.tipo_pago_id = tp.id_tipo_pago
                     WHERE ne.activo = 1
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.direccion as cliente_direccion,
                            (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.cliente_id = c.id_cliente) as cliente_telefonos,
                            u.nombre as usuario_nombre,
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id_cliente
                     INNER JOIN usuarios u ON ne.usuario_id = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.tipo_pago_id = tp.id_tipo_pago
                     WHERE ne.id_nota_entrega = :id AND ne.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function obtenerDetalle($notaId)
    {
        return $this->_obtenerDetalle($notaId);
    }

    private function _obtenerDetalle($notaId)
    {
        $consulta = "SELECT ned.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo,
                            i.marca as insumo_marca, i.id_insumo as insumo_id,
                            pd.insumo_id as pd_insumo_id
                     FROM nota_entrega_detalle ned
                     INNER JOIN presupuesto_detalle pd ON ned.presupuesto_detalle_id = pd.id_presupuesto_detalle
                     INNER JOIN insumos i ON pd.insumo_id = i.id_insumo
                     WHERE ned.nota_id = :nota_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nota_id', $notaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function crearNotaEntrega($datos, $detalle)
    {
        return $this->_crearNotaEntrega($datos, $detalle);
    }

    private function _crearNotaEntrega($datos, $detalle)
    {
        try {
            $this->conexion->beginTransaction();
            
            foreach ($detalle as $item) {
                $consultaStock = "SELECT i.stock_actual FROM insumos i
                                  INNER JOIN presupuesto_detalle pd ON pd.insumo_id = i.id_insumo
                                  WHERE pd.id_presupuesto_detalle = :pd_id AND i.activo = 1 FOR UPDATE";
                $stmtStock = $this->conexion->prepare($consultaStock);
                $stmtStock->bindValue(':pd_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtStock->execute();
                $insumo = $stmtStock->fetch();
                
                if (!$insumo || $insumo['stock_actual'] < $item['cantidad']) {
                    throw new PDOException('Stock insuficiente para el item ID: ' . $item['presupuesto_detalle_id']);
                }
            }
            
            $estadoNota = !empty($datos['estado']) ? $datos['estado'] : 'pendiente';
            $condicionPago = !empty($datos['condicion_pago']) ? $datos['condicion_pago'] : 'contado';
            $tipoPagoId = !empty($datos['tipo_pago_id']) ? $datos['tipo_pago_id'] : null;
            $consulta = "INSERT INTO notas_entrega (cliente_id, usuario_id, fecha, total, estado, condicion_pago, tipo_pago_id, presupuesto_id) 
                          VALUES (:cliente_id, :usuario_id, NOW(), :total, :estado, :condicion_pago, :tipo_pago_id, :presupuesto_id)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindValue(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $datos['usuario_id'], PDO::PARAM_INT);
            $stmt->bindValue(':total', $datos['total']);
            $stmt->bindValue(':estado', $estadoNota, PDO::PARAM_STR);
            $stmt->bindValue(':condicion_pago', $condicionPago, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_pago_id', $tipoPagoId, empty($tipoPagoId) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':presupuesto_id', $datos['presupuesto_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $notaId = $this->conexion->lastInsertId();
            
            $consultaDetalle = "INSERT INTO nota_entrega_detalle (nota_id, presupuesto_detalle_id, cantidad, precio_unitario, subtotal) 
                                VALUES (:nota_id, :presupuesto_detalle_id, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);
            
            $consultaDescontar = "UPDATE insumos i
                                  INNER JOIN presupuesto_detalle pd ON pd.insumo_id = i.id_insumo
                                  SET i.stock_actual = i.stock_actual - :cantidad
                                  WHERE pd.id_presupuesto_detalle = :pd_id";
            $stmtDescontar = $this->conexion->prepare($consultaDescontar);
            
            foreach ($detalle as $item) {
                $stmtDetalle->bindValue(':nota_id', $notaId, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':presupuesto_detalle_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();
                
                $stmtDescontar->bindValue(':cantidad', $item['cantidad']);
                $stmtDescontar->bindValue(':pd_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtDescontar->execute();
            }
            
            $consultaPresupuesto = "UPDATE presupuestos SET estado = 'convertido' WHERE id_presupuesto = :id";
            $stmtPresupuesto = $this->conexion->prepare($consultaPresupuesto);
            $stmtPresupuesto->bindValue(':id', $datos['presupuesto_id'], PDO::PARAM_INT);
            $stmtPresupuesto->execute();
            
            if (!empty($datos['condicion_pago'])) {
                if ($datos['condicion_pago'] === 'credito') {
                    $fechaVen = !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : date('Y-m-d', strtotime('+10 days'));
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
                } else {
                    $tipoPagoId = !empty($datos['tipo_pago_id']) ? $datos['tipo_pago_id'] : 1;
                    $bancoId = !empty($datos['banco_id']) ? $datos['banco_id'] : null;
                    $referencia = !empty($datos['referencia']) ? $datos['referencia'] : null;
                    $consultaPago = "INSERT INTO pagos_recibidos (cuenta_cobrar_id, tipo_pago_id, banco_id, monto, fecha, referencia) 
                                     VALUES (NULL, :tipo_pago_id, :banco_id, :monto, NOW(), :referencia)";
                    $stmtPago = $this->conexion->prepare($consultaPago);
                    $stmtPago->bindValue(':tipo_pago_id', $tipoPagoId, PDO::PARAM_INT);
                    $stmtPago->bindValue(':banco_id', $bancoId, empty($bancoId) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmtPago->bindValue(':monto', $datos['total']);
                    $stmtPago->bindValue(':referencia', $referencia, empty($referencia) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmtPago->execute();
                }
            }
            
            $this->conexion->commit();
            
            return $notaId;
            
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    public function ponerEnEspera($id)
    {
        return $this->_cambiarEstado($id, 'en_espera');
    }

    public function cambiarEstado($id, $estado)
    {
        return $this->_cambiarEstado($id, $estado);
    }

    private function _cambiarEstado($id, $estado)
    {
        $consulta = "UPDATE notas_entrega SET estado = :estado WHERE id_nota_entrega = :id AND activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function actualizarDetalleNota($id, $detalle)
    {
        return $this->_actualizarDetalleNota($id, $detalle);
    }

    private function _actualizarDetalleNota($id, $detalle)
    {
        try {
            $this->conexion->beginTransaction();

            $detalleAnterior = $this->_obtenerDetalle($id);
            foreach ($detalleAnterior as $item) {
                $consultaRestaurar = "UPDATE insumos i
                                      INNER JOIN presupuesto_detalle pd ON pd.insumo_id = i.id_insumo
                                      SET i.stock_actual = i.stock_actual + :cantidad
                                      WHERE pd.id_presupuesto_detalle = :pd_id";
                $stmtRestaurar = $this->conexion->prepare($consultaRestaurar);
                $stmtRestaurar->bindValue(':cantidad', $item['cantidad']);
                $stmtRestaurar->bindValue(':pd_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtRestaurar->execute();
            }

            $consultaEliminar = "DELETE FROM nota_entrega_detalle WHERE nota_id = :nota_id";
            $stmtEliminar = $this->conexion->prepare($consultaEliminar);
            $stmtEliminar->bindValue(':nota_id', $id, PDO::PARAM_INT);
            $stmtEliminar->execute();

            $total = 0;
            $consultaDetalle = "INSERT INTO nota_entrega_detalle (nota_id, presupuesto_detalle_id, cantidad, precio_unitario, subtotal) 
                                VALUES (:nota_id, :presupuesto_detalle_id, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);

            $consultaDescontar = "UPDATE insumos i
                                  INNER JOIN presupuesto_detalle pd ON pd.insumo_id = i.id_insumo
                                  SET i.stock_actual = i.stock_actual - :cantidad
                                  WHERE pd.id_presupuesto_detalle = :pd_id";
            $stmtDescontar = $this->conexion->prepare($consultaDescontar);

            foreach ($detalle as $item) {
                $consultaStock = "SELECT i.stock_actual FROM insumos i
                                  INNER JOIN presupuesto_detalle pd ON pd.insumo_id = i.id_insumo
                                  WHERE pd.id_presupuesto_detalle = :pd_id AND i.activo = 1";
                $stmtStock = $this->conexion->prepare($consultaStock);
                $stmtStock->bindValue(':pd_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtStock->execute();
                $insumo = $stmtStock->fetch();

                if (!$insumo || $insumo['stock_actual'] < $item['cantidad']) {
                    throw new PDOException('Stock insuficiente para el item ID: ' . $item['presupuesto_detalle_id']);
                }

                $stmtDetalle->bindValue(':nota_id', $id, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':presupuesto_detalle_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();

                $stmtDescontar->bindValue(':cantidad', $item['cantidad']);
                $stmtDescontar->bindValue(':pd_id', $item['presupuesto_detalle_id'], PDO::PARAM_INT);
                $stmtDescontar->execute();

                $total += $item['subtotal'];
            }

            $consultaTotal = "UPDATE notas_entrega SET total = :total WHERE id_nota_entrega = :id";
            $stmtTotal = $this->conexion->prepare($consultaTotal);
            $stmtTotal->bindValue(':total', $total);
            $stmtTotal->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtTotal->execute();

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    public function buscarNotas($termino)
    {
        return $this->_buscarNotas($termino);
    }

    private function _buscarNotas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre,
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id_cliente
                     INNER JOIN usuarios u ON ne.usuario_id = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.tipo_pago_id = tp.id_tipo_pago
                     WHERE ne.activo = 1 AND (c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3) 
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
