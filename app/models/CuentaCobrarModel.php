<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

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
    // OBJETIVO: Ejecuta la consulta que lista todas las cuentas activas con datos del cliente y nota
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT cc.*, 
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                        ne.id_nota_entrega as id_nota_entrega,
                        ne.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN clientes c ON cc.id_cliente = c.id_cliente
                LEFT JOIN notas_entrega ne ON cc.id_nota_entrega = ne.id_nota_entrega
                WHERE cc.activo = 1
                ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una cuenta por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT cc.*, 
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                        ne.id_nota_entrega as id_nota_entrega,
                        ne.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN clientes c ON cc.id_cliente = c.id_cliente
                LEFT JOIN notas_entrega ne ON cc.id_nota_entrega = ne.id_nota_entrega
                WHERE cc.id_cuenta_cobrar = :id AND cc.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarSelectPagos
    // OBJETIVO: Obtiene los pagos registrados para una cuenta, con tipo de pago y banco
    private function _ejecutarSelectPagos(): array
    {
        $consulta = "SELECT pr.*, tp.nombre as tipo_pago_nombre, b.nombre as banco_nombre
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
    // OBJETIVO: Ejecuta la transacción que inserta el pago y actualiza saldo/estado de la cuenta
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

            $nuevoSaldo = $cuenta['saldo_pendiente'] - $this->monto;
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';

            $consultaActualizar = "UPDATE cuentas_cobrar SET saldo_pendiente = :saldo, estado = :estado WHERE id_cuenta_cobrar = :id";
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
        $consulta = "UPDATE cuentas_cobrar SET activo = 0 WHERE id_cuenta_cobrar = :id";
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
                     WHERE activo = 1 AND estado IN ('pendiente', 'moroso')";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarCantidadVencidas
    // OBJETIVO: Cuenta CxC activas, no pagadas, con fecha vencimiento anterior a hoy
    private function _ejecutarCantidadVencidas(): int
    {
        $consulta = "SELECT COUNT(*) as total
                     FROM cuentas_cobrar
                     WHERE activo = 1
                       AND estado <> 'pagado'
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
        $consulta = "SELECT cc.*, 
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                        ne.id_nota_entrega as id_nota_entrega,
                        ne.fecha as nota_fecha
                FROM cuentas_cobrar cc 
                INNER JOIN clientes c ON cc.id_cliente = c.id_cliente
                LEFT JOIN notas_entrega ne ON cc.id_nota_entrega = ne.id_nota_entrega
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
}
