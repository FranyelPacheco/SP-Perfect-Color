<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\EnvaseTrait;
use PDO;

class InventarioModel extends ModeloBase
{
    use EnvaseTrait;

    // Presentaciones de galón fijas: id => [etiqueta, equivalencia en 128avos]
    private const PRESENTACIONES = [
        1 => ['1 Galón', 128],
        2 => ['1/2 Galón', 64],
        3 => ['1/4 Galón', 32],
        4 => ['1/8 Galón', 16],
        5 => ['1/16 Galón', 8],
        6 => ['1/32 Galón', 4],
        7 => ['1/64 Galón', 2],
        8 => ['1/128 Galón', 1],
    ];

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
        $filas = $stmt->fetchAll();

        $volumenes = [];
        foreach ($filas as $fila) {
            if ((int)$fila['id_tipo_producto'] === 1) {
                $raiz = (int)($fila['id_producto_padre'] ?: $fila['id_producto']);
                $volumenes[$raiz] = ($volumenes[$raiz] ?? 0) + (int)floor((float)$fila['stock_actual']) * $this->factor128((string)$fila['fraccion']);
            }
        }
        foreach ($filas as &$fila) {
            if ((int)$fila['id_tipo_producto'] === 1) {
                $fila['volumen_familia_128'] = $volumenes[(int)($fila['id_producto_padre'] ?: $fila['id_producto'])] ?? 0;
            }
        }
        unset($fila);
        return $filas;
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

