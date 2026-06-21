<?php
declare(strict_types=1);

namespace App\Models;

use \PDO;
use \PDOException;

class NotaEntregaModel extends ModeloBase
{
    private int $id;
    private int $idCliente;
    private int $idUsuario;
    private float $total;
    private int $idPresupuesto;
    private string $estado;
    private string $condicionPago;
    private ?int $idTipoPago;
    private ?int $idBanco;
    private ?string $referencia;
    private ?string $fechaVencimiento;
    private array $detalle;
    private string $termino;
    private int $notaId;

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
    public function crearNotaEntrega(int $idCliente, int $idUsuario, float $total, int $idPresupuesto, string $estado, string $condicionPago, array $detalle, ?int $idTipoPago = null, ?int $idBanco = null, ?string $referencia = null, ?string $fechaVencimiento = null): int
    {
        $this->idCliente = $idCliente;
        $this->idUsuario = $idUsuario;
        $this->total = $total;
        $this->idPresupuesto = $idPresupuesto;
        $this->estado = $estado;
        $this->condicionPago = $condicionPago;
        $this->detalle = $detalle;
        $this->idTipoPago = $idTipoPago;
        $this->idBanco = $idBanco;
        $this->referencia = $referencia;
        $this->fechaVencimiento = $fechaVencimiento;
        return $this->_ejecutarCrearNota();
    }

