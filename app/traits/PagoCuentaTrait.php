<?php
declare(strict_types=1);

namespace App\Traits;

use PDO;
use PDOException;

// TRAIT: PagoCuentaTrait
// OBJETIVO: Registro de pagos, soft-delete y totales del día compartidos por CuentaCobrarModel y CuentaPagarModel
// NOTA: La clase define sus tablas con los métodos abstractos; requiere ValidacionTrait y RespuestaModeloTrait (vía ModeloBase)
trait PagoCuentaTrait
{
    abstract protected function tablaCuenta(): string;       // cuentas_cobrar | cuentas_pagar
    abstract protected function pkCuenta(): string;          // id_cuenta_cobrar | id_cuenta_pagar
    abstract protected function tablaPagos(): string;        // pagos_recibidos | pagos_realizados
    abstract protected function etiquetaCuenta(): string;    // por cobrar | por pagar
    abstract public function buscarPorId(int $id): array|false;

    // FUNCIÓN: validarDatosPago
    // OBJETIVO: Valida reglas de negocio para registrar un pago de la cuenta
    public function validarDatosPago(array $datos): array
    {
        return $this->_ejecutarValidarDatosPago($datos);
    }

    private function _ejecutarValidarDatosPago(array $datos): array
    {
        $cuentaId = intval($datos['cuenta_id'] ?? 0);
        $monto = floatval($datos['monto'] ?? 0);
        $tipoPagoId = intval($datos['id_tipo_pago'] ?? 0);
        $bancoId = !empty($datos['id_banco']) ? intval($datos['id_banco']) : null;
        $referencia = trim($datos['referencia'] ?? '');
        $fecha = !empty($datos['fecha']) ? trim($datos['fecha']) : date('Y-m-d');

        if ($cuentaId < 1) {
            return ['valido' => false, 'mensaje' => 'Cuenta no valida'];
        }

        $cuenta = $this->buscarPorId($cuentaId);
        if (!$cuenta) {
            return ['valido' => false, 'mensaje' => 'Cuenta ' . $this->etiquetaCuenta() . ' no encontrada'];
        }

        if ($monto <= 0) {
            return ['valido' => false, 'mensaje' => 'El monto debe ser mayor a cero'];
        }

        if (!$this->validarFecha($fecha)) {
            return ['valido' => false, 'mensaje' => 'La fecha del pago no es válida'];
        }

        if (mb_strlen($referencia) > 100) {
            return ['valido' => false, 'mensaje' => 'La referencia no puede superar los 100 caracteres'];
        }

        if ($monto > (float)$cuenta['saldo_pendiente']) {
            return ['valido' => false, 'mensaje' => 'El monto del pago supera el saldo pendiente'];
        }

        if ($tipoPagoId < 1) {
            return ['valido' => false, 'mensaje' => 'Debe seleccionar un tipo de pago'];
        }

        $pago = $this->validarPagoBancario($tipoPagoId, $bancoId, $referencia, 'transferencia o pago móvil');
        if (!$pago['valido']) {
            return ['valido' => false, 'mensaje' => $pago['mensaje']];
        }

        return [
            'valido' => true,
            'cuenta_id' => $cuentaId,
            'monto' => $monto,
            'tipo_pago_id' => $tipoPagoId,
            'banco_id' => $pago['id_banco'],
            'referencia' => (string)$pago['referencia'],
            'fecha' => $fecha
        ];
    }

    // FUNCIÓN: procesarPago
    // OBJETIVO: Valida y aplica el pago a la cuenta
    public function procesarPago(array $datos): array
    {
        $val = $this->validarDatosPago($datos);
        if (!$val['valido']) {
            return $this->respuestaError($val['mensaje']);
        }
        return $this->_ejecutarProcesarPago($val);
    }

    private function _ejecutarProcesarPago(array $val): array
    {
        try {
            $this->registrarPago($val['cuenta_id'], $val['monto'], $val['tipo_pago_id'], $val['banco_id'], $val['referencia'], $val['fecha']);
            return $this->respuestaExito('Pago registrado exitosamente');
        } catch (PDOException $e) {
            error_log('Error al registrar pago de cuenta ' . $this->etiquetaCuenta() . ': ' . $e->getMessage());
            $esMensajePropio = str_starts_with($e->getMessage(), 'El monto') || str_starts_with($e->getMessage(), 'Cuenta');
            return $this->respuestaError($esMensajePropio ? $e->getMessage() : 'Error al registrar el pago');
        }
    }

    // FUNCIÓN: registrarPago
    // OBJETIVO: Registra un pago parcial o total y actualiza el saldo/situación de la cuenta
    // NOTA: Transacción con bloqueo de fila para que dos pagos simultáneos no pisen el saldo
    public function registrarPago(int $cuentaId, float $monto, int $tipoPagoId, ?int $bancoId = null, ?string $referencia = null, ?string $fecha = null): bool
    {
        if ($cuentaId < 1 || $monto <= 0 || $tipoPagoId < 1) {
            throw new PDOException('Parámetros obligatorios faltantes');
        }
        return $this->_ejecutarRegistrarPago($cuentaId, $monto, $tipoPagoId, $bancoId, $referencia, $fecha ?? date('Y-m-d H:i:s'));
    }

