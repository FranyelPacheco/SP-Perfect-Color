<?php
declare(strict_types=1);

namespace App\Models;

use PDO, PDOException;

class CuentaCobrarModel extends ModeloBase
{
    private int $id;
    private int $idCliente;
    private int $idNotaEntrega;
    private float $montoTotal;
    private float $saldoPendiente;
    private string $fechaVencimiento;
    private string $estado;
    private float $monto;
    private int $tipoPagoId;
    private ?int $bancoId = null;
    private ?string $referencia = null;
    private string $fecha;
    private int $dias;
    private string $termino;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodas
    // OBJETIVO: Obtiene todas las cuentas por cobrar activas con datos del cliente y nota
    public function listarTodas(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca una cuenta por cobrar por su ID con datos relacionados
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;
        if ($this->id < 1) {
            throw new PDOException('ID no válido');
        }
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: obtenerPagos
    // OBJETIVO: Obtiene todos los pagos recibidos asociados a una cuenta
    public function obtenerPagos(int $cuentaId): array
    {
        $this->id = $cuentaId;
        if ($this->id < 1) {
            throw new PDOException('ID de cuenta no válido');
        }
        return $this->_ejecutarSelectPagos();
    }

    // FUNCIÓN: validarDatosPago
    // OBJETIVO: Valida reglas de negocio para el registro de un pago de cuenta por cobrar
    public function validarDatosPago(array $datos): array
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
            return ['valido' => false, 'mensaje' => 'Cuenta por cobrar no encontrada'];
        }

        if ($monto <= 0) {
            return ['valido' => false, 'mensaje' => 'El monto debe ser mayor a cero'];
        }

        if ($monto > (float)$cuenta['saldo_pendiente']) {
            return ['valido' => false, 'mensaje' => 'El monto del pago supera el saldo pendiente'];
        }

        if ($tipoPagoId < 1) {
            return ['valido' => false, 'mensaje' => 'Debe seleccionar un tipo de pago'];
        }

        if ($tipoPagoId === 1) {
            $bancoId = null;
            if ($referencia === '') {
                $referencia = 'Efectivo';
            }
        } elseif ($tipoPagoId === 2 || $tipoPagoId === 3) {
            if (empty($bancoId) || $bancoId < 1) {
                return ['valido' => false, 'mensaje' => 'Debe seleccionar un banco para transferencia o pago movil'];
            }
            if ($referencia === '') {
                return ['valido' => false, 'mensaje' => 'Debe ingresar el numero de referencia para transferencia o pago movil'];
            }
        }

