<?php
declare(strict_types=1);

namespace App\Controllers;

use PDOException;
use App\Models\{
    NotaEntregaCompraModel,
    ProveedorModel,
    InventarioModel,
    TipoPagoModel,
    BancoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarPermiso,
    verificarRolAdmin
};

$notaEntregaCompraModel = new NotaEntregaCompraModel();
$proveedorModel = new ProveedorModel();
$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de notas de entrega de compras a proveedores
if ($metodo === 'index') {
    verificarPermiso('notaEntregaCompra');

    $contenidoVista = __DIR__ . '/../views/notaEntregaCompraListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve el listado de notas de compra en JSON para DataTables
} elseif ($metodo === 'listarAjax') {
    verificarPermiso('notaEntregaCompra');

    $notas = $notaEntregaCompraModel->listarTodas();
    respuestaJson('exito', 'Notas de entrega de compras obtenidas correctamente', [
        'notas' => $notas
    ]);

// FUNCIÓN: crear
// OBJETIVO: Renderiza el formulario para registrar una nueva recepción de compra de insumos
} elseif ($metodo === 'crear') {
    verificarRolAdmin();

    $proveedores = $proveedorModel->listarTodos();
    $todosInsumos = $inventarioModel->listarTodos();
    $insumos = array_values(array_filter($todosInsumos, function($ins) {
        $tipo = (int)($ins['id_tipo_producto'] ?? 2);
        if ($tipo === 3) return false; // Excluir preparados
        $frac = trim((string)($ins['fraccion'] ?? ''));
        $padre = $ins['id_producto_padre'] ?? null;
        // Solo productos padre de 1 galón o sin fracción
        return (empty($padre) && ($frac === '' || $frac === '1'));
    }));
    $tiposPago = (new TipoPagoModel())->listarTodos();
    $bancos = (new BancoModel())->listarTodos();

    $pageTitle = 'SP Perfect Color - Registrar Nota de Entrega (Compra)';
    $pageDescription = 'Recepción de compra de insumos y productos de proveedores';
    $contenidoVista = __DIR__ . '/../views/notaEntregaCompraFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: guardar
// OBJETIVO: Procesa la creación de la nota de entrega de compra, incrementa stock y crea CxP
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $resultado = $notaEntregaCompraModel->registrarCompra($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado['datos'] ?? null);

// FUNCIÓN: ver
// OBJETIVO: Muestra el detalle completo de la nota de compra y sus productos recibidos
} elseif ($metodo === 'ver') {
    verificarPermiso('notaEntregaCompra');

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/notaEntregaCompra');
        exit;
    }

    $nota = $notaEntregaCompraModel->buscarPorId($id);
    if (!$nota) {
        header('Location: /SP%20Perfect%20Color/notaEntregaCompra');
        exit;
    }

    $detalle = $notaEntregaCompraModel->obtenerDetalle($id);

    $pageTitle = 'SP Perfect Color - Nota de Entrega (Compra) #' . $id;
    $pageDescription = 'Detalle de la nota de entrega de compra de insumos #' . $id;
    $contenidoVista = __DIR__ . '/../views/notaEntregaCompraVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: anular
// OBJETIVO: Anula la nota de entrega de compra y revierte el stock sumado
} elseif ($metodo === 'anular') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $resultado = $notaEntregaCompraModel->anularNota($id);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
