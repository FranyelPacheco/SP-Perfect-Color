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
    // OBJETIVO: Obtiene todas las notas de entrega activas en un rango de fechas
    public function ventasPorRango(string $desde, string $hasta): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarVentasPorRango();
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
    // OBJETIVO: Obtiene cuentas por cobrar pendientes/morosas en un rango de fechas
    public function carteraCxc(string $desde, string $hasta): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarCarteraCxc();
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

    private function _ejecutarVentasPorRango(): array
    {
        $consulta = "SELECT ne.*,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre,
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.activo = 1 AND DATE(ne.fecha) BETWEEN :desde AND :hasta
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $this->hasta, PDO::PARAM_STR);
        $stmt->execute();
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

    private function _ejecutarCarteraCxc(): array
    {
        $consulta = "SELECT cc.*,
                        CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                        c.cedula as cliente_cedula,
                        ne.id_nota_entrega as id_nota_entrega,
                        ne.fecha as nota_fecha
                 FROM cuentas_cobrar cc
                 INNER JOIN clientes c ON cc.id_cliente = c.id_cliente
                 LEFT JOIN notas_entrega ne ON cc.id_nota_entrega = ne.id_nota_entrega
                 WHERE cc.activo = 1
                   AND cc.estado IN ('pendiente', 'moroso')
                   AND DATE(cc.created_at) BETWEEN :desde AND :hasta
                 ORDER BY cc.estado ASC, cc.fecha_vencimiento ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $this->hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
