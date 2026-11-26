<?php

namespace App\Controllers;

use App\Models\{
    NotaEntregaModel,
    PresupuestoModel,
    ClienteModel,
    InventarioModel,
    TipoPagoModel,
    BancoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarPermiso,
    verificarRolVendedor,
    soloPost
};

$notaEntregaModel = new NotaEntregaModel();
$presupuestoModel = new PresupuestoModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de notas de entrega
if ($metodo === 'index') {
    verificarPermiso('notaEntrega');
    
    $contenidoVista = __DIR__ . '/../views/notaEntregaListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de notas de entrega en JSON
} elseif ($metodo === 'listarAjax') {
    verificarPermiso('notaEntrega');
    
    $notas = $notaEntregaModel->listarTodos();
    
    respuestaJson('exito', 'Notas de entrega obtenidas correctamente', [
        'notas' => $notas
    ]);

// FUNCIÓN: crearDesdePresupuesto
// OBJETIVO: Renderiza el formulario para crear nota de entrega desde un presupuesto aprobado
} elseif ($metodo === 'crearDesdePresupuesto') {
    verificarRolVendedor();
    
    $presupuestoId = intval($_GET['id'] ?? 0);
    
    if ($presupuestoId < 1) {
        header('Location: ' . url('presupuesto'));
        exit;
    }
    
    $presupuesto = $presupuestoModel->buscarPorId($presupuestoId);
    
    $estadoActual = $presupuesto['estado'] ?? '';
    $idEstadoVenta = intval($presupuesto['id_estado_venta'] ?? 0);
    if (!$presupuesto || ($estadoActual !== 'aprobado' && $estadoActual !== 'presupuesto' && $estadoActual !== 'pendiente' && $idEstadoVenta !== 1 && $idEstadoVenta !== 2)) {
        header('Location: ' . url('presupuesto'));
        exit;
    }
    
    $detalle = $presupuestoModel->obtenerDetalle($presupuestoId);
    
    $pageTitle = 'SP Perfect Color - Procesar Entrega y Cobro #' . $presupuestoId;
    $pageDescription = 'Aprobar/Rechazar y cobrar nota de entrega a partir del presupuesto #' . $presupuestoId . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: guardar
// OBJETIVO: Aprueba y cobra o rechaza la nota de entrega desde un presupuesto
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    soloPost();
    
    $resultado = $notaEntregaModel->procesarNotaEntrega($_POST, (int)$_SESSION['id_usuario']);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado['datos'] ?? null);

// FUNCIÓN: ver
// OBJETIVO: Renderiza la vista de detalle de una nota de entrega con sus items
} elseif ($metodo === 'ver') {
    verificarPermiso('notaEntrega');
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: ' . url('notaEntrega'));
        exit;
    }
    
    $nota = $notaEntregaModel->buscarPorId($id);
    
    if (!$nota) {
        header('Location: ' . url('notaEntrega'));
        exit;
    }
    
    $detalle = $notaEntregaModel->obtenerDetalle($id);
    
    $pageTitle = 'SP Perfect Color - Nota de Entrega #' . $id;
    $pageDescription = 'Detalle de la nota de entrega #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: editar
// OBJETIVO: Renderiza el formulario para modificar condición, método de pago y observaciones de una nota aprobada (RF 4.1.1)
} elseif ($metodo === 'editar') {
    verificarRolVendedor();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) {
        header('Location: ' . url('notaEntrega'));
        exit;
    }

    $nota = $notaEntregaModel->buscarPorId($id);
    if (!$nota || (int)($nota['id_estado_venta'] ?? 0) !== 2) {
        header('Location: ' . url('notaEntrega'));
        exit;
    }

    $detalle = $notaEntregaModel->obtenerDetalle($id);
    $tiposPago = (new TipoPagoModel())->listarActivos();
    $bancos = (new BancoModel())->listarActivos();

    $pageTitle = 'SP Perfect Color - Editar Nota de Entrega #' . $id;
    $pageDescription = 'Modificar datos de la nota de entrega #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaEditView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: actualizar
// OBJETIVO: Guarda las modificaciones de una nota de entrega aprobada (RF 4.1.1)
} elseif ($metodo === 'actualizar') {
    verificarRolVendedor();

    soloPost();

    $id = intval($_POST['id_venta'] ?? 0);
    $resultado = $notaEntregaModel->modificarNotaEntrega($id, $_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado['datos'] ?? null);

// FUNCIÓN: obtenerClientesAjax
// OBJETIVO: Obtiene los clientes disponibles para el formulario de nota de entrega
} elseif ($metodo === 'obtenerClientesAjax') {
    verificarPermiso('notaEntrega');
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: obtenerTiposPagoAjax
// OBJETIVO: Obtiene los tipos de pago activos para el formulario
} elseif ($metodo === 'obtenerTiposPagoAjax') {
    verificarPermiso('notaEntrega');
    
    $tiposPago = (new TipoPagoModel())->listarActivos();
    
    respuestaJson('exito', 'Tipos de pago obtenidos', [
        'tipos_pago' => $tiposPago
    ]);

// FUNCIÓN: obtenerBancosAjax
// OBJETIVO: Obtiene los bancos activos para el formulario
} elseif ($metodo === 'obtenerBancosAjax') {
    verificarPermiso('notaEntrega');
    
    $bancos = (new BancoModel())->listarActivos();
    
    respuestaJson('exito', 'Bancos obtenidos', [
        'bancos' => $bancos
    ]);

// FUNCIÓN: pdf
// OBJETIVO: Genera y visualiza o descarga el comprobante formal en PDF de la nota de entrega
} elseif ($metodo === 'pdf') {
    verificarPermiso('notaEntrega');

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) {
        header('Location: ' . url('notaEntrega'));
        exit;
    }

    $nota = $notaEntregaModel->buscarPorId($id);
    if (!$nota) {
        header('Location: ' . url('notaEntrega'));
        exit;
    }

    $detalle = $notaEntregaModel->obtenerDetalle($id);

    // Obtener tasa de cambio del día o última tasa registrada
    $tasaModel = new \App\Models\TasaCambioModel();
    $tasaObj = $tasaModel->obtenerTasaHoy();
    if (!$tasaObj) {
        $tasaObj = $tasaModel->obtenerUltimaTasa();
    }
    $tasaBs = $tasaObj ? (float)$tasaObj['tasa'] : null;

    require_once __DIR__ . '/../helpers/notaEntregaPdfHelper.php';
    $descargar = isset($_GET['descargar']) && $_GET['descargar'] == '1';
    \App\Helpers\generarPdfNotaEntrega($nota, $detalle, $tasaBs, $descargar);
    exit;

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
