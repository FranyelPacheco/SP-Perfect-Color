<?php

namespace App\Controllers;

use PDOException;
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
    verificarAutenticacion,
    verificarRolVendedor
};

$notaEntregaModel = new NotaEntregaModel();
$presupuestoModel = new PresupuestoModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de notas de entrega
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/notaEntregaListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de notas de entrega en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $notas = $notaEntregaModel->listarTodos();
    
    respuestaJson('exito', 'Notas de entrega obtenidas correctamente', [
        'notas' => $notas
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca notas de entrega por término de búsqueda o devuelve todas
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $notas = $notaEntregaModel->listarTodos();
    } else {
        $notas = $notaEntregaModel->buscarNotas($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'notas' => $notas
    ]);

// FUNCIÓN: crearDesdePresupuesto
// OBJETIVO: Renderiza el formulario para crear nota de entrega desde un presupuesto aprobado
} elseif ($metodo === 'crearDesdePresupuesto') {
    verificarRolVendedor();
    
    $presupuestoId = intval($_GET['id'] ?? 0);
    
    if ($presupuestoId < 1) {
        header('Location: /SP%20Perfect%20Color/presupuesto');
        exit;
    }
    
    $presupuesto = $presupuestoModel->buscarPorId($presupuestoId);
    
    $estadoActual = $presupuesto['estado'] ?? '';
    $idEstadoVenta = intval($presupuesto['id_estado_venta'] ?? 0);
    if (!$presupuesto || ($estadoActual !== 'aprobado' && $estadoActual !== 'presupuesto' && $estadoActual !== 'pendiente' && $idEstadoVenta !== 1 && $idEstadoVenta !== 2)) {
        header('Location: /SP%20Perfect%20Color/presupuesto');
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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $presupuestoId = intval($_POST['id_presupuesto'] ?? 0);
    if ($presupuestoId < 1) {
        respuestaJson('error', 'Debe seleccionar un presupuesto');
    }

    $presupuesto = $presupuestoModel->buscarPorId($presupuestoId);
    if (!$presupuesto) {
        respuestaJson('error', 'Presupuesto asociado no encontrado');
    }

    $decision = trim($_POST['decision'] ?? 'aprobar');
    if ($decision === 'rechazar') {
        if ($presupuestoModel->cambiarEstado($presupuestoId, 'rechazado')) {
            respuestaJson('exito', 'Nota de entrega / Presupuesto #' . $presupuestoId . ' rechazada correctamente');
        } else {
            respuestaJson('error', 'No se pudo rechazar la nota de entrega');
        }
    }

    // Tomar id_cliente directamente del presupuesto
    $clienteId = intval($presupuesto['id_cliente'] ?? 0);
    if ($clienteId < 1) {
        $clienteId = intval($_POST['id_cliente'] ?? 0);
    }
    if ($clienteId < 1) {
        respuestaJson('error', 'No se pudo identificar el cliente del presupuesto');
    }

    $condicionPago = $_POST['condicion_pago'] ?? 'contado';
    $tipoPagoId = !empty($_POST['id_tipo_pago']) ? intval($_POST['id_tipo_pago']) : null;
    $bancoId = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia = trim($_POST['referencia'] ?? '');
    $fechaVencimiento = $_POST['fecha_vencimiento'] ?? '';
    $montoInicial = floatval($_POST['monto_inicial'] ?? 0);
    $tipoPagoInicial = !empty($_POST['id_tipo_pago_inicial']) ? intval($_POST['id_tipo_pago_inicial']) : null;
    $bancoInicial = !empty($_POST['id_banco_inicial']) ? intval($_POST['id_banco_inicial']) : null;
    $referenciaInicial = trim($_POST['referencia_inicial'] ?? '');

    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un item');
    }
    
    if ($condicionPago === 'credito') {
        if (empty($fechaVencimiento)) {
            respuestaJson('error', 'Debe seleccionar una fecha de vencimiento para pagos a credito');
        }
        if ($montoInicial > 0) {
            if (empty($tipoPagoInicial)) {
                respuestaJson('error', 'Debe seleccionar el método de pago para el abono inicial');
            }
            if ($tipoPagoInicial === 1) {
                // Efectivo: banco no aplica, referencia por defecto
                $bancoInicial = null;
                if (empty($referenciaInicial)) $referenciaInicial = 'Efectivo';
            } else {
                if (empty($bancoInicial)) {
                    respuestaJson('error', 'Debe seleccionar el banco receptor para el abono inicial');
                }
                if (empty($referenciaInicial)) {
                    respuestaJson('error', 'Debe ingresar la referencia para el abono inicial');
                }
            }
        }
    }
    
    if ($condicionPago === 'contado') {
        if (empty($tipoPagoId) || $tipoPagoId < 1) {
            respuestaJson('error', 'Debe seleccionar un tipo de pago para aprobar la entrega');
        }
        if ($tipoPagoId === 1) {
            // Efectivo
            $bancoId = null;
            if (empty($referencia)) $referencia = 'Efectivo';
        } else {
            if (empty($bancoId) || $bancoId < 1) {
                respuestaJson('error', 'Debe seleccionar un banco para el pago por transferencia o pago móvil');
            }
            if (empty($referencia)) {
                respuestaJson('error', 'Debe ingresar el número de referencia para el pago');
            }
        }
    }
    
    $total = 0;
    $detalle = [];
    
    foreach ($items as $item) {
        $presupuestoDetalleId = intval($item['id_presupuesto_detalle'] ?? 0);
        $cantidad = floatval($item['cantidad'] ?? 0);
        $precioUnitario = floatval($item['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precioUnitario;
        
        if ($presupuestoDetalleId < 1 || $cantidad <= 0 || $precioUnitario <= 0) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }
        
        $total += $subtotal;
        
        $detalle[] = [
            'id_presupuesto_detalle' => $presupuestoDetalleId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal
        ];
    }
    
    try {
        $notaId = $notaEntregaModel->crearNotaEntrega(
            $clienteId,
            (int)$_SESSION['id_usuario'],
            $total,
            $presupuestoId,
            $condicionPago,
            $detalle,
            $tipoPagoId,
            $bancoId,
            $referencia !== '' ? $referencia : null,
            $fechaVencimiento !== '' ? $fechaVencimiento : null,
            $montoInicial,
            $tipoPagoInicial,
            $bancoInicial,
            $referenciaInicial !== '' ? $referenciaInicial : null
        );
        
        respuestaJson('exito', 'Nota de entrega creada exitosamente', [
            'id_nota_entrega' => $notaId,
            'total' => $total
        ]);
    } catch (\Throwable $e) {
        error_log('ERROR CRITICO AL CREAR NOTA: ' . $e->getMessage() . ' (en ' . $e->getFile() . ':' . $e->getLine() . ')');
        respuestaJson('error', 'Error al crear la nota de entrega');
    }

// FUNCIÓN: ver
// OBJETIVO: Renderiza la vista de detalle de una nota de entrega con sus items
} elseif ($metodo === 'ver') {
    verificarAutenticacion();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }
    
    $nota = $notaEntregaModel->buscarPorId($id);
    
    if (!$nota) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }
    
    $detalle = $notaEntregaModel->obtenerDetalle($id);
    
    $pageTitle = 'SP Perfect Color - Nota de Entrega #' . $id;
    $pageDescription = 'Detalle de la nota de entrega #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: obtenerClientesAjax
