<?php
declare(strict_types=1);

namespace App\Models;

use \PDO;
use \PDOException;

class PresupuestoModel extends ModeloBase
{
    private int $id;
    private int $idCliente;
    private int $idUsuario;
    private float $total;
    private string $observaciones;
    private string $estado;
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
    public function cambiarEstado(int $id, string $estado): bool
    {
        if ($id <= 0) {
            throw new PDOException('ID no válido');
        }
        $this->id = $id;
        $this->estado = $estado;
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
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     WHERE p.activo = 1
                     ORDER BY p.fecha DESC, p.id_presupuesto DESC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de un presupuesto por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
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

            $consulta = "INSERT INTO presupuestos (id_cliente, id_usuario, fecha, total, estado, observaciones) 
                         VALUES (:id_cliente, :id_usuario, NOW(), :total, 'pendiente', :observaciones)";
            $stmt = $this->conexion->prepare($consulta);
            $stmt->bindParam(':id_cliente', $this->idCliente, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $this->idUsuario, PDO::PARAM_INT);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':observaciones', $this->observaciones, PDO::PARAM_STR);
            $stmt->execute();

            $presupuestoId = $this->conexion->lastInsertId();

            $consultaDetalle = "INSERT INTO presupuesto_detalle (id_presupuesto, id_insumo, cantidad, precio_unitario, subtotal) 
                                VALUES (:id_presupuesto, :id_insumo, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->conexion->prepare($consultaDetalle);

            foreach ($this->detalle as $item) {
                $stmtDetalle->bindValue(':id_presupuesto', $presupuestoId, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':id_insumo', $item['id_insumo'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $item['cantidad']);
                $stmtDetalle->bindValue(':precio_unitario', $item['precio_unitario']);
                $stmtDetalle->bindValue(':subtotal', $item['subtotal']);
                $stmtDetalle->execute();
            }

            $this->conexion->commit();
            return (int) $presupuestoId;

        } catch (PDOException $e) {
            $this->conexion->rollback();
            throw $e;
        }
    }

    // FUNCIÓN: _ejecutarSelectDetalle
    // OBJETIVO: Obtiene las líneas de detalle de un presupuesto con datos del insumo
    private function _ejecutarSelectDetalle(): array
    {
        $consulta = "SELECT pd.*, i.nombre as insumo_nombre, i.codigo as insumo_codigo,
                            i.marca as insumo_marca, i.stock_actual as stock_actual
                     FROM presupuesto_detalle pd
                     INNER JOIN insumos i ON pd.id_insumo = i.id_insumo
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
        $consulta = "UPDATE presupuestos SET estado = :estado WHERE id_presupuesto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':estado', $this->estado, PDO::PARAM_STR);
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
            $condiciones[] = "p.estado = :estado";
            $parametros[':estado'] = $this->estado;
        }

        $condiciones[] = "p.activo = 1";

        $where = '';
        if (!empty($condiciones)) {
            $where = 'WHERE ' . implode(' AND ', $condiciones);
        }

        $consulta = "SELECT p.*, 
                            CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                            c.cedula as cliente_cedula,
                            u.nombre as usuario_nombre
                     FROM presupuestos p 
                     INNER JOIN clientes c ON p.id_cliente = c.id_cliente
                     INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                     {$where}
                     ORDER BY p.fecha DESC, p.id_presupuesto DESC";

        $stmt = $this->conexion->prepare($consulta);

        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
}