        $error = $this->validarTextoSeguro($codigo, 50, 'El codigo') ?? $this->validarTextoSeguro($nombre, 150, 'El nombre del insumo');
        if ($error !== null) {
            return ['valido' => false, 'mensaje' => $error];
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

        // Solo se editan nombre, precio y stock mínimo; código, tipo, unidad y stock no se tocan
        $nombre = trim($datos['nombre'] ?? '');
        $precioVenta = floatval($datos['precio_venta'] ?? 0);
        $stockMinimoRaw = $datos['stock_minimo'] ?? '0';

        $error = $this->validarTextoSeguro($nombre, 150, 'El nombre del producto');
        if ($error !== null) {
            return ['exito' => false, 'mensaje' => $error];
        }
        if ($precioVenta <= 0) {
            return ['exito' => false, 'mensaje' => 'El precio de venta debe ser un numero positivo'];
        }
        if (!is_numeric($stockMinimoRaw) || floor((float)$stockMinimoRaw) != (float)$stockMinimoRaw || (float)$stockMinimoRaw < 0) {
            return ['exito' => false, 'mensaje' => 'El stock minimo debe ser un numero entero no negativo'];
        }

        // Solo las presentaciones hijas pueden silenciar su alerta; el producto padre siempre alerta
        $alertaActiva = empty($insumoExistente['id_producto_padre']) ? 1 : (empty($datos['alerta_activa']) ? 0 : 1);

        $stmt = $this->conexion->prepare("UPDATE productos SET nombre = :nombre, precio_venta = :precio, stock_minimo = :minimo, alerta_activa = :alerta WHERE id_producto = :id");
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':precio', number_format($precioVenta, 2, '.', ''));
        $stmt->bindValue(':minimo', (int)$stockMinimoRaw, PDO::PARAM_INT);
        $stmt->bindValue(':alerta', $alertaActiva, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['exito' => true, 'mensaje' => 'Producto actualizado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al actualizar el producto'];
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
        return $this->buscarInactivoPor('productos', 'id_producto', 'codigo', $codigo);
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
        $consulta = "SELECT p.id_producto, p.codigo, p.nombre, p.unidad_medida, p.stock_actual, p.precio_venta, p.fraccion, p.id_producto_padre,
                            (SELECT COALESCE(SUM(FLOOR(h.stock_actual) * CASE h.fraccion WHEN '1' THEN 128 WHEN '1/2' THEN 64 WHEN '1/4' THEN 32 WHEN '1/8' THEN 16
                                    WHEN '1/16' THEN 8 WHEN '1/32' THEN 4 WHEN '1/64' THEN 2 WHEN '1/128' THEN 1 ELSE 0 END), 0)
                             FROM productos h WHERE h.estado = 1 AND (h.id_producto = p.id_producto OR h.id_producto_padre = p.id_producto)) as volumen_familia_128
                     FROM productos p
                     WHERE p.estado = 1 AND p.id_tipo_producto = 1 AND p.id_producto_padre IS NULL
                     ORDER BY p.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: cambiarAlertaStock
    // OBJETIVO: Silencia o reactiva la alerta de stock mínimo de una presentación hija (el producto padre no se puede silenciar)
    public function cambiarAlertaStock(int $id, bool $activa): array
    {
        $producto = $this->buscarPorId($id);
        if (!$producto) {
            return ['exito' => false, 'mensaje' => 'Producto no encontrado'];
        }
        if (empty($producto['id_producto_padre'])) {
            return ['exito' => false, 'mensaje' => 'La alerta del producto principal no se puede silenciar'];
        }
        $stmt = $this->conexion->prepare("UPDATE productos SET alerta_activa = :activa WHERE id_producto = :id");
        $stmt->bindValue(':activa', $activa ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return ['exito' => true, 'mensaje' => $activa ? 'Alerta de stock mínimo activada' : 'Alerta de stock mínimo silenciada'];
    }

    // FUNCIÓN: listarPresentaciones
    // OBJETIVO: Lista las presentaciones de galón (1, 1/2 ... 1/128) con su equivalencia en 128avos
    public function listarPresentaciones(): array
    {
        $lista = [];
        foreach (self::PRESENTACIONES as $id => [$etiqueta, $factor]) {
            $lista[] = ['id_presentacion' => $id, 'etiqueta' => $etiqueta, 'factor_128' => $factor];
        }
        return $lista;
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
                     WHERE p.estado = 1 AND p.stock_actual <= p.stock_minimo AND p.alerta_activa = 1
                       AND NOT (p.id_tipo_producto = 3 AND p.stock_minimo <= 0)
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
        if (count($bases) > 6) {
            return ['exito' => false, 'mensaje' => 'La mezcla admite como máximo 6 tintes base'];
        }

        $factorPresentacion = $this->factor128(trim((string)preg_replace('/\s*Gal[oó]n.*$/iu', '', $presentacion)));
        if ($factorPresentacion === 0) {
            return ['exito' => false, 'mensaje' => 'La presentación de la mezcla no es válida'];
        }

        $componentes = [];
        $volumenFormula = 0;
        foreach ($bases as $b) {
            $idBase = (int)($b['id_producto_base'] ?? $b['id_producto'] ?? 0);
            $cantGalon = (float)($b['cantidad_galon'] ?? 0);
            $volumen128 = (int)round($cantGalon * 128);
            if ($idBase <= 0 || $volumen128 < 1) {
                return ['exito' => false, 'mensaje' => 'Una de las bases de la fórmula no es válida'];
            }
            $componentes[] = ['id' => $idBase, 'fraccion' => (string)($b['fraccion'] ?? ''), 'galon' => $cantGalon, 'v128' => $volumen128];
            $volumenFormula += $volumen128;
        }
        if ($volumenFormula !== $factorPresentacion) {
            return ['exito' => false, 'mensaje' => "La suma de las bases ({$volumenFormula}/128 de galón) debe ser igual a la presentación ({$factorPresentacion}/128)"];
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
                             VALUES (:codigo, :nombre, 3, :unidad_medida, :stock_actual, 0, :precio_venta, 1)";
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

            $stmtBase = $this->conexion->prepare("SELECT id_tipo_producto, nombre FROM productos WHERE id_producto = :id_base AND estado = 1 LIMIT 1");

            foreach ($componentes as $c) {
                $stmtBase->bindValue(':id_base', $c['id'], PDO::PARAM_INT);
                $stmtBase->execute();
                $baseInfo = $stmtBase->fetch(PDO::FETCH_ASSOC);
                if (!$baseInfo || (int)$baseInfo['id_tipo_producto'] !== 1) {
                    throw new \RuntimeException('Solo se pueden mezclar tintes base activos');
                }

                // Guardar receta en mezcla_producto
                $stmtMezcla->bindValue(':id_producto_mezclado', $idProductoMezclado, PDO::PARAM_INT);
                $stmtMezcla->bindValue(':id_producto_base', $c['id'], PDO::PARAM_INT);
                $stmtMezcla->bindValue(':fraccion', $c['fraccion'] !== '' ? $c['fraccion'] : $this->etiquetaFraccion($c['v128']));
                $stmtMezcla->bindValue(':cantidad_galon', number_format($c['galon'], 7, '.', ''));
                $stmtMezcla->execute();

                // Descontar envases de la base, abriendo uno mayor si hace falta
                foreach ($this->descomponerVolumen128($c['v128']) as $parte) {
                    $this->consumirEnvase($c['id'], $cantidadEnvases, $parte);
                }
            }

            $this->conexion->commit();
            return [
                'exito' => true,
                'id_producto' => $idProductoMezclado,
                'codigo' => $codigo,
                'mensaje' => 'Pintura preparada registrada exitosamente e inventario base descontado.'
            ];
        } catch (\RuntimeException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        } catch (\Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('Error al preparar mezcla: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al preparar la mezcla'];
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
        $idPresentacion = intval($datos['id_presentacion'] ?? 0);

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

            $stmtProd = $this->conexion->prepare("SELECT stock_actual, nombre, id_tipo_producto, id_producto_padre FROM productos WHERE id_producto = :id AND estado = 1 LIMIT 1 FOR UPDATE");
            $stmtProd->bindParam(':id', $idProducto, PDO::PARAM_INT);
            $stmtProd->execute();
            $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                $this->conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Producto no encontrado o inactivo'];
            }

            $enGalones = (int)$prod['id_tipo_producto'] === 1;
            $equivalente128 = null;
            $descuento = (float)$cantidad;
            $detalle = (string)$cantidad;

            if ($enGalones) {
                $pres = isset(self::PRESENTACIONES[$idPresentacion])
                    ? ['etiqueta' => self::PRESENTACIONES[$idPresentacion][0], 'factor_128' => self::PRESENTACIONES[$idPresentacion][1]]
                    : null;
                if (!$pres) {
                    $this->conexion->rollBack();
                    return ['exito' => false, 'mensaje' => 'Debe indicar la presentación que se perdió (1 galón, 1/2, 1/4...)'];
                }
                $equivalente128 = $cantidad * (int)$pres['factor_128'];
                $descuento = $equivalente128 / 128;
                $detalle = "{$cantidad} x {$pres['etiqueta']}";
                $this->consumirEnvase($idProducto, $cantidad, (int)$pres['factor_128']);
            } else {
                $idPresentacion = 0;
                $stmtUpdate = $this->conexion->prepare("UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id AND stock_actual >= :cantidad2");
                $stmtUpdate->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':cantidad2', $cantidad, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':id', $idProducto, PDO::PARAM_INT);
                $stmtUpdate->execute();
                if ($stmtUpdate->rowCount() === 0) {
                    $this->conexion->rollBack();
                    return [
                        'exito' => false,
                        'mensaje' => "Stock insuficiente para realizar el ajuste (actual: " . (int)$prod['stock_actual'] . ", a descontar: {$cantidad})"
                    ];
                }
            }

            $descuentoTexto = number_format($descuento, 7, '.', '');

            $stmtLog = $this->conexion->prepare("INSERT INTO merma_inventario (id_producto, id_presentacion, cantidad, equivalente_128, cantidad_descontada, motivo, id_usuario)
                                                 VALUES (:producto, :presentacion, :cantidad, :equivalente, :descontada, :motivo, :usuario)");
            $stmtLog->bindValue(':producto', $idProducto, PDO::PARAM_INT);
            $stmtLog->bindValue(':presentacion', $idPresentacion ?: null, $idPresentacion ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtLog->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmtLog->bindValue(':equivalente', $equivalente128, $equivalente128 === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtLog->bindValue(':descontada', $descuentoTexto);
            $stmtLog->bindValue(':motivo', $motivo);
            $stmtLog->bindValue(':usuario', $_SESSION['id_usuario'] ?? null, isset($_SESSION['id_usuario']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtLog->execute();

            $this->conexion->commit();
            return [
                'exito' => true,
                'mensaje' => "Merma registrada en '{$prod['nombre']}' (-{$detalle})."
            ];
        } catch (\RuntimeException $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => $e->getMessage()];
        } catch (\Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('Error en merma: ' . $e->getMessage());
            return ['exito' => false, 'mensaje' => 'Error al registrar el ajuste'];
        }
    }
}
