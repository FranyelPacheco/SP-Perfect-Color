<?php
// Archivo: inventarioController.php
// Controlador para la gestion de inventario (Procedimental)

namespace App\Controllers;

use App\Models\InventarioModel;
use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    verificarAcceso,
    verificarRolAdmin,
    validarRequerido,
    validarDecimalPositivo,
    validarFecha
};

$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de insumos
if ($metodo === 'index') {
    verificarAcceso([1]);
    
    $contenidoVista = __DIR__ . '/../views/inventarioListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene productos, proveedores, alertas de stock bajo, rubros y tipos de producto en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAcceso([1]);
    
    $insumos = $inventarioModel->listarTodos();
    $proveedores = $inventarioModel->listarProveedoresActivos();
    $alertas = $inventarioModel->obtenerAlertasStockBajo();
    $rubros = $inventarioModel->listarRubrosActivos();
    $tiposProducto = $inventarioModel->listarTiposProducto();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos,
        'proveedores' => $proveedores,
        'alertas' => $alertas,
        'rubros' => $rubros,
        'tipos_producto' => $tiposProducto
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca insumos por término de búsqueda o devuelve todos
} elseif ($metodo === 'buscarAjax') {
    verificarAcceso([1]);
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $insumos = $inventarioModel->listarTodos();
    } else {
        $insumos = $inventarioModel->buscarInsumos($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'insumos' => $insumos
    ]);

