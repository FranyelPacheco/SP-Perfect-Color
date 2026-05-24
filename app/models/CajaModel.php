<?php
// Archivo: CajaModel.php
// Modelo para operaciones con la tabla caja

require_once __DIR__ . '/../core/conexionBD.php';

class CajaModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    // Verifica si hay una caja abierta para el usuario actual
    public function cajaAbierta($usuarioId)
    {
        $consulta = "SELECT * FROM caja WHERE usuario_id = :usuario_id AND estado = 'abierta' LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    // Abre una nueva caja
    public function abrirCaja($usuarioId, $montoInicial)
    {
        $consulta = "INSERT INTO caja (usuario_id, fecha_apertura, monto_inicial, estado) 
                     VALUES (:usuario_id, NOW(), :monto_inicial, 'abierta')";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindParam(':monto_inicial', $montoInicial);
        $stmt->execute();
        
        return $this->conexion->lastInsertId();
    }

    // Cierra una caja
    public function cerrarCaja($cajaId, $montoFinal)
    {
        $consulta = "UPDATE caja SET fecha_cierre = NOW(), monto_final = :monto_final, estado = 'cerrada' 
                     WHERE id = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':monto_final', $montoFinal);
        $stmt->bindParam(':id', $cajaId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // Obtiene el resumen de ventas del dia para una caja
    public function obtenerResumenCaja($cajaId)
    {
        // Ventas en efectivo
        $consultaEfectivo = "SELECT COALESCE(SUM(total), 0) as total 
                             FROM facturas 
                             WHERE caja_id = :caja_id AND metodo_pago = 'Efectivo' AND estado != 'anulado'";
        $stmt = $this->conexion->prepare($consultaEfectivo);
        $stmt->bindParam(':caja_id', $cajaId, PDO::PARAM_INT);
        $stmt->execute();
        $efectivo = $stmt->fetch()['total'];

        // Ventas por punto de venta
        $consultaPunto = "SELECT COALESCE(SUM(total), 0) as total 
                          FROM facturas 
                          WHERE caja_id = :caja_id AND metodo_pago = 'Punto de Venta' AND estado != 'anulado'";
        $stmt = $this->conexion->prepare($consultaPunto);
        $stmt->bindParam(':caja_id', $cajaId, PDO::PARAM_INT);
        $stmt->execute();
        $puntoVenta = $stmt->fetch()['total'];

        // Ventas por pago movil
        $consultaMovil = "SELECT COALESCE(SUM(total), 0) as total 
                          FROM facturas 
                          WHERE caja_id = :caja_id AND metodo_pago = 'Pago Movil' AND estado != 'anulado'";
        $stmt = $this->conexion->prepare($consultaMovil);
        $stmt->bindParam(':caja_id', $cajaId, PDO::PARAM_INT);
        $stmt->execute();
        $pagoMovil = $stmt->fetch()['total'];

        // Ventas a credito
        $consultaCredito = "SELECT COALESCE(SUM(total), 0) as total 
                            FROM facturas 
                            WHERE caja_id = :caja_id AND metodo_pago = 'Credito' AND estado != 'anulado'";
        $stmt = $this->conexion->prepare($consultaCredito);
        $stmt->bindParam(':caja_id', $cajaId, PDO::PARAM_INT);
        $stmt->execute();
        $credito = $stmt->fetch()['total'];

        // Total de facturas
        $consultaTotal = "SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total 
                          FROM facturas 
                          WHERE caja_id = :caja_id AND estado != 'anulado'";
        $stmt = $this->conexion->prepare($consultaTotal);
        $stmt->bindParam(':caja_id', $cajaId, PDO::PARAM_INT);
        $stmt->execute();
        $totales = $stmt->fetch();

        return [
            'efectivo' => $efectivo,
            'punto_venta' => $puntoVenta,
            'pago_movil' => $pagoMovil,
            'credito' => $credito,
            'total_general' => $totales['total'],
            'cantidad_facturas' => $totales['cantidad']
        ];
    }

    // Lista todas las cajas
    public function listarCajas()
    {
        $consulta = "SELECT c.*, u.nombre as usuario_nombre 
                     FROM caja c 
                     INNER JOIN usuarios u ON c.usuario_id = u.id 
                     ORDER BY c.fecha_apertura DESC 
                     LIMIT 50";
        $stmt = $this->conexion->query($consulta);
        
        return $stmt->fetchAll();
    }
}