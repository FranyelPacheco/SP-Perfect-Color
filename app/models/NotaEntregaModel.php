<?php
declare(strict_types=1);

namespace App\Models;

use PDO, PDOException;

class NotaEntregaModel extends ModeloBase
{
    private int $id;
    private int $idCliente;
    private int $idUsuario;
    private float $total;
    private int $idPresupuesto;
    private string $condicionPago;
    private ?int $idTipoPago;
    private ?int $idBanco;
    private ?string $referencia;
    private ?string $fechaVencimiento;
    private float $montoInicial = 0;
    private ?int $idTipoPagoInicial = null;
    private ?int $idBancoInicial = null;
    private ?string $referenciaInicial = null;
    private array $detalle;
    private string $termino;
    private int $notaId;
    private int $topLimite;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todas las notas de venta activas con datos relacionados
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca una nota de venta por su ID
    public function buscarPorId(int $id): array|false
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: obtenerDetalle
    // OBJETIVO: Obtiene las líneas de detalle de una nota de venta
    public function obtenerDetalle(int $notaId): array
    {
        if ($notaId <= 0) {
            throw new PDOException('ID de nota no válido');
        }
        $this->id = $notaId;
        return $this->_ejecutarSelectDetalle();
    }

    // FUNCIÓN: crearNotaEntrega
    // OBJETIVO: Crea una nota de venta a partir de un presupuesto, descuenta stock y genera cuenta/pago
    // NOTA: Usa transacción; si es crédito crea cuenta por cobrar con abono inicial opcional, si es contado registra cuenta saldada y pago
    public function crearNotaEntrega(
        int $idCliente,
        int $idUsuario,
        float $total,
        int $idPresupuesto,
        string $condicionPago,
        array $detalle,
        ?int $idTipoPago = null,
        ?int $idBanco = null,
        ?string $referencia = null,
        ?string $fechaVencimiento = null,
        float $montoInicial = 0,
        ?int $idTipoPagoInicial = null,
        ?int $idBancoInicial = null,
        ?string $referenciaInicial = null
    ): int {
        $this->idCliente = $idCliente;
        $this->idUsuario = $idUsuario;
        $this->total = $total;
        $this->idPresupuesto = $idPresupuesto;
        $this->condicionPago = $condicionPago;
        $this->detalle = $detalle;
        $this->idTipoPago = $idTipoPago;
        $this->idBanco = $idBanco;
        $this->referencia = $referencia;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->montoInicial = $montoInicial;
        $this->idTipoPagoInicial = $idTipoPagoInicial;
        $this->idBancoInicial = $idBancoInicial;
        $this->referenciaInicial = $referenciaInicial;
        return $this->_ejecutarCrearNota();
    }

