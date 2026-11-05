<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\PagoCuentaTrait;
use App\Traits\TasaBcvTrait;
use PDO, PDOException;

// MODELO: CuentaPagarModel
// OBJETIVO: Cuentas por pagar a proveedores: listado, detalle, alta manual, abonos y creación desde las compras
// NOTA: Registro de pagos y baja lógica en PagoCuentaTrait
class CuentaPagarModel extends ModeloBase
{
    use TasaBcvTrait;
    use PagoCuentaTrait;

    protected function tablaCuenta(): string { return 'cuentas_pagar'; }
    protected function pkCuenta(): string { return 'id_cuenta_pagar'; }
    protected function tablaPagos(): string { return 'pagos_realizados'; }
    protected function etiquetaCuenta(): string { return 'por pagar'; }

    private int $id;
    private int $idProveedor;
    private float $montoTotal;
    private float $saldoPendiente;
    private string $fechaVencimiento;

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
            throw new PDOException('No se encontró el registro. Actualice la página e intente de nuevo.');
        }
        return $this->_ejecutarSelectPagos();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las cuentas activas con datos del proveedor obtenido vía compra
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT cp.*, cp.estado as activo, cp.situacion as estado, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            ne.fecha as nota_fecha,
                            {$this->sqlTasaBcv('ne.fecha')}
                     FROM cuentas_pagar cp 
                     INNER JOIN compra ne ON cp.id_compra = ne.id_compra
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     WHERE cp.estado = 1
                     ORDER BY cp.situacion ASC, cp.fecha_vencimiento ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una cuenta por ID con teléfonos del proveedor obtenido vía compra
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT cp.*, cp.estado as activo, cp.situacion as estado, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            ne.fecha as nota_fecha,
                            (SELECT GROUP_CONCAT(tp.telefono SEPARATOR ', ') FROM telefono_proveedor tp WHERE tp.id_proveedor = p.id_proveedor) as proveedor_telefonos,
                            {$this->sqlTasaBcv('ne.fecha')}
                     FROM cuentas_pagar cp 
                     INNER JOIN compra ne ON cp.id_compra = ne.id_compra
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
                            COALESCE(pr.tasa_bcv, {$this->sqlTasaPago('pr.fecha')}) as tasa_bcv
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

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta la inserción de una nueva cuenta por pagar asociada a su nota de entrega
    private function _ejecutarInsert(): int|false
    {
        $venc = substr((string)$this->fechaVencimiento, 0, 10);
        $fechaValida = \DateTime::createFromFormat('Y-m-d', $venc);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $venc) {
            return false;
        }

        $this->conexion->beginTransaction();
        try {
            return $this->_insertarCompraYCuenta();
        } catch (\Throwable $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    private function _insertarCompraYCuenta(): int|false
    {
        $stmtNe = $this->conexion->prepare("INSERT INTO compra (id_proveedor, fecha, total, observaciones, created_at, estado) 
                                            VALUES (:id_proveedor, NOW(), :total, 'Cuenta por pagar registrada directamente', NOW(), 1)");
        $stmtNe->bindParam(':id_proveedor', $this->idProveedor, PDO::PARAM_INT);
        $stmtNe->bindParam(':total', $this->montoTotal);
        $stmtNe->execute();
        $idNotaEntrega = (int)$this->conexion->lastInsertId();

        $consulta = "INSERT INTO cuentas_pagar (id_compra, monto_total, saldo_pendiente, fecha_vencimiento, created_at, estado) 
                     VALUES (:id_compra, :monto_total, :saldo_pendiente, :fecha_vencimiento, NOW(), 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_compra', $idNotaEntrega, PDO::PARAM_INT);
        $stmt->bindParam(':monto_total', $this->montoTotal);
        $stmt->bindParam(':saldo_pendiente', $this->saldoPendiente);
        $stmt->bindParam(':fecha_vencimiento', $this->fechaVencimiento, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $idCuenta = (int)$this->conexion->lastInsertId();
            $this->conexion->commit();
            return $idCuenta;
        }
        $this->conexion->rollBack();
        return false;
    }

}
