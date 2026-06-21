<?php
// Archivo: presupuestoController.php
// Controlador para la gestion de presupuestos (Procedimental)

namespace App\Controllers;

use App\Models\PresupuestoModel;
use App\Models\ClienteModel;
use App\Models\InventarioModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolVendedor;
use function App\Helpers\validarRequerido;
use \PDOException;

$presupuestoModel = new PresupuestoModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de presupuestos
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/presupuestoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de presupuestos en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $presupuestos = $presupuestoModel->listarTodos();
    
    respuestaJson('exito', 'Presupuestos obtenidos correctamente', [
        'presupuestos' => $presupuestos
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca presupuestos por cliente o estado
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();
    
    $termino = trim($_GET['termino'] ?? '');
    $estado = trim($_GET['estado'] ?? '');
    
    $presupuestos = $presupuestoModel->buscarPresupuestos($termino, $estado);
    
    respuestaJson('exito', 'Busqueda completada', [
        'presupuestos' => $presupuestos
    ]);

// FUNCIÓN: nuevo
// OBJETIVO: Renderiza el formulario para crear un nuevo presupuesto
} elseif ($metodo === 'nuevo') {
    verificarRolVendedor();
    
    $pageTitle = 'SP Perfect Color - Nuevo Presupuesto';
    $pageDescription = 'Crear un nuevo presupuesto o cotizaciÃ³n - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/presupuestoFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: obtenerInsumosAjax
// OBJETIVO: Obtiene los insumos disponibles para el formulario de presupuesto
} elseif ($metodo === 'obtenerInsumosAjax') {
    verificarAutenticacion();
    
    $insumos = $inventarioModel->listarTodos();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos
    ]);

// FUNCIÓN: obtenerClientesAjax
// OBJETIVO: Obtiene los clientes disponibles para el formulario de presupuesto
} elseif ($metodo === 'obtenerClientesAjax') {
    verificarAutenticacion();
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo presupuesto con su detalle y calcula el total
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $clienteId = intval($_POST['id_cliente'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if ($clienteId < 1) {
        respuestaJson('error', 'Debe seleccionar un cliente');
    }
    
    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un insumo al presupuesto');
    }
    
    $total = 0;
    $detalle = [];
    
    foreach ($items as $item) {
        $insumoId = intval($item['id_insumo'] ?? 0);
        $cantidad = floatval($item['cantidad'] ?? 0);
        $precioUnitario = floatval($item['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precioUnitario;
        
        if ($insumoId < 1 || $cantidad <= 0 || $precioUnitario <= 0) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }
        
        $total += $subtotal;
        
        $detalle[] = [
            'id_insumo' => $insumoId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal
        ];
    }
    
    try {
        $presupuestoId = $presupuestoModel->insertarPresupuesto($clienteId, (int)$_SESSION['id_usuario'], $total, $observaciones, $detalle);
        
        respuestaJson('exito', 'Presupuesto creado exitosamente', [
            'id_presupuesto' => $presupuestoId,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al crear el presupuesto: ' . $e->getMessage());
    }

// FUNCIÓN: ver
// OBJETIVO: Renderiza la vista de detalle de un presupuesto con sus items
} elseif ($metodo === 'ver') {
    verificarAutenticacion();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: ../presupuesto');
        exit;
    }
    
    $presupuesto = $presupuestoModel->buscarPorId($id);
    
    if (!$presupuesto) {
        header('Location: ../presupuesto');
        exit;
    }
    
    $detalle = $presupuestoModel->obtenerDetalle($id);
    
    $pageTitle = 'SP Perfect Color - Presupuesto #' . $id;
    $pageDescription = 'Detalle del presupuesto #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/presupuestoVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: cambiarEstado
// OBJETIVO: Cambia el estado de un presupuesto (pendiente/aprobado/rechazado/convertido)
} elseif ($metodo === 'cambiarEstado') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    
    $estadosValidos = ['pendiente', 'aprobado', 'rechazado', 'convertido'];
    
    if ($id < 1 || !in_array($estado, $estadosValidos)) {
        respuestaJson('error', 'Datos invalidos');
    }
    
    if ($presupuestoModel->cambiarEstado($id, $estado)) {
        $mensajes = [
            'aprobado' => 'Presupuesto aprobado exitosamente',
            'rechazado' => 'Presupuesto rechazado',
            'convertido' => 'Presupuesto convertido a nota de entrega',
            'pendiente' => 'Presupuesto vuelto a estado pendiente'
        ];
        
        respuestaJson('exito', $mensajes[$estado] ?? 'Estado actualizado');
    } else {
        respuestaJson('error', 'Error al cambiar el estado');
    }

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un presupuesto (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) {
        respuestaJson('error', 'ID invalido');
    }
    
    try {
        $presupuestoModel->eliminarPresupuesto($id);
        respuestaJson('exito', 'Presupuesto eliminado correctamente');
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al eliminar el presupuesto: ' . $e->getMessage());
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
