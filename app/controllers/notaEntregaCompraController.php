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
    verificarAutenticacion,
    verificarRolAdmin
};

$notaEntregaCompraModel = new NotaEntregaCompraModel();
$proveedorModel = new ProveedorModel();
$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de notas de entrega de compras a proveedores
if ($metodo === 'index') {
    verificarAutenticacion();

    $contenidoVista = __DIR__ . '/../views/notaEntregaCompraListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve el listado de notas de compra en JSON para DataTables
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();

    $notas = $notaEntregaCompraModel->listarTodas();
    respuestaJson('exito', 'Notas de entrega de compras obtenidas correctamente', [
        'notas' => $notas
    ]);

// FUNCIÓN: crear
// OBJETIVO: Renderiza el formulario para registrar una nueva recepción de compra de insumos
} elseif ($metodo === 'crear') {
    verificarRolAdmin();

    $proveedores = $proveedorModel->listarTodos();
    $insumos = $inventarioModel->listarTodos();
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

    $idProveedor = intval($_POST['id_proveedor'] ?? 0);
    $fecha = trim($_POST['fecha'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $condicionPago = trim($_POST['condicion_pago'] ?? 'contado');
    $fechaVencimiento = !empty($_POST['fecha_vencimiento']) ? trim($_POST['fecha_vencimiento']) : null;
    $idTipoPago = !empty($_POST['id_tipo_pago']) ? intval($_POST['id_tipo_pago']) : null;
    $idBanco = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia = !empty($_POST['referencia']) ? trim($_POST['referencia']) : null;

    if ($idProveedor < 1) {
        respuestaJson('error', 'Debe seleccionar un proveedor');
    }

    if (empty($fecha)) {
        $fecha = date('Y-m-d H:i:s');
    }

    $itemsRaw = $_POST['items'] ?? '[]';
    $items = is_array($itemsRaw) ? $itemsRaw : json_decode($itemsRaw, true);

    if (empty($items) || !is_array($items)) {
        respuestaJson('error', 'Debe agregar al menos un producto recibido a la nota de entrega');
    }

    try {
        $idNota = $notaEntregaCompraModel->crearNotaCompra(
            $idProveedor,
            $fecha,
            $observaciones,
            $condicionPago,
            $fechaVencimiento,
            $items,
            $idTipoPago,
            $idBanco,
            $referencia
        );

        respuestaJson('exito', 'Nota de entrega de compra #' . $idNota . ' registrada con éxito. Stock de inventario actualizado.', [
            'id_nota_entrega' => $idNota
        ]);
    } catch (PDOException $e) {
        error_log('Error al guardar nota de compra: ' . $e->getMessage());
        respuestaJson('error', 'Error al procesar la compra: ' . $e->getMessage());
    }

// FUNCIÓN: ver
// OBJETIVO: Muestra el detalle completo de la nota de compra y sus productos recibidos
} elseif ($metodo === 'ver') {
    verificarAutenticacion();

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
    if ($id < 1) {
        respuestaJson('error', 'ID no válido');
    }

    try {
        if ($notaEntregaCompraModel->anular($id)) {
            respuestaJson('exito', 'Nota de entrega anulada y stock revertido correctamente');
        } else {
            respuestaJson('error', 'No se pudo anular la nota de entrega');
        }
    } catch (PDOException $e) {
        error_log('Error al anular nota de compra: ' . $e->getMessage());
        respuestaJson('error', 'Error al anular nota de compra');
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
