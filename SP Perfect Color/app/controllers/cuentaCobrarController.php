<?php
// Archivo: cuentaCobrarController.php
// Controlador para cuentas por cobrar (Procedimental)

namespace App\Controllers;

use App\Models\CuentaCobrarModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolVendedor;
use \PDOException;

// Instancia limpia del modelo para uso procedimental
$cuentaCobrarModel = new CuentaCobrarModel();

// 1. Muestra la lista de cuentas por cobrar
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/cuentaCobrarListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene las cuentas en formato JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $cuentas = $cuentaCobrarModel->listarTodas();
    
    respuestaJson('exito', 'Cuentas obtenidas correctamente', [
        'cuentas' => $cuentas
    ]);

// 3. Busca cuentas
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $cuentas = $cuentaCobrarModel->listarTodas();
    } else {
        $cuentas = $cuentaCobrarModel->buscarCuentas($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'cuentas' => $cuentas
    ]);

// 4. Muestra el detalle de una cuenta con sus pagos
} elseif ($metodo === 'ver') {
    verificarAutenticacion();
    
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

// 5. Registra un pago via AJAX
} elseif ($metodo === 'registrarPago') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $cuentaId = intval($_POST['cuenta_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    
    if ($cuentaId < 1) {
        respuestaJson('error', 'Cuenta no valida');
    }
    
    if ($monto <= 0) {
        respuestaJson('error', 'El monto debe ser mayor a cero');
    }
    
    try {
        $cuentaCobrarModel->registrarPago($cuentaId, $monto, $metodoPago, $fecha);
        respuestaJson('exito', 'Pago registrado exitosamente');
    } catch (PDOException $e) {
        respuestaJson('error', $e->getMessage());
    }

// 6. Eliminacion logica de una cuenta
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
        $cuentaCobrarModel->eliminarCuenta($id);
        respuestaJson('exito', 'Cuenta eliminada correctamente');
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al eliminar la cuenta: ' . $e->getMessage());
    }

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}