    private function _ejecutarRegistrarPago(int $cuentaId, float $monto, int $tipoPagoId, ?int $bancoId, ?string $referencia, string $fecha): bool
    {
        $tabla = $this->tablaCuenta();
        $pk = $this->pkCuenta();

        try {
            $this->conexion->beginTransaction();

            $stmtBloqueo = $this->conexion->prepare("SELECT saldo_pendiente FROM {$tabla} WHERE {$pk} = :id FOR UPDATE");
            $stmtBloqueo->bindValue(':id', $cuentaId, PDO::PARAM_INT);
            $stmtBloqueo->execute();
            $saldo = $stmtBloqueo->fetchColumn();

            if ($saldo === false || !$this->buscarPorId($cuentaId)) {
                throw new PDOException('Cuenta no encontrada');
            }
            if ($monto > (float)$saldo) {
                throw new PDOException('El monto del pago supera el saldo pendiente');
            }

            $stmtPago = $this->conexion->prepare("INSERT INTO {$this->tablaPagos()} ({$pk}, id_tipo_pago, id_banco, monto, fecha, referencia, tasa_bcv)
                                                  VALUES (:cuenta_id, :id_tipo_pago, :id_banco, :monto, :fecha, :referencia, {$this->sqlTasaPago(':fecha_tasa')})");
            $stmtPago->bindValue(':cuenta_id', $cuentaId, PDO::PARAM_INT);
            $stmtPago->bindValue(':id_tipo_pago', $tipoPagoId, PDO::PARAM_INT);
            $stmtPago->bindValue(':id_banco', $bancoId, $bancoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtPago->bindValue(':monto', $monto);
            $stmtPago->bindValue(':fecha', $fecha, PDO::PARAM_STR);
            $stmtPago->bindValue(':fecha_tasa', $fecha, PDO::PARAM_STR);
            $stmtPago->bindValue(':referencia', $referencia, $referencia === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmtPago->execute();

            // Umbral para no dejar residuos de punto flotante como saldo
            $nuevoSaldo = round((float)$saldo - $monto, 2);
            $situacion = $nuevoSaldo <= 0.001 ? 'pagado' : 'pendiente';
            if ($situacion === 'pagado') {
                $nuevoSaldo = 0;
            }

            $stmtActualizar = $this->conexion->prepare("UPDATE {$tabla} SET saldo_pendiente = :saldo, situacion = :situacion WHERE {$pk} = :id");
            $stmtActualizar->bindValue(':saldo', $nuevoSaldo);
            $stmtActualizar->bindValue(':situacion', $situacion, PDO::PARAM_STR);
            $stmtActualizar->bindValue(':id', $cuentaId, PDO::PARAM_INT);
            $stmtActualizar->execute();

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    // FUNCIÓN: darDeBaja
    // OBJETIVO: Soft-delete permitido solo si la cuenta está totalmente pagada
    public function darDeBaja(int $id): array
    {
        if ($id < 1) {
            return $this->respuestaError('ID invalido');
        }
        return $this->_ejecutarDarDeBaja($id);
    }

    private function _ejecutarDarDeBaja(int $id): array
    {
        $cuenta = $this->buscarPorId($id);
        if (!$cuenta) {
            return $this->respuestaError('Cuenta ' . $this->etiquetaCuenta() . ' no encontrada');
        }

        $saldo = floatval($cuenta['saldo_pendiente'] ?? 0);
        $situacion = strtolower($cuenta['situacion'] ?? ($cuenta['estado'] ?? ''));
        if ($saldo > 0 || $situacion !== 'pagado') {
            return $this->respuestaError('No se puede eliminar una cuenta ' . $this->etiquetaCuenta() . ' que no esté totalmente pagada');
        }

        try {
            $this->eliminarCuenta($id);
            return $this->respuestaExito('Cuenta eliminada correctamente');
        } catch (PDOException $e) {
            error_log('Error al eliminar cuenta ' . $this->etiquetaCuenta() . ': ' . $e->getMessage());
            return $this->respuestaError('Error al eliminar la cuenta');
        }
    }

    // FUNCIÓN: eliminarCuenta
    // OBJETIVO: Desactiva la cuenta (soft delete)
    public function eliminarCuenta(int $id): bool
    {
        if ($id < 1) {
            throw new PDOException('ID no válido');
        }
        return $this->_ejecutarDelete($id);
    }

    private function _ejecutarDelete(int $id): bool
    {
        $stmt = $this->conexion->prepare("UPDATE {$this->tablaCuenta()} SET estado = 0 WHERE {$this->pkCuenta()} = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: obtenerTotalPagosHoy
    // OBJETIVO: Suma de los pagos de la cuenta registrados hoy (dashboard)
    public function obtenerTotalPagosHoy(): string|false
    {
        return $this->_ejecutarTotalPagosHoy();
    }

    private function _ejecutarTotalPagosHoy(): string|false
    {
        return $this->conexion->query("SELECT COALESCE(SUM(monto), 0) as total FROM {$this->tablaPagos()} WHERE DATE(fecha) = CURDATE()")->fetchColumn();
    }
}
