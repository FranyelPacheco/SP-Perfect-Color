<?php
declare(strict_types=1);

namespace App\Models;

use PDO, PDOException;

class PresupuestoModel extends ModeloBase
{
    private int $id;
    private int $idCliente;
    private int $idUsuario;
    private float $total;
    private string $observaciones;
    private string $estado;
    private int $idEstadoPresupuesto;
    private array $detalle;
    private string $termino;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los presupuestos activos con datos del cliente y usuario
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un presupuesto por su ID con datos relacionados
    public function buscarPorId(int $id): array|false
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: insertarPresupuesto
    // OBJETIVO: Crea un nuevo presupuesto con su detalle en una transacción
    // NOTA: Inserta el presupuesto y todos sus items en presupuesto_detalle
    public function insertarPresupuesto(int $idCliente, int $idUsuario, float $total, string $observaciones, array $detalle): int
    {
        $this->idCliente = $idCliente;
        $this->idUsuario = $idUsuario;
        $this->total = $total;
        $this->observaciones = $observaciones;
        $this->detalle = $detalle;
        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: obtenerDetalle
    // OBJETIVO: Obtiene el detalle de un presupuesto (insumos, cantidades, precios)
    public function obtenerDetalle(int $presupuestoId): array
    {
        if ($presupuestoId <= 0) {
            throw new PDOException('ID de presupuesto no válido');
        }
        $this->id = $presupuestoId;
        return $this->_ejecutarSelectDetalle();
    }

    // FUNCIÓN: cambiarEstado
    // OBJETIVO: Cambia el estado de un presupuesto (pendiente, aprobado, rechazado, convertido)
    public function cambiarEstado(int $id, string|int $estado): bool
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        $map = [
            'presupuesto' => 1,
            'pendiente' => 1,
            'aprobado' => 2,
            'rechazado' => 3,
            'convertido' => 4,
            'anulado' => 4,
        ];
        if (is_numeric($estado)) {
            $this->idEstadoPresupuesto = (int)$estado;
        } else {
            $this->idEstadoPresupuesto = $map[strtolower(trim((string)$estado))] ?? 1;
        }
        return $this->_ejecutarUpdateEstado();
    }

