<?php

namespace App\Controllers;

use App\Models\NotaEntregaModel;
use App\Models\PresupuestoModel;
use App\Models\ClienteModel;
use App\Models\InventarioModel;
use App\Models\TipoPagoModel;
use App\Models\BancoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolVendedor;
use \PDOException;

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
    
    if (!$presupuesto || $presupuesto['estado'] !== 'aprobado') {
        header('Location: /SP%20Perfect%20Color/presupuesto');
        exit;
    }
    
    $detalle = $presupuestoModel->obtenerDetalle($presupuestoId);
    
    $pageTitle = 'SP Perfect Color - Nota de Entrega desde Presupuesto #' . $presupuestoId;
    $pageDescription = 'Crear nota de entrega a partir del presupuesto #' . $presupuestoId . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: guardar
// OBJETIVO: Crea una nueva nota de entrega con detalle, condicion de pago y datos de pago
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
    $estadoNota = 'entregado';
    $fechaVencimiento = $_POST['fecha_vencimiento'] ?? '';
    $items = json_decode($_POST['items'] ?? '[]', true);
    
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
    
    if ($condicionPago === 'contado' && (empty($tipoPagoId) || $tipoPagoId < 1)) {
        respuestaJson('error', 'Debe seleccionar un tipo de pago');
    }
    
    if ($condicionPago === 'contado' && ($tipoPagoId === 2 || $tipoPagoId === 3)) {
        if (empty($bancoId) || $bancoId < 1) {
            respuestaJson('error', 'Debe seleccionar un banco para transferencia o pago movil');
        }
        if (empty($referencia)) {
            respuestaJson('error', 'Debe ingresar el numero de referencia para transferencia o pago movil');
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
            $estadoNota,
            $condicionPago,
            $detalle,
            $tipoPagoId,
            $bancoId,
            $referencia !== '' ? $referencia : null,
            $fechaVencimiento !== '' ? $fechaVencimiento : null
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

// FUNCIÓN: obtenerPresupuestosAprobadosAjax
// OBJETIVO: Obtiene presupuestos aprobados para crear nota de entrega
} elseif ($metodo === 'obtenerPresupuestosAprobadosAjax') {
    verificarAutenticacion();
    
    $presupuestos = $presupuestoModel->buscarPresupuestos('', 'aprobado');
    
    respuestaJson('exito', 'Presupuestos obtenidos correctamente', [
        'presupuestos' => $presupuestos
    ]);

// FUNCIÓN: obtenerDetallePresupuestoAjax
// OBJETIVO: Obtiene el detalle de un presupuesto para precargar los items en el formulario
} elseif ($metodo === 'obtenerDetallePresupuestoAjax') {
    verificarAutenticacion();
    
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
// FUNCIÓN: editar
// OBJETIVO: Renderiza el formulario de edición de items de una nota de entrega
} elseif ($metodo === 'editar') {
    verificarRolVendedor();

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

    $pageTitle = 'SP Perfect Color - Editar Nota de Entrega #' . $id;
    $pageDescription = 'Modificar items de la nota de entrega #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/notaEntregaEditView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: actualizar
// OBJETIVO: Guarda los cambios de items de una nota de entrega
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
    if (!$nota) {
        respuestaJson('error', 'Nota de entrega no encontrada');
    }

    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un item');
    }

    $detalle = [];
    foreach ($items as $item) {
        $presupuestoDetalleId = intval($item['id_presupuesto_detalle'] ?? 0);
        $esNuevo = $presupuestoDetalleId === 0;
        $idInsumo = $esNuevo ? intval($item['id_insumo'] ?? 0) : 0;
        $cantidad = floatval($item['cantidad'] ?? 0);
        $precioUnitario = floatval($item['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precioUnitario;

        if ($cantidad <= 0 || $precioUnitario <= 0) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }
        if (!$esNuevo && $presupuestoDetalleId < 1) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }
        if ($esNuevo && $idInsumo < 1) {
            respuestaJson('error', 'Item nuevo sin insumo valido');
        }

        $detalle[] = [
            'id_presupuesto_detalle' => $presupuestoDetalleId,
            'id_insumo' => $idInsumo,
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

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
