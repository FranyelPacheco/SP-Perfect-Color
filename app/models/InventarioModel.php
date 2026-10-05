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

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: contarTodos
    // OBJETIVO: Retorna el total de productos activos en el sistema
    public function contarTodos(): int
    {
        return $this->_ejecutarCountAll();
    }

    // FUNCIÓN: _ejecutarCountAll
    // OBJETIVO: Ejecuta el COUNT de todos los productos con activo = 1
    private function _ejecutarCountAll(): int
    {
        $consulta = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";
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
        $consulta = "SELECT p.*, tp.nombre as tipo_producto_nombre,
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
                     WHERE p.activo = 1
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
        $consulta = "SELECT p.*, tp.nombre as tipo_producto_nombre,
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
                     WHERE p.id_producto = :id AND p.activo = 1
                     GROUP BY p.id_producto LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarPorCodigo
    // OBJETIVO: Busca un producto activo por su código único
    public function buscarPorCodigo(string $codigo): array|false
    {
        $this->codigo = $codigo;
        return $this->_ejecutarSelectByCodigo();
    }

    // FUNCIÓN: _ejecutarSelectByCodigo
    // OBJETIVO: Ejecuta la búsqueda de producto por código
    private function _ejecutarSelectByCodigo(): array|false
    {
        $consulta = "SELECT p.*, p.id_producto as id_insumo FROM productos p WHERE p.codigo = :codigo AND p.activo = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
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
                     stock_actual, stock_minimo, precio_venta, precio_compra)
                     VALUES (:codigo, :nombre, :id_tipo_producto, :unidad_medida,
                     :stock_actual, :stock_minimo, :precio_venta, :precio_compra)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':id_tipo_producto', $this->id_tipo_producto, PDO::PARAM_INT);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida, PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $this->stock_actual);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);
        $stmt->bindParam(':precio_venta', $this->precio_venta);
        $stmt->bindParam(':precio_compra', $this->precio_compra);
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
    // OBJETIVO: Ejecuta el UPDATE del producto seteando activo = 1 (reactivación implícita)
    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE productos 
                     SET codigo = :codigo, nombre = :nombre,
                         id_tipo_producto = :id_tipo_producto,
                         unidad_medida = :unidad_medida, stock_actual = :stock_actual,
                         stock_minimo = :stock_minimo, precio_venta = :precio_venta,
                         precio_compra = :precio_compra, activo = 1
                     WHERE id_producto = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':codigo', $this->codigo, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':id_tipo_producto', $this->id_tipo_producto, PDO::PARAM_INT);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida, PDO::PARAM_STR);
        $stmt->bindParam(':stock_actual', $this->stock_actual);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);
        $stmt->bindParam(':precio_venta', $this->precio_venta);
        $stmt->bindParam(':precio_compra', $this->precio_compra);
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
    // OBJETIVO: Ejecuta el UPDATE que desactiva el producto (activo = 0)
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE productos SET activo = 0 WHERE id_producto = :id";
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
        $consulta = "SELECT COUNT(*) as total FROM productos WHERE codigo = :codigo AND activo = 1";
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
        $consulta = "SELECT id_producto FROM productos WHERE codigo = :codigo AND activo = 0 LIMIT 1";
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
        $consulta = "SELECT p.*, tp.nombre as tipo_producto_nombre,
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
                     WHERE p.activo = 1 AND (p.nombre LIKE :termino1
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
        $consulta = "SELECT id_proveedor, nombre_empresa, rif FROM proveedores WHERE activo = 1 ORDER BY nombre_empresa ASC";
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
        $consulta = "SELECT id_tipo_producto, nombre, descripcion FROM tipo_producto WHERE activo = 1 ORDER BY id_tipo_producto ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarBases
    // OBJETIVO: Retorna todos los insumos base para formulación de mezclas
    public function listarBases(): array
    {
        $consulta = "SELECT id_producto, codigo, nombre, unidad_medida, stock_actual, precio_venta, precio_compra 
                     FROM productos 
                     WHERE activo = 1 AND id_tipo_producto = 1 
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
        $consulta = "SELECT p.*, p.id_producto as id_insumo,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubro_nombre,
                            GROUP_CONCAT(DISTINCT pr.nombre_empresa SEPARATOR ', ') as proveedores_nombre
                     FROM productos p
                     LEFT JOIN producto_proveedor pp ON pp.id_producto = p.id_producto
                     LEFT JOIN proveedores pr ON pr.id_proveedor = pp.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = pr.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.activo = 1 AND p.stock_actual <= p.stock_minimo
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
}
