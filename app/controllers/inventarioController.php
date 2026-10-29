<?php
// Archivo: inventarioController.php
// Controlador para la gestion de inventario (Procedimental)

namespace App\Controllers;

use App\Models\InventarioModel;
use function App\Helpers\{
    respuestaJson,
    verificarAcceso,
    verificarSoloAdmin,
    soloPost,
    responder
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
        'tipos_producto' => $tiposProducto,
        'presentaciones' => $inventarioModel->listarPresentaciones()
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
    verificarSoloAdmin();
    
    soloPost();
    
    $resultado = $inventarioModel->registrar($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado['datos'] ?? null);

// FUNCIÓN: obtener
// OBJETIVO: Obtiene un insumo por ID para edición
} elseif ($metodo === 'obtener') {
    verificarSoloAdmin();
    
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
    verificarSoloAdmin();
    
    soloPost();
    
    $id = intval($_POST['id'] ?? 0);
    $resultado = $inventarioModel->modificar($id, $_POST);
    responder($resultado);

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
    verificarSoloAdmin();
    
    soloPost();
    
    $id = intval($_POST['id'] ?? 0);
    $resultado = $inventarioModel->darDeBaja($id);
    responder($resultado);

// FUNCIÓN: listarBasesAjax
// OBJETIVO: Obtiene tintes base para formulación de colores
} elseif ($metodo === 'listarBasesAjax') {
    verificarAcceso([1]);
    $bases = $inventarioModel->listarBases();
    respuestaJson('exito', 'Bases obtenidas', ['bases' => $bases]);

// FUNCIÓN: guardarMezcla
// OBJETIVO: Registra un producto preparado y descuenta las bases consumidas
} elseif ($metodo === 'guardarMezcla') {
    verificarSoloAdmin();
    soloPost();
    
    $resultado = $inventarioModel->registrarMezclaTaller($_POST);
    if ($resultado['exito']) {
        respuestaJson('exito', $resultado['mensaje'], ['id_producto' => $resultado['id_producto'], 'codigo' => $resultado['codigo']]);
    } else {
        respuestaJson('error', $resultado['mensaje']);
    }

// FUNCIÓN: silenciarAlerta
// OBJETIVO: Silencia o reactiva la alerta de stock mínimo de una presentación hija
} elseif ($metodo === 'silenciarAlerta') {
    verificarSoloAdmin();
    soloPost();

    $resultado = $inventarioModel->cambiarAlertaStock(intval($_POST['id'] ?? 0), !empty($_POST['activa']));
    responder($resultado);

// FUNCIÓN: ajustarMerma
// OBJETIVO: Descuenta inventario por merma o daño justificado
} elseif ($metodo === 'ajustarMerma') {
    verificarSoloAdmin();
    soloPost();

    $resultado = $inventarioModel->registrarAjusteMerma($_POST);
    responder($resultado);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
