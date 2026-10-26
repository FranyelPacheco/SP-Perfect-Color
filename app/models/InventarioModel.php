<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class InventarioModel extends ModeloBase
{
    private int $id_producto;
    private int $id_tipo_producto;
    private string $codigo;
    private string $nombre;
    private ?int $id_rubro;
    private string $unidad_medida;
    private float $stock_actual;
    private float $stock_minimo;
    private float $precio_venta;
    private float $precio_compra;
    private int $activo;
    private int $id;
    private ?int $idExcluir;
    private int $productoId;
    private int $proveedorId;
    private int $idProveedor;
    private string $termino;

    // FUNCIÓN: contarTodos
    // OBJETIVO: Retorna el total de productos activos en el sistema
    public function contarTodos(): int
    {
        return $this->_ejecutarCountAll();
    }

    // FUNCIÓN: _ejecutarCountAll
    // OBJETIVO: Ejecuta el COUNT de todos los productos con estado = 1
    private function _ejecutarCountAll(): int
    {
        $consulta = "SELECT COUNT(*) as total FROM productos WHERE estado = 1";
        $stmt = $this->conexion->query($consulta);
        return (int)$stmt->fetch()['total'];
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los productos activos con sus rubros, tipo y proveedores asociados
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista todos los productos activos agrupados por ID
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT p.*, p.estado as activo, tp.nombre as tipo_producto_nombre,
                            p.id_producto as id_insumo,
                            (SELECT COALESCE(SUM(vd.cantidad), 0)
                             FROM venta_detalle vd
                             INNER JOIN ventas v ON vd.id_venta = v.id_venta
                             WHERE vd.id_producto = p.id_producto
                               AND v.id_estado_venta = 1
                               AND v.estado = 1) as stock_comprometido,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubro_nombre,
                            GROUP_CONCAT(DISTINCT pr.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT pr.id_proveedor SEPARATOR ',') as proveedores_id
                     FROM productos p
                     INNER JOIN tipo_producto tp ON p.id_tipo_producto = tp.id_tipo_producto
                     LEFT JOIN producto_proveedor pp ON pp.id_producto = p.id_producto
                     LEFT JOIN proveedores pr ON pr.id_proveedor = pp.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = pr.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.estado = 1
                     GROUP BY p.id_producto
                     ORDER BY p.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un producto por su ID, incluyendo rubros, tipo y proveedores
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la búsqueda de un producto por ID con datos relacionados
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT p.*, p.estado as activo, tp.nombre as tipo_producto_nombre,
                            p.id_producto as id_insumo,
                            (SELECT COALESCE(SUM(vd.cantidad), 0)
                             FROM venta_detalle vd
                             INNER JOIN ventas v ON vd.id_venta = v.id_venta
                             WHERE vd.id_producto = p.id_producto
                               AND v.id_estado_venta = 1
                               AND v.estado = 1) as stock_comprometido,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubro_nombre,
                            GROUP_CONCAT(DISTINCT pr.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT pr.id_proveedor SEPARATOR ',') as proveedores_id
                     FROM productos p
                     INNER JOIN tipo_producto tp ON p.id_tipo_producto = tp.id_tipo_producto
                     LEFT JOIN producto_proveedor pp ON pp.id_producto = p.id_producto
                     LEFT JOIN proveedores pr ON pr.id_proveedor = pp.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = pr.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.id_producto = :id AND p.estado = 1
                     GROUP BY p.id_producto LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: validarDatosInsumo
    // OBJETIVO: Valida reglas de negocio para creación o actualización de productos
    public function validarDatosInsumo(array $datos, ?int $idActual = null, bool $esCreacion = false): array
    {
        $codigo = strtoupper(trim($datos['codigo'] ?? ''));
        $nombre = trim($datos['nombre'] ?? '');
        $idTipoProducto = !empty($datos['id_tipo_producto']) ? intval($datos['id_tipo_producto']) : 2;
        $unidadMedida = trim($datos['unidad_medida'] ?? '');
        $stockActualRaw = $datos['stock_actual'] ?? '0';
        $stockMinimoRaw = $datos['stock_minimo'] ?? '0';
        $precioVenta = floatval($datos['precio_venta'] ?? 0);
        $precioCompra = floatval($datos['precio_compra'] ?? 0);
        $proveedorId = !empty($datos['id_proveedor']) ? intval($datos['id_proveedor']) : null;
        $rubroId = !empty($datos['id_rubro']) ? intval($datos['id_rubro']) : null;

        if ($idTipoProducto !== 1 && $idTipoProducto !== 2) {
            return ['valido' => false, 'mensaje' => 'Tipo de producto no valido (solo Base o Simple)'];
        }

        if ($idTipoProducto === 1) {
            $unidadMedida = 'Galon';
        } else {
            if (!in_array($unidadMedida, ['Unidad', 'KG'], true)) {
                return ['valido' => false, 'mensaje' => 'Para productos simples la unidad de medida debe ser Unidad o KG'];
            }
        }

        if ($esCreacion) {
            if (!is_numeric($stockActualRaw) || floor((float)$stockActualRaw) != (float)$stockActualRaw || (float)$stockActualRaw < 0) {
                return ['valido' => false, 'mensaje' => 'El stock actual debe ser un numero entero no negativo'];
            }
            $stockActual = (float)intval($stockActualRaw);
        } else {
            $stockActual = (float)$stockActualRaw;
        }

        if (!is_numeric($stockMinimoRaw) || floor((float)$stockMinimoRaw) != (float)$stockMinimoRaw || (float)$stockMinimoRaw < 0) {
            return ['valido' => false, 'mensaje' => 'El stock minimo debe ser un numero entero no negativo'];
        }
        $stockMinimo = (float)intval($stockMinimoRaw);

        if ($codigo === '') {
            return ['valido' => false, 'mensaje' => 'El codigo es obligatorio'];
        }

        if ($nombre === '') {
            return ['valido' => false, 'mensaje' => 'El nombre del insumo es obligatorio'];
        }

        if ($precioVenta <= 0) {
            return ['valido' => false, 'mensaje' => 'El precio de venta debe ser un numero positivo'];
        }

        if ($this->codigoExiste($codigo, $idActual)) {
            return ['valido' => false, 'mensaje' => ($idActual ? 'Ya existe otro insumo con ese codigo' : 'Ya existe un insumo con ese codigo')];
        }

        return [
            'valido' => true,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'rubro_id' => $rubroId,
            'unidad_medida' => $unidadMedida,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo,
            'precio_venta' => $precioVenta,
            'precio_compra' => $precioCompra,
            'proveedor_id' => $proveedorId,
            'id_tipo_producto' => $idTipoProducto
        ];
    }

    // FUNCIÓN: registrar
    // OBJETIVO: Valida y registra un nuevo insumo o reactiva uno inactivo existente
    public function registrar(array $datos): array
    {
        $val = $this->validarDatosInsumo($datos, null, true);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        $inactivoId = $this->buscarInactivoPorCodigo($val['codigo']);
        if ($inactivoId) {
            if ($this->actualizarInsumo($inactivoId, $val['codigo'], $val['nombre'], $val['rubro_id'], $val['unidad_medida'], $val['stock_actual'], $val['stock_minimo'], $val['precio_venta'], $val['precio_compra'], $val['id_tipo_producto'])) {
                $this->eliminarProveedoresDeInsumo($inactivoId);
                if ($val['proveedor_id']) {
                    $this->asignarProveedorAInsumo($inactivoId, $val['proveedor_id']);
                }
                return ['exito' => true, 'mensaje' => 'Insumo reactivado exitosamente', 'datos' => ['id_producto' => $inactivoId]];
            }
            return ['exito' => false, 'mensaje' => 'Error al reactivar el insumo'];
        }

        $nuevoId = $this->insertarInsumo($val['codigo'], $val['nombre'], $val['rubro_id'], $val['unidad_medida'], $val['stock_actual'], $val['stock_minimo'], $val['precio_venta'], $val['precio_compra'], $val['id_tipo_producto']);
        if ($nuevoId) {
            if ($val['proveedor_id']) {
                $this->asignarProveedorAInsumo($nuevoId, $val['proveedor_id']);
            }
            return [
                'exito' => true,
                'mensaje' => 'Insumo creado exitosamente',
                'datos' => [
                    'id_producto' => $nuevoId,
                    'codigo' => $val['codigo'],
                    'nombre' => $val['nombre'],
                    'precio_venta' => $val['precio_venta']
                ]
            ];
        }
        return ['exito' => false, 'mensaje' => 'Error al crear el insumo'];
    }

    // FUNCIÓN: modificar
    // OBJETIVO: Valida y actualiza los datos de un insumo existente
    public function modificar(int $id, array $datos): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de insumo no valido'];
        }

        $insumoExistente = $this->buscarPorId($id);
        if (!$insumoExistente) {
            return ['exito' => false, 'mensaje' => 'Insumo no encontrado'];
        }

        // El stock actual es de solo lectura y se mantiene el actual
        $datos['stock_actual'] = (float)$insumoExistente['stock_actual'];

        $val = $this->validarDatosInsumo($datos, $id, false);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        if ($this->actualizarInsumo($id, $val['codigo'], $val['nombre'], $val['rubro_id'], $val['unidad_medida'], $val['stock_actual'], $val['stock_minimo'], $val['precio_venta'], 0, $val['id_tipo_producto'])) {
            return ['exito' => true, 'mensaje' => 'Insumo actualizado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al actualizar el insumo'];
    }

    // FUNCIÓN: darDeBaja
    // OBJETIVO: Realiza el soft-delete seguro de un insumo
    public function darDeBaja(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de insumo no valido'];
        }

        if ($this->eliminarInsumo($id)) {
            return ['exito' => true, 'mensaje' => 'Insumo eliminado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al eliminar el insumo'];
    }

    // FUNCIÓN: insertarInsumo
    // OBJETIVO: Inserta un nuevo producto en la BD con los datos proporcionados
    public function insertarInsumo(string $codigo, string $nombre, ?int $idRubro = null, string $unidadMedida = 'Unidad', float $stockActual = 0, float $stockMinimo = 5, float $precioVenta = 0, float $precioCompra = 0, int $idTipoProducto = 2): int|false
    {
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->id_rubro = $idRubro;
        $this->unidad_medida = $unidadMedida;
        $this->stock_actual = $stockActual;
        $this->stock_minimo = $stockMinimo;
        $this->precio_venta = $precioVenta;
        $this->precio_compra = $precioCompra;
        $this->id_tipo_producto = $idTipoProducto;
        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta la inserción del producto en la tabla productos
    private function _ejecutarInsert(): int|false
    {
        $consulta = "INSERT INTO productos (codigo, nombre, id_tipo_producto, unidad_medida,
                     stock_actual, stock_minimo, precio_venta, estado)
                     VALUES (:codigo, :nombre, :id_tipo_producto, :unidad_medida,
                     :stock_actual, :stock_minimo, :precio_venta, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':id_tipo_producto', $this->id_tipo_producto, PDO::PARAM_INT);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida, PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $this->stock_actual);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);
        $stmt->bindParam(':precio_venta', $this->precio_venta);
        if ($stmt->execute()) return (int)$this->conexion->lastInsertId();
        return false;
    }

    // FUNCIÓN: actualizarInsumo
    // OBJETIVO: Actualiza todos los campos de un producto existente y lo marca como activo
    public function actualizarInsumo(int $id, string $codigo, string $nombre, ?int $idRubro = null, string $unidadMedida = 'Unidad', float $stockActual = 0, float $stockMinimo = 5, float $precioVenta = 0, float $precioCompra = 0, int $idTipoProducto = 2): bool
    {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->id_rubro = $idRubro;
        $this->unidad_medida = $unidadMedida;
        $this->stock_actual = $stockActual;
        $this->stock_minimo = $stockMinimo;
        $this->precio_venta = $precioVenta;
        $this->precio_compra = $precioCompra;
        $this->id_tipo_producto = $idTipoProducto;
        return $this->_ejecutarUpdate();
    }

    // FUNCIÓN: _ejecutarUpdate
    // OBJETIVO: Ejecuta el UPDATE del producto seteando estado = 1 (reactivación implícita)
    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE productos 
                     SET codigo = :codigo, nombre = :nombre,
                         id_tipo_producto = :id_tipo_producto,
                         unidad_medida = :unidad_medida, stock_actual = :stock_actual,
                         stock_minimo = :stock_minimo, precio_venta = :precio_venta,
                         estado = 1
                     WHERE id_producto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':id_tipo_producto', $this->id_tipo_producto, PDO::PARAM_INT);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida, PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $this->stock_actual);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);
        $stmt->bindParam(':precio_venta', $this->precio_venta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminarInsumo
    // OBJETIVO: Marca un producto como inactivo (soft delete)
    public function eliminarInsumo(int $id): bool
    {
        $this->id = $id;
        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el UPDATE que desactiva el producto (estado = 0)
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE productos SET estado = 0 WHERE id_producto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: codigoExiste
    // OBJETIVO: Verifica si ya existe un producto activo con el mismo código, opcionalmente excluyendo un ID
    public function codigoExiste(string $codigo, ?int $idExcluir = null): bool
    {
        $this->codigo = $codigo;
        $this->idExcluir = $idExcluir;
        return $this->_ejecutarCheckCodigo();
    }

    // FUNCIÓN: _ejecutarCheckCodigo
    // OBJETIVO: Ejecuta la consulta COUNT para verificar unicidad del código
    private function _ejecutarCheckCodigo(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM productos WHERE codigo = :codigo AND estado = 1";
        if ($this->idExcluir !== null) {
            $consulta .= " AND id_producto != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        if ($this->idExcluir !== null) {
            $stmt->bindParam(':id', $this->idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch()['total'] > 0;
    }

    // FUNCIÓN: buscarInactivoPorCodigo
    // OBJETIVO: Busca un producto inactivo por código para reactivación
    // NOTA: Usado antes de insertar para evitar duplicados por soft delete
    public function buscarInactivoPorCodigo(string $codigo): int|false
    {
        if ($codigo === '') return false;
        $this->codigo = $codigo;
        return $this->_ejecutarBuscarInactivo();
    }

    // FUNCIÓN: _ejecutarBuscarInactivo
    // OBJETIVO: Retorna el ID de un producto inactivo con el código dado, o false si no existe
    private function _ejecutarBuscarInactivo(): int|false
    {
        $consulta = "SELECT id_producto FROM productos WHERE codigo = :codigo AND estado = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_producto'] : false;
    }

    // FUNCIÓN: buscarInsumos
    // OBJETIVO: Busca productos por nombre, código o nombre de rubro usando LIKE
    public function buscarInsumos(string $termino): array
    {
        $this->termino = '%' . $termino . '%';
        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: _ejecutarSearch
    // OBJETIVO: Ejecuta la búsqueda con tres condiciones OR (nombre, código, rubro)
    private function _ejecutarSearch(): array
    {
        $consulta = "SELECT p.*, p.estado as activo, tp.nombre as tipo_producto_nombre,
                            p.id_producto as id_insumo,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubro_nombre,
                            GROUP_CONCAT(DISTINCT pr.nombre_empresa SEPARATOR ', ') as proveedores_nombre,
                            GROUP_CONCAT(DISTINCT pr.id_proveedor SEPARATOR ',') as proveedores_id
                     FROM productos p
                     INNER JOIN tipo_producto tp ON p.id_tipo_producto = tp.id_tipo_producto
                     LEFT JOIN producto_proveedor pp ON pp.id_producto = p.id_producto
                     LEFT JOIN proveedores pr ON pr.id_proveedor = pp.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = pr.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.estado = 1 AND (p.nombre LIKE :termino1
                        OR p.codigo LIKE :termino2
                        OR r.nombre LIKE :termino3)
                     GROUP BY p.id_producto
                     ORDER BY p.nombre ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $this->termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $this->termino, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $this->termino, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarProveedoresActivos
    // OBJETIVO: Retorna todos los proveedores activos para usar en selects del formulario
    public function listarProveedoresActivos(): array
    {
        return $this->_ejecutarSelectProveedoresActivos();
    }

    // FUNCIÓN: _ejecutarSelectProveedoresActivos
    // OBJETIVO: Ejecuta la consulta de proveedores activos ordenados por nombre
    private function _ejecutarSelectProveedoresActivos(): array
    {
        $consulta = "SELECT id_proveedor, nombre_empresa, rif FROM proveedores WHERE estado = 1 ORDER BY nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarRubrosActivos
    // OBJETIVO: Retorna todos los rubros disponibles
    public function listarRubrosActivos(): array
    {
        return $this->_ejecutarSelectRubrosActivos();
    }

    // FUNCIÓN: _ejecutarSelectRubrosActivos
    // OBJETIVO: Ejecuta la consulta de todos los rubros ordenados por nombre
    private function _ejecutarSelectRubrosActivos(): array
    {
        $consulta = "SELECT id_rubro, nombre FROM rubro ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarTiposProducto
    // OBJETIVO: Retorna todos los tipos de producto activos
    public function listarTiposProducto(): array
    {
        $consulta = "SELECT id_tipo_producto, nombre, descripcion FROM tipo_producto WHERE estado = 1 ORDER BY id_tipo_producto ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarBases
    // OBJETIVO: Retorna todos los insumos base para formulación de mezclas
    public function listarBases(): array
    {
        $consulta = "SELECT id_producto, codigo, nombre, unidad_medida, stock_actual, precio_venta, fraccion, id_producto_padre
                     FROM productos 
                     WHERE estado = 1 AND id_tipo_producto = 1 AND id_producto_padre IS NULL
                     ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: obtenerAlertasStockBajo
    // OBJETIVO: Obtiene los productos cuyo stock actual es menor o igual al stock mínimo
    // NOTA: Usado en el dashboard para alertas de inventario
    public function obtenerAlertasStockBajo(): array
    {
        return $this->_ejecutarAlertasStockBajo();
    }

    // FUNCIÓN: _ejecutarAlertasStockBajo
    // OBJETIVO: Ejecuta la consulta de productos con stock bajo, incluyendo rubros y proveedores
    private function _ejecutarAlertasStockBajo(): array
    {
        $consulta = "SELECT p.*, p.estado as activo, p.id_producto as id_insumo,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubro_nombre,
                            GROUP_CONCAT(DISTINCT pr.nombre_empresa SEPARATOR ', ') as proveedores_nombre
                     FROM productos p
                     LEFT JOIN producto_proveedor pp ON pp.id_producto = p.id_producto
                     LEFT JOIN proveedores pr ON pr.id_proveedor = pp.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = pr.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.estado = 1 AND p.stock_actual <= p.stock_minimo
                     GROUP BY p.id_producto
                     ORDER BY p.stock_actual ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: obtenerRubrosPorProveedor
    // OBJETIVO: Obtiene los rubros asociados a un proveedor específico
    public function obtenerRubrosPorProveedor(int $proveedorId): array
    {
        $this->idProveedor = $proveedorId;
        return $this->_ejecutarRubrosPorProveedor();
    }

    // FUNCIÓN: _ejecutarRubrosPorProveedor
    // OBJETIVO: Ejecuta la consulta de rubros filtrados por proveedor
    private function _ejecutarRubrosPorProveedor(): array
    {
        $consulta = "SELECT r.id_rubro, r.nombre
                     FROM rubro r
                     INNER JOIN rubro_proveedor rp ON rp.id_rubro = r.id_rubro
                     WHERE rp.id_proveedor = :id_proveedor
                     ORDER BY r.nombre ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $this->idProveedor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // FUNCIÓN: asignarProveedorAInsumo
    // OBJETIVO: Asocia un proveedor a un producto en la tabla intermedia
    // NOTA: Usa INSERT IGNORE para evitar duplicados
    public function asignarProveedorAInsumo(int $productoId, int $proveedorId): bool
    {
        $this->productoId = $productoId;
        $this->proveedorId = $proveedorId;
        return $this->_ejecutarAsignarProveedor();
    }

    // FUNCIÓN: _ejecutarAsignarProveedor
    // OBJETIVO: Ejecuta el INSERT IGNORE en la tabla producto_proveedor
    private function _ejecutarAsignarProveedor(): bool
    {
        $consulta = "INSERT IGNORE INTO producto_proveedor (id_producto, id_proveedor) VALUES (:id_producto, :id_proveedor)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_producto', $this->productoId, PDO::PARAM_INT);
        $stmt->bindParam(':id_proveedor', $this->proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminarProveedoresDeInsumo
    // OBJETIVO: Elimina todas las relaciones proveedor-producto para un producto dado
    public function eliminarProveedoresDeInsumo(int $productoId): bool
    {
        $this->productoId = $productoId;
        return $this->_ejecutarEliminarProveedores();
    }

    // FUNCIÓN: _ejecutarEliminarProveedores
    // OBJETIVO: Ejecuta el DELETE de las relaciones en producto_proveedor
    private function _ejecutarEliminarProveedores(): bool
    {
        $consulta = "DELETE FROM producto_proveedor WHERE id_producto = :id_producto";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_producto', $this->productoId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: registrarMezclaTaller
    // OBJETIVO: Crea una pintura preparada (tipo 3), guarda su fórmula en mezcla_producto y descuenta el stock de las bases utilizadas
    public function registrarMezclaTaller(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $presentacion = trim($datos['presentacion'] ?? '1/4 Galón');
        $cantidadEnvases = intval($datos['cantidad'] ?? 1);
        $precioVenta = floatval($datos['precio_venta'] ?? 0);
        $basesRaw = $datos['bases'] ?? '[]';
        $bases = is_array($basesRaw) ? $basesRaw : json_decode((string)$basesRaw, true);

        $error = $this->validarTextoSeguro($nombre, 40, 'El nombre del color o fórmula');
        if ($error !== null) {
            return ['exito' => false, 'mensaje' => $error];
        }
        if ($cantidadEnvases < 1) {
            return ['exito' => false, 'mensaje' => 'La cantidad de envases debe ser al menos 1'];
        }
        if ($precioVenta <= 0) {
            return ['exito' => false, 'mensaje' => 'El precio de venta debe ser mayor a 0'];
        }
        if (!is_array($bases) || count($bases) === 0) {
            return ['exito' => false, 'mensaje' => 'Debe agregar al menos un tinte base a la mezcla'];
        }

        try {
            $this->conexion->beginTransaction();

            // 1. Generar código único para el producto preparado
            $codigo = 'PREP-' . strtoupper(substr(uniqid(), -6));
            while ($this->codigoExiste($codigo)) {
                $codigo = 'PREP-' . strtoupper(substr(uniqid(), -6));
            }

            // 2. Insertar producto en productos (tipo 3 = Preparado)
            $consultaProd = "INSERT INTO productos (codigo, nombre, id_tipo_producto, unidad_medida, stock_actual, stock_minimo, precio_venta, estado)
                             VALUES (:codigo, :nombre, 3, :unidad_medida, :stock_actual, 1.0000, :precio_venta, 1)";
            $stmtProd = $this->conexion->prepare($consultaProd);
            $stmtProd->bindParam(':codigo', $codigo, PDO::PARAM_STR);
            $stmtProd->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmtProd->bindParam(':unidad_medida', $presentacion, PDO::PARAM_STR);
            $stockActualEnvases = (float)$cantidadEnvases;
            $stmtProd->bindParam(':stock_actual', $stockActualEnvases);
            $stmtProd->bindParam(':precio_venta', $precioVenta);
            $stmtProd->execute();

            $idProductoMezclado = (int)$this->conexion->lastInsertId();

            // 3. Preparar statements para mezcla_producto y actualización de stock de bases
            $consultaMezcla = "INSERT INTO mezcla_producto (id_producto_mezclado, id_producto_base, fraccion, cantidad_galon, estado)
                               VALUES (:id_producto_mezclado, :id_producto_base, :fraccion, :cantidad_galon, 1)";
            $stmtMezcla = $this->conexion->prepare($consultaMezcla);

            $consultaDescuento = "UPDATE productos SET stock_actual = stock_actual - :consumo_total WHERE id_producto = :id_base";
            $stmtDescuento = $this->conexion->prepare($consultaDescuento);

            $stmtVerificarStock = $this->conexion->prepare("SELECT stock_actual, nombre FROM productos WHERE id_producto = :id_base LIMIT 1 FOR UPDATE");

            foreach ($bases as $b) {
                $idBase = (int)($b['id_producto_base'] ?? $b['id_producto'] ?? 0);
                $fraccion = (string)($b['fraccion'] ?? '1/4');
                $cantGalon = (float)($b['cantidad_galon'] ?? 0);
                $consumoTotal = $cantGalon * $cantidadEnvases;

                if ($idBase <= 0 || $cantGalon <= 0) {
                    continue;
                }

                // Verificar stock suficiente del tinte base
                $stmtVerificarStock->bindParam(':id_base', $idBase, PDO::PARAM_INT);
                $stmtVerificarStock->execute();
                $baseInfo = $stmtVerificarStock->fetch(PDO::FETCH_ASSOC);

                if (!$baseInfo || (float)$baseInfo['stock_actual'] < $consumoTotal) {
                    $this->conexion->rollBack();
                    $nombreBase = $baseInfo['nombre'] ?? "ID $idBase";
                    return [
                        'exito' => false,
                        'mensaje' => "Stock insuficiente del tinte base '{$nombreBase}' para preparar la mezcla (requerido: {$consumoTotal} gal, disponible: " . ($baseInfo ? $baseInfo['stock_actual'] : 0) . " gal)"
                    ];
                }

                // Guardar receta en mezcla_producto
                $stmtMezcla->bindParam(':id_producto_mezclado', $idProductoMezclado, PDO::PARAM_INT);
                $stmtMezcla->bindParam(':id_producto_base', $idBase, PDO::PARAM_INT);
                $stmtMezcla->bindParam(':fraccion', $fraccion, PDO::PARAM_STR);
                $stmtMezcla->bindParam(':cantidad_galon', $cantGalon);
                $stmtMezcla->execute();

                // Descontar inventario base
                $stmtDescuento->bindParam(':consumo_total', $consumoTotal);
                $stmtDescuento->bindParam(':id_base', $idBase, PDO::PARAM_INT);
                $stmtDescuento->execute();
            }

            $this->conexion->commit();
            return [
                'exito' => true,
                'id_producto' => $idProductoMezclado,
                'codigo' => $codigo,
                'mensaje' => 'Pintura preparada registrada exitosamente e inventario base descontado.'
            ];
        } catch (\Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return [
                'exito' => false,
                'mensaje' => 'Error al preparar la mezcla: ' . $e->getMessage()
            ];
        }
    }

    // FUNCIÓN: registrarAjusteMerma
    // OBJETIVO: Descuenta stock de un producto por motivo de daño, evaporación o merma
    public function registrarAjusteMerma(array $datos): array
    {
        $idProducto = intval($datos['id_producto'] ?? 0);
        $cantidadRaw = trim((string)($datos['cantidad'] ?? '0'));
        $cantidad = intval($cantidadRaw);
        $motivo = trim($datos['motivo'] ?? '');

        if ($idProducto < 1) {
            return ['exito' => false, 'mensaje' => 'Debe seleccionar un producto'];
        }
        if (!ctype_digit($cantidadRaw) || $cantidad < 1) {
            return ['exito' => false, 'mensaje' => 'La cantidad a descontar debe ser un número entero mayor a 0'];
        }
        $error = $this->validarTextoSeguro($motivo, 250, 'El motivo del daño o merma');
        if ($error !== null) {
            return ['exito' => false, 'mensaje' => $error];
        }

        try {
            $this->conexion->beginTransaction();

            $stmtProd = $this->conexion->prepare("SELECT stock_actual, nombre FROM productos WHERE id_producto = :id AND estado = 1 LIMIT 1 FOR UPDATE");
            $stmtProd->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtProd->execute();
            $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                $this->conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Producto no encontrado o inactivo'];
            }

            if ((float)$prod['stock_actual'] < $cantidad) {
                $this->conexion->rollBack();
                return [
                    'exito' => false,
                    'mensaje' => "Stock insuficiente para realizar el ajuste (actual: {$prod['stock_actual']}, a descontar: {$cantidad})"
                ];
            }

            $stmtUpdate = $this->conexion->prepare("UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id");
            $stmtUpdate->bindValue(':cantidad', (float)$cantidad);
            $stmtUpdate->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtUpdate->execute();

            error_log(sprintf('[MERMA] usuario=%s producto=%d cantidad=%d motivo=%s', $_SESSION['id_usuario'] ?? 'n/d', $idProducto, $cantidad, $motivo));
            $this->conexion->commit();
            return [
                'exito' => true,
                'mensaje' => "Ajuste por merma aplicado correctamente a '{$prod['nombre']}' (-{$cantidad})."
            ];
        } catch (\Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => 'Error al registrar ajuste: ' . $e->getMessage()];
        }
    }
}
