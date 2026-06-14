<?php
namespace App\Models;

use App\Core\ConexionBD;
use \PDO;

class ReporteModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    public function ventasPorRango($desde, $hasta)
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.cliente_id = c.id 
                     INNER JOIN usuarios u ON ne.usuario_id = u.id 
                     WHERE ne.activo = 1 AND ne.fecha BETWEEN :desde AND :hasta
                     ORDER BY ne.fecha DESC, ne.id DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function ingresosPorRango($desde, $hasta)
    {
        $consulta = "SELECT pr.*, cc.cliente_id, cc.nota_entrega_id,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre
                     FROM pagos_recibidos pr
                     LEFT JOIN cuentas_cobrar cc ON pr.cuenta_cobrar_id = cc.id
                     LEFT JOIN clientes c ON cc.cliente_id = c.id
                     WHERE pr.fecha BETWEEN :desde AND :hasta
                     ORDER BY pr.fecha DESC, pr.id DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        $pagos = $stmt->fetchAll();

        // Tambien obtener pagos directos de contado sin cuenta_cobrar
        $consultaDirectos = "SELECT ne.id as nota_entrega_id, ne.total as monto, ne.fecha, ne.metodo_pago,
                                    CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre
                             FROM notas_entrega ne
                             INNER JOIN clientes c ON ne.cliente_id = c.id
                             WHERE ne.activo = 1 AND ne.tipo_pago = 'contado'
                             AND ne.fecha BETWEEN :desde2 AND :hasta2
                             AND ne.id NOT IN (
                                SELECT cc.nota_entrega_id FROM cuentas_cobrar cc WHERE cc.nota_entrega_id IS NOT NULL
                             )
                             ORDER BY ne.fecha DESC, ne.id DESC";
        $stmt2 = $this->conexion->prepare($consultaDirectos);
        $stmt2->bindParam(':desde2', $desde, PDO::PARAM_STR);
        $stmt2->bindParam(':hasta2', $hasta, PDO::PARAM_STR);
        $stmt2->execute();
        $directos = $stmt2->fetchAll();

        return ['pagos' => $pagos, 'directos' => $directos];
    }

    public function egresosPorRango($desde, $hasta)
    {
        $consulta = "SELECT pr.*, cp.proveedor_id, p.nombre_empresa as proveedor_nombre
                     FROM pagos_realizados pr
                     INNER JOIN cuentas_pagar cp ON pr.cuenta_pagar_id = cp.id
                     INNER JOIN proveedores p ON cp.proveedor_id = p.id
                     WHERE pr.fecha BETWEEN :desde AND :hasta
                     ORDER BY pr.fecha DESC, pr.id DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Totales agrupados por tipo de pago (contado / credito)
    public function totalVentasPorTipoPago($desde, $hasta)
    {
        $consulta = "SELECT COALESCE(tipo_pago, 'sin_asignar') as tipo, COUNT(*) as cantidad, SUM(total) as total
                     FROM notas_entrega
                     WHERE activo = 1 AND fecha BETWEEN :desde AND :hasta
                     GROUP BY tipo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Totales agrupados por metodo de pago
    public function totalVentasPorMetodoPago($desde, $hasta)
    {
        $consulta = "SELECT COALESCE(metodo_pago, 'sin_asignar') as metodo, COUNT(*) as cantidad, SUM(total) as total
                     FROM notas_entrega
                     WHERE activo = 1 AND fecha BETWEEN :desde AND :hasta AND tipo_pago = 'contado'
                     GROUP BY metodo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Ingresos agrupados por metodo de pago (pagos_recibidos + contado directo)
    public function totalIngresosPorMetodoPago($desde, $hasta)
    {
        $consulta = "SELECT pr.metodo_pago as metodo, COUNT(*) as cantidad, SUM(pr.monto) as total
                     FROM pagos_recibidos pr
                     WHERE pr.fecha BETWEEN :desde AND :hasta
                     GROUP BY pr.metodo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Egresos agrupados por metodo de pago
    public function totalEgresosPorMetodoPago($desde, $hasta)
    {
        $consulta = "SELECT pr.metodo_pago as metodo, COUNT(*) as cantidad, SUM(pr.monto) as total
                     FROM pagos_realizados pr
                     WHERE pr.fecha BETWEEN :desde AND :hasta
                     GROUP BY pr.metodo_pago
                     ORDER BY total DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde', $desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta', $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
