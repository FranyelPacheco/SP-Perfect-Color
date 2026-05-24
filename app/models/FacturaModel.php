<?php
// Archivo: FacturaModel.php
// Modelo para operaciones con facturas

require_once __DIR__ . '/../core/conexionBD.php';

class FacturaModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todas las facturas
    public function listarTodos()
    {
        $consulta = "SELECT f.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM facturas f 
                     INNER JOIN clientes c ON f.cliente_id = c.id 
                     INNER JOIN usuarios u ON f.usuario_id = u.id 
                     ORDER BY f.fecha DESC, f.id DESC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca una factura por ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT f.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM facturas f 
                     INNER JOIN clientes c ON f.cliente_id = c.id 
                     INNER JOIN usuarios u ON f.usuario_id = u.id 
                     WHERE f.id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtiene el detalle de una factura
    public function obtenerDetalle($facturaId)
    {
        $consulta = "SELECT fd.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo
                     FROM factura_detalle fd
                     INNER JOIN insumos i ON fd.insumo_id = i.id
                     WHERE fd.factura_id = :factura_id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':factura_id', $facturaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Crea una nueva factura
    public function crearFactura($datos, $detalle)
    {
        try {
            $this->conexion->beginTransaction();
            
            // Generar numero de factura
            $fecha = date('Ymd');
            $consultaNumero = "SELECT COUNT(*) as total FROM facturas WHERE fecha = CURDATE()";
            $stmtNumero = $this->conexion->query($consultaNumero);
            $consecutivo = $stmtNumero->fetch()['total'] + 1;
            $numeroFactura = $fecha . '-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
            
            // Insertar la factura
            $consulta = "INSERT INTO facturas (cliente_id, usuario_id, caja_id, fecha, numero_factura, total, metodo_pago, estado, nota_entrega_id) 
                         VALUES (:cliente_id, :usuario_id, :caja_id, CURDATE(), :numero_factura, :total, :metodo_pago, :estado, :nota_entrega_id)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $datos['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':caja_id', $datos['caja_id'], PDO::PARAM_INT);
            $stmt->bindParam(':numero_factura', $numeroFactura, PDO::PARAM_STR);
            $stmt->bindParam(':total', $datos['total']);
            $stmt->bindParam(':metodo_pago', $datos['metodo_pago'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $datos['estado'], PDO::PARAM_STR);
            $stmt->bindParam(':nota_entrega_id', $datos['nota_entrega_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $facturaId = $this->conexion->lastInsertId();
            
            // Insertar detalle
            $consultaDetalle = "INSERT INTO factura_detalle (factura_id, insumo_id, cantidad, precio_unitario, subtotal) 
                                VALUES (:factura_id, :insumo_id, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);
            
            foreach ($detalle as $item) {
                $stmtDetalle->bindParam(':factura_id', $facturaId, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':insumo_id', $item['insumo_id'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $item['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindParam(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();
            }
            
            // Si es a credito, crear cuenta por cobrar
            if ($datos['metodo_pago'] === 'Credito') {
                $consultaCuenta = "INSERT INTO cuentas_cobrar (cliente_id, factura_id, monto_total, saldo_pendiente, fecha_vencimiento, estado) 
                                   VALUES (:cliente_id, :factura_id, :monto_total, :saldo_pendiente, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'pendiente')";
                $stmtCuenta = $this->conexion->prepare($consultaCuenta);
                $stmtCuenta->bindParam(':cliente_id', $datos['cliente_id'], PDO::PARAM_INT);
                $stmtCuenta->bindParam(':factura_id', $facturaId, PDO::PARAM_INT);
                $stmtCuenta->bindParam(':monto_total', $datos['total']);
                $stmtCuenta->bindParam(':saldo_pendiente', $datos['total']);
                $stmtCuenta->execute();
            }
            
            // Si viene de una nota de entrega, marcarla como entregada
            if (!empty($datos['nota_entrega_id'])) {
                $consultaNota = "UPDATE notas_entrega SET estado = 'entregado' WHERE id = :id";
                $stmtNota = $this->conexion->prepare($consultaNota);
                $stmtNota->bindParam(':id', $datos['nota_entrega_id'], PDO::PARAM_INT);
                $stmtNota->execute();
            }
            
            $this->conexion->commit();
            
            return [
                'factura_id' => $facturaId,
                'numero_factura' => $numeroFactura
            ];
            
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // Busca facturas por cliente o numero
    public function buscarFacturas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT f.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM facturas f 
                     INNER JOIN clientes c ON f.cliente_id = c.id 
                     INNER JOIN usuarios u ON f.usuario_id = u.id 
                     WHERE c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3 
                        OR f.numero_factura LIKE :termino4 
                     ORDER BY f.fecha DESC, f.id DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino4', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}