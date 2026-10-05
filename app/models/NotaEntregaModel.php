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
    // OBJETIVO: Obtiene todas las notas de entrega activas con datos relacionados
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca una nota de entrega por su ID
    public function buscarPorId(int $id): array|false
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: obtenerDetalle
    // OBJETIVO: Obtiene las líneas de detalle de una nota de entrega
    public function obtenerDetalle(int $notaId): array
    {
        if ($notaId <= 0) {
            throw new PDOException('ID de nota no válido');
        }
        $this->id = $notaId;
        return $this->_ejecutarSelectDetalle();
    }

    // FUNCIÓN: crearNotaEntrega
    // OBJETIVO: Crea una nota de entrega a partir de un presupuesto, descuenta stock y genera cuenta/pago
    // NOTA: Usa transacción; si es crédito crea cuenta por cobrar, si es contado registra pago
    public function crearNotaEntrega(int $idCliente, int $idUsuario, float $total, int $idPresupuesto, string $condicionPago, array $detalle, ?int $idTipoPago = null, ?int $idBanco = null, ?string $referencia = null, ?string $fechaVencimiento = null): int
    {
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
        return $this->_ejecutarCrearNota();
    }

    // FUNCIÓN: actualizarDetalleNota
    // OBJETIVO: Reemplaza el detalle de una nota, restaurando y descontando stock según el nuevo detalle
    // NOTA: Transacción que elimina detalle anterior, restaura stock, verifica disponibilidad y descuenta nuevamente
    public function actualizarDetalleNota(int $id, array $detalle): bool
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        $this->detalle = $detalle;
        return $this->_ejecutarActualizarDetalle();
    }

    // FUNCIÓN: buscarNotas
    // OBJETIVO: Busca notas de entrega por nombre/cedula del cliente
    public function buscarNotas(string $termino): array
    {
        $this->termino = $termino;
        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las notas activas
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre,
                            COALESCE(tp.nombre, CASE WHEN ne.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END) as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN cuentas_cobrar cc ON cc.id_nota_entrega = ne.id_nota_entrega
                     LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar
                     LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.activo = 1
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una nota por ID con datos del cliente y tipo de pago desde pagos_recibidos
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.direccion as cliente_direccion,
                            (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                            u.nombre as usuario_nombre,
                            COALESCE(tp.nombre, CASE WHEN ne.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END) as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN cuentas_cobrar cc ON cc.id_nota_entrega = ne.id_nota_entrega
                     LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar
                     LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.id_nota_entrega = :id AND ne.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarSelectDetalle
    // OBJETIVO: Obtiene las líneas de detalle de la nota a partir del presupuesto asociado
    // NOTA: Consulta presupuesto_detalle y sus items de composición
    private function _ejecutarSelectDetalle(): array
    {
        $consulta = "SELECT pd.id_presupuesto_detalle,
                            ne.id_nota_entrega,
                            pd.id_presupuesto,
                            pd.descripcion as insumo_nombre,
                            pd.cantidad,
                            pd.precio_unitario,
                            pd.subtotal,
                            COALESCE(p.codigo, 'PREP') as insumo_codigo,
                            COALESCE(p.stock_actual, 0) as stock_actual,
                            ic.id_producto as id_insumo,
                            ic.id_producto as id_producto,
                            ic.fraccion_128,
                            ic.cantidad_consumida
                     FROM notas_entrega ne
                     INNER JOIN presupuesto_detalle pd ON ne.id_presupuesto = pd.id_presupuesto
                     LEFT JOIN item_composicion ic ON pd.id_presupuesto_detalle = ic.id_presupuesto_detalle
                     LEFT JOIN productos p ON ic.id_producto = p.id_producto
                     WHERE ne.id_nota_entrega = :id_nota_entrega";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_nota_entrega', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarCrearNota
    // OBJETIVO: Ejecuta la transacción completa de creación de nota de entrega
    // NOTA: Incluye validación de stock, inserción de detalle, descuento de stock y actualización del presupuesto
    private function _ejecutarCrearNota(): int
    {
        try {
            $this->conexion->beginTransaction();

            $this->_validarStock();

            $condicionPagoVal = !empty($this->condicionPago) ? $this->condicionPago : 'contado';

            $consulta = "INSERT INTO notas_entrega (id_cliente, id_usuario, fecha, total, condicion_pago, id_presupuesto) 
                          VALUES (:id_cliente, :id_usuario, NOW(), :total, :condicion_pago, :id_presupuesto)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindValue(':id_cliente', $this->idCliente, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $this->idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':total', $this->total);
            $stmt->bindValue(':condicion_pago', $condicionPagoVal, PDO::PARAM_STR);
            $stmt->bindValue(':id_presupuesto', $this->idPresupuesto, PDO::PARAM_INT);
            $stmt->execute();

            $this->notaId = (int)$this->conexion->lastInsertId();

            $this->_insertarDetalleYDescontarStock();
            $this->_cambiarEstadoPresupuesto();

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

    // FUNCIÓN: _ejecutarActualizarDetalle
    // OBJETIVO: Reemplaza el detalle de la nota: restaura stock viejo, elimina detalle, verifica stock, inserta nuevo y descuenta
    // NOTA: Soporta items nuevos (sin id_presupuesto_detalle) creando presupuesto_detalle on-the-fly
    private function _ejecutarActualizarDetalle(): bool
    {
        try {
            $this->conexion->beginTransaction();

            $detalleAnterior = $this->_ejecutarSelectDetalle();
            foreach ($detalleAnterior as $item) {
                $consultaRestaurar = "UPDATE productos p
                                      INNER JOIN item_composicion ic ON ic.id_producto = p.id_producto
                                      SET p.stock_actual = p.stock_actual + (ic.cantidad_consumida * :factor)
                                      WHERE ic.id_presupuesto_detalle = :pd_id";
                $stmtRestaurar = $this->conexion->prepare($consultaRestaurar);
                $stmtRestaurar->bindValue(':factor', $item['cantidad']);
                $stmtRestaurar->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
                $stmtRestaurar->execute();
            }

            $consultaPresupuestoId = "SELECT id_presupuesto FROM notas_entrega WHERE id_nota_entrega = :id";
            $stmtPresupuestoId = $this->conexion->prepare($consultaPresupuestoId);
            $stmtPresupuestoId->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmtPresupuestoId->execute();
            $notaData = $stmtPresupuestoId->fetch();
            $idPresupuesto = $notaData ? (int)$notaData['id_presupuesto'] : 0;

            if ($idPresupuesto < 1) {
                throw new PDOException('Presupuesto asociado no encontrado');
            }

            // Eliminar detalles previos de presupuesto_detalle (los item_composicion se eliminan por CASCADE)
            $consultaEliminar = "DELETE FROM presupuesto_detalle WHERE id_presupuesto = :id_presupuesto";
            $stmtEliminar = $this->conexion->prepare($consultaEliminar);
            $stmtEliminar->bindValue(':id_presupuesto', $idPresupuesto, PDO::PARAM_INT);
            $stmtEliminar->execute();

            $total = 0;
            $consultaInsertPresupuestoDetalle = "INSERT INTO presupuesto_detalle (id_presupuesto, descripcion, cantidad, precio_unitario, subtotal) 
                                                  VALUES (:id_presupuesto, :descripcion, :cantidad, :precio_unitario, :subtotal)";
            $stmtInsertPresupuestoDetalle = $this->conexion->prepare($consultaInsertPresupuestoDetalle);

            $consultaInsertItemComp = "INSERT INTO item_composicion (id_presupuesto_detalle, id_producto, fraccion_128, cantidad_consumida)
                                       VALUES (:id_presupuesto_detalle, :id_producto, 0, 1.0000)";
            $stmtInsertItemComp = $this->conexion->prepare($consultaInsertItemComp);

            $consultaStockDirecto = "SELECT nombre, stock_actual FROM productos WHERE id_producto = :id_producto AND activo = 1 FOR UPDATE";
            $stmtStockDirecto = $this->conexion->prepare($consultaStockDirecto);

            $consultaDescontarDirecto = "UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id_producto";
            $stmtDescontarDirecto = $this->conexion->prepare($consultaDescontarDirecto);

            foreach ($this->detalle as $item) {
                $idInsumo = (int)($item['id_producto'] ?? $item['id_insumo'] ?? 0);
                if ($idInsumo < 1) {
                    throw new PDOException('Item sin producto valido');
                }

                $stmtStockDirecto->bindValue(':id_producto', $idInsumo, PDO::PARAM_INT);
                $stmtStockDirecto->execute();
                $insumo = $stmtStockDirecto->fetch();

                if (!$insumo || (float)$insumo['stock_actual'] < (float)$item['cantidad']) {
                    throw new PDOException('Stock insuficiente para el producto: ' . ($insumo['nombre'] ?? $idInsumo));
                }

                $stmtInsertPresupuestoDetalle->bindValue(':id_presupuesto', $idPresupuesto, PDO::PARAM_INT);
                $stmtInsertPresupuestoDetalle->bindValue(':descripcion', $insumo['nombre'] ?? 'Producto');
                $stmtInsertPresupuestoDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtInsertPresupuestoDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtInsertPresupuestoDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtInsertPresupuestoDetalle->execute();

                $nuevoId = (int)$this->conexion->lastInsertId();

                $stmtInsertItemComp->bindValue(':id_presupuesto_detalle', $nuevoId, PDO::PARAM_INT);
                $stmtInsertItemComp->bindValue(':id_producto', $idInsumo, PDO::PARAM_INT);
                $stmtInsertItemComp->execute();

                $stmtDescontarDirecto->bindValue(':cantidad', $item['cantidad']);
                $stmtDescontarDirecto->bindValue(':id_producto', $idInsumo, PDO::PARAM_INT);
                $stmtDescontarDirecto->execute();

                $total += $item['subtotal'];
            }

            $consultaTotal = "UPDATE notas_entrega SET total = :total WHERE id_nota_entrega = :id";
            $stmtTotal = $this->conexion->prepare($consultaTotal);
            $stmtTotal->bindValue(':total', $total);
            $stmtTotal->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmtTotal->execute();

            // Sincronizar cuenta por cobrar si existe
            $consultaCxc = "SELECT id_cuenta_cobrar, monto_total, saldo_pendiente FROM cuentas_cobrar WHERE id_nota_entrega = :id AND activo = 1";
            $stmtCxc = $this->conexion->prepare($consultaCxc);
            $stmtCxc->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmtCxc->execute();
            $cxc = $stmtCxc->fetch();

            if ($cxc) {
                $totalViejo = (float)$cxc['monto_total'];
                $saldoViejo = (float)$cxc['saldo_pendiente'];
                $diferencia = (float)$total - $totalViejo;
                $nuevoSaldo = round($saldoViejo + $diferencia, 2);

                if ($nuevoSaldo <= 0.001) {
                    $nuevoSaldo = 0;
                    $nuevoEstado = 'pagado';
                } else {
                    $nuevoEstado = 'pendiente';
                }

                $consultaUpdCxc = "UPDATE cuentas_cobrar 
                                   SET monto_total = :monto_total, saldo_pendiente = :saldo_pendiente, estado = :estado 
                                   WHERE id_cuenta_cobrar = :id_cxc";
                $stmtUpdCxc = $this->conexion->prepare($consultaUpdCxc);
                $stmtUpdCxc->bindValue(':monto_total', $total);
                $stmtUpdCxc->bindValue(':saldo_pendiente', $nuevoSaldo);
                $stmtUpdCxc->bindValue(':estado', $nuevoEstado);
                $stmtUpdCxc->bindValue(':id_cxc', $cxc['id_cuenta_cobrar'], PDO::PARAM_INT);
                $stmtUpdCxc->execute();
            }

            $this->conexion->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca notas de entrega por nombre o cédula del cliente con LIKE
    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre,
                            COALESCE(tp.nombre, CASE WHEN ne.condicion_pago = 'credito' THEN 'Crédito' ELSE 'Contado' END) as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN cuentas_cobrar cc ON cc.id_nota_entrega = ne.id_nota_entrega
                     LEFT JOIN pagos_recibidos pr ON pr.id_cuenta_cobrar = cc.id_cuenta_cobrar
                     LEFT JOIN tipo_pago tp ON pr.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.activo = 1 AND (c.nombres LIKE :termino1 
                         OR c.apellidos LIKE :termino2 
                         OR c.cedula LIKE :termino3) 
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _validarStock
    // OBJETIVO: Verifica que todos los insumos de composición tengan stock suficiente antes de crear la nota
    // NOTA: Usa FOR UPDATE para bloquear filas dentro de la transacción
    private function _validarStock(): void
    {
        foreach ($this->detalle as $item) {
            $pdId = (int)$item['id_presupuesto_detalle'];
            $cantMultiplicador = (float)$item['cantidad'];

            // Obtener todos los componentes del item en presupuesto_detalle
            $consultaComp = "SELECT ic.id_producto, ic.cantidad_consumida, p.nombre, p.stock_actual
                             FROM item_composicion ic
                             INNER JOIN productos p ON ic.id_producto = p.id_producto
                             WHERE ic.id_presupuesto_detalle = :pd_id AND p.activo = 1 FOR UPDATE";
            $stmtComp = $this->conexion->prepare($consultaComp);
            $stmtComp->bindValue(':pd_id', $pdId, PDO::PARAM_INT);
            $stmtComp->execute();
            $componentes = $stmtComp->fetchAll();

            if (empty($componentes)) {
                throw new PDOException('No se encontraron insumos de composición para el item ID: ' . $pdId);
            }

            foreach ($componentes as $comp) {
                // Cantidad requerida total = cantidad_consumida unitaria * cantidad solicitada
                $requerido = (float)$comp['cantidad_consumida'] * $cantMultiplicador;
                if ((float)$comp['stock_actual'] < $requerido) {
                    throw new PDOException('Stock insuficiente para el insumo ' . $comp['nombre'] . ' (Disponible: ' . $comp['stock_actual'] . ', Requerido: ' . $requerido . ')');
                }
            }
        }
    }

    // FUNCIÓN: _insertarDetalleYDescontarStock
    // OBJETIVO: Descuenta el stock físico de cada producto mediante item_composicion a partir del presupuesto
    private function _insertarDetalleYDescontarStock(): void
    {
        $consultaDescontar = "UPDATE productos p
                              INNER JOIN item_composicion ic ON ic.id_producto = p.id_producto
                              SET p.stock_actual = p.stock_actual - (ic.cantidad_consumida * :factor)
                              WHERE ic.id_presupuesto_detalle = :pd_id";
        $stmtDescontar = $this->conexion->prepare($consultaDescontar);

        foreach ($this->detalle as $item) {
            $stmtDescontar->bindValue(':factor', $item['cantidad']);
            $stmtDescontar->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
            $stmtDescontar->execute();
        }
    }

    // FUNCIÓN: _cambiarEstadoPresupuesto
    // OBJETIVO: Marca el presupuesto como 'convertido' al generar la nota de entrega
    private function _cambiarEstadoPresupuesto(): void
    {
        $consulta = "UPDATE presupuestos SET id_estado_presupuesto = 4 WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':id', $this->idPresupuesto, PDO::PARAM_INT);
        $stmt->execute();
    }

    // FUNCIÓN: _crearCuentaCobrar
    // OBJETIVO: Crea una cuenta por cobrar cuando la condición de pago es crédito
    // NOTA: El saldo pendiente se inicializa igual al monto total y el id_cliente se toma de la nota de entrega
    private function _crearCuentaCobrar(): void
    {
        if ($this->idCliente <= 0 && $this->notaId > 0) {
            $stmtCli = $this->conexion->prepare("SELECT id_cliente FROM notas_entrega WHERE id_nota_entrega = :nid");
            $stmtCli->bindValue(':nid', $this->notaId, PDO::PARAM_INT);
            $stmtCli->execute();
            $cliRow = $stmtCli->fetch();
            if ($cliRow && !empty($cliRow['id_cliente'])) {
                $this->idCliente = (int)$cliRow['id_cliente'];
            }
        }

        $consulta = "INSERT INTO cuentas_cobrar (id_cliente, id_nota_entrega, monto_total, saldo_pendiente, fecha_vencimiento, estado, activo) 
                     VALUES (:id_cliente, :id_nota_entrega, :monto_total, :saldo_pendiente, :fecha_vencimiento, 'pendiente', 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':id_cliente', $this->idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':id_nota_entrega', $this->notaId, PDO::PARAM_INT);
        $stmt->bindValue(':monto_total', $this->total);
        $stmt->bindValue(':saldo_pendiente', $this->total);
        $stmt->bindValue(':fecha_vencimiento', $this->fechaVencimiento, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $errInfo = $stmt->errorInfo();
            throw new PDOException('Error al insertar en cuentas_cobrar: ' . ($errInfo[2] ?? 'desconocido'));
        }
    }

    // FUNCIÓN: _crearYPagardeInmediatoContado
    // OBJETIVO: Crea la cuenta por cobrar para la venta de contado y registra su pago completo de inmediato (cadena lineal notas_entrega -> cuentas_cobrar -> pagos_recibidos)
    private function _crearYPagardeInmediatoContado(): void
    {
        if ($this->idCliente <= 0 && $this->notaId > 0) {
            $stmtCli = $this->conexion->prepare("SELECT id_cliente FROM notas_entrega WHERE id_nota_entrega = :nid");
            $stmtCli->bindValue(':nid', $this->notaId, PDO::PARAM_INT);
            $stmtCli->execute();
            $cliRow = $stmtCli->fetch();
            if ($cliRow && !empty($cliRow['id_cliente'])) {
                $this->idCliente = (int)$cliRow['id_cliente'];
            }
        }

        // 1. Crear cuenta por cobrar ya saldada (monto total = total, saldo pendiente = 0, estado = 'pagado')
        $consultaCxc = "INSERT INTO cuentas_cobrar (id_cliente, id_nota_entrega, monto_total, saldo_pendiente, fecha_vencimiento, estado, activo) 
                        VALUES (:id_cliente, :id_nota_entrega, :monto_total, 0, NOW(), 'pagado', 1)";
        $stmtCxc = $this->conexion->prepare($consultaCxc);
        $stmtCxc->bindValue(':id_cliente', $this->idCliente, PDO::PARAM_INT);
        $stmtCxc->bindValue(':id_nota_entrega', $this->notaId, PDO::PARAM_INT);
        $stmtCxc->bindValue(':monto_total', $this->total);
        $stmtCxc->execute();

        $idCuentaCobrar = (int)$this->conexion->lastInsertId();

        // 2. Registrar el pago de contado apuntando a la cuenta por cobrar creada
        $tipoPagoVal = $this->idTipoPago ?? 1;
        $consultaPago = "INSERT INTO pagos_recibidos (id_cuenta_cobrar, id_tipo_pago, id_banco, monto, fecha, referencia) 
                         VALUES (:id_cuenta_cobrar, :id_tipo_pago, :id_banco, :monto, NOW(), :referencia)";
        $stmtPago = $this->conexion->prepare($consultaPago);
        $stmtPago->bindValue(':id_cuenta_cobrar', $idCuentaCobrar, PDO::PARAM_INT);
        $stmtPago->bindValue(':id_tipo_pago', $tipoPagoVal, PDO::PARAM_INT);
        $stmtPago->bindValue(':id_banco', $this->idBanco, empty($this->idBanco) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmtPago->bindValue(':monto', $this->total);
        $stmtPago->bindValue(':referencia', $this->referencia, empty($this->referencia) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtPago->execute();
    }

    // FUNCIÓN: obtenerVentasMes
    // OBJETIVO: Retorna la suma total de notas de entrega en el mes actual
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
    // OBJETIVO: Suma el total de notas de entrega activas del mes en curso
    private function _ejecutarVentasMes(): string
    {
        $consulta = "SELECT COALESCE(SUM(total), 0) as total
                     FROM notas_entrega
                     WHERE activo = 1
                       AND MONTH(fecha) = MONTH(CURDATE())
                       AND YEAR(fecha) = YEAR(CURDATE())";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchColumn();
    }

    // FUNCIÓN: _ejecutarTopProductos
    // OBJETIVO: Agrupa por producto la cantidad consumida del día, ordena y limita
    private function _ejecutarTopProductos(): array
    {
        $consulta = "SELECT p.id_producto, p.id_producto as id_insumo, p.codigo, p.nombre,
                            SUM(pd.cantidad * ic.cantidad_consumida) as total_vendido
                     FROM notas_entrega ne
                     INNER JOIN presupuesto_detalle pd ON ne.id_presupuesto = pd.id_presupuesto
                     INNER JOIN item_composicion ic ON pd.id_presupuesto_detalle = ic.id_presupuesto_detalle
                     INNER JOIN productos p ON ic.id_producto = p.id_producto
                     WHERE DATE(ne.fecha) = CURDATE() AND ne.activo = 1
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
                            COALESCE(SUM(ne.total), 0) as total_comprado
                     FROM notas_entrega ne
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     WHERE ne.activo = 1
                       AND MONTH(ne.fecha) = MONTH(CURDATE())
                       AND YEAR(ne.fecha) = YEAR(CURDATE())
                     GROUP BY c.id_cliente
                     ORDER BY total_comprado DESC
                     LIMIT 1";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetch();
    }
}
