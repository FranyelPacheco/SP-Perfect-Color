<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use InvalidArgumentException;

class ReporteModel extends ModeloBase
{
    private ?string $desde;
    private ?string $hasta;

    // FUNCIÓN: ventasPorRango
    // OBJETIVO: Obtiene todas las notas de venta activas en un rango de fechas, con filtros opcionales
    public function ventasPorRango(string $desde, string $hasta, ?int $idCliente = null, ?string $condicion = null, ?int $idTipoPago = null): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarVentasPorRango($idCliente, $condicion, $idTipoPago);
    }

    // FUNCIÓN: comprasPorRango
    // OBJETIVO: Obtiene todas las notas de entrega de compras a proveedores en un rango de fechas
    public function comprasPorRango(string $desde, string $hasta, ?int $idProveedor = null, ?string $condicion = null): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        return $this->_ejecutarComprasPorRango($idProveedor, $condicion);
    }

    // FUNCIÓN: ambasPorRango
    // OBJETIVO: Obtiene el flujo consolidado de ventas y compras ordenado cronológicamente
    public function ambasPorRango(string $desde, string $hasta): array
    {
        $this->desde = $desde;
        $this->hasta = $hasta;
        $this->_validarRangoFechas();
        $consulta = "SELECT 'Venta' as tipo_transaccion,
                            v.id_venta as doc_numero,
                            DATE(COALESCE(v.fecha_aprobacion, v.fecha)) as fecha,
                            CONCAT(c.nombres, ' ', c.apellidos) as tercero_nombre,
                            c.cedula as tercero_doc,
                            CASE WHEN v.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END as condicion,
                            v.total as monto_ingreso,
                            0.00 as monto_egreso,
                            v.total as balance_fila
                     FROM ventas v
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     WHERE v.estado = 1 AND v.id_estado_venta = 2 AND DATE(COALESCE(v.fecha_aprobacion, v.fecha)) BETWEEN :desde1 AND :hasta1

                     UNION ALL

                     SELECT 'Compra' as tipo_transaccion,
                            ne.id_nota_entrega as doc_numero,
                            DATE(ne.fecha) as fecha,
                            p.nombre_empresa as tercero_nombre,
                            p.rif as tercero_doc,
                            CASE WHEN cp.id_cuenta_pagar IS NOT NULL AND cp.saldo_pendiente > 0 THEN 'Crédito' ELSE 'Contado' END as condicion,
                            0.00 as monto_ingreso,
                            ne.total as monto_egreso,
                            -ne.total as balance_fila
                     FROM nota_entrega ne
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     LEFT JOIN cuentas_pagar cp ON cp.id_nota_entrega = ne.id_nota_entrega AND cp.estado = 1
                     WHERE ne.estado = 1 AND DATE(ne.fecha) BETWEEN :desde2 AND :hasta2

                     ORDER BY fecha DESC, doc_numero DESC";

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':desde1', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta1', $this->hasta, PDO::PARAM_STR);
        $stmt->bindParam(':desde2', $this->desde, PDO::PARAM_STR);
        $stmt->bindParam(':hasta2', $this->hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
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

    private function _ejecutarComprasPorRango(?int $idProveedor = null, ?string $condicion = null): array
    {
        $condiciones = ["ne.estado = 1", "DATE(ne.fecha) BETWEEN :desde AND :hasta"];
        $params = [':desde' => $this->desde, ':hasta' => $this->hasta];

        if ($idProveedor !== null) {
            $condiciones[] = "ne.id_proveedor = :id_proveedor";
            $params[':id_proveedor'] = $idProveedor;
        }
        if ($condicion !== null) {
            if ($condicion === 'credito') {
                $condiciones[] = "cp.id_cuenta_pagar IS NOT NULL AND cp.saldo_pendiente > 0";
            } elseif ($condicion === 'contado') {
                $condiciones[] = "(cp.id_cuenta_pagar IS NULL OR cp.saldo_pendiente = 0)";
            }
        }

        $where = implode(' AND ', $condiciones);
        $consulta = "SELECT ne.id_nota_entrega as id_compra,
                            ne.id_nota_entrega,
                            ne.fecha,
                            ne.total,
                            ne.observaciones,
                            p.id_proveedor,
                            p.nombre_empresa as proveedor_nombre,
                            p.rif as proveedor_rif,
                            COALESCE(cp.situacion, 'pagado') as situacion_pago,
                            COALESCE(cp.saldo_pendiente, 0.00) as saldo_pendiente,
                            CASE WHEN cp.id_cuenta_pagar IS NOT NULL AND cp.saldo_pendiente > 0 THEN 'Crédito' ELSE 'Contado' END as condicion_pago
                     FROM nota_entrega ne
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     LEFT JOIN cuentas_pagar cp ON cp.id_nota_entrega = ne.id_nota_entrega AND cp.estado = 1
                     WHERE $where
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    }
