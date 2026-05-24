<?php
// Archivo: CuentaPagarModel.php
// Modelo para operaciones con cuentas por pagar

require_once __DIR__ . '/../core/conexionBD.php';

class CuentaPagarModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Lista todas las cuentas por pagar
    public function listarTodas()
    {
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.proveedor_id = p.id 
                     ORDER BY cp.estado ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    // Busca una cuenta por pagar por ID
    public function buscarPorId($id)
    {
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            p.telefono as proveedor_telefono
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.proveedor_id = p.id 
                     WHERE cp.id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Obtiene los pagos realizados
    public function obtenerPagos($cuentaId)
    {
        $consulta = "SELECT * FROM pagos_realizados WHERE cuenta_pagar_id = :cuenta_id ORDER BY fecha DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Registra un pago
    public function registrarPago($cuentaId, $monto, $metodoPago)
    {
        try {
            $this->conexion->beginTransaction();
            
            $cuenta = $this->buscarPorId($cuentaId);
            
            if (!$cuenta) {
                throw new PDOException('Cuenta no encontrada');
            }
            
            if ($monto > $cuenta['saldo_pendiente']) {
                throw new PDOException('El monto del pago supera el saldo pendiente');
            }
            
            // Registrar pago
            $consulta = "INSERT INTO pagos_realizados (cuenta_pagar_id, monto, fecha, metodo_pago) 
                         VALUES (:cuenta_id, :monto, CURDATE(), :metodo_pago)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':metodo_pago', $metodoPago, PDO::PARAM_STR);
            $stmt->execute();
            
            // Actualizar saldo
            $nuevoSaldo = $cuenta['saldo_pendiente'] - $monto;
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';
            
            $consulta = "UPDATE cuentas_pagar SET saldo_pendiente = :saldo, estado = :estado WHERE id = :id";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':saldo', $nuevoSaldo);
            $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $cuentaId, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->conexion->commit();
            
            return true;
            
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // Busca cuentas por pagar
    public function buscarCuentas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.proveedor_id = p.id 
                     WHERE p.nombre_empresa LIKE :termino1 
                        OR p.rif LIKE :termino2 
                     ORDER BY cp.estado ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}