    // FUNCIÓN: eliminarPresupuesto
    // OBJETIVO: Desactiva un presupuesto (soft delete)
    public function eliminarPresupuesto(int $id): bool
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: buscarPresupuestos
    // OBJETIVO: Busca presupuestos por nombre/cedula del cliente y opcionalmente por estado
    public function buscarPresupuestos(string $termino, string $estado = ''): array
    {
        $this->termino = $termino;
        $this->estado = $estado;
        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todas las ventas/presupuestos activos
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT v.*, v.id_venta as id_presupuesto, v.estado as activo, ev.nombre as estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            'Sistema' as usuario_nombre
                     FROM ventas v 
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     INNER JOIN estado_venta ev ON v.id_estado_venta = ev.id_estado_venta
                     WHERE v.estado = 1
                     ORDER BY v.fecha DESC, v.id_venta DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de una venta por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT v.*, v.id_venta as id_presupuesto, v.estado as activo, ev.nombre as estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            'Sistema' as usuario_nombre
                     FROM ventas v 
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     INNER JOIN estado_venta ev ON v.id_estado_venta = ev.id_estado_venta
                     WHERE v.id_venta = :id AND v.estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta la inserción de la venta/presupuesto y su detalle en una transacción
    private function _ejecutarInsert(): int
    {
        try {
            $this->conexion->beginTransaction();

            $consulta = "INSERT INTO ventas (id_cliente, id_estado_venta, fecha, fecha_aprobacion, total, condicion_pago, observaciones, estado) 
                         VALUES (:id_cliente, 1, NOW(), NULL, :total, 'contado', :observaciones, 1)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':id_cliente', $this->idCliente, PDO::PARAM_INT);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':observaciones', $this->observaciones, PDO::PARAM_STR);
            $stmt->execute();

            $presupuestoId = (int)$this->conexion->lastInsertId();

            $consultaProdNuevo = "INSERT INTO productos (id_tipo_producto, id_producto_padre, fraccion, codigo, nombre, unidad_medida, stock_actual, stock_minimo, precio_venta, estado)
                                  VALUES (3, NULL, '1', :codigo, :nombre, 'Galon', :stock, 0, :precio_venta, 1)";
            $stmtProdNuevo = $this->conexion->prepare($consultaProdNuevo);

            $consultaMezcla = "INSERT INTO mezcla_producto (id_producto_mezclado, id_producto_base, fraccion, cantidad_galon) 
                               VALUES (:id_mezclado, :id_base, :fraccion, :cantidad_galon)";
            $stmtMezcla = $this->conexion->prepare($consultaMezcla);

            $consultaDetalle = "INSERT INTO venta_detalle (id_venta, id_producto, descripcion, cantidad, precio_unitario, subtotal) 
                                VALUES (:id_venta, :id_producto, :descripcion, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);

            $mixSeq = 1;
            foreach ($this->detalle as $item) {
                $descripcion = !empty($item['descripcion']) ? $item['descripcion'] : (!empty($item['nombre']) ? $item['nombre'] : 'Producto');
                $idProd = (int)($item['id_producto'] ?? $item['id_insumo'] ?? 0);

                if (!empty($item['mezclas']) && is_array($item['mezclas'])) {
                    $codigoMezcla = 'PREP-' . $presupuestoId . '-' . $mixSeq . '-' . substr(uniqid(), -4);
                    $mixSeq++;
                    $stmtProdNuevo->bindValue(':codigo', $codigoMezcla, PDO::PARAM_STR);
                    $stmtProdNuevo->bindValue(':nombre', $descripcion, PDO::PARAM_STR);
                    $stmtProdNuevo->bindValue(':stock', (float)$item['cantidad']);
                    $stmtProdNuevo->bindValue(':precio_venta', (float)$item['precio_unitario']);
                    $stmtProdNuevo->execute();

                    $idProd = (int)$this->conexion->lastInsertId();

                    foreach ($item['mezclas'] as $m) {
                        $idBase = (int)($m['id_producto_base'] ?? $m['id_producto'] ?? 0);
                        if ($idBase > 0) {
                            $fraccionTexto = !empty($m['fraccion']) ? (string)$m['fraccion'] : (!empty($m['fraccion_128']) ? $m['fraccion_128'] . '/128' : '1/4');
                            $stmtMezcla->bindValue(':id_mezclado', $idProd, PDO::PARAM_INT);
                            $stmtMezcla->bindValue(':id_base', $idBase, PDO::PARAM_INT);
                            $stmtMezcla->bindValue(':fraccion', $fraccionTexto, PDO::PARAM_STR);
                            $stmtMezcla->bindValue(':cantidad_galon', (float)($m['cantidad_consumida'] ?? $m['cantidad_galon'] ?? 0));
                            $stmtMezcla->execute();
                        }
                    }
                }

                if ($idProd > 0) {
                    $stmtDetalle->bindValue(':id_venta', $presupuestoId, PDO::PARAM_INT);
                    $stmtDetalle->bindValue(':id_producto', $idProd, PDO::PARAM_INT);
                    $stmtDetalle->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
                    $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                    $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                    $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                    $stmtDetalle->execute();
                }
            }

            $this->conexion->commit();
            return $presupuestoId;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarSelectDetalle
    // OBJETIVO: Obtiene las líneas de detalle de una venta con datos del producto
    private function _ejecutarSelectDetalle(): array
    {
        $consulta = "SELECT vd.*, 
                            vd.id_venta_detalle as id_presupuesto_detalle,
                            vd.id_venta as id_presupuesto,
                            COALESCE(NULLIF(vd.descripcion, ''), p.nombre) as insumo_nombre,
                            COALESCE(p.codigo, 'PREP') as insumo_codigo, 
                            COALESCE(p.stock_actual, 0) as stock_actual,
                            vd.id_producto as id_insumo, 
                            vd.id_producto as id_producto
                     FROM venta_detalle vd
                     INNER JOIN productos p ON vd.id_producto = p.id_producto
                     WHERE vd.id_venta = :id_venta";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_venta', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarUpdateEstado
    // OBJETIVO: Ejecuta el UPDATE del estado de la venta
    private function _ejecutarUpdateEstado(): bool
    {
        $sqlFechaAprobacion = ($this->idEstadoPresupuesto === 2) ? ", fecha_aprobacion = NOW()" : "";
        $consulta = "UPDATE ventas SET id_estado_venta = :id_estado_venta {$sqlFechaAprobacion} WHERE id_venta = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_estado_venta', $this->idEstadoPresupuesto, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el soft delete de la venta (estado = 0)
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE ventas SET estado = 0 WHERE id_venta = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca ventas construyendo condiciones dinámicas según término y estado
    private function _ejecutarSearch(): array
    {
        $condiciones = [];
        $parametros = [];

        if (!empty($this->termino)) {
            $condiciones[] = "(c.nombres LIKE :termino1 OR c.apellidos LIKE :termino2 OR c.cedula LIKE :termino3)";
            $terminoLike = '%' . $this->termino . '%';
            $parametros[':termino1'] = $terminoLike;
            $parametros[':termino2'] = $terminoLike;
            $parametros[':termino3'] = $terminoLike;
        }

        if (!empty($this->estado)) {
            if (is_numeric($this->estado)) {
                $condiciones[] = "v.id_estado_venta = :estado";
                $parametros[':estado'] = (int)$this->estado;
            } else {
                $condiciones[] = "ev.nombre = :estado";
                $parametros[':estado'] = $this->estado;
            }
        }

        $condiciones[] = "v.estado = 1";

        $where = '';
        if (!empty($condiciones)) {
            $where = 'WHERE ' . implode(' AND ', $condiciones);
        }

        $consulta = "SELECT v.*, v.id_venta as id_presupuesto, v.estado as activo, ev.nombre as estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            'Sistema' as usuario_nombre
                     FROM ventas v 
                     INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                     INNER JOIN estado_venta ev ON v.id_estado_venta = ev.id_estado_venta
                     {$where}
                     ORDER BY v.fecha DESC, v.id_venta DESC";

        $stmt = $this->conexion->prepare($consulta);

        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
}