// FUNCIÓN: verificarCodigoAjax
// OBJETIVO: Verifica en tiempo real si un codigo de insumo ya está registrado
} elseif ($metodo === 'verificarCodigoAjax') {
    verificarAcceso([1]);
    $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($codigo) && $inventarioModel->codigoExiste($codigo, $id);
    respuestaJson('exito', '', ['existe' => $existe]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo insumo o reactiva uno inactivo, con proveedor asociado
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $unidadMedida = trim($_POST['unidad_medida'] ?? '');
    $stockActualRaw = $_POST['stock_actual'] ?? '0';
    $stockMinimoRaw = $_POST['stock_minimo'] ?? '0';
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $precioCompra = floatval($_POST['precio_compra'] ?? 0);
    $proveedorId = !empty($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : null;
    $rubroId = !empty($_POST['id_rubro']) ? intval($_POST['id_rubro']) : null;
    $idTipoProducto = !empty($_POST['id_tipo_producto']) ? intval($_POST['id_tipo_producto']) : 2;

    if ($idTipoProducto !== 1 && $idTipoProducto !== 2) {
        respuestaJson('error', 'Tipo de producto no valido (solo Base o Simple)');
    }

    if ($idTipoProducto === 1) {
        $unidadMedida = 'Galon';
    } else {
        if (!in_array($unidadMedida, ['Unidad', 'KG'], true)) {
            respuestaJson('error', 'Para productos simples la unidad de medida debe ser Unidad o KG');
        }
    }

    if (!is_numeric($stockActualRaw) || floor((float)$stockActualRaw) != (float)$stockActualRaw || (float)$stockActualRaw < 0) {
        respuestaJson('error', 'El stock actual debe ser un numero entero no negativo');
    }
    if (!is_numeric($stockMinimoRaw) || floor((float)$stockMinimoRaw) != (float)$stockMinimoRaw || (float)$stockMinimoRaw < 0) {
        respuestaJson('error', 'El stock minimo debe ser un numero entero no negativo');
    }
    $stockActual = (float)intval($stockActualRaw);
    $stockMinimo = (float)intval($stockMinimoRaw);
    
    if (!validarRequerido($codigo)) {
        respuestaJson('error', 'El codigo es obligatorio');
    }
    
    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre del insumo es obligatorio');
    }
    
    if (!validarDecimalPositivo($precioVenta)) {
        respuestaJson('error', 'El precio de venta debe ser un numero positivo');
    }
    
    if ($inventarioModel->codigoExiste($codigo)) {
        respuestaJson('error', 'Ya existe un insumo con ese codigo');
    }
    
    $inactivoId = $inventarioModel->buscarInactivoPorCodigo($codigo);
    if ($inactivoId) {
        if ($inventarioModel->actualizarInsumo($inactivoId, $codigo, $nombre, $rubroId, $unidadMedida, $stockActual, $stockMinimo, $precioVenta, $precioCompra, $idTipoProducto)) {
            $inventarioModel->eliminarProveedoresDeInsumo($inactivoId);
            if ($proveedorId) {
                $inventarioModel->asignarProveedorAInsumo($inactivoId, $proveedorId);
            }
            respuestaJson('exito', 'Insumo reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el insumo');
        }
    }
    
    $nuevoId = $inventarioModel->insertarInsumo($codigo, $nombre, $rubroId, $unidadMedida, $stockActual, $stockMinimo, $precioVenta, $precioCompra, $idTipoProducto);
    if ($nuevoId) {
        if ($proveedorId) {
            $inventarioModel->asignarProveedorAInsumo($nuevoId, $proveedorId);
        }
        respuestaJson('exito', 'Insumo creado exitosamente', [
            'id_producto' => $nuevoId,
            'codigo' => $codigo,
            'nombre' => $nombre,
            'precio_venta' => $precioVenta
        ]);
    } else {
        respuestaJson('error', 'Error al crear el insumo');
    }

// FUNCIÓN: obtener
// OBJETIVO: Obtiene un insumo por ID para edición
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de insumo no valido');
    }
    
    $insumo = $inventarioModel->buscarPorId($id);
    
    if ($insumo) {
        respuestaJson('exito', 'Insumo obtenido correctamente', $insumo);
    } else {
        respuestaJson('error', 'Insumo no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza un insumo existente y su relación con proveedor
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $rubroId = !empty($_POST['id_rubro']) ? intval($_POST['id_rubro']) : null;
    $unidadMedida = trim($_POST['unidad_medida'] ?? '');
    $stockActualRaw = $_POST['stock_actual'] ?? '0';
    $stockMinimoRaw = $_POST['stock_minimo'] ?? '0';
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $precioCompra = floatval($_POST['precio_compra'] ?? 0);
    $proveedorId = !empty($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : null;
    $idTipoProducto = !empty($_POST['id_tipo_producto']) ? intval($_POST['id_tipo_producto']) : 2;

    $insumoExistente = $inventarioModel->buscarPorId($id);
    if (!$insumoExistente) {
        respuestaJson('error', 'Insumo no encontrado');
    }

    // El stock actual y el proveedor son de solo lectura y no se modifican desde este formulario
    $stockActual = (float)$insumoExistente['stock_actual'];

    if ($idTipoProducto !== 1 && $idTipoProducto !== 2) {
        respuestaJson('error', 'Tipo de producto no valido (solo Base o Simple)');
    }

    if ($idTipoProducto === 1) {
        $unidadMedida = 'Galon';
    } else {
        if (!in_array($unidadMedida, ['Unidad', 'KG'], true)) {
            respuestaJson('error', 'Para productos simples la unidad de medida debe ser Unidad o KG');
        }
    }

    if (!is_numeric($stockMinimoRaw) || floor((float)$stockMinimoRaw) != (float)$stockMinimoRaw || (float)$stockMinimoRaw < 0) {
        respuestaJson('error', 'El stock minimo debe ser un numero entero no negativo');
    }
    $stockMinimo = (float)intval($stockMinimoRaw);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de insumo no valido');
    }
    
    if (!validarRequerido($codigo)) {
        respuestaJson('error', 'El codigo es obligatorio');
    }
    
    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre del insumo es obligatorio');
    }
    
    if (!validarDecimalPositivo($precioVenta)) {
        respuestaJson('error', 'El precio de venta debe ser un numero positivo');
    }
    
    if ($inventarioModel->codigoExiste($codigo, $id)) {
        respuestaJson('error', 'Ya existe otro insumo con ese codigo');
    }
    
    if ($inventarioModel->actualizarInsumo($id, $codigo, $nombre, $rubroId, $unidadMedida, $stockActual, $stockMinimo, $precioVenta, 0, $idTipoProducto)) {
        respuestaJson('exito', 'Insumo actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el insumo');
    }

// FUNCIÓN: listarRubrosAjax
// OBJETIVO: Obtiene la lista de rubros activos para el select del formulario
} elseif ($metodo === 'listarRubrosAjax') {
    verificarAcceso([1]);
    
    $rubros = $inventarioModel->listarRubrosActivos();
    
    respuestaJson('exito', 'Rubros obtenidos correctamente', [
        'rubros' => $rubros
    ]);

// FUNCIÓN: obtenerRubrosPorProveedorAjax
// OBJETIVO: Obtiene rubros filtrados por un proveedor específico
} elseif ($metodo === 'obtenerRubrosPorProveedorAjax') {
    verificarAcceso([1]);

    $proveedorId = intval($_GET['id_proveedor'] ?? 0);
    if ($proveedorId < 1) {
        respuestaJson('error', 'ID de proveedor no valido');
    }

    $rubros = $inventarioModel->obtenerRubrosPorProveedor($proveedorId);

    respuestaJson('exito', 'Rubros obtenidos correctamente', [
        'rubros' => $rubros
    ]);

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un insumo (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de insumo no valido');
    }
    
    if ($inventarioModel->eliminarInsumo($id)) {
        respuestaJson('exito', 'Insumo eliminado exitosamente');
    } else {
        respuestaJson('error', 'Error al eliminar el insumo');
    }

// FUNCIÓN: listarBasesAjax
// OBJETIVO: Obtiene tintes base para formulación de colores
} elseif ($metodo === 'listarBasesAjax') {
    verificarAcceso([1]);
    $bases = $inventarioModel->listarBases();
    respuestaJson('exito', 'Bases obtenidas', ['bases' => $bases]);

// FUNCIÓN: guardarMezcla
// OBJETIVO: Registra un producto preparado y descuenta las bases consumidas
} elseif ($metodo === 'guardarMezcla') {
    verificarRolAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $presentacion = trim($_POST['presentacion'] ?? '1/4 Galón');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $basesJson = $_POST['bases'] ?? '[]';
    $bases = json_decode($basesJson, true);

    if (empty($nombre)) {
        respuestaJson('error', 'El nombre del color o fórmula es obligatorio');
    }
    if ($cantidad < 1) {
        respuestaJson('error', 'La cantidad de envases debe ser al menos 1');
    }
    if ($precioVenta <= 0) {
        respuestaJson('error', 'El precio de venta debe ser mayor a 0');
    }
    if (!is_array($bases) || count($bases) === 0) {
        respuestaJson('error', 'Debe agregar al menos un tinte base a la mezcla');
    }

    $resultado = $inventarioModel->registrarMezclaTaller($nombre, $presentacion, $cantidad, $precioVenta, $bases);
    if ($resultado['exito']) {
        respuestaJson('exito', $resultado['mensaje'], ['id_producto' => $resultado['id_producto'], 'codigo' => $resultado['codigo']]);
    } else {
        respuestaJson('error', $resultado['mensaje']);
    }

// FUNCIÓN: ajustarMerma
// OBJETIVO: Descuenta inventario por merma o daño justificado
} elseif ($metodo === 'ajustarMerma') {
    verificarRolAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $idProducto = intval($_POST['id_producto'] ?? 0);
    $cantidadRaw = trim($_POST['cantidad'] ?? '0');
    $cantidad = intval($cantidadRaw);
    $motivo = trim($_POST['motivo'] ?? '');

    if ($idProducto < 1) {
        respuestaJson('error', 'Debe seleccionar un producto');
    }
    if (!ctype_digit($cantidadRaw) || $cantidad < 1) {
        respuestaJson('error', 'La cantidad a descontar debe ser un número entero mayor a 0');
    }
    if (empty($motivo)) {
        respuestaJson('error', 'Debe especificar el motivo del daño o merma');
    }

    $resultado = $inventarioModel->registrarAjusteMerma($idProducto, $cantidad, $motivo);
    if ($resultado['exito']) {
        respuestaJson('exito', $resultado['mensaje']);
    } else {
        respuestaJson('error', $resultado['mensaje']);
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
