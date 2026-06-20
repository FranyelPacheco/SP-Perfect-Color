<?php

namespace App\Controllers;

use App\Models\NotaEntregaModel;
use App\Models\PresupuestoModel;
use App\Models\ClienteModel;
use App\Models\InventarioModel;
use App\Core\ConexionBD;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolVendedor;
use \PDOException;

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
    
    $pageTitle = 'SP Perfect Color - Nota de Entrega desde Presupuesto #' . $presupuestoId;
    $pageDescription = 'Crear nota de entrega a partir del presupuesto #' . $presupuestoId . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 6. Guarda una nueva nota de entrega via AJAX
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $clienteId = intval($_POST['id_cliente'] ?? 0);
    $presupuestoId = intval($_POST['id_presupuesto'] ?? 0);
    $condicionPago = $_POST['condicion_pago'] ?? 'contado';
    $tipoPagoId = !empty($_POST['id_tipo_pago']) ? intval($_POST['id_tipo_pago']) : null;
    $bancoId = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia = trim($_POST['referencia'] ?? '');
    $estadoNota = $_POST['estado'] ?? 'pendiente';
    $fechaVencimiento = $_POST['fecha_vencimiento'] ?? '';
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    // Validar
    if ($clienteId < 1) {
        respuestaJson('error', 'Debe seleccionar un cliente');
    }
    
    if ($presupuestoId < 1) {
        respuestaJson('error', 'Debe seleccionar un presupuesto');
    }
    
    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un item');
    }
    
    if ($condicionPago === 'credito' && empty($fechaVencimiento)) {
        respuestaJson('error', 'Debe seleccionar una fecha de vencimiento para pagos a credito');
    }
    
    // Tipo de pago obligatorio cuando es contado
    if ($condicionPago === 'contado' && (empty($tipoPagoId) || $tipoPagoId < 1)) {
        respuestaJson('error', 'Debe seleccionar un tipo de pago');
    }
    
    // Transferencia(2) o Pago Movil(3) requieren banco y referencia
    if ($condicionPago === 'contado' && ($tipoPagoId === 2 || $tipoPagoId === 3)) {
        if (empty($bancoId) || $bancoId < 1) {
            respuestaJson('error', 'Debe seleccionar un banco para transferencia o pago movil');
        }
        if (empty($referencia)) {
            respuestaJson('error', 'Debe ingresar el numero de referencia para transferencia o pago movil');
        }
    }
    
    // Calcular total y preparar detalle
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
    
    // Preparar datos
    $datos = [
        'id_cliente' => $clienteId,
        'id_usuario' => $_SESSION['id_usuario'],
        'total' => $total,
        'id_presupuesto' => $presupuestoId,
        'estado' => $estadoNota,
        'condicion_pago' => $condicionPago,
        'id_tipo_pago' => $tipoPagoId,
        'id_banco' => $bancoId,
        'referencia' => $referencia,
        'fecha_vencimiento' => $fechaVencimiento
    ];
    
    try {
        $notaId = $notaEntregaModel->crearNotaEntrega($datos, $detalle);
        
        respuestaJson('exito', 'Nota de entrega creada exitosamente', [
            'id_nota_entrega' => $notaId,
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
    
    $pageTitle = 'SP Perfect Color - Nota de Entrega #' . $id;
    $pageDescription = 'Detalle de la nota de entrega #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 8. Obtiene los clientes para el formulario
} elseif ($metodo === 'obtenerClientesAjax') {
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// 9. Obtiene presupuestos aprobados para crear nota
} elseif ($metodo === 'obtenerPresupuestosAprobadosAjax') {
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $presupuestos = $presupuestoModel->buscarPresupuestos('', 'aprobado');
    
    respuestaJson('exito', 'Presupuestos obtenidos correctamente', [
        'presupuestos' => $presupuestos
    ]);

// 10. Obtiene detalle de un presupuesto para pre-cargar items
} elseif ($metodo === 'obtenerDetallePresupuestoAjax') {
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $presupuestoId = intval($_GET['id_presupuesto'] ?? 0);
    if ($presupuestoId < 1) {
        respuestaJson('error', 'ID de presupuesto invalido');
    }
    
    $detalle = $presupuestoModel->obtenerDetalle($presupuestoId);
    $presupuesto = $presupuestoModel->buscarPorId($presupuestoId);
    
    if (!$presupuesto || $presupuesto['estado'] !== 'aprobado') {
        respuestaJson('error', 'Presupuesto no disponible');
    }
    
    respuestaJson('exito', 'Detalle obtenido correctamente', [
        'detalle' => $detalle,
        'presupuesto' => $presupuesto
    ]);

// 11. Obtiene tipos de pago
} elseif ($metodo === 'obtenerTiposPagoAjax') {
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    $consulta = "SELECT id_tipo_pago, nombre FROM tipo_pago WHERE activo = 1 ORDER BY nombre ASC";
    $stmt = $conexion->query($consulta);
    $tiposPago = $stmt->fetchAll();
    
    respuestaJson('exito', 'Tipos de pago obtenidos', [
        'tipos_pago' => $tiposPago
    ]);

// 12. Obtiene bancos
} elseif ($metodo === 'obtenerBancosAjax') {
    if (!isset($_SESSION['id_usuario'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    $consulta = "SELECT id_banco, nombre FROM banco WHERE activo = 1 ORDER BY nombre ASC";
    $stmt = $conexion->query($consulta);
    $bancos = $stmt->fetchAll();
    
    respuestaJson('exito', 'Bancos obtenidos', [
        'bancos' => $bancos
    ]);

// 13. Cambia el estado de una nota de entrega
} elseif ($metodo === 'cambiarEstado') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    
    if ($id < 1 || !in_array($estado, ['en_espera', 'pendiente', 'entregado'])) {
        respuestaJson('error', 'Datos invalidos');
    }
    
    $nota = $notaEntregaModel->buscarPorId($id);
    if (!$nota) {
        respuestaJson('error', 'Nota de entrega no encontrada');
    }
    
    try {
        $notaEntregaModel->cambiarEstado($id, $estado);
        respuestaJson('exito', 'Estado actualizado a: ' . $estado);
    } catch (\Throwable $e) {
        error_log('ERROR al cambiar estado de nota: ' . $e->getMessage());
        respuestaJson('error', 'Error al actualizar el estado');
    }

// 14. Muestra formulario para editar una nota en espera
} elseif ($metodo === 'editar') {
    verificarRolVendedor();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }

    $nota = $notaEntregaModel->buscarPorId($id);
    if (!$nota || $nota['estado'] !== 'en_espera') {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }

    $detalle = $notaEntregaModel->obtenerDetalle($id);

    $pageTitle = 'SP Perfect Color - Editar Nota de Entrega #' . $id;
    $pageDescription = 'Modificar items de la nota de entrega #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaEditView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 15. Guarda los cambios de una nota editada
} elseif ($metodo === 'actualizar') {
    verificarRolVendedor();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id_nota_entrega'] ?? 0);
    $items = json_decode($_POST['items'] ?? '[]', true);

    if ($id < 1) {
        respuestaJson('error', 'ID de nota invalido');
    }

    $nota = $notaEntregaModel->buscarPorId($id);
    if (!$nota || $nota['estado'] !== 'en_espera') {
        respuestaJson('error', 'Solo se pueden editar notas en espera');
    }

    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un item');
    }

    $detalle = [];
    foreach ($items as $item) {
        $presupuestoDetalleId = intval($item['id_presupuesto_detalle'] ?? 0);
        $cantidad = floatval($item['cantidad'] ?? 0);
        $precioUnitario = floatval($item['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precioUnitario;

        if ($presupuestoDetalleId < 1 || $cantidad <= 0 || $precioUnitario <= 0) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }

        $detalle[] = [
            'id_presupuesto_detalle' => $presupuestoDetalleId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal
        ];
    }

    try {
        $notaEntregaModel->actualizarDetalleNota($id, $detalle);
        respuestaJson('exito', 'Nota de entrega actualizada exitosamente', ['id_nota_entrega' => $id]);
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al actualizar: ' . $e->getMessage());
    } catch (\Throwable $e) {
        respuestaJson('error', 'Error al actualizar la nota: ' . $e->getMessage());
    }

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