// OBJETIVO: Obtiene los clientes disponibles para el formulario de nota de entrega
} elseif ($metodo === 'obtenerClientesAjax') {
    verificarAutenticacion();
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: obtenerTiposPagoAjax
// OBJETIVO: Obtiene los tipos de pago activos para el formulario
} elseif ($metodo === 'obtenerTiposPagoAjax') {
    verificarAutenticacion();
    
    $tiposPago = (new TipoPagoModel())->listarTodos();
    
    respuestaJson('exito', 'Tipos de pago obtenidos', [
        'tipos_pago' => $tiposPago
    ]);

// FUNCIÓN: obtenerBancosAjax
// OBJETIVO: Obtiene los bancos activos para el formulario
} elseif ($metodo === 'obtenerBancosAjax') {
    verificarAutenticacion();
    
    $bancos = (new BancoModel())->listarTodos();
    
    respuestaJson('exito', 'Bancos obtenidos', [
        'bancos' => $bancos
    ]);

// FUNCIÓN: pdf
// OBJETIVO: Genera y visualiza o descarga el comprobante formal en PDF de la nota de entrega
} elseif ($metodo === 'pdf') {
    verificarAutenticacion();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }

    $nota = $notaEntregaModel->buscarPorId($id);
    if (!$nota) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
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
