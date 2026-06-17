<?php

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;
use \PDOException;

class CuentaCobrarModel
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
        $consulta = "SELECT cc.*, 
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.cliente_id = c.id_cliente) as cliente_telefonos,
                        ne.id_nota_entrega as nota_id,
                        ne.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN clientes c ON cc.cliente_id = c.id_cliente
                LEFT JOIN notas_entrega ne ON cc.nota_entrega_id = ne.id_nota_entrega
                WHERE cc.activo = 1
                ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        if (empty($id)) {
            throw new PDOException('ID no válido');
        }
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT cc.*, 
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.cliente_id = c.id_cliente) as cliente_telefonos,
                        ne.id_nota_entrega as nota_id,
                        ne.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN clientes c ON cc.cliente_id = c.id_cliente
                LEFT JOIN notas_entrega ne ON cc.nota_entrega_id = ne.id_nota_entrega
                WHERE cc.id_cuenta_cobrar = :id AND cc.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function obtenerPagos($cuentaId)
    {
        if (empty($cuentaId)) {
            throw new PDOException('ID de cuenta no válido');
        }
        return $this->_obtenerPagos($cuentaId);
    }

    private function _obtenerPagos($cuentaId)
    {
        $consulta = "SELECT pr.*, tp.nombre as tipo_pago_nombre, b.nombre as banco_nombre
                     FROM pagos_recibidos pr
                     LEFT JOIN tipo_pago tp ON pr.tipo_pago_id = tp.id_tipo_pago
                     LEFT JOIN banco b ON pr.banco_id = b.id_banco
                     WHERE pr.cuenta_cobrar_id = :cuenta_id 
                     ORDER BY pr.fecha DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function registrarPago($cuentaId, $monto, $tipoPagoId, $bancoId = null, $referencia = null, $fecha = null)
    {
        if (empty($cuentaId) || empty($monto) || empty($tipoPagoId)) {
            throw new PDOException('Parámetros obligatorios faltantes');
        }
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
            $consultaPago = "INSERT INTO pagos_recibidos (cuenta_cobrar_id, tipo_pago_id, banco_id, monto, fecha, referencia) 
                             VALUES (:cuenta_id, :tipo_pago_id, :banco_id, :monto, :fecha, :referencia)";
            $stmtPago = $this->conexion->prepare($consultaPago);
            $stmtPago->bindParam(':cuenta_id', $cuentaId, PDO::PARAM_INT);
            $stmtPago->bindParam(':tipo_pago_id', $tipoPagoId, PDO::PARAM_INT);
            $stmtPago->bindValue(':banco_id', $bancoId, empty($bancoId) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtPago->bindParam(':monto', $monto);
            $stmtPago->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmtPago->bindValue(':referencia', $referencia, empty($referencia) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmtPago->execute();
            
            $nuevoSaldo = $cuenta['saldo_pendiente'] - $monto;
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';
            
            $consultaActualizar = "UPDATE cuentas_cobrar SET saldo_pendiente = :saldo, estado = :estado WHERE id_cuenta_cobrar = :id";
            $stmtActualizar = $this->conexion->prepare($consultaActualizar);
            $stmtActualizar->bindParam(':saldo', $nuevoSaldo);
            $stmtActualizar->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
            $stmtActualizar->bindParam(':id', $cuentaId, PDO::PARAM_INT);
            $stmtActualizar->execute();

            $this->conexion->commit();
            
            return true;
            
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    public function obtenerTotalPagosHoy()
    {
        return $this->_obtenerTotalPagosHoy();
    }

    private function _obtenerTotalPagosHoy()
    {
        $hoy = date('Y-m-d');
        $consulta = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_recibidos WHERE DATE(fecha) = :hoy";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function eliminarCuenta($id)
    {
        if (empty($id)) {
            throw new PDOException('ID no válido');
        }
        return $this->_eliminarCuenta($id);
    }

    private function _eliminarCuenta($id)
    {
        $consulta = "UPDATE cuentas_cobrar SET activo = 0 WHERE id_cuenta_cobrar = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function buscarCuentas($termino)
    {
        if (empty($termino)) {
            throw new PDOException('Término de búsqueda no válido');
        }
        return $this->_buscarCuentas($termino);
    }

    private function _buscarCuentas($termino)
    {
        $terminoLike = '%' . $termino . '%';
        $consulta = "SELECT cc.*, 
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.cliente_id = c.id_cliente) as cliente_telefonos,
                        ne.id_nota_entrega as nota_id,
                        ne.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN clientes c ON cc.cliente_id = c.id_cliente
                LEFT JOIN notas_entrega ne ON cc.nota_entrega_id = ne.id_nota_entrega
                WHERE cc.activo = 1 AND (c.nombres LIKE :termino1 
                    OR c.apellidos LIKE :termino2 
                    OR c.cedula LIKE :termino3) 
                ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
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