    // FUNCIÓN: procesarNotaEntrega
    // OBJETIVO: Valida de forma integral la aprobación o rechazo de una nota de entrega desde un presupuesto
    public function procesarNotaEntrega(array $datos, int $idUsuario): array
    {
        $presupuestoId = intval($datos['id_presupuesto'] ?? 0);
        if ($presupuestoId < 1) {
            return ['exito' => false, 'mensaje' => 'Debe seleccionar un presupuesto'];
        }

        $presupuesto = $this->buscarPorId($presupuestoId);
        if (!$presupuesto) {
            return ['exito' => false, 'mensaje' => 'Presupuesto asociado no encontrado'];
        }

        if ((int)($presupuesto['id_estado_venta'] ?? 0) !== 1) {
            return ['exito' => false, 'mensaje' => 'El presupuesto ya fue procesado y no admite más cambios'];
        }

        $decision = trim($datos['decision'] ?? 'aprobar');
        if ($decision === 'rechazar') {
            $stmtRechazar = $this->conexion->prepare("UPDATE ventas SET id_estado_venta = 3 WHERE id_venta = :id");
            $stmtRechazar->bindParam(':id', $presupuestoId, PDO::PARAM_INT);
            if ($stmtRechazar->execute()) {
                return ['exito' => true, 'mensaje' => 'Nota de entrega / Presupuesto #' . $presupuestoId . ' rechazada correctamente'];
            }
            return ['exito' => false, 'mensaje' => 'No se pudo rechazar la nota de entrega'];
        }

        $clienteId = intval($presupuesto['id_cliente'] ?? 0);
        if ($clienteId < 1) {
            $clienteId = intval($datos['id_cliente'] ?? 0);
        }
        if ($clienteId < 1) {
            return ['exito' => false, 'mensaje' => 'No se pudo identificar el cliente del presupuesto'];
        }

        $condicionPago = $datos['condicion_pago'] ?? 'contado';
        if (!in_array($condicionPago, ['contado', 'credito'], true)) {
            return ['exito' => false, 'mensaje' => 'Condición de pago no válida'];
        }
        $tipoPagoId = !empty($datos['id_tipo_pago']) ? intval($datos['id_tipo_pago']) : null;
        $bancoId = !empty($datos['id_banco']) ? intval($datos['id_banco']) : null;
        $referencia = trim($datos['referencia'] ?? '');
        $fechaVencimiento = $datos['fecha_vencimiento'] ?? '';
        $montoInicial = floatval($datos['monto_inicial'] ?? 0);
        $tipoPagoInicial = !empty($datos['id_tipo_pago_inicial']) ? intval($datos['id_tipo_pago_inicial']) : null;
        $bancoInicial = !empty($datos['id_banco_inicial']) ? intval($datos['id_banco_inicial']) : null;
        $referenciaInicial = trim($datos['referencia_inicial'] ?? '');

        $itemsRaw = $datos['items'] ?? '[]';
        $items = is_array($itemsRaw) ? $itemsRaw : json_decode((string)$itemsRaw, true);

        if (empty($items)) {
            return ['exito' => false, 'mensaje' => 'Debe agregar al menos un item'];
        }

        if ($condicionPago === 'credito') {
            if (empty($fechaVencimiento)) {
                return ['exito' => false, 'mensaje' => 'Debe seleccionar una fecha de vencimiento para pagos a credito'];
            }
            if ($montoInicial > 0) {
                if (empty($tipoPagoInicial)) {
                    return ['exito' => false, 'mensaje' => 'Debe seleccionar el método de pago para el abono inicial'];
                }
                if ($tipoPagoInicial === 1) {
                    $bancoInicial = null;
                    if (empty($referenciaInicial)) $referenciaInicial = 'Efectivo';
                } else {
                    if (empty($bancoInicial)) {
                        return ['exito' => false, 'mensaje' => 'Debe seleccionar el banco receptor para el abono inicial'];
                    }
                    if (empty($referenciaInicial)) {
                        return ['exito' => false, 'mensaje' => 'Debe ingresar la referencia para el abono inicial'];
                    }
                }
            }
        }

        if ($condicionPago === 'contado') {
            if (empty($tipoPagoId) || $tipoPagoId < 1) {
                return ['exito' => false, 'mensaje' => 'Debe seleccionar un tipo de pago para aprobar la entrega'];
            }
            if ($tipoPagoId === 1) {
                $bancoId = null;
                if (empty($referencia)) $referencia = 'Efectivo';
            } else {
                if (empty($bancoId) || $bancoId < 1) {
                    return ['exito' => false, 'mensaje' => 'Debe seleccionar un banco para el pago por transferencia o pago móvil'];
                }
                if (empty($referencia)) {
                    return ['exito' => false, 'mensaje' => 'Debe ingresar el número de referencia para el pago'];
                }
            }
        }

        $total = 0;
        $detalle = [];

        $stmtLineas = $this->conexion->prepare("SELECT id_venta_detalle FROM venta_detalle WHERE id_venta = :id");
        $stmtLineas->bindParam(':id', $presupuestoId, PDO::PARAM_INT);
        $stmtLineas->execute();
        $lineasValidas = array_map('intval', $stmtLineas->fetchAll(PDO::FETCH_COLUMN));

        foreach ($items as $item) {
            $presupuestoDetalleId = intval($item['id_presupuesto_detalle'] ?? 0);
            if (!in_array($presupuestoDetalleId, $lineasValidas, true)) {
                return ['exito' => false, 'mensaje' => 'Uno de los items no pertenece al presupuesto'];
            }
            $cantidad = floatval($item['cantidad'] ?? 0);
            $precioUnitario = floatval($item['precio_unitario'] ?? 0);
            $subtotal = $cantidad * $precioUnitario;

            if ($presupuestoDetalleId < 1 || $cantidad <= 0 || $precioUnitario <= 0) {
                return ['exito' => false, 'mensaje' => 'Datos invalidos en uno de los items'];
            }

            $total += $subtotal;
            $detalle[] = [
                'id_presupuesto_detalle' => $presupuestoDetalleId,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal
            ];
        }

        if ($montoInicial < 0 || $montoInicial > $total) {
            return ['exito' => false, 'mensaje' => 'El abono inicial no puede ser negativo ni superar el total'];
        }

        try {
            $notaId = $this->crearNotaEntrega(
                $clienteId,
                $idUsuario,
                $total,
                $presupuestoId,
                $condicionPago,
                $detalle,
                $tipoPagoId,
                $bancoId,
                $referencia !== '' ? $referencia : null,
                $fechaVencimiento !== '' ? $fechaVencimiento : null,
                $montoInicial,
                $tipoPagoInicial,
                $bancoInicial,
                $referenciaInicial !== '' ? $referenciaInicial : null
            );

            return [
                'exito' => true,
                'mensaje' => 'Nota de entrega creada exitosamente',
                'datos' => [
                    'id_nota_entrega' => $notaId,
                    'total' => $total
                ]
            ];
        } catch (\Throwable $e) {
            error_log('ERROR CRITICO AL CREAR NOTA: ' . $e->getMessage() . ' (en ' . $e->getFile() . ':' . $e->getLine() . ')');
            return ['exito' => false, 'mensaje' => 'Error al crear la nota de entrega: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: buscarNotas
    // OBJETIVO: Busca notas de venta por nombre/cédula del cliente
    public function buscarNotas(string $termino): array
    {
        $this->termino = $termino;
        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las ventas aprobadas
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT v.*,
                            v.id_venta as id,
                            v.id_venta as id_nota_venta,
                            v.id_venta as id_nota_entrega,
                            v.id_venta as id_presupuesto,
                            v.estado as activo,
                            c.id_cliente,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            'Sistema' as usuario_nombre,
                            COALESCE(tp_v.nombre, tp_pr.nombre, CASE WHEN v.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END) as tipo_pago_nombre,
                            COALESCE(ev.nombre, CASE WHEN v.id_estado_venta = 1 THEN 'En Espera' WHEN v.id_estado_venta = 2 THEN 'Aprobado' WHEN v.id_estado_venta = 3 THEN 'Rechazado' ELSE 'Otro' END) as estado_nombre,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(COALESCE(v.fecha_aprobacion, v.fecha)) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM ventas v 
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id_estado_venta
                     LEFT JOIN tipo_pago tp_v ON v.id_tipo_pago = tp_v.id_tipo_pago
                     LEFT JOIN cuentas_cobrar cc ON cc.id_venta = v.id_venta
                     LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar
                     LEFT JOIN tipo_pago tp_pr ON pr.id_tipo_pago = tp_pr.id_tipo_pago
                     WHERE v.estado = 1 AND v.id_estado_venta IN (1, 2, 3)
                     GROUP BY v.id_venta
                     ORDER BY v.fecha DESC, v.id_venta DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una venta por ID con datos del cliente y tipo de pago
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT v.*,
                            v.id_venta as id,
                            v.id_venta as id_nota_venta,
                            v.id_venta as id_nota_entrega,
                            v.id_venta as id_presupuesto,
                            v.estado as activo,
                            c.id_cliente,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.direccion as cliente_direccion,
                            (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                            'Sistema' as usuario_nombre,
                            COALESCE(tp_v.nombre, tp_pr.nombre, CASE WHEN v.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END) as tipo_pago_nombre,
                            COALESCE(
                                (SELECT tc.tasa FROM tasa_cambio tc WHERE tc.fecha <= DATE(COALESCE(v.fecha_aprobacion, v.fecha)) AND tc.estado = 1 ORDER BY tc.fecha DESC, tc.id_tasa_cambio DESC LIMIT 1),
                                (SELECT tc2.tasa FROM tasa_cambio tc2 WHERE tc2.estado = 1 ORDER BY tc2.fecha DESC, tc2.id_tasa_cambio DESC LIMIT 1),
                                0
                            ) as tasa_bcv
                     FROM ventas v 
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     LEFT JOIN tipo_pago tp_v ON v.id_tipo_pago = tp_v.id_tipo_pago
                     LEFT JOIN cuentas_cobrar cc ON cc.id_venta = v.id_venta
                     LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar
                     LEFT JOIN tipo_pago tp_pr ON pr.id_tipo_pago = tp_pr.id_tipo_pago
                     WHERE v.id_venta = :id AND v.estado = 1
                     LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarSelectDetalle
    // OBJETIVO: Obtiene las líneas de detalle de la venta
    private function _ejecutarSelectDetalle(): array
    {
        $consulta = "SELECT vd.id_venta_detalle,
                            vd.id_venta_detalle as id_presupuesto_detalle,
                            v.id_venta as id_nota_venta,
                            v.id_venta as id_nota_entrega,
                            vd.id_venta as id_presupuesto,
                            vd.descripcion as insumo_nombre,
                            vd.cantidad,
                            vd.precio_unitario,
                            vd.subtotal,
                            COALESCE(p.codigo, 'PREP') as insumo_codigo,
                            COALESCE(p.stock_actual, 0) as stock_actual,
                            vd.id_producto as id_insumo,
                            vd.id_producto as id_producto
                     FROM ventas v
                     INNER JOIN venta_detalle vd ON v.id_venta = vd.id_venta
                     LEFT JOIN productos p ON vd.id_producto = p.id_producto
                     WHERE v.id_venta = :id_nota_venta";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_nota_venta', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarCrearNota
    // OBJETIVO: Aprueba la venta (id_estado_venta = 2), fija fecha_aprobacion, descuenta stock y genera cuenta/cobro
    private function _ejecutarCrearNota(): int
    {
        try {
            $this->conexion->beginTransaction();

            $this->_validarStock();

            $condicionPagoVal = !empty($this->condicionPago) ? $this->condicionPago : 'contado';
            $tipoPagoId = ($condicionPagoVal === 'contado') ? ($this->idTipoPago ?? 1) : $this->idTipoPago;

            $consulta = "UPDATE ventas 
                         SET id_estado_venta = 2, 
                             id_tipo_pago = :id_tipo_pago, 
                             fecha_aprobacion = NOW(), 
                             total = :total, 
                             condicion_pago = :condicion_pago 
                         WHERE id_venta = :id_venta";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindValue(':id_venta', $this->idPresupuesto, PDO::PARAM_INT);
            $stmt->bindValue(':id_tipo_pago', $tipoPagoId, empty($tipoPagoId) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':total', $this->total);
            $stmt->bindValue(':condicion_pago', $condicionPagoVal, PDO::PARAM_STR);
            $stmt->execute();

            $this->notaId = $this->idPresupuesto;

            $this->_insertarDetalleYDescontarStock();

            if (!empty($condicionPagoVal)) {
                if ($condicionPagoVal === 'credito') {
                    $this->fechaVencimiento = !empty($this->fechaVencimiento) ? $this->fechaVencimiento : date('Y-m-d', strtotime('+10 days'));
                    $this->_crearCuentaCobrar();
                } else {
                    $this->_crearYPagardeInmediatoContado();
                }
            }

            $this->conexion->commit();
            return $this->notaId;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca ventas aprobadas por nombre o cédula del cliente con LIKE
    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT v.*,
                            v.id_venta as id,
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
                     WHERE v.estado = 1 AND v.id_estado_venta = 2 AND (c.nombres LIKE :termino1 
                         OR c.apellidos LIKE :termino2 
                         OR c.cedula LIKE :termino3) 
                     GROUP BY v.id_venta
                     ORDER BY v.fecha DESC, v.id_venta DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _validarStock
    // OBJETIVO: Verifica que todos los productos (o sus bases en mezcla_producto) tengan stock suficiente
    private function _validarStock(): void
    {
        $stmtVd = $this->conexion->prepare(
            "SELECT vd.id_producto, p.nombre, p.stock_actual, p.id_tipo_producto
             FROM venta_detalle vd
             INNER JOIN productos p ON vd.id_producto = p.id_producto
             WHERE vd.id_venta_detalle = :vd_id AND p.estado = 1 FOR UPDATE"
        );
        $stmtBases = $this->conexion->prepare(
            "SELECT mp.id_producto_base, mp.cantidad_galon, p.nombre, p.stock_actual
             FROM mezcla_producto mp
             INNER JOIN productos p ON mp.id_producto_base = p.id_producto
             WHERE mp.id_producto_mezclado = :id_prod AND p.estado = 1 FOR UPDATE"
        );

        foreach ($this->detalle as $item) {
            $vdId = (int)($item['id_venta_detalle'] ?? $item['id_presupuesto_detalle'] ?? 0);
            $cantMultiplicador = (float)$item['cantidad'];

            $stmtVd->bindValue(':vd_id', $vdId, PDO::PARAM_INT);
            $stmtVd->execute();
            $prod = $stmtVd->fetch();

            if (!$prod) {
                throw new PDOException('No se encontró el producto para el ítem ID: ' . $vdId);
            }

            if ((int)$prod['id_tipo_producto'] === 3 && (float)$prod['stock_actual'] < $cantMultiplicador) {
                $stmtBases->bindValue(':id_prod', (int)$prod['id_producto'], PDO::PARAM_INT);
                $stmtBases->execute();
                $componentes = $stmtBases->fetchAll();

                if (empty($componentes)) {
                    throw new PDOException('Stock insuficiente para el producto preparado: ' . $prod['nombre']);
                }

                foreach ($componentes as $comp) {
                    $requerido = (float)$comp['cantidad_galon'] * $cantMultiplicador;
                    if ((float)$comp['stock_actual'] < $requerido) {
                        throw new PDOException('Stock insuficiente para la base ' . $comp['nombre'] . ' (Disponible: ' . $comp['stock_actual'] . ', Requerido: ' . $requerido . ')');
                    }
                }
            } else {
                if ((float)$prod['stock_actual'] < $cantMultiplicador) {
                    throw new PDOException('Stock insuficiente para el producto ' . $prod['nombre'] . ' (Disponible: ' . $prod['stock_actual'] . ', Requerido: ' . $cantMultiplicador . ')');
                }
            }
        }
    }

    // FUNCIÓN: _insertarDetalleYDescontarStock
    // OBJETIVO: Descuenta el stock físico del producto o de sus bases en mezcla_producto
    private function _insertarDetalleYDescontarStock(): void
    {
        $stmtVd = $this->conexion->prepare(
            "SELECT vd.id_producto, p.stock_actual, p.id_tipo_producto
             FROM venta_detalle vd
             INNER JOIN productos p ON vd.id_producto = p.id_producto
             WHERE vd.id_venta_detalle = :vd_id"
        );
        $stmtBases = $this->conexion->prepare(
            "SELECT id_producto_base, cantidad_galon FROM mezcla_producto WHERE id_producto_mezclado = :id_prod"
        );
        $stmtDescontar = $this->conexion->prepare(
            "UPDATE productos SET stock_actual = stock_actual - :cant WHERE id_producto = :id_prod"
        );
        $stmtUpdVd = $this->conexion->prepare(
            "UPDATE venta_detalle SET cantidad = :cant, subtotal = :subtotal WHERE id_venta_detalle = :vd_id"
        );

        foreach ($this->detalle as $item) {
            $vdId = (int)($item['id_venta_detalle'] ?? $item['id_presupuesto_detalle'] ?? 0);
            $cant = (float)$item['cantidad'];

            $stmtVd->bindValue(':vd_id', $vdId, PDO::PARAM_INT);
            $stmtVd->execute();
            $prod = $stmtVd->fetch();

            if (!$prod) {
                continue;
            }

            if (isset($item['subtotal'])) {
                $stmtUpdVd->bindValue(':cant', $cant);
                $stmtUpdVd->bindValue(':subtotal', (float)$item['subtotal']);
                $stmtUpdVd->bindValue(':vd_id', $vdId, PDO::PARAM_INT);
                $stmtUpdVd->execute();
            }

            if ((int)$prod['id_tipo_producto'] === 3 && (float)$prod['stock_actual'] < $cant) {
                $stmtBases->bindValue(':id_prod', (int)$prod['id_producto'], PDO::PARAM_INT);
                $stmtBases->execute();
                $bases = $stmtBases->fetchAll();

                foreach ($bases as $b) {
                    $stmtDescontar->bindValue(':cant', (float)$b['cantidad_galon'] * $cant);
                    $stmtDescontar->bindValue(':id_prod', (int)$b['id_producto_base'], PDO::PARAM_INT);
                    $stmtDescontar->execute();
                }
            } else {
                $stmtDescontar->bindValue(':cant', $cant);
                $stmtDescontar->bindValue(':id_prod', (int)$prod['id_producto'], PDO::PARAM_INT);
                $stmtDescontar->execute();
            }
        }
    }

    // FUNCIÓN: _crearCuentaCobrar
    // OBJETIVO: Crea una cuenta por cobrar cuando la condición de pago es crédito, descontando y registrando abono inicial si aplica
    private function _crearCuentaCobrar(): void
    {
        $montoInicial = max(0, (float)$this->montoInicial);
        $saldoPendiente = max(0, $this->total - $montoInicial);
        $situacion = ($saldoPendiente <= 0) ? 'pagado' : 'pendiente';

        $consulta = "INSERT INTO cuentas_cobrar (id_venta, monto_total, saldo_pendiente, fecha_vencimiento, situacion, estado) 
                     VALUES (:id_venta, :monto_total, :saldo_pendiente, :fecha_vencimiento, :situacion, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':id_venta', $this->notaId, PDO::PARAM_INT);
        $stmt->bindValue(':monto_total', $this->total);
        $stmt->bindValue(':saldo_pendiente', $saldoPendiente);
        $stmt->bindValue(':fecha_vencimiento', $this->fechaVencimiento, PDO::PARAM_STR);
        $stmt->bindValue(':situacion', $situacion, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $errInfo = $stmt->errorInfo();
            throw new PDOException('Error al insertar en cuentas_cobrar: ' . ($errInfo[2] ?? 'desconocido'));
        }

        $idCuentaCobrar = (int)$this->conexion->lastInsertId();

        // Si se pagó una inicial, registrar automáticamente en pagos_recibidos
        if ($montoInicial > 0) {
            $tipoPagoInicial = !empty($this->idTipoPagoInicial) ? (int)$this->idTipoPagoInicial : 1;
            $consultaPago = "INSERT INTO pagos_recibidos (id_cuenta_cobrar, id_tipo_pago, id_banco, monto, fecha, referencia, estado) 
                             VALUES (:id_cuenta_cobrar, :id_tipo_pago, :id_banco, :monto, NOW(), :referencia, 1)";
            $stmtPago = $this->conexion->prepare($consultaPago);
            $stmtPago->bindValue(':id_cuenta_cobrar', $idCuentaCobrar, PDO::PARAM_INT);
            $stmtPago->bindValue(':id_tipo_pago', $tipoPagoInicial, PDO::PARAM_INT);
            $stmtPago->bindValue(':id_banco', $this->idBancoInicial, empty($this->idBancoInicial) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtPago->bindValue(':monto', $montoInicial);
            $referenciaVal = !empty($this->referenciaInicial) ? $this->referenciaInicial : 'Abono Inicial';
            $stmtPago->bindValue(':referencia', $referenciaVal, PDO::PARAM_STR);
            $stmtPago->execute();
        }
    }

    // FUNCIÓN: _crearYPagardeInmediatoContado
    // OBJETIVO: Crea la cuenta por cobrar para la venta de contado y registra su pago completo de inmediato
    private function _crearYPagardeInmediatoContado(): void
    {
        $consultaCxc = "INSERT INTO cuentas_cobrar (id_venta, monto_total, saldo_pendiente, fecha_vencimiento, situacion, estado) 
                        VALUES (:id_venta, :monto_total, 0, NOW(), 'pagado', 1)";
        $stmtCxc = $this->conexion->prepare($consultaCxc);
        $stmtCxc->bindValue(':id_venta', $this->notaId, PDO::PARAM_INT);
        $stmtCxc->bindValue(':monto_total', $this->total);
        $stmtCxc->execute();

        $idCuentaCobrar = (int)$this->conexion->lastInsertId();

        $tipoPagoVal = $this->idTipoPago ?? 1;
        $consultaPago = "INSERT INTO pagos_recibidos (id_cuenta_cobrar, id_tipo_pago, id_banco, monto, fecha, referencia, estado) 
                         VALUES (:id_cuenta_cobrar, :id_tipo_pago, :id_banco, :monto, NOW(), :referencia, 1)";
        $stmtPago = $this->conexion->prepare($consultaPago);
        $stmtPago->bindValue(':id_cuenta_cobrar', $idCuentaCobrar, PDO::PARAM_INT);
        $stmtPago->bindValue(':id_tipo_pago', $tipoPagoVal, PDO::PARAM_INT);
        $stmtPago->bindValue(':id_banco', $this->idBanco, empty($this->idBanco) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmtPago->bindValue(':monto', $this->total);
        $stmtPago->bindValue(':referencia', $this->referencia, empty($this->referencia) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtPago->execute();
    }

    // FUNCIÓN: obtenerVentasMes
    // OBJETIVO: Retorna la suma total de ventas aprobadas en el mes actual
    public function obtenerVentasMes(): string
    {
        return $this->_ejecutarVentasMes();
    }

    // FUNCIÓN: obtenerTopProductos
    // OBJETIVO: Retorna los N productos más vendidos del día de hoy
    public function obtenerTopProductos(int $limite = 5): array
    {
        $this->topLimite = $limite;
        return $this->_ejecutarTopProductos();
    }

    // FUNCIÓN: obtenerClienteTopMes
    // OBJETIVO: Retorna el cliente con mayor monto comprado en el mes actual
    public function obtenerClienteTopMes(): array|false
    {
        return $this->_ejecutarClienteTopMes();
    }

    // FUNCIÓN: _ejecutarVentasMes
    // OBJETIVO: Suma el total de ventas activas aprobadas del mes en curso
    private function _ejecutarVentasMes(): string
    {
        $consulta = "SELECT COALESCE(SUM(total), 0) as total
                     FROM ventas
                     WHERE estado = 1 AND id_estado_venta = 2
                       AND MONTH(fecha) = MONTH(CURDATE())
                       AND YEAR(fecha) = YEAR(CURDATE())";
        $stmt = $this->conexion->query($consulta);
        return (string)$stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarTopProductos
    // OBJETIVO: Agrupa por producto la cantidad vendida del día, ordena y limita
    private function _ejecutarTopProductos(): array
    {
        $consulta = "SELECT p.id_producto, p.id_producto as id_insumo, p.codigo, p.nombre,
                            SUM(vd.cantidad) as total_vendido
                     FROM ventas v
                     INNER JOIN venta_detalle vd ON v.id_venta = vd.id_venta
                     INNER JOIN productos p ON vd.id_producto = p.id_producto
                     WHERE DATE(v.fecha) = CURDATE() AND v.estado = 1 AND v.id_estado_venta = 2
                     GROUP BY p.id_producto
                     ORDER BY total_vendido DESC
                     LIMIT :limite";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':limite', $this->topLimite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarClienteTopMes
    // OBJETIVO: Busca el cliente con mayor suma de total comprado en el mes
    private function _ejecutarClienteTopMes(): array|false
    {
        $consulta = "SELECT c.id_cliente, c.cedula, c.nombres, c.apellidos,
                            COALESCE(SUM(v.total), 0) as total_comprado
                     FROM ventas v
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     WHERE v.estado = 1 AND v.id_estado_venta = 2
                       AND MONTH(v.fecha) = MONTH(CURDATE())
                       AND YEAR(v.fecha) = YEAR(CURDATE())
                     GROUP BY c.id_cliente
                     ORDER BY total_comprado DESC
                     LIMIT 1";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetch();
    }
}
