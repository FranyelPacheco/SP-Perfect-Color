<?php
// Archivo: CuentaCobrarModel.php
// Modelo para operaciones con cuentas por cobrar

namespace App\Models;

use App\Core\ConexionBD;

class CuentaCobrarModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todas las cuentas por cobrar
    public function listarTodas()
    {
        $consulta = "SELECT cc.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.telefono as cliente_telefono,
                            f.numero_factura
                     FROM cuentas_cobrar cc 
                     INNER JOIN clientes c ON cc.cliente_id = c.id 
                     LEFT JOIN facturas f ON cc.factura_id = f.id 
                     ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca una cuenta por cobrar por ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT cc.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            f.numero_factura
                     FROM cuentas_cobrar cc 
                     INNER JOIN clientes c ON cc.cliente_id = c.id 
                     LEFT JOIN facturas f ON cc.factura_id = f.id 
                     WHERE cc.id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtiene los pagos de una cuenta
    public function obtenerPagos($cuentaId)
    {
        $consulta = "SELECT * FROM pagos_recibidos WHERE cuenta_cobrar_id = :cuenta_id ORDER BY fecha DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Registra un pago a una cuenta por cobrar
    public function registrarPago($cuentaId, $monto, $metodoPago)
    {
        try {
            $this->conexion->beginTransaction();
            
            // Obtener la cuenta
            $cuenta = $this->buscarPorId($cuentaId);
            
            if (!$cuenta) {
                throw new PDOException('Cuenta no encontrada');
            }
            
            if ($monto > $cuenta['saldo_pendiente']) {
                throw new PDOException('El monto del pago supera el saldo pendiente');
            }
            
            // Registrar el pago
            $consultaPago = "INSERT INTO pagos_recibidos (cuenta_cobrar_id, monto, fecha, metodo_pago) 
                             VALUES (:cuenta_id, :monto, CURDATE(), :metodo_pago)";
            $stmtPago = $this->conexion->prepare($consultaPago);
            $stmtPago->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
            $stmtPago->bindParam(':monto', $monto);
            $stmtPago->bindParam(':metodo_pago', $metodoPago, PDO::PARAM_STR);
            $stmtPago->execute();
            
            // Actualizar saldo pendiente
            $nuevoSaldo = $cuenta['saldo_pendiente'] - $monto;
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';
            
            $consultaActualizar = "UPDATE cuentas_cobrar SET saldo_pendiente = :saldo, estado = :estado WHERE id = :id";
            $stmtActualizar = $this->conexion->prepare($consultaActualizar);
            $stmtActualizar->bindParam(':saldo', $nuevoSaldo);
            $stmtActualizar->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
            $stmtActualizar->bindParam(':id', $cuentaId, PDO::PARAM_INT);
            $stmtActualizar->execute();
            
            // Si la cuenta esta pagada y tiene factura asociada, actualizar la factura
            if ($nuevoEstado == 'pagado' && !empty($cuenta['factura_id'])) {
                $consultaFactura = "UPDATE facturas SET estado = 'pagado' WHERE id = :id";
                $stmtFactura = $this->conexion->prepare($consultaFactura);
                $stmtFactura->bindParam(':id', $cuenta['factura_id'], PDO::PARAM_INT);
                $stmtFactura->execute();
            }
            
            $this->conexion->commit();
            
            return true;
            
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // Busca cuentas por cobrar por cliente
    public function buscarCuentas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT cc.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.telefono as cliente_telefono,
                            f.numero_factura
                     FROM cuentas_cobrar cc 
                     INNER JOIN clientes c ON cc.cliente_id = c.id 
                     LEFT JOIN facturas f ON cc.factura_id = f.id 
                     WHERE c.nombres LIKE :termino1 
                        OR c.apellidos LIKE :termino2 
                        OR c.cedula LIKE :termino3 
                     ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}