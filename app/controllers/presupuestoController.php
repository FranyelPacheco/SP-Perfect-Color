<?php
// Archivo: presupuestoController.php
// Controlador para la gestion de presupuestos (Procedimental)

namespace App\Controllers;

use PDOException;
use App\Models\{
    PresupuestoModel,
    ClienteModel,
    InventarioModel,
    BancoModel,
    TipoPagoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    verificarPermiso,
    verificarRolVendedor,
    validarRequerido
};

$presupuestoModel = new PresupuestoModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de presupuestos
if ($metodo === 'index') {
    verificarPermiso('presupuesto');
    
    $contenidoVista = __DIR__ . '/../views/presupuestoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de presupuestos en JSON
} elseif ($metodo === 'listarAjax') {
    verificarPermiso('presupuesto');
    
    $presupuestos = $presupuestoModel->listarTodos();
    
    respuestaJson('exito', 'Presupuestos obtenidos correctamente', [
        'presupuestos' => $presupuestos
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca presupuestos por cliente o estado
} elseif ($metodo === 'buscarAjax') {
    verificarPermiso('presupuesto');
    
    $termino = trim($_GET['termino'] ?? '');
    $estado = trim($_GET['estado'] ?? '');
    
    $presupuestos = $presupuestoModel->buscarPresupuestos($termino, $estado);
    
    respuestaJson('exito', 'Busqueda completada', [
        'presupuestos' => $presupuestos
    ]);

// FUNCIÓN: nuevo / crear
// OBJETIVO: Renderiza el formulario para crear un nuevo presupuesto
} elseif ($metodo === 'nuevo' || $metodo === 'crear') {
    verificarPermiso('presupuesto');
    verificarRolVendedor();
    
    $pageTitle = 'SP Perfect Color - Nuevo Presupuesto';
    $pageDescription = 'Crear un nuevo presupuesto o cotización - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/presupuestoFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: obtenerInsumosAjax
// OBJETIVO: Obtiene los insumos disponibles para el formulario de presupuesto
} elseif ($metodo === 'obtenerInsumosAjax') {
    verificarPermiso('presupuesto');
    
    $insumos = $inventarioModel->listarTodos();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos
    ]);

// FUNCIÓN: obtenerClientesAjax
// OBJETIVO: Obtiene los clientes disponibles para el formulario de presupuesto
} elseif ($metodo === 'obtenerClientesAjax') {
    verificarPermiso('presupuesto');
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: obtenerDatosPagoAjax
// OBJETIVO: Obtiene tipos de pago y bancos activos para ventas de contado
} elseif ($metodo === 'obtenerDatosPagoAjax') {
    verificarPermiso('presupuesto');
    
    $tiposPago = (new TipoPagoModel())->listarTodos();
    $bancos = (new BancoModel())->listarTodos();
    
    respuestaJson('exito', 'Datos de pago obtenidos', [
        'tipos_pago' => $tiposPago,
        'bancos' => $bancos
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo presupuesto con su detalle y calcula el total
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $resultado = $presupuestoModel->crearPresupuesto($_POST, (int)$_SESSION['id_usuario']);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado['datos'] ?? null);

// FUNCIÓN: ver
// OBJETIVO: Renderiza la vista de detalle de un presupuesto con sus items
} elseif ($metodo === 'ver') {
    verificarPermiso('presupuesto');
    
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
    
    $resultado = $presupuestoModel->actualizarEstado($id, $estado);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un presupuesto (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $resultado = $presupuestoModel->darDeBaja($id);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
