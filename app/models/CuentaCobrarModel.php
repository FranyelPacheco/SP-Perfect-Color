<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\PagoCuentaTrait;
use App\Traits\TasaBcvTrait;
use PDO, PDOException;

class CuentaCobrarModel extends ModeloBase
{
    use TasaBcvTrait;
    use PagoCuentaTrait;

    protected function tablaCuenta(): string { return 'cuentas_cobrar'; }
    protected function pkCuenta(): string { return 'id_cuenta_cobrar'; }
    protected function tablaPagos(): string { return 'pagos_recibidos'; }
    protected function etiquetaCuenta(): string { return 'por cobrar'; }

    private int $id;
    private int $idCliente;
    private int $idNotaEntrega;
    private float $montoTotal;
    private float $saldoPendiente;
    private string $fechaVencimiento;
    private string $estado;
    private int $dias;
    private string $termino;

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

    // FUNCIÓN: obtenerPagosPorDia
    // OBJETIVO: Obtiene el total de pagos agrupados por día en los últimos N días
    // NOTA: Usado para generar la gráfica de ingresos del dashboard
    public function obtenerPagosPorDia(int $dias = 7): array
    {
        $this->dias = $dias;
        return $this->_ejecutarPagosPorDia();
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
                        {$this->sqlTasaBcv('COALESCE(v.fecha_aprobacion, v.fecha)')}
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
                        {$this->sqlTasaBcv('COALESCE(v.fecha_aprobacion, v.fecha)')}
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
                            {$this->sqlTasaBcv('pr.fecha')}
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
