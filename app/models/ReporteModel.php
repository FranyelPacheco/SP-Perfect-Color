<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use InvalidArgumentException;

class ReporteModel extends ModeloBase
{
    private ?string $desde;
    private ?string $hasta;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: ventasPorRango
    // OBJETIVO: Obtiene todas las notas de venta activas en un rango de fechas, con filtros opcionales
    public function ventasPorRango(string $desde, string $hasta, ?int $idCliente = null, ?string $condicion = null, ?int $idTipoPago = null): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarVentasPorRango($idCliente, $condicion, $idTipoPago);
    }

    // FUNCIÓN: totalVentasPorTipoPago
    // OBJETIVO: Agrupa ventas por condicion_pago (contado/crédito) en un rango
    public function totalVentasPorTipoPago(string $desde, string $hasta): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarTotalVentasPorTipoPago();
    }

    // FUNCIÓN: totalVentasPorMetodoPago
    // OBJETIVO: Agrupa ventas de contado por método de pago en un rango
    public function totalVentasPorMetodoPago(string $desde, string $hasta): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarTotalVentasPorMetodoPago();
    }

    // FUNCIÓN: carteraCxc
    // OBJETIVO: Obtiene cuentas por cobrar en un rango de fechas, con filtros opcionales
    public function carteraCxc(string $desde, string $hasta, ?int $idCliente = null, ?string $estado = null): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarCarteraCxc($idCliente, $estado);
    }

    // FUNCIÓN: _validarRangoFechas
    // OBJETIVO: Valida que ambas fechas existan y que desde <= hasta
    private function _validarRangoFechas(): void
    {
        if (empty($this->desde) || empty($this->hasta)) {
            throw new InvalidArgumentException("Las fechas 'desde' y 'hasta' son obligatorias");
        }
        if ($this->desde > $this->hasta) {
            throw new InvalidArgumentException("La fecha 'desde' no puede ser mayor que 'hasta'");
        }
    }

    private function _ejecutarVentasPorRango(?int $idCliente = null, ?string $condicion = null, ?int $idTipoPago = null): array
    {
        $condiciones = ["v.estado = 1", "v.id_estado_venta = 2", "DATE(COALESCE(v.fecha_aprobacion, v.fecha)) BETWEEN :desde AND :hasta"];
        $params = [':desde' => $this->desde, ':hasta' => $this->hasta];

        if ($idCliente !== null) {
            $condiciones[] = "v.id_cliente = :id_cliente";
            $params[':id_cliente'] = $idCliente;
        }
        if ($condicion !== null) {
            $condiciones[] = "v.condicion_pago = :condicion";
            $params[':condicion'] = $condicion;
        }
        if ($idTipoPago !== null) {
            $condiciones[] = "COALESCE(v.id_tipo_pago, pr.id_tipo_pago) = :id_tipo_pago";
            $params[':id_tipo_pago'] = $idTipoPago;
        }

        $where = implode(' AND ', $condiciones);
        $consulta = "SELECT v.*,
                            v.id_venta as id_nota_venta,
                            v.id_venta as id_nota_entrega,
                            v.id_venta as id_presupuesto,
                            v.estado as activo,
                            c.id_cliente,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            'Sistema' as usuario_nombre,
                            COALESCE(tp_v.nombre, tp_pr.nombre, CASE WHEN v.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END) as tipo_pago_nombre
                     FROM ventas v
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     LEFT JOIN tipo_pago tp_v ON v.id_tipo_pago = tp_v.id_tipo_pago
                     LEFT JOIN cuentas_cobrar cc ON cc.id_venta = v.id_venta
                     LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar
                     LEFT JOIN tipo_pago tp_pr ON pr.id_tipo_pago = tp_pr.id_tipo_pago
                     WHERE $where
                     GROUP BY v.id_venta
                     ORDER BY COALESCE(v.fecha_aprobacion, v.fecha) DESC, v.id_venta DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function _ejecutarTotalVentasPorTipoPago(): array
    {
        $consulta = "SELECT COALESCE(condicion_pago, 'sin_asignar') as tipo, COUNT(*) as cantidad, SUM(total) as total
                     FROM ventas
                     WHERE estado = 1 AND id_estado_venta = 2 AND DATE(COALESCE(fecha_aprobacion, fecha)) BETWEEN :desde AND :hasta
                     GROUP BY condicion_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $this->hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _ejecutarTotalVentasPorMetodoPago(): array
    {
        $consulta = "SELECT COALESCE(tp.nombre, 'sin_asignar') as metodo, COUNT(*) as cantidad, SUM(v.total) as total
                     FROM ventas v
                     LEFT JOIN tipo_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
                     WHERE v.estado = 1 AND v.id_estado_venta = 2 AND DATE(COALESCE(v.fecha_aprobacion, v.fecha)) BETWEEN :desde AND :hasta AND v.condicion_pago = 'contado'
                     GROUP BY v.id_tipo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $this->hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _ejecutarCarteraCxc(?int $idCliente = null, ?string $estado = null): array
    {
        $condiciones = ["cc.estado = 1", "DATE(cc.created_at) BETWEEN :desde AND :hasta"];
        $params = [':desde' => $this->desde, ':hasta' => $this->hasta];

        if ($idCliente !== null) {
            $condiciones[] = "v.id_cliente = :id_cliente";
            $params[':id_cliente'] = $idCliente;
        }
        if ($estado !== null) {
            $condiciones[] = "cc.situacion = :estado";
            $params[':estado'] = $estado;
        } else {
            $condiciones[] = "cc.situacion = 'pendiente'";
        }

        $where = implode(' AND ', $condiciones);
        $consulta = "SELECT cc.*,
                            cc.situacion as estado,
                            cc.estado as activo,
                            c.id_cliente,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            v.id_venta as id_nota_entrega,
                            v.id_venta as id_nota_venta,
                            v.fecha as nota_fecha
                     FROM cuentas_cobrar cc
                     INNER JOIN ventas v ON cc.id_venta = v.id_venta
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     WHERE $where
                     ORDER BY cc.situacion ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
