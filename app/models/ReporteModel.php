<?php
namespace App\Models;

use App\Core\ConexionBD;
use \PDO;
use \InvalidArgumentException;

class ReporteModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    public function ventasPorRango($desde, $hasta)
    {
        if (empty($desde) || empty($hasta)) {
            throw new InvalidArgumentException("Las fechas 'desde' y 'hasta' son obligatorias");
        }
        if ($desde > $hasta) {
            throw new InvalidArgumentException("La fecha 'desde' no puede ser mayor que 'hasta'");
        }
        return $this->_ventasPorRango($desde, $hasta);
    }

    public function totalVentasPorTipoPago($desde, $hasta)
    {
        if (empty($desde) || empty($hasta)) {
            throw new InvalidArgumentException("Las fechas 'desde' y 'hasta' son obligatorias");
        }
        if ($desde > $hasta) {
            throw new InvalidArgumentException("La fecha 'desde' no puede ser mayor que 'hasta'");
        }
        return $this->_totalVentasPorTipoPago($desde, $hasta);
    }

    public function totalVentasPorMetodoPago($desde, $hasta)
    {
        if (empty($desde) || empty($hasta)) {
            throw new InvalidArgumentException("Las fechas 'desde' y 'hasta' son obligatorias");
        }
        if ($desde > $hasta) {
            throw new InvalidArgumentException("La fecha 'desde' no puede ser mayor que 'hasta'");
        }
        return $this->_totalVentasPorMetodoPago($desde, $hasta);
    }

    public function carteraCxc($desde, $hasta)
    {
        if (empty($desde) || empty($hasta)) {
            throw new InvalidArgumentException("Las fechas 'desde' y 'hasta' son obligatorias");
        }
        if ($desde > $hasta) {
            throw new InvalidArgumentException("La fecha 'desde' no puede ser mayor que 'hasta'");
        }
        return $this->_carteraCxc($desde, $hasta);
    }

    private function _ventasPorRango($desde, $hasta)
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
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _totalVentasPorTipoPago($desde, $hasta)
    {
        $consulta = "SELECT COALESCE(condicion_pago, 'sin_asignar') as tipo, COUNT(*) as cantidad, SUM(total) as total
                     FROM notas_entrega
                     WHERE activo = 1 AND DATE(fecha) BETWEEN :desde AND :hasta
                     GROUP BY condicion_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _totalVentasPorMetodoPago($desde, $hasta)
    {
        $consulta = "SELECT COALESCE(tp.nombre, 'sin_asignar') as metodo, COUNT(*) as cantidad, SUM(ne.total) as total
                     FROM notas_entrega ne
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.activo = 1 AND DATE(ne.fecha) BETWEEN :desde AND :hasta AND ne.condicion_pago = 'contado'
                     GROUP BY ne.id_tipo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _carteraCxc($desde, $hasta)
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
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