    // FUNCIÓN: cambiarEstado
    // OBJETIVO: Cambia el estado de una nota de entrega
    public function cambiarEstado(int $id, string $estado): bool
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        $this->estado = $estado;
        return $this->_ejecutarUpdateEstado();
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
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.activo = 1
                     ORDER BY ne.fecha DESC, ne.id_nota_entrega DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una nota por ID con datos del cliente y tipo de pago
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT ne.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            c.direccion as cliente_direccion,
                            (SELECT GROUP_CONCAT(tc.telefono SEPARATOR ', ') FROM telefono_cliente tc WHERE tc.id_cliente = c.id_cliente) as cliente_telefonos,
                            u.nombre as usuario_nombre,
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
                     WHERE ne.id_nota_entrega = :id AND ne.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarSelectDetalle
    // OBJETIVO: Obtiene las líneas de detalle de la nota con datos del insumo
    // NOTA: Hace JOIN con presupuesto_detalle para obtener el id_insumo
    private function _ejecutarSelectDetalle(): array
    {
        $consulta = "SELECT ned.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo,
                            i.marca as insumo_marca, i.id_insumo as id_insumo,
                            pd.id_insumo as pd_id_insumo
                     FROM nota_entrega_detalle ned
                     INNER JOIN presupuesto_detalle pd ON ned.id_presupuesto_detalle = pd.id_presupuesto_detalle
                     INNER JOIN insumos i ON pd.id_insumo = i.id_insumo
                     WHERE ned.id_nota_entrega = :id_nota_entrega";
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

            $estadoNota = !empty($this->estado) ? $this->estado : 'pendiente';
            $condicionPagoVal = !empty($this->condicionPago) ? $this->condicionPago : 'contado';

            $consulta = "INSERT INTO notas_entrega (id_cliente, id_usuario, fecha, total, estado, condicion_pago, id_tipo_pago, id_presupuesto) 
                          VALUES (:id_cliente, :id_usuario, NOW(), :total, :estado, :condicion_pago, :id_tipo_pago, :id_presupuesto)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindValue(':id_cliente', $this->idCliente, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $this->idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':total', $this->total);
            $stmt->bindValue(':estado', $estadoNota, PDO::PARAM_STR);
            $stmt->bindValue(':condicion_pago', $condicionPagoVal, PDO::PARAM_STR);
            $stmt->bindValue(':id_tipo_pago', $this->idTipoPago, empty($this->idTipoPago) ? PDO::PARAM_NULL : PDO::PARAM_INT);
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
                    $this->_registrarPagoContado();
                }
            }

            $this->conexion->commit();
            return $this->notaId;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarUpdateEstado
    // OBJETIVO: Ejecuta el UPDATE del estado de la nota de entrega
    private function _ejecutarUpdateEstado(): bool
    {
        $consulta = "UPDATE notas_entrega SET estado = :estado WHERE id_nota_entrega = :id AND activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':estado', $this->estado, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarActualizarDetalle
    // OBJETIVO: Reemplaza el detalle de la nota: restaura stock viejo, elimina detalle, verifica stock, inserta nuevo y descuenta
    // NOTA: Transacción que recalcula el total de la nota
    private function _ejecutarActualizarDetalle(): bool
    {
        try {
            $this->conexion->beginTransaction();

            $detalleAnterior = $this->_ejecutarSelectDetalle();
            foreach ($detalleAnterior as $item) {
                $consultaRestaurar = "UPDATE insumos i
                                      INNER JOIN presupuesto_detalle pd ON pd.id_insumo = i.id_insumo
                                      SET i.stock_actual = i.stock_actual + :cantidad
                                      WHERE pd.id_presupuesto_detalle = :pd_id";
                $stmtRestaurar = $this->conexion->prepare($consultaRestaurar);
                $stmtRestaurar->bindValue(':cantidad', $item['cantidad']);
                $stmtRestaurar->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
                $stmtRestaurar->execute();
            }

            $consultaEliminar = "DELETE FROM nota_entrega_detalle WHERE id_nota_entrega = :id_nota_entrega";
            $stmtEliminar = $this->conexion->prepare($consultaEliminar);
            $stmtEliminar->bindValue(':id_nota_entrega', $this->id, PDO::PARAM_INT);
            $stmtEliminar->execute();

            $total = 0;
            $consultaDetalle = "INSERT INTO nota_entrega_detalle (id_nota_entrega, id_presupuesto_detalle, cantidad, precio_unitario, subtotal) 
                                VALUES (:id_nota_entrega, :id_presupuesto_detalle, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);

            $consultaDescontar = "UPDATE insumos i
                                  INNER JOIN presupuesto_detalle pd ON pd.id_insumo = i.id_insumo
                                  SET i.stock_actual = i.stock_actual - :cantidad
                                  WHERE pd.id_presupuesto_detalle = :pd_id";
            $stmtDescontar = $this->conexion->prepare($consultaDescontar);

            foreach ($this->detalle as $item) {
                $consultaStock = "SELECT i.stock_actual FROM insumos i
                                  INNER JOIN presupuesto_detalle pd ON pd.id_insumo = i.id_insumo
                                  WHERE pd.id_presupuesto_detalle = :pd_id AND i.activo = 1";
                $stmtStock = $this->conexion->prepare($consultaStock);
                $stmtStock->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
                $stmtStock->execute();
                $insumo = $stmtStock->fetch();

                if (!$insumo || $insumo['stock_actual'] < $item['cantidad']) {
                    throw new PDOException('Stock insuficiente para el item ID: ' . $item['id_presupuesto_detalle']);
                }

                $stmtDetalle->bindValue(':id_nota_entrega', $this->id, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':id_presupuesto_detalle', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();

                $stmtDescontar->bindValue(':cantidad', $item['cantidad']);
                $stmtDescontar->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
                $stmtDescontar->execute();

                $total += $item['subtotal'];
            }

            $consultaTotal = "UPDATE notas_entrega SET total = :total WHERE id_nota_entrega = :id";
            $stmtTotal = $this->conexion->prepare($consultaTotal);
            $stmtTotal->bindValue(':total', $total);
            $stmtTotal->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmtTotal->execute();

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
                            tp.nombre as tipo_pago_nombre
                     FROM notas_entrega ne 
                     INNER JOIN clientes c ON ne.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON ne.id_usuario = u.id_usuario
                     LEFT JOIN tipo_pago tp ON ne.id_tipo_pago = tp.id_tipo_pago
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
    // OBJETIVO: Verifica que todos los items tengan stock suficiente antes de crear la nota
    // NOTA: Usa FOR UPDATE para bloquear filas dentro de la transacción
    private function _validarStock(): void
    {
        foreach ($this->detalle as $item) {
            $consulta = "SELECT i.stock_actual FROM insumos i
                         INNER JOIN presupuesto_detalle pd ON pd.id_insumo = i.id_insumo
                         WHERE pd.id_presupuesto_detalle = :pd_id AND i.activo = 1 FOR UPDATE";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
            $stmt->execute();
            $insumo = $stmt->fetch();

            if (!$insumo || (float)$insumo['stock_actual'] < (float)$item['cantidad']) {
                throw new PDOException('Stock insuficiente para el item ID: ' . $item['id_presupuesto_detalle']);
            }
        }
    }

    // FUNCIÓN: _insertarDetalleYDescontarStock
    // OBJETIVO: Inserta el detalle en nota_entrega_detalle y descuenta el stock de cada insumo
    private function _insertarDetalleYDescontarStock(): void
    {
        $consultaDetalle = "INSERT INTO nota_entrega_detalle (id_nota_entrega, id_presupuesto_detalle, cantidad, precio_unitario, subtotal) 
                            VALUES (:id_nota_entrega, :id_presupuesto_detalle, :cantidad, :precio_unitario, :subtotal)";
        $stmtDetalle = $this->conexion->prepare($consultaDetalle);

        $consultaDescontar = "UPDATE insumos i
                              INNER JOIN presupuesto_detalle pd ON pd.id_insumo = i.id_insumo
                              SET i.stock_actual = i.stock_actual - :cantidad
                              WHERE pd.id_presupuesto_detalle = :pd_id";
        $stmtDescontar = $this->conexion->prepare($consultaDescontar);

        foreach ($this->detalle as $item) {
            $stmtDetalle->bindValue(':id_nota_entrega', $this->notaId, PDO::PARAM_INT);
            $stmtDetalle->bindValue(':id_presupuesto_detalle', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
            $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
            $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
            $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
            $stmtDetalle->execute();

            $stmtDescontar->bindValue(':cantidad', $item['cantidad']);
            $stmtDescontar->bindValue(':pd_id', $item['id_presupuesto_detalle'], PDO::PARAM_INT);
            $stmtDescontar->execute();
        }
    }

    // FUNCIÓN: _cambiarEstadoPresupuesto
    // OBJETIVO: Marca el presupuesto como 'convertido' al generar la nota de entrega
    private function _cambiarEstadoPresupuesto(): void
    {
        $consulta = "UPDATE presupuestos SET estado = 'convertido' WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':id', $this->idPresupuesto, PDO::PARAM_INT);
        $stmt->execute();
    }

    // FUNCIÓN: _crearCuentaCobrar
    // OBJETIVO: Crea una cuenta por cobrar cuando la condición de pago es crédito
    // NOTA: El saldo pendiente se inicializa igual al monto total
    private function _crearCuentaCobrar(): void
    {
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

    // FUNCIÓN: _registrarPagoContado
    // OBJETIVO: Registra el pago de contado en la tabla pagos_recibidos
    // NOTA: id_cuenta_cobrar es NULL porque no hay cuenta asociada en pagos de contado directos
    private function _registrarPagoContado(): void
    {
        $tipoPagoVal = $this->idTipoPago ?? 1;
        $consulta = "INSERT INTO pagos_recibidos (id_cuenta_cobrar, id_tipo_pago, id_banco, monto, fecha, referencia) 
                     VALUES (NULL, :id_tipo_pago, :id_banco, :monto, NOW(), :referencia)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindValue(':id_tipo_pago', $tipoPagoVal, PDO::PARAM_INT);
        $stmt->bindValue(':id_banco', $this->idBanco, empty($this->idBanco) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':monto', $this->total);
        $stmt->bindValue(':referencia', $this->referencia, empty($this->referencia) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }
}
