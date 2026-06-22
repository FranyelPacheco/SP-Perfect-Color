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
    // OBJETIVO: Obtiene todas las notas de entrega activas en un rango de fechas, con filtros opcionales
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
    // NOTA: Lanza InvalidArgumentException si la validación falla
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
        $condiciones = ["ne.activo = 1", "DATE(ne.fecha) BETWEEN :desde AND :hasta"];
        $params = [':desde' => $this->desde, ':hasta' => $this->hasta];

        if ($idCliente !== null) {
            $condiciones[] = "ne.id_cliente = :id_cliente";
            $params[':id_cliente'] = $idCliente;
        }
        if ($condicion !== null) {
            $condiciones[] = "ne.condicion_pago = :condicion";
            $params[':condicion'] = $condicion;
        }
        if ($idTipoPago !== null) {
            $condiciones[] = "ne.id_tipo_pago = :id_tipo_pago";
            $params[':id_tipo_pago'] = $idTipoPago;
        }

        $where = implode(' AND ', $condiciones);
        $consulta = "SELECT ne.*,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre,
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
                     WHERE $where
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function _ejecutarTotalVentasPorTipoPago(): array
    {
        $consulta = "SELECT COALESCE(condicion_pago, 'sin_asignar') as tipo, COUNT(*) as cantidad, SUM(total) as total
                     FROM notas_entrega
                     WHERE activo = 1 AND DATE(fecha) BETWEEN :desde AND :hasta
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
        $consulta = "SELECT COALESCE(tp.nombre, 'sin_asignar') as metodo, COUNT(*) as cantidad, SUM(ne.total) as total
                     FROM notas_entrega ne
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.activo = 1 AND DATE(ne.fecha) BETWEEN :desde AND :hasta AND ne.condicion_pago = 'contado'
                     GROUP BY ne.id_tipo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $this->hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _ejecutarCarteraCxc(?int $idCliente = null, ?string $estado = null): array
    {
        $condiciones = ["cc.activo = 1", "DATE(cc.created_at) BETWEEN :desde AND :hasta"];
        $params = [':desde' => $this->desde, ':hasta' => $this->hasta];

        if ($idCliente !== null) {
            $condiciones[] = "cc.id_cliente = :id_cliente";
            $params[':id_cliente'] = $idCliente;
        }
        if ($estado !== null) {
            $condiciones[] = "cc.estado = :estado";
            $params[':estado'] = $estado;
        } else {
            $condiciones[] = "cc.estado IN ('pendiente', 'moroso')";
        }

        $where = implode(' AND ', $condiciones);
        $consulta = "SELECT cc.*,
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        ne.id_nota_entrega as id_nota_entrega,
                        ne.fecha as nota_fecha
                 FROM cuentas_cobrar cc
                 INNER JOIN clientes c ON cc.id_cliente = c.id_cliente
                 LEFT JOIN notas_entrega ne ON cc.id_nota_entrega = ne.id_nota_entrega
                 WHERE $where
                 ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
