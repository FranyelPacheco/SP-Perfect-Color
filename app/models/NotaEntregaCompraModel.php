<?php
declare(strict_types=1);

namespace App\Models;

use PDO, PDOException;

// MODELO: NotaEntregaCompraModel
// OBJETIVO: Gestión de Notas de Entrega de Compras a Proveedores (Recepción de Insumos, aumento de stock y Cuentas por Pagar)
class NotaEntregaCompraModel extends ModeloBase
{
    private ?int $id = null;
    private ?int $idProveedor = null;
    private ?string $fecha = null;
    private float $total = 0.00;
    private ?string $observaciones = null;
    private string $condicionPago = 'contado';
    private ?string $fechaVencimiento = null;
    private array $items = [];

    // FUNCIÓN: listarTodas
    // OBJETIVO: Lista todas las notas de entrega de compras con datos del proveedor y situación de pago
    public function listarTodas(): array
    {
        $consulta = "SELECT ne.id_nota_entrega, ne.fecha, ne.total, ne.observaciones, ne.created_at, ne.estado,
                            p.id_proveedor, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif,
                            cp.id_cuenta_pagar, 
                            COALESCE(cp.situacion, 'pagado') as situacion_pago,
                            COALESCE(cp.saldo_pendiente, 0.00) as saldo_pendiente,
                            cp.fecha_vencimiento,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(ne.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM nota_entrega ne
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     LEFT JOIN cuentas_pagar cp ON cp.id_nota_entrega = ne.id_nota_entrega AND cp.estado = 1
                     WHERE ne.estado = 1
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Obtiene la cabecera de una nota de entrega de compra por su ID
    public function buscarPorId(int $id): array|false
    {
        $consulta = "SELECT ne.id_nota_entrega, ne.fecha, ne.total, ne.observaciones, ne.created_at, ne.estado,
                            p.id_proveedor, p.nombre_empresa as proveedor_nombre, p.rif as proveedor_rif, p.direccion as proveedor_direccion,
                            (SELECT GROUP_CONCAT(tp.telefono SEPARATOR ', ') FROM telefono_proveedor tp WHERE tp.id_proveedor = p.id_proveedor AND tp.estado = 1) as proveedor_telefonos,
                            cp.id_cuenta_pagar, 
                            COALESCE(cp.situacion, 'pagado') as situacion_pago,
                            COALESCE(cp.saldo_pendiente, 0.00) as saldo_pendiente,
                            cp.fecha_vencimiento,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(ne.fecha) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM nota_entrega ne
                     INNER JOIN proveedores p ON ne.id_proveedor = p.id_proveedor
                     LEFT JOIN cuentas_pagar cp ON cp.id_nota_entrega = ne.id_nota_entrega AND cp.estado = 1
                     WHERE ne.id_nota_entrega = :id AND ne.estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: obtenerDetalle
    // OBJETIVO: Obtiene las líneas de insumos/productos recibidos en la nota de compra
    public function obtenerDetalle(int $idNotaEntrega): array
    {
        $consulta = "SELECT nep.id_nota_entrega_producto, nep.id_nota_entrega, nep.id_producto,
                            nep.cantidad, nep.precio_compra, nep.subtotal, nep.estado,
                            prod.nombre as producto_nombre, prod.codigo as producto_codigo,
                            prod.stock_actual
                     FROM nota_entrega_producto nep
                     INNER JOIN productos prod ON nep.id_producto = prod.id_producto
                     WHERE nep.id_nota_entrega = :id AND nep.estado = 1
                     ORDER BY nep.id_nota_entrega_producto ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $idNotaEntrega, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FUNCIÓN: validarDatosCompra
    // OBJETIVO: Valida reglas de negocio para el registro de una nota de entrega de compra
    public function validarDatosCompra(array $datos): array
    {
        $idProveedor = intval($datos['id_proveedor'] ?? 0);
        $fecha = trim($datos['fecha'] ?? '');
        $observaciones = trim($datos['observaciones'] ?? '');
        $condicionPago = trim($datos['condicion_pago'] ?? 'contado');
        $fechaVencimiento = !empty($datos['fecha_vencimiento']) ? trim($datos['fecha_vencimiento']) : null;
        $idTipoPago = !empty($datos['id_tipo_pago']) ? intval($datos['id_tipo_pago']) : null;
        $idBanco = !empty($datos['id_banco']) ? intval($datos['id_banco']) : null;
        $referencia = !empty($datos['referencia']) ? trim($datos['referencia']) : null;

        $montoInicial = floatval($datos['monto_inicial'] ?? 0);
        $idTipoPagoInicial = !empty($datos['id_tipo_pago_inicial']) ? intval($datos['id_tipo_pago_inicial']) : null;
        $idBancoInicial = !empty($datos['id_banco_inicial']) ? intval($datos['id_banco_inicial']) : null;
        $referenciaInicial = !empty($datos['referencia_inicial']) ? trim($datos['referencia_inicial']) : null;

        if ($idProveedor < 1) {
            return ['valido' => false, 'mensaje' => 'Debe seleccionar un proveedor'];
        }

        $stmtProv = $this->conexion->prepare("SELECT id_proveedor FROM proveedores WHERE id_proveedor = :id AND estado = 1 LIMIT 1");
        $stmtProv->bindParam(':id', $idProveedor, PDO::PARAM_INT);
        $stmtProv->execute();
        if (!$stmtProv->fetch()) {
            return ['valido' => false, 'mensaje' => 'El proveedor seleccionado no existe o está inactivo'];
        }

        if (strlen($observaciones) > 250) {
            return ['valido' => false, 'mensaje' => 'Las observaciones no pueden superar los 250 caracteres'];
        }
        if (preg_match('/[\'"`;\\\\<>]/', $observaciones)) {
            return ['valido' => false, 'mensaje' => 'Las observaciones contienen caracteres no permitidos'];
        }

        if (empty($fecha)) {
            $fecha = date('Y-m-d H:i:s');
        }

        if ($condicionPago === 'contado' && $idTipoPago === 1) {
            $idBanco = null;
            if (empty($referencia)) {
                $referencia = 'Efectivo';
            }
        }

        if ($condicionPago === 'credito' && $montoInicial > 0) {
            if ($idTipoPagoInicial === 1) {
                $idBancoInicial = null;
                if (empty($referenciaInicial)) {
                    $referenciaInicial = 'Efectivo (Abono Inicial)';
                }
            } else {
                if (empty($idBancoInicial) || empty($referenciaInicial)) {
                    return ['valido' => false, 'mensaje' => 'Debe indicar banco y referencia para el abono inicial.'];
                }
            }
        }

        $itemsRaw = $datos['items'] ?? '[]';
        $items = is_array($itemsRaw) ? $itemsRaw : json_decode((string)$itemsRaw, true);

        if (empty($items) || !is_array($items)) {
            return ['valido' => false, 'mensaje' => 'Debe agregar al menos un producto recibido a la nota de entrega'];
        }

        foreach ($items as &$it) {
            $cantRaw = $it['cantidad'] ?? 0;
            if (!is_numeric($cantRaw) || floor((float)$cantRaw) != (float)$cantRaw || (int)$cantRaw < 1) {
                return ['valido' => false, 'mensaje' => 'Las cantidades deben ser números enteros mayores o iguales a 1.'];
            }
            $it['cantidad'] = (int)$cantRaw;
            $precioRaw = floatval($it['precio_compra'] ?? 0);
            if ($precioRaw <= 0) {
                return ['valido' => false, 'mensaje' => 'El precio de compra debe ser mayor a 0'];
            }
        }
        unset($it);

        return [
            'valido' => true,
            'id_proveedor' => $idProveedor,
            'fecha' => $fecha,
            'observaciones' => $observaciones,
            'condicion_pago' => $condicionPago,
            'fecha_vencimiento' => $fechaVencimiento,
            'items' => $items,
            'id_tipo_pago' => $idTipoPago,
            'id_banco' => $idBanco,
            'referencia' => $referencia,
            'monto_inicial' => $montoInicial,
            'id_tipo_pago_inicial' => $idTipoPagoInicial,
            'id_banco_inicial' => $idBancoInicial,
            'referencia_inicial' => $referenciaInicial
        ];
    }

    // FUNCIÓN: registrarCompra
    // OBJETIVO: Valida y procesa la recepción de compra completa
    public function registrarCompra(array $datos): array
    {
        $val = $this->validarDatosCompra($datos);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        try {
            $idNota = $this->crearNotaCompra(
                $val['id_proveedor'],
                $val['fecha'],
                $val['observaciones'],
                $val['condicion_pago'],
                $val['fecha_vencimiento'],
                $val['items'],
                $val['id_tipo_pago'],
                $val['id_banco'],
                $val['referencia'],
                $val['monto_inicial'],
                $val['id_tipo_pago_inicial'],
                $val['id_banco_inicial'],
                $val['referencia_inicial']
            );

            return [
                'exito' => true,
                'mensaje' => 'Nota de entrega de compra #' . $idNota . ' registrada con éxito. Stock de inventario actualizado.',
                'datos' => ['id_nota_entrega' => $idNota]
            ];
        } catch (\PDOException $e) {
            error_log('Error al guardar nota de compra: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al procesar la compra: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: anularNota
    // OBJETIVO: Valida y revierte la nota de entrega de compra
    public function anularNota(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID no válido'];
        }

        try {
            if ($this->anular($id)) {
                return ['exito' => true, 'mensaje' => 'Nota de entrega anulada y stock revertido correctamente'];
            }
            return ['exito' => false, 'mensaje' => 'No se pudo anular la nota de entrega'];
        } catch (\PDOException $e) {
            error_log('Error al anular nota de compra: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al anular nota de compra'];
        }
    }

    // FUNCIÓN: crearNotaCompra
    // OBJETIVO: Registra la recepción de compra, aumenta el stock de cada insumo y genera CxP si es crédito (con opción de abono inicial)
    public function crearNotaCompra(
        int $idProveedor,
        string $fecha,
        ?string $observaciones,
        string $condicionPago,
        ?string $fechaVencimiento,
        array $items,
        ?int $idTipoPago = null,
        ?int $idBanco = null,
        ?string $referencia = null,
        float $montoInicial = 0.00,
        ?int $idTipoPagoInicial = null,
        ?int $idBancoInicial = null,
        ?string $referenciaInicial = null
    ): int {
        if ($idProveedor < 1 || empty($items)) {
            throw new PDOException('Debe especificar un proveedor válido y al menos un producto.');
        }

        try {
            $this->conexion->beginTransaction();

            // 1. Calcular total de la compra
            $totalCompra = 0.00;
            foreach ($items as $item) {
                $cant = intval($item['cantidad'] ?? 0);
                $precio = floatval($item['precio_compra'] ?? 0);
                if ($cant <= 0 || $precio < 0) {
                    throw new PDOException('La cantidad entera y el precio de cada producto deben ser mayores a cero.');
                }
                $totalCompra += round($cant * $precio, 2);
            }

            if ($condicionPago === 'credito' && $montoInicial > $totalCompra) {
                throw new PDOException('El abono inicial no puede superar el monto total de la compra.');
            }

            // 2. Insertar cabecera en nota_entrega
            $sqlNota = "INSERT INTO nota_entrega (id_proveedor, fecha, total, observaciones, created_at, estado)
                        VALUES (:id_proveedor, :fecha, :total, :observaciones, NOW(), 1)";
            $stmtNota = $this->conexion->prepare($sqlNota);
            $stmtNota->bindParam(':id_proveedor', $idProveedor, PDO::PARAM_INT);
            $stmtNota->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmtNota->bindParam(':total', $totalCompra);
            $stmtNota->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
            $stmtNota->execute();

            $idNotaEntrega = (int)$this->conexion->lastInsertId();

            // 3. Insertar detalle en nota_entrega_producto y SUMAR stock a cada producto inmediatamente
            $sqlDetalle = "INSERT INTO nota_entrega_producto (id_nota_entrega, id_producto, cantidad, precio_compra, subtotal, estado)
                           VALUES (:id_nota_entrega, :id_producto, :cantidad, :precio_compra, :subtotal, 1)";
            $stmtDetalle = $this->conexion->prepare($sqlDetalle);

            $sqlStock = "UPDATE productos 
                         SET stock_actual = stock_actual + :cantidad
                         WHERE id_producto = :id_producto";
            $stmtStock = $this->conexion->prepare($sqlStock);

            foreach ($items as $item) {
                $idProducto = intval($item['id_producto']);
                $cant = intval($item['cantidad']);
                $precio = floatval($item['precio_compra']);
                $subtotal = round($cant * $precio, 2);

                // Insertar ítem
                $stmtDetalle->bindParam(':id_nota_entrega', $idNotaEntrega, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $cant, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':precio_compra', $precio);
                $stmtDetalle->bindParam(':subtotal', $subtotal);
                $stmtDetalle->execute();

                // Sumar stock
                $stmtStock->bindParam(':cantidad', $cant, PDO::PARAM_INT);
                $stmtStock->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
                $stmtStock->execute();
            }

            // 4. Integración con Cuentas por Pagar
            if ($condicionPago === 'credito') {
                $fechaVenc = !empty($fechaVencimiento) ? $fechaVencimiento : date('Y-m-d H:i:s', strtotime('+15 days'));
                $abonoInicial = max(0.00, round($montoInicial, 2));
                $saldoPendiente = max(0.00, round($totalCompra - $abonoInicial, 2));
                $situacionCxp = ($saldoPendiente <= 0) ? 'pagado' : 'pendiente';

                $sqlCxp = "INSERT INTO cuentas_pagar (id_nota_entrega, monto_total, saldo_pendiente, fecha_vencimiento, situacion, created_at, estado)
                           VALUES (:id_nota_entrega, :monto_total, :saldo_pendiente, :fecha_vencimiento, :situacion, NOW(), 1)";
                $stmtCxp = $this->conexion->prepare($sqlCxp);
                $stmtCxp->bindParam(':id_nota_entrega', $idNotaEntrega, PDO::PARAM_INT);
                $stmtCxp->bindParam(':monto_total', $totalCompra);
                $stmtCxp->bindParam(':saldo_pendiente', $saldoPendiente);
                $stmtCxp->bindParam(':fecha_vencimiento', $fechaVenc, PDO::PARAM_STR);
                $stmtCxp->bindParam(':situacion', $situacionCxp, PDO::PARAM_STR);
                $stmtCxp->execute();

                $idCxp = (int)$this->conexion->lastInsertId();

                // Si hubo pago/abono inicial en crédito, registrarlo en pagos_realizados
                if ($abonoInicial > 0) {
                    $sqlPagoIni = "INSERT INTO pagos_realizados (id_cuenta_pagar, id_tipo_pago, id_banco, monto, fecha, referencia, estado)
                                   VALUES (:id_cuenta_pagar, :id_tipo_pago, :id_banco, :monto, :fecha, :referencia, 1)";
                    $stmtPagoIni = $this->conexion->prepare($sqlPagoIni);
                    $tipoPagoIniVal = $idTipoPagoInicial ?? 1;
                    $bcoIniVal = ($tipoPagoIniVal === 1) ? null : ($idBancoInicial ?: null);
                    $refIniVal = ($tipoPagoIniVal === 1)
                        ? ($referenciaInicial ?: 'Efectivo (Abono Inicial)')
                        : ($referenciaInicial ?: 'ABONO INICIAL');

                    $stmtPagoIni->bindParam(':id_cuenta_pagar', $idCxp, PDO::PARAM_INT);
                    $stmtPagoIni->bindParam(':id_tipo_pago', $tipoPagoIniVal, PDO::PARAM_INT);
                    $stmtPagoIni->bindValue(':id_banco', $bcoIniVal, $bcoIniVal ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmtPagoIni->bindParam(':monto', $abonoInicial);
                    $stmtPagoIni->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                    $stmtPagoIni->bindValue(':referencia', $refIniVal, PDO::PARAM_STR);
                    $stmtPagoIni->execute();
                }
            } else {
                // Contado: registrar cuenta saldada y pago realizado inmediato
                $sqlCxp = "INSERT INTO cuentas_pagar (id_nota_entrega, monto_total, saldo_pendiente, fecha_vencimiento, situacion, created_at, estado)
                           VALUES (:id_nota_entrega, :monto_total, 0.00, :fecha_vencimiento, 'pagado', NOW(), 1)";
                $stmtCxp = $this->conexion->prepare($sqlCxp);
                $stmtCxp->bindParam(':id_nota_entrega', $idNotaEntrega, PDO::PARAM_INT);
                $stmtCxp->bindParam(':monto_total', $totalCompra);
                $stmtCxp->bindParam(':fecha_vencimiento', $fecha, PDO::PARAM_STR);
                $stmtCxp->execute();

                $idCxp = (int)$this->conexion->lastInsertId();

                $sqlPago = "INSERT INTO pagos_realizados (id_cuenta_pagar, id_tipo_pago, id_banco, monto, fecha, referencia, estado)
                            VALUES (:id_cuenta_pagar, :id_tipo_pago, :id_banco, :monto, :fecha, :referencia, 1)";
                $stmtPago = $this->conexion->prepare($sqlPago);
                $tipoPagoVal = $idTipoPago ?? 1; // 1 = Efectivo por defecto
                $stmtPago->bindParam(':id_cuenta_pagar', $idCxp, PDO::PARAM_INT);
                $stmtPago->bindParam(':id_tipo_pago', $tipoPagoVal, PDO::PARAM_INT);
                $stmtPago->bindValue(':id_banco', $idBanco ?: null, $idBanco ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmtPago->bindParam(':monto', $totalCompra);
                $stmtPago->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmtPago->bindValue(':referencia', $referencia ?: 'PAGO CONTADO DIRECTO', PDO::PARAM_STR);
                $stmtPago->execute();
            }

            $this->conexion->commit();
            return $idNotaEntrega;

        } catch (PDOException $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    // FUNCIÓN: anular
    // OBJETIVO: Anula la nota de entrega de compra, revirtiendo el incremento de stock
    public function anular(int $id): bool
    {
        try {
            $this->conexion->beginTransaction();

            $stmtEstado = $this->conexion->prepare("SELECT estado FROM nota_entrega WHERE id_nota_entrega = :id FOR UPDATE");
            $stmtEstado->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtEstado->execute();
            if ((int)$stmtEstado->fetchColumn() !== 1) {
                $this->conexion->rollBack();
                return false;
            }

            $detalle = $this->obtenerDetalle($id);
            $sqlRevertir = "UPDATE productos SET stock_actual = GREATEST(0, stock_actual - :cantidad) WHERE id_producto = :id_producto";
            $stmtRevertir = $this->conexion->prepare($sqlRevertir);

            foreach ($detalle as $item) {
                $stmtRevertir->bindValue(':cantidad', $item['cantidad']);
                $stmtRevertir->bindValue(':id_producto', $item['id_producto'], PDO::PARAM_INT);
                $stmtRevertir->execute();
            }

            // Marcar nota_entrega inactiva
            $stmtNe = $this->conexion->prepare("UPDATE nota_entrega SET estado = 0 WHERE id_nota_entrega = :id");
            $stmtNe->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtNe->execute();

            // Desactivar cuenta por pagar asociada
            $stmtCp = $this->conexion->prepare("UPDATE cuentas_pagar SET estado = 0 WHERE id_nota_entrega = :id");
            $stmtCp->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCp->execute();

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }
}
