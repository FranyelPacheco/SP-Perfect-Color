<?php
// Archivo: cuentaPagarController.php
// Controlador para cuentas por pagar (Procedimental)

namespace App\Controllers;

use App\Models\{
    CuentaPagarModel,
    ProveedorModel,
    TipoPagoModel,
    BancoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarAcceso,
    verificarSoloAdmin,
    soloPost,
    responder
};

$cuentaPagarModel = new CuentaPagarModel();
$proveedorModel = new ProveedorModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de cuentas por pagar
if ($metodo === 'index') {
    verificarAcceso([1]);
    
    $contenidoVista = __DIR__ . '/../views/cuentaPagarListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de cuentas por pagar en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAcceso([1]);
    
    $cuentas = $cuentaPagarModel->listarTodas();
    
    respuestaJson('exito', 'Cuentas obtenidas correctamente', [
        'cuentas' => $cuentas
    ]);

// FUNCIÓN: ver
// OBJETIVO: Renderiza la vista de detalle de una cuenta con sus pagos realizados
} elseif ($metodo === 'ver') {
    verificarAcceso([1]);
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/cuentaPagar');
        exit;
    }
    
    $cuenta = $cuentaPagarModel->buscarPorId($id);
    
    if (!$cuenta) {
        header('Location: /SP%20Perfect%20Color/cuentaPagar');
        exit;
    }
    
    $pagos = $cuentaPagarModel->obtenerPagos($id);
    
    $pageTitle = 'SP Perfect Color - Cuenta por Pagar #' . $id;
    $pageDescription = 'Detalle de la cuenta por pagar #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/cuentaPagarVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: registrarPago
// OBJETIVO: Registra un pago realizado con validación de banco/referencia para transferencia o pago móvil
} elseif ($metodo === 'registrarPago') {
    verificarSoloAdmin();
    
    soloPost();
    
    $resultado = $cuentaPagarModel->procesarPago($_POST);
    responder($resultado);

// FUNCIÓN: obtenerDatosPagoAjax
// OBJETIVO: Obtiene tipos de pago y bancos para el modal de registro de pago
} elseif ($metodo === 'obtenerDatosPagoAjax') {
    verificarAcceso([1]);
    
    $tiposPago = (new TipoPagoModel())->listarTodos();
    $bancos = (new BancoModel())->listarTodos();
    
    respuestaJson('exito', 'Datos obtenidos', [
        'tipos_pago' => $tiposPago,
        'bancos' => $bancos
    ]);

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de una cuenta por pagar (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarSoloAdmin();
    
    soloPost();
    
    $id = intval($_POST['id'] ?? 0);
    $resultado = $cuentaPagarModel->darDeBaja($id);
    responder($resultado);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
