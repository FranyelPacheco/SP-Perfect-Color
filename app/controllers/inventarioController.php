<?php
// Archivo: inventarioController.php
// Controlador para la gestion de inventario (Procedimental)

namespace App\Controllers;

use App\Models\InventarioModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarAcceso;
use function App\Helpers\verificarRolAdmin;
use function App\Helpers\validarRequerido;
use function App\Helpers\validarDecimalPositivo;
use function App\Helpers\validarFecha;

$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de insumos
if ($metodo === 'index') {
    verificarAcceso([1]);
    
    $contenidoVista = __DIR__ . '/../views/inventarioListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene insumos, proveedores, alertas de stock bajo y rubros en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAcceso([1]);
    
    $insumos = $inventarioModel->listarTodos();
    $proveedores = $inventarioModel->listarProveedoresActivos();
    $alertas = $inventarioModel->obtenerAlertasStockBajo();
    $rubros = $inventarioModel->listarRubrosActivos();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos,
        'proveedores' => $proveedores,
        'alertas' => $alertas,
        'rubros' => $rubros
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

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo insumo o reactiva uno inactivo, con proveedor asociado
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $unidadMedida = trim($_POST['unidad_medida'] ?? '');
    $stockActual = floatval($_POST['stock_actual'] ?? 0);
    $stockMinimo = floatval($_POST['stock_minimo'] ?? 5);
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $precioCompra = floatval($_POST['precio_compra'] ?? 0);
    $proveedorId = !empty($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : null;
    $rubroId = !empty($_POST['id_rubro']) ? intval($_POST['id_rubro']) : null;
    
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
        if ($inventarioModel->actualizarInsumo($inactivoId, $codigo, $nombre, $marca, $rubroId, $unidadMedida, $stockActual, $stockMinimo, $precioVenta, $precioCompra)) {
            $inventarioModel->eliminarProveedoresDeInsumo($inactivoId);
            if ($proveedorId) {
                $inventarioModel->asignarProveedorAInsumo($inactivoId, $proveedorId);
            }
            respuestaJson('exito', 'Insumo reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el insumo');
        }
    }
    
    $nuevoId = $inventarioModel->insertarInsumo($codigo, $nombre, $marca, $rubroId, $unidadMedida, $stockActual, $stockMinimo, $precioVenta, $precioCompra);
    if ($nuevoId) {
        if ($proveedorId) {
            $inventarioModel->asignarProveedorAInsumo($nuevoId, $proveedorId);
        }
        respuestaJson('exito', 'Insumo creado exitosamente');
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
    $marca = trim($_POST['marca'] ?? '');
    $rubroId = !empty($_POST['id_rubro']) ? intval($_POST['id_rubro']) : null;
    $unidadMedida = trim($_POST['unidad_medida'] ?? '');
    $stockActual = floatval($_POST['stock_actual'] ?? 0);
    $stockMinimo = floatval($_POST['stock_minimo'] ?? 5);
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $precioCompra = floatval($_POST['precio_compra'] ?? 0);
    $proveedorId = !empty($_POST['id_proveedor']) ? intval($_POST['id_proveedor']) : null;
    
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
    
    if ($inventarioModel->actualizarInsumo($id, $codigo, $nombre, $marca, $rubroId, $unidadMedida, $stockActual, $stockMinimo, $precioVenta, $precioCompra)) {
        $inventarioModel->eliminarProveedoresDeInsumo($id);
        if ($proveedorId) {
            $inventarioModel->asignarProveedorAInsumo($id, $proveedorId);
        }
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

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
