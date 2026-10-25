<?php
declare(strict_types=1);

namespace App\Models;

use PDO, PDOException;

class CuentaPagarModel extends ModeloBase
{
    private int $id;
    private int $idProveedor;
    private float $montoTotal;
    private float $saldoPendiente;
    private string $fechaVencimiento;
    private string $estado;
    private float $monto;
    private int $tipoPagoId;
    private ?int $bancoId = null;
    private ?string $referencia = null;
    private string $fecha;
    private string $termino;

    // FUNCIÓN: listarTodas
    // OBJETIVO: Obtiene todas las cuentas por pagar activas con datos del proveedor
    public function listarTodas(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca una cuenta por pagar por su ID con datos del proveedor
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;
        if ($this->id < 1) {
            throw new PDOException('ID no válido');
        }
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: obtenerPagos
    // OBJETIVO: Obtiene todos los pagos realizados asociados a una cuenta
    public function obtenerPagos(int $cuentaId): array
    {
        $this->id = $cuentaId;
        if ($this->id < 1) {
            throw new PDOException('ID de cuenta no válido');
        }
        return $this->_ejecutarSelectPagos();
    }

    // FUNCIÓN: validarDatosPago
    // OBJETIVO: Valida reglas de negocio para el registro de un pago de cuenta por pagar
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
            return ['valido' => false, 'mensaje' => 'Cuenta por pagar no encontrada'];
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
    // OBJETIVO: Valida y aplica el pago realizado a la cuenta por pagar
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
            error_log('Error al registrar pago CxP: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al registrar el pago: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: validarDatosManual
    // OBJETIVO: Valida la creación manual de una cuenta por pagar
    public function validarDatosManual(array $datos): array
    {
        $proveedorId = intval($datos['id_proveedor'] ?? 0);
        $montoTotal = floatval($datos['monto_total'] ?? 0);
        $fechaVencimiento = trim($datos['fecha_vencimiento'] ?? '');

        if ($proveedorId < 1) {
            return ['valido' => false, 'mensaje' => 'Debe seleccionar un proveedor'];
        }

        $stmtProv = $this->conexion->prepare("SELECT id_proveedor FROM proveedores WHERE id_proveedor = :id AND estado = 1 LIMIT 1");
        $stmtProv->bindParam(':id', $proveedorId, PDO::PARAM_INT);
        $stmtProv->execute();
        if (!$stmtProv->fetch()) {
            return ['valido' => false, 'mensaje' => 'El proveedor seleccionado no existe o está inactivo'];
        }

        if ($montoTotal <= 0) {
            return ['valido' => false, 'mensaje' => 'El monto total debe ser mayor a cero'];
        }

        if ($fechaVencimiento === '') {
            return ['valido' => false, 'mensaje' => 'Debe indicar una fecha de vencimiento'];
        }

        return [
            'valido' => true,
            'id_proveedor' => $proveedorId,
            'monto_total' => $montoTotal,
            'fecha_vencimiento' => $fechaVencimiento
        ];
    }

    // FUNCIÓN: registrarCuentaManual
    // OBJETIVO: Valida y registra una cuenta por pagar manual
    public function registrarCuentaManual(array $datos): array
    {
        $val = $this->validarDatosManual($datos);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        try {
            $cuentaId = $this->crearCuenta($val['id_proveedor'], $val['monto_total'], $val['fecha_vencimiento']);
            return [
                'exito' => true,
                'mensaje' => 'Cuenta por pagar creada exitosamente',
                'datos' => ['cuenta_id' => $cuentaId]
            ];
        } catch (\PDOException $e) {
            error_log('Error al crear CxP manual: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al crear la cuenta'];
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
            return ['exito' => false, 'mensaje' => 'Cuenta por pagar no encontrada'];
        }

        $saldo = floatval($cuenta['saldo_pendiente'] ?? 0);
        $situacion = strtolower($cuenta['situacion'] ?? ($cuenta['estado'] ?? ''));
        if ($saldo > 0 || $situacion !== 'pagado') {
            return ['exito' => false, 'mensaje' => 'No se puede eliminar una cuenta por pagar que no esté totalmente pagada'];
        }

        try {
            $this->eliminarCuenta($id);
            return ['exito' => true, 'mensaje' => 'Cuenta eliminada correctamente'];
        } catch (\PDOException $e) {
            error_log('Error al eliminar CxP: ' . $e->getMessage());
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

    // FUNCIÓN: crearCuenta
    // OBJETIVO: Crea una nueva cuenta por pagar con monto total y fecha de vencimiento
    public function crearCuenta(int $idProveedor, float $montoTotal, string $fechaVencimiento): int|false
    {
        $this->idProveedor = $idProveedor;
        $this->montoTotal = $montoTotal;
        $this->saldoPendiente = $montoTotal;
        $this->fechaVencimiento = $fechaVencimiento;

        if ($this->idProveedor < 1 || $this->montoTotal <= 0) {
            throw new PDOException('Parámetros obligatorios faltantes');
        }

        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: eliminarCuenta
    // OBJETIVO: Desactiva una cuenta por pagar (soft delete)
    public function eliminarCuenta(int $id): bool
    {
        $this->id = $id;
        if ($this->id < 1) {
            throw new PDOException('ID no válido');
        }
        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: buscarCuentas
    // OBJETIVO: Busca cuentas por pagar por nombre de empresa o RIF del proveedor
    public function buscarCuentas(string $termino): array
    {
        $this->termino = $termino;
        if ($this->termino === '') {
            throw new PDOException('Término de búsqueda no válido');
        }
        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: obtenerTotalPagosHoy
    // OBJETIVO: Retorna la suma de todos los pagos realizados en el día de hoy
    // NOTA: Usado en el dashboard
    public function obtenerTotalPagosHoy(): string|false
    {
        return $this->_ejecutarTotalPagosHoy();
    }

    // FUNCIÓN: _ejecutarTotalPagosHoy
    // OBJETIVO: Calcula la suma de pagos realizados en la fecha actual
    private function _ejecutarTotalPagosHoy(): string|false
    {
        $consulta = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_realizados WHERE DATE(fecha) = CURDATE()";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las cuentas activas con datos del proveedor obtenido vía nota_entrega
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT cp.*, cp.estado as activo, cp.situacion as estado, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            ne.fecha as nota_fecha,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(ne.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM cuentas_pagar cp 
                     INNER JOIN nota_entrega ne ON cp.id_nota_entrega = ne.id_nota_entrega
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     WHERE cp.estado = 1
                     ORDER BY cp.situacion ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una cuenta por ID con teléfonos del proveedor obtenido vía nota_entrega
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT cp.*, cp.estado as activo, cp.situacion as estado, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            ne.fecha as nota_fecha,
                            (SELECT GROUP_CONCAT(tp.telefono SEPARATOR ', ') FROM telefono_proveedor tp WHERE tp.id_proveedor = p.id_proveedor) as proveedor_telefonos,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(ne.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM cuentas_pagar cp 
                     INNER JOIN nota_entrega ne ON cp.id_nota_entrega = ne.id_nota_entrega
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     WHERE cp.id_cuenta_pagar = :id AND cp.estado = 1";
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
                     FROM pagos_realizados pr
                     LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago
                     LEFT JOIN banco b ON pr.id_banco = b.id_banco
                     WHERE pr.id_cuenta_pagar = :cuenta_id 
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

            $consulta = "INSERT INTO pagos_realizados (id_cuenta_pagar, id_tipo_pago, id_banco, monto, fecha, referencia) 
                         VALUES (:cuenta_id, :id_tipo_pago, :id_banco, :monto, :fecha, :referencia)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':cuenta_id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':id_tipo_pago', $this->tipoPagoId, PDO::PARAM_INT);
            $stmt->bindValue(':id_banco', $this->bancoId, $this->bancoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':monto', $this->monto);
            $stmt->bindParam(':fecha', $this->fecha, PDO::PARAM_STR);
            $stmt->bindValue(':referencia', $this->referencia, $this->referencia === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();

            $nuevoSaldo = round($cuenta['saldo_pendiente'] - $this->monto, 2);
            if ($nuevoSaldo <= 0.001) {
                $nuevoSaldo = 0;
                $nuevoEstado = 'pagado';
            } else {
                $nuevoEstado = 'pendiente';
            }

            $consulta = "UPDATE cuentas_pagar SET saldo_pendiente = :saldo, situacion = :estado WHERE id_cuenta_pagar = :id";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':saldo', $nuevoSaldo);
            $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $this->conexion->commit();
            return true;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta la inserción de una nueva cuenta por pagar asociada a su nota de entrega
    private function _ejecutarInsert(): int|false
    {
        $stmtNe = $this->conexion->prepare("INSERT INTO nota_entrega (id_proveedor, fecha, total, observaciones, created_at, estado) 
                                            VALUES (:id_proveedor, NOW(), :total, 'Cuenta por pagar registrada directamente', NOW(), 1)");
        $stmtNe->bindParam(':id_proveedor', $this->idProveedor, PDO::PARAM_INT);
        $stmtNe->bindParam(':total', $this->montoTotal);
        $stmtNe->execute();
        $idNotaEntrega = (int)$this->conexion->lastInsertId();

        $consulta = "INSERT INTO cuentas_pagar (id_nota_entrega, monto_total, saldo_pendiente, fecha_vencimiento, situacion, created_at, estado) 
                     VALUES (:id_nota_entrega, :monto_total, :saldo_pendiente, :fecha_vencimiento, 'pendiente', NOW(), 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_nota_entrega', $idNotaEntrega, PDO::PARAM_INT);
        $stmt->bindParam(':monto_total', $this->montoTotal);
        $stmt->bindParam(':saldo_pendiente', $this->saldoPendiente);
        $stmt->bindParam(':fecha_vencimiento', $this->fechaVencimiento, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return (int)$this->conexion->lastInsertId();
        }
        return false;
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el soft delete de la cuenta por pagar
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE cuentas_pagar SET estado = 0 WHERE id_cuenta_pagar = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca cuentas por pagar filtrando por nombre de empresa o RIF del proveedor vía nota_entrega
    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT cp.*, cp.estado as activo, cp.situacion as estado, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif
                     FROM cuentas_pagar cp 
                     INNER JOIN nota_entrega ne ON cp.id_nota_entrega = ne.id_nota_entrega
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     WHERE cp.estado = 1 AND (p.nombre_empresa LIKE :termino1 
                        OR p.rif LIKE :termino2) 
                     ORDER BY cp.situacion ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
