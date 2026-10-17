<?php
// Archivo: cuentaCobrarController.php
// Controlador para cuentas por cobrar (Procedimental)

namespace App\Controllers;

use PDOException;
use App\Models\{
    CuentaCobrarModel,
    TipoPagoModel,
    BancoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarRolAdmin
};

$cuentaCobrarModel = new CuentaCobrarModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de cuentas por cobrar
if ($metodo === 'index') {
    verificarRolAdmin();
    
    $contenidoVista = __DIR__ . '/../views/cuentaCobrarListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de cuentas por cobrar en JSON
} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();
    
    $cuentas = $cuentaCobrarModel->listarTodas();
    
    respuestaJson('exito', 'Cuentas obtenidas correctamente', [
        'cuentas' => $cuentas
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca cuentas por cobrar por término de búsqueda o devuelve todas
} elseif ($metodo === 'buscarAjax') {
    verificarRolAdmin();
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $cuentas = $cuentaCobrarModel->listarTodas();
    } else {
        $cuentas = $cuentaCobrarModel->buscarCuentas($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'cuentas' => $cuentas
    ]);

// FUNCIÓN: ver
// OBJETIVO: Renderiza la vista de detalle de una cuenta con sus pagos recibidos
} elseif ($metodo === 'ver') {
    verificarRolAdmin();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/cuentaCobrar');
        exit;
    }
    
    $cuenta = $cuentaCobrarModel->buscarPorId($id);
    
    if (!$cuenta) {
        header('Location: /SP%20Perfect%20Color/cuentaCobrar');
        exit;
    }
    
    $pagos = $cuentaCobrarModel->obtenerPagos($id);
    
    $pageTitle = 'SP Perfect Color - Cuenta por Cobrar #' . $id;
    $pageDescription = 'Detalle de la cuenta por cobrar #' . $id . ' - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/cuentaCobrarVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: registrarPago
// OBJETIVO: Registra un pago recibido con validación de banco/referencia para transferencia o pago móvil
} elseif ($metodo === 'registrarPago') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $resultado = $cuentaCobrarModel->procesarPago($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

// FUNCIÓN: obtenerDatosPagoAjax
// OBJETIVO: Obtiene tipos de pago y bancos para el modal de registro de pago
} elseif ($metodo === 'obtenerDatosPagoAjax') {
    verificarRolAdmin();
    
    $tiposPago = (new TipoPagoModel())->listarTodos();
    $bancos = (new BancoModel())->listarTodos();
    
    respuestaJson('exito', 'Datos obtenidos', [
        'tipos_pago' => $tiposPago,
        'bancos' => $bancos
    ]);

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de una cuenta por cobrar (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $resultado = $cuentaCobrarModel->darDeBaja($id);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
