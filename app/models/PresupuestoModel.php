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
            'pendiente' => 1,
            'aprobado' => 2,
            'rechazado' => 3,
            'convertido' => 4,
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
    // OBJETIVO: Ejecuta la consulta que lista todos los presupuestos activos
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT p.*, ep.nombre as estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     INNER JOIN estado_presupuesto ep ON p.id_estado_presupuesto = ep.id_estado_presupuesto
                     WHERE p.activo = 1
                     ORDER BY p.fecha DESC, p.id_presupuesto DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de un presupuesto por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT p.*, ep.nombre as estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     INNER JOIN estado_presupuesto ep ON p.id_estado_presupuesto = ep.id_estado_presupuesto
                     WHERE p.id_presupuesto = :id AND p.activo = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta la inserción del presupuesto y su detalle en una transacción
    // NOTA: Hace rollback automático si falla algún INSERT del detalle
    private function _ejecutarInsert(): int
    {
        try {
            $this->conexion->beginTransaction();

            $consulta = "INSERT INTO presupuestos (id_cliente, id_usuario, fecha, total, id_estado_presupuesto, observaciones) 
                         VALUES (:id_cliente, :id_usuario, NOW(), :total, 1, :observaciones)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':id_cliente', $this->idCliente, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $this->idUsuario, PDO::PARAM_INT);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':observaciones', $this->observaciones, PDO::PARAM_STR);
            $stmt->execute();

            $presupuestoId = $this->conexion->lastInsertId();

            $consultaDetalle = "INSERT INTO presupuesto_detalle (id_presupuesto, descripcion, cantidad, precio_unitario, subtotal) 
                                VALUES (:id_presupuesto, :descripcion, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);

            $consultaComp = "INSERT INTO item_composicion (id_presupuesto_detalle, id_producto, fraccion_128, cantidad_consumida) 
                             VALUES (:id_pd, :id_producto, :fraccion, :cantidad_consumida)";
            $stmtComp = $this->conexion->prepare($consultaComp);

            foreach ($this->detalle as $item) {
                $descripcion = !empty($item['descripcion']) ? $item['descripcion'] : (!empty($item['nombre']) ? $item['nombre'] : 'Producto');
                $stmtDetalle->bindValue(':id_presupuesto', $presupuestoId, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
                $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();

                $pdId = (int)$this->conexion->lastInsertId();

                if (!empty($item['mezclas']) && is_array($item['mezclas'])) {
                    // Si es una mezcla, insertamos cada tinte base en item_composicion
                    foreach ($item['mezclas'] as $m) {
                        $idBase = (int)($m['id_producto_base'] ?? $m['id_producto'] ?? 0);
                        if ($idBase > 0) {
                            $stmtComp->bindValue(':id_pd', $pdId, PDO::PARAM_INT);
                            $stmtComp->bindValue(':id_producto', $idBase, PDO::PARAM_INT);
                            $stmtComp->bindValue(':fraccion', (int)($m['fraccion_128'] ?? 0), PDO::PARAM_INT);
                            $stmtComp->bindValue(':cantidad_consumida', (float)($m['cantidad_consumida'] ?? $m['cantidad_galon'] ?? 0));
                            $stmtComp->execute();
                        }
                    }
                } else {
                    // Articulo directo / simple: 1 componente exacto
                    $idProd = (int)($item['id_producto'] ?? $item['id_insumo'] ?? 0);
                    if ($idProd > 0) {
                        $stmtComp->bindValue(':id_pd', $pdId, PDO::PARAM_INT);
                        $stmtComp->bindValue(':id_producto', $idProd, PDO::PARAM_INT);
                        $stmtComp->bindValue(':fraccion', 0, PDO::PARAM_INT);
                        $stmtComp->bindValue(':cantidad_consumida', 1.0000);
                        $stmtComp->execute();
                    }
                }
            }

            $this->conexion->commit();
            return (int) $presupuestoId;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarSelectDetalle
    // OBJETIVO: Obtiene las líneas de detalle de un presupuesto con datos del producto y su composición
    private function _ejecutarSelectDetalle(): array
    {
        $consulta = "SELECT pd.*, 
                            pd.descripcion as insumo_nombre,
                            COALESCE(p.codigo, 'PREP') as insumo_codigo, 
                            COALESCE(p.stock_actual, 0) as stock_actual,
                            ic.id_producto as id_insumo, 
                            ic.id_producto as id_producto,
                            ic.id_item_composicion,
                            ic.fraccion_128,
                            ic.cantidad_consumida
                     FROM presupuesto_detalle pd
                     LEFT JOIN item_composicion ic ON pd.id_presupuesto_detalle = ic.id_presupuesto_detalle
                     LEFT JOIN productos p ON ic.id_producto = p.id_producto
                     WHERE pd.id_presupuesto = :id_presupuesto";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_presupuesto', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarUpdateEstado
    // OBJETIVO: Ejecuta el UPDATE del estado del presupuesto
    private function _ejecutarUpdateEstado(): bool
    {
        $consulta = "UPDATE presupuestos SET id_estado_presupuesto = :id_estado_presupuesto WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_estado_presupuesto', $this->idEstadoPresupuesto, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el soft delete del presupuesto (activo = 0)
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE presupuestos SET activo = 0 WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Busca presupuestos construyendo condiciones dinámicas según término y estado
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
                $condiciones[] = "p.id_estado_presupuesto = :estado";
                $parametros[':estado'] = (int)$this->estado;
            } else {
                $condiciones[] = "ep.nombre = :estado";
                $parametros[':estado'] = $this->estado;
            }
        }

        $condiciones[] = "p.activo = 1";

        $where = '';
        if (!empty($condiciones)) {
            $where = 'WHERE ' . implode(' AND ', $condiciones);
        }

        $consulta = "SELECT p.*, ep.nombre as estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     INNER JOIN estado_presupuesto ep ON p.id_estado_presupuesto = ep.id_estado_presupuesto
                     {$where}
                     ORDER BY p.fecha DESC, p.id_presupuesto DESC";

        $stmt = $this->conexion->prepare($consulta);

        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
}
