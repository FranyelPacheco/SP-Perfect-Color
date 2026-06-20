<?php

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;
use \PDOException;

class CuentaPagarModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    public function listarTodas()
    {
        return $this->_listarTodas();
    }

    private function _listarTodas()
    {
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.id_proveedor = p.id_proveedor
                     WHERE cp.activo = 1
                     ORDER BY cp.estado ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            (SELECT GROUP_CONCAT(tp.telefono SEPARATOR ', ') FROM telf_proveedor tp WHERE tp.id_proveedor = p.id_proveedor) as proveedor_telefonos
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.id_proveedor = p.id_proveedor
                     WHERE cp.id_cuenta_pagar = :id AND cp.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function obtenerPagos($cuentaId)
    {
        return $this->_obtenerPagos($cuentaId);
    }

    private function _obtenerPagos($cuentaId)
    {
        $consulta = "SELECT pr.*, tp.nombre as tipo_pago_nombre, b.nombre as banco_nombre
                     FROM pagos_realizados pr
                     LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago
                     LEFT JOIN banco b ON pr.id_banco = b.id_banco
                     WHERE pr.id_cuenta_pagar = :cuenta_id 
                     ORDER BY pr.fecha DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function registrarPago($cuentaId, $monto, $tipoPagoId, $bancoId = null, $referencia = null, $fecha = null)
    {
        return $this->_registrarPago($cuentaId, $monto, $tipoPagoId, $bancoId, $referencia, $fecha);
    }

    private function _registrarPago($cuentaId, $monto, $tipoPagoId, $bancoId = null, $referencia = null, $fecha = null)
    {
        try {
            $this->conexion->beginTransaction();
            
            $cuenta = $this->_buscarPorId($cuentaId);
            
            if (!$cuenta) {
                throw new PDOException('Cuenta no encontrada');
            }
            
            if ($monto > $cuenta['saldo_pendiente']) {
                throw new PDOException('El monto del pago supera el saldo pendiente');
            }
            
            $fecha = $fecha ?? date('Y-m-d H:i:s');
            $consulta = "INSERT INTO pagos_realizados (id_cuenta_pagar, id_tipo_pago, id_banco, monto, fecha, referencia) 
                         VALUES (:cuenta_id, :id_tipo_pago, :id_banco, :monto, :fecha, :referencia)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
            $stmt->bindParam(':id_tipo_pago', $tipoPagoId, PDO::PARAM_INT);
            $stmt->bindValue(':id_banco', $bancoId, empty($bancoId) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmt->bindValue(':referencia', $referencia, empty($referencia) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
            
            $nuevoSaldo = $cuenta['saldo_pendiente'] - $monto;
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';
            
            $consulta = "UPDATE cuentas_pagar SET saldo_pendiente = :saldo, estado = :estado WHERE id_cuenta_pagar = :id";
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

    public function crearCuenta($proveedorId, $montoTotal, $fechaVencimiento)
    {
        return $this->_crearCuenta($proveedorId, $montoTotal, $fechaVencimiento);
    }

    private function _crearCuenta($proveedorId, $montoTotal, $fechaVencimiento)
    {
        $consulta = "INSERT INTO cuentas_pagar (id_proveedor, monto_total, saldo_pendiente, fecha_vencimiento, estado, activo) 
                     VALUES (:id_proveedor, :monto_total, :saldo_pendiente, :fecha_vencimiento, 'pendiente', 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':monto_total', $montoTotal);
        $stmt->bindParam(':saldo_pendiente', $montoTotal);
        $stmt->bindParam(':fecha_vencimiento', $fechaVencimiento, PDO::PARAM_STR);
        $stmt->execute();
        
        return $this->conexion->lastInsertId();
    }

    public function obtenerTotalPagosHoy()
    {
        return $this->_obtenerTotalPagosHoy();
    }

    private function _obtenerTotalPagosHoy()
    {
        $hoy = date('Y-m-d');
        $consulta = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_realizados WHERE DATE(fecha) = :hoy";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function eliminarCuenta($id)
    {
        return $this->_eliminarCuenta($id);
    }

    private function _eliminarCuenta($id)
    {
        $consulta = "UPDATE cuentas_pagar SET activo = 0 WHERE id_cuenta_pagar = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function buscarCuentas($termino)
    {
        return $this->_buscarCuentas($termino);
    }

    private function _buscarCuentas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.id_proveedor = p.id_proveedor
                     WHERE cp.activo = 1 AND (p.nombre_empresa LIKE :termino1 
                        OR p.rif LIKE :termino2) 
                     ORDER BY cp.estado ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function listarTiposPago()
    {
        return $this->_listarTiposPago();
    }

    private function _listarTiposPago()
    {
        $consulta = "SELECT id_tipo_pago, nombre FROM tipo_pago WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    public function listarBancos()
    {
        return $this->_listarBancos();
    }

    private function _listarBancos()
    {
        $consulta = "SELECT id_banco, nombre FROM banco WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }
}
