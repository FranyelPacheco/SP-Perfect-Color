<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\PagoCuentaTrait;
use App\Traits\TasaBcvTrait;
use PDO, PDOException;

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
    private string $estado;
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

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las cuentas activas con datos del proveedor obtenido vía nota_entrega
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT cp.*, cp.estado as activo, cp.situacion as estado, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            ne.fecha as nota_fecha,
                            {$this->sqlTasaBcv('ne.fecha')}
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
                            {$this->sqlTasaBcv('ne.fecha')}
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
