<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

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

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

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
        $hoy = date('Y-m-d');
        $consulta = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos_realizados WHERE DATE(fecha) = :hoy";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las cuentas activas con datos del proveedor
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.id_proveedor = p.id_proveedor
                     WHERE cp.activo = 1
                     ORDER BY cp.estado ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una cuenta por ID con teléfonos del proveedor
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT cp.*, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            (SELECT GROUP_CONCAT(tp.telefono SEPARATOR ', ') FROM telf_proveedor tp WHERE tp.id_proveedor = p.id_proveedor) as proveedor_telefonos
                     FROM cuentas_pagar cp 
                     INNER JOIN proveedores p ON cp.id_proveedor = p.id_proveedor
                     WHERE cp.id_cuenta_pagar = :id AND cp.activo = 1";
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

            $nuevoSaldo = $cuenta['saldo_pendiente'] - $this->monto;
            $nuevoEstado = ($nuevoSaldo <= 0) ? 'pagado' : 'pendiente';

            $consulta = "UPDATE cuentas_pagar SET saldo_pendiente = :saldo, estado = :estado WHERE id_cuenta_pagar = :id";
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
    // OBJETIVO: Ejecuta la inserción de una nueva cuenta por pagar
    private function _ejecutarInsert(): int|false
    {
        $consulta = "INSERT INTO cuentas_pagar (id_proveedor, monto_total, saldo_pendiente, fecha_vencimiento, estado, activo) 
                     VALUES (:id_proveedor, :monto_total, :saldo_pendiente, :fecha_vencimiento, 'pendiente', 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $this->idProveedor, PDO::PARAM_INT);
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
        $consulta = "UPDATE cuentas_pagar SET activo = 0 WHERE id_cuenta_pagar = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca cuentas por pagar filtrando por nombre de empresa o RIF del proveedor
    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
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
}