        return [
            'valido' => true,
            'cuenta_id' => $cuentaId,
            'monto' => $monto,
            'tipo_pago_id' => $tipoPagoId,
            'banco_id' => $bancoId,
            'referencia' => $referencia,
            'fecha' => $fecha
        ];
    }

    // FUNCIÓN: procesarPago
    // OBJETIVO: Valida y aplica el pago recibido a la cuenta por cobrar
    public function procesarPago(array $datos): array
    {
        $val = $this->validarDatosPago($datos);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        try {
            $this->registrarPago(
                $val['cuenta_id'],
                $val['monto'],
                $val['tipo_pago_id'],
                $val['banco_id'],
                $val['referencia'],
                $val['fecha']
            );
            return ['exito' => true, 'mensaje' => 'Pago registrado exitosamente'];
        } catch (\PDOException $e) {
            error_log('Error al registrar pago CxC: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al registrar el pago: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: darDeBaja
    // OBJETIVO: Valida que la cuenta esté saldada antes de realizar soft-delete
    public function darDeBaja(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID invalido'];
        }

        $cuenta = $this->buscarPorId($id);
        if (!$cuenta) {
            return ['exito' => false, 'mensaje' => 'Cuenta por cobrar no encontrada'];
        }

        $saldo = floatval($cuenta['saldo_pendiente'] ?? 0);
        $situacion = strtolower($cuenta['situacion'] ?? ($cuenta['estado'] ?? ''));
        if ($saldo > 0 || $situacion !== 'pagado') {
            return ['exito' => false, 'mensaje' => 'No se puede eliminar una cuenta por cobrar que no esté totalmente pagada'];
        }

        try {
            $this->eliminarCuenta($id);
            return ['exito' => true, 'mensaje' => 'Cuenta eliminada correctamente'];
        } catch (\PDOException $e) {
            error_log('Error al eliminar CxC: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al eliminar la cuenta'];
        }
    }

    // FUNCIÓN: registrarPago
    // OBJETIVO: Registra un pago parcial o total y actualiza el saldo/estado de la cuenta
    // NOTA: Transacción que valida que el monto no supere el saldo pendiente
    public function registrarPago(int $cuentaId, float $monto, int $tipoPagoId, ?int $bancoId = null, ?string $referencia = null, ?string $fecha = null): bool
    {
        $this->id = $cuentaId;
        $this->monto = $monto;
        $this->tipoPagoId = $tipoPagoId;
        $this->bancoId = $bancoId;
        $this->referencia = $referencia;
        $this->fecha = $fecha ?? date('Y-m-d H:i:s');

        if ($this->id < 1 || $this->monto <= 0 || $this->tipoPagoId < 1) {
            throw new PDOException('Parámetros obligatorios faltantes');
        }

        return $this->_ejecutarRegistrarPago();
    }

    // FUNCIÓN: obtenerTotalPagosHoy
    // OBJETIVO: Retorna la suma de todos los pagos recibidos en el día de hoy
    // NOTA: Usado en el dashboard para mostrar total de ingresos del día
    public function obtenerTotalPagosHoy(): string|false
    {
        return $this->_ejecutarTotalPagosHoy();
    }

    // FUNCIÓN: obtenerPagosPorDia
    // OBJETIVO: Obtiene el total de pagos agrupados por día en los últimos N días
    // NOTA: Usado para generar la gráfica de ingresos del dashboard
    public function obtenerPagosPorDia(int $dias = 7): array
    {
        $this->dias = $dias;
        return $this->_ejecutarPagosPorDia();
    }

    // FUNCIÓN: obtenerTotalPendiente
    // OBJETIVO: Suma los saldos pendientes de todas las CxC activas no pagadas
    public function obtenerTotalPendiente(): string
    {
        return $this->_ejecutarTotalPendiente();
    }

    // FUNCIÓN: obtenerCantidadVencidas
    // OBJETIVO: Cuenta las CxC cuya fecha de vencimiento ya pasó y no han sido pagadas
    public function obtenerCantidadVencidas(): int
    {
        return $this->_ejecutarCantidadVencidas();
    }

    // FUNCIÓN: eliminarCuenta
    // OBJETIVO: Desactiva una cuenta por cobrar (soft delete)
    public function eliminarCuenta(int $id): bool
    {
        $this->id = $id;
        if ($this->id < 1) {
            throw new PDOException('ID no válido');
        }
        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: buscarCuentas
    // OBJETIVO: Busca cuentas por cobrar por nombre o cédula del cliente
    public function buscarCuentas(string $termino): array
    {
        $this->termino = $termino;
        if ($this->termino === '') {
            throw new PDOException('Término de búsqueda no válido');
        }
        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las cuentas activas con datos del cliente y venta
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT cc.*, cc.estado as activo, cc.situacion as estado,
                        c.id_cliente,
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                        v.id_venta as id_nota_entrega,
                        v.id_venta as id_nota_venta,
                        v.id_venta,
                        v.fecha as nota_fecha,
                        COALESCE(
                            (SELECT t.tasa FROM tasa_cambio t WHERE t.fecha <= DATE(COALESCE(v.fecha_aprobacion, v.fecha)) AND t.estado = 1 ORDER BY t.fecha DESC, t.id_tasa_cambio DESC LIMIT 1),
                            (SELECT t2.tasa FROM tasa_cambio t2 WHERE t2.estado = 1 ORDER BY t2.fecha DESC, t2.id_tasa_cambio DESC LIMIT 1),
                            0
                        ) as tasa_bcv
                FROM cuentas_cobrar cc 
                INNER JOIN ventas v ON cc.id_venta = v.id_venta
                INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                WHERE cc.estado = 1
                ORDER BY cc.situacion ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una cuenta por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT cc.*, cc.estado as activo, cc.situacion as estado,
                        c.id_cliente,
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                        v.id_venta as id_nota_entrega,
                        v.id_venta as id_nota_venta,
                        v.id_venta,
                        v.fecha as nota_fecha,
                        COALESCE(
                            (SELECT t.tasa FROM tasa_cambio t WHERE t.fecha <= DATE(COALESCE(v.fecha_aprobacion, v.fecha)) AND t.estado = 1 ORDER BY t.fecha DESC, t.id_tasa_cambio DESC LIMIT 1),
                            (SELECT t2.tasa FROM tasa_cambio t2 WHERE t2.estado = 1 ORDER BY t2.fecha DESC, t2.id_tasa_cambio DESC LIMIT 1),
                            0
                        ) as tasa_bcv
                FROM cuentas_cobrar cc 
                INNER JOIN ventas v ON cc.id_venta = v.id_venta
                INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                WHERE cc.id_cuenta_cobrar = :id AND cc.estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarSelectPagos
    // OBJETIVO: Obtiene los pagos registrados para una cuenta, con tipo de pago, banco y tasa BCV histórica de la fecha del pago
    private function _ejecutarSelectPagos(): array
    {
        $consulta = "SELECT pr.*, tp.nombre as tipo_pago_nombre, b.nombre as banco_nombre,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(pr.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM pagos_recibidos pr
                     LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago
                     LEFT JOIN banco b ON pr.id_banco = b.id_banco
                     WHERE pr.id_cuenta_cobrar = :cuenta_id 
                     ORDER BY pr.fecha DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cuenta_id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarRegistrarPago
    // OBJETIVO: Ejecuta la transacción que inserta el pago y actualiza saldo/situacion de la cuenta
    private function _ejecutarRegistrarPago(): bool
    {
        try {
            $this->conexion->beginTransaction();

            $cuenta = $this->buscarPorId($this->id);

            if (!$cuenta) {
                throw new PDOException('Cuenta no encontrada');
            }

            if ($this->monto > $cuenta['saldo_pendiente']) {
                throw new PDOException('El monto del pago supera el saldo pendiente');
            }

            $consultaPago = "INSERT INTO pagos_recibidos (id_cuenta_cobrar, id_tipo_pago, id_banco, monto, fecha, referencia) 
                             VALUES (:cuenta_id, :id_tipo_pago, :id_banco, :monto, :fecha, :referencia)";
            $stmtPago = $this->conexion->prepare($consultaPago);
            $stmtPago->bindParam(':cuenta_id', $this->id, PDO::PARAM_INT);
            $stmtPago->bindParam(':id_tipo_pago', $this->tipoPagoId, PDO::PARAM_INT);
            $stmtPago->bindValue(':id_banco', $this->bancoId, $this->bancoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtPago->bindParam(':monto', $this->monto);
            $stmtPago->bindParam(':fecha', $this->fecha, PDO::PARAM_STR);
            $stmtPago->bindValue(':referencia', $this->referencia, $this->referencia === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmtPago->execute();

            $nuevoSaldo = round($cuenta['saldo_pendiente'] - $this->monto, 2);
            if ($nuevoSaldo <= 0.001) {
                $nuevoSaldo = 0;
                $nuevoEstado = 'pagado';
            } else {
                $nuevoEstado = 'pendiente';
            }

            $consultaActualizar = "UPDATE cuentas_cobrar SET saldo_pendiente = :saldo, situacion = :estado WHERE id_cuenta_cobrar = :id";
            $stmtActualizar = $this->conexion->prepare($consultaActualizar);
            $stmtActualizar->bindParam(':saldo', $nuevoSaldo);
            $stmtActualizar->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
            $stmtActualizar->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmtActualizar->execute();

            $this->conexion->commit();
            return true;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarTotalPagosHoy
    // OBJETIVO: Calcula la suma de pagos recibidos en la fecha actual
    private function _ejecutarTotalPagosHoy(): string|false
    {
        $consulta = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_recibidos WHERE DATE(fecha) = CURDATE()";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarPagosPorDia
    // OBJETIVO: Agrupa pagos por fecha en un rango de días para la gráfica de ingresos
    private function _ejecutarPagosPorDia(): array
    {
        $consulta = "SELECT DATE(fecha) as fecha, COALESCE(SUM(monto), 0) as total
                     FROM pagos_recibidos
                     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
                     GROUP BY DATE(fecha)
                     ORDER BY fecha ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':dias', $this->dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el soft delete de la cuenta por cobrar
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE cuentas_cobrar SET estado = 0 WHERE id_cuenta_cobrar = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarTotalPendiente
    // OBJETIVO: Suma saldo_pendiente de cuentas activas cuyo estado no sea pagado
    private function _ejecutarTotalPendiente(): string
    {
        $consulta = "SELECT COALESCE(SUM(saldo_pendiente), 0) as total
                     FROM cuentas_cobrar
                     WHERE estado = 1 AND situacion = 'pendiente'";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarCantidadVencidas
    // OBJETIVO: Cuenta CxC activas, no pagadas, con fecha vencimiento anterior a hoy
    private function _ejecutarCantidadVencidas(): int
    {
        $consulta = "SELECT COUNT(*) as total
                     FROM cuentas_cobrar
                     WHERE estado = 1
                       AND situacion <> 'pagado'
                       AND fecha_vencimiento IS NOT NULL
                       AND fecha_vencimiento < CURDATE()";
        $stmt = $this->conexion->query($consulta);
        return (int)$stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca cuentas por cobrar filtrando por nombre o cédula del cliente
    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT cc.*, cc.estado as activo, cc.situacion as estado,
                        c.id_cliente,
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                        v.id_venta as id_nota_entrega,
                        v.id_venta as id_nota_venta,
                        v.id_venta,
                        v.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN ventas v ON cc.id_venta = v.id_venta
                INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                WHERE cc.estado = 1 AND (c.nombres LIKE :termino1 
                    OR c.apellidos LIKE :termino2 
                    OR c.cedula LIKE :termino3) 
                ORDER BY cc.situacion ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
