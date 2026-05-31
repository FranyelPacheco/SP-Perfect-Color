<?php
// Archivo: notaEntregaController.php
// Controlador para la gestion de notas de entrega (Procedimental)

namespace App\Controllers;

use App\Models\NotaEntregaModel;
use App\Models\PresupuestoModel;
use App\Models\ClienteModel;
use App\Models\InventarioModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolVendedor;
use \PDOException;

// Instancias limpias de los modelos para uso procedimental
$notaEntregaModel = new NotaEntregaModel();
$presupuestoModel = new PresupuestoModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();

// 1. Muestra la lista de notas de entrega
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/notaEntregaListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene la lista de notas de entrega en formato JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $notas = $notaEntregaModel->listarTodos();
    
    respuestaJson('exito', 'Notas de entrega obtenidas correctamente', [
        'notas' => $notas
    ]);

// 3. Busca notas de entrega por cliente
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

// 4. Muestra el formulario para crear nota de entrega desde presupuesto
} elseif ($metodo === 'crearDesdePresupuesto') {
    verificarRolVendedor();
    
    $presupuestoId = intval($_GET['id'] ?? 0);
    
    if ($presupuestoId < 1) {
        header('Location: /SP%20Perfect%20Color/presupuesto');
        exit;
    }
    
    $presupuesto = $presupuestoModel->buscarPorId($presupuestoId);
    
    if (!$presupuesto || $presupuesto['estado'] !== 'aprobado') {
        header('Location: /SP%20Perfect%20Color/presupuesto');
        exit;
    }
    
    $detalle = $presupuestoModel->obtenerDetalle($presupuestoId);
    
    $contenidoVista = __DIR__ . '/../views/notaEntregaFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 5. Muestra el formulario para crear nota de entrega directa
} elseif ($metodo === 'nueva') {
    verificarRolVendedor();
    
    $contenidoVista = __DIR__ . '/../views/notaEntregaDirectaView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 6. Guarda una nueva nota de entrega via AJAX
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    error_log('DATOS RECIBIDOS: ' . print_r($_POST, true));
    
    $clienteId = intval($_POST['cliente_id'] ?? 0);
    $presupuestoId = !empty($_POST['presupuesto_id']) ? intval($_POST['presupuesto_id']) : null;
    $tipoPago = $_POST['tipo_pago'] ?? 'credito';
    $fechaVencimiento = $_POST['fecha_vencimiento'] ?? '';
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    error_log("VALIDACION: cliente_id=$clienteId, tipo_pago=$tipoPago, fecha_vencimiento='$fechaVencimiento', items=" . count($items));
    
    // Validar
    if ($clienteId < 1) {
        error_log('VALIDACION FALLIDA: cliente_id invalido');
        respuestaJson('error', 'Debe seleccionar un cliente');
    }
    
    if (empty($items)) {
        error_log('VALIDACION FALLIDA: sin items');
        respuestaJson('error', 'Debe agregar al menos un insumo');
    }
    
    if ($tipoPago === 'credito' && empty($fechaVencimiento)) {
        error_log("VALIDACION FALLIDA: credito sin fecha_vencimiento (valor='$fechaVencimiento')");
        respuestaJson('error', 'Debe seleccionar una fecha de vencimiento para pagos a credito');
    }
    
    // Calcular total y preparar detalle
    $total = 0;
    $detalle = [];
    
    foreach ($items as $item) {
        $insumoId = intval($item['insumo_id'] ?? 0);
        $cantidad = floatval($item['cantidad'] ?? 0);
        $precioUnitario = floatval($item['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precioUnitario;
        
        if ($insumoId < 1 || $cantidad <= 0 || $precioUnitario <= 0) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }
        
        // Verificar stock disponible
        $insumo = $inventarioModel->buscarPorId($insumoId);
        if (!$insumo || $insumo['stock_actual'] < $cantidad) {
            respuestaJson('error', 'Stock insuficiente para: ' . ($insumo['nombre'] ?? 'ID: ' . $insumoId));
        }
        
        $total += $subtotal;
        
        $detalle[] = [
            'insumo_id' => $insumoId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal
        ];
    }
    
    // Preparar datos
    $datos = [
        'cliente_id' => $clienteId,
        'usuario_id' => $_SESSION['usuario_id'],
        'total' => $total,
        'presupuesto_id' => $presupuestoId,
        'tipo_pago' => $tipoPago,
        'fecha_vencimiento' => $fechaVencimiento
    ];
    
    error_log('Tipo de pago recibido: ' . ($datos['tipo_pago'] ?? 'nulo'));
    
    // Crear nota de entrega con transaccion
    try {
        $notaId = $notaEntregaModel->crearNotaEntrega($datos, $detalle);
        
        respuestaJson('exito', 'Nota de entrega creada exitosamente', [
            'nota_id' => $notaId,
            'total' => $total
        ]);
    } catch (\Throwable $e) {
        $detalleError = $e->getMessage() . ' (en ' . $e->getFile() . ':' . $e->getLine() . ')';
        error_log('ERROR CRITICO AL CREAR NOTA: ' . $detalleError);
        respuestaJson('error', 'Error al crear la nota: ' . $detalleError);
    }

// 7. Muestra el detalle de una nota de entrega
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
    
    $contenidoVista = __DIR__ . '/../views/notaEntregaVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 8. Obtiene los clientes para el formulario
} elseif ($metodo === 'obtenerClientesAjax') {
    if (!isset($_SESSION['usuario_id'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// 9. Obtiene los insumos para el formulario
} elseif ($metodo === 'obtenerInsumosAjax') {
    if (!isset($_SESSION['usuario_id'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $insumos = $inventarioModel->listarTodos();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos
    ]);

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}