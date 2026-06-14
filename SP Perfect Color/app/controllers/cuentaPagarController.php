<?php
// Archivo: cuentaPagarController.php
// Controlador para cuentas por pagar (Procedimental)

namespace App\Controllers;

use App\Models\CuentaPagarModel;
use App\Models\ProveedorModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarAcceso;
use function App\Helpers\verificarRolAdmin;
use \PDOException;

// Instancias limpias de los modelos para uso procedimental
$cuentaPagarModel = new CuentaPagarModel();
$proveedorModel = new ProveedorModel();

// 1. Muestra la lista de cuentas por pagar
if ($metodo === 'index') {
    verificarAcceso([1]);
    
    $contenidoVista = __DIR__ . '/../views/cuentaPagarListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene las cuentas en formato JSON
} elseif ($metodo === 'listarAjax') {
    verificarAcceso([1]);
    
    $cuentas = $cuentaPagarModel->listarTodas();
    
    respuestaJson('exito', 'Cuentas obtenidas correctamente', [
        'cuentas' => $cuentas
    ]);

// 3. Busca cuentas
} elseif ($metodo === 'buscarAjax') {
    verificarAcceso([1]);
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $cuentas = $cuentaPagarModel->listarTodas();
    } else {
        $cuentas = $cuentaPagarModel->buscarCuentas($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'cuentas' => $cuentas
    ]);

// 4. Muestra el detalle de una cuenta
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

// 5. Registra un pago via AJAX
} elseif ($metodo === 'registrarPago') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $cuentaId = intval($_POST['cuenta_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? 'Transferencia';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    
    if ($cuentaId < 1) {
        respuestaJson('error', 'Cuenta no valida');
    }
    
    if ($monto <= 0) {
        respuestaJson('error', 'El monto debe ser mayor a cero');
    }
    
    try {
        $cuentaPagarModel->registrarPago($cuentaId, $monto, $metodoPago, $fecha);
        respuestaJson('exito', 'Pago registrado exitosamente');
    } catch (PDOException $e) {
        respuestaJson('error', $e->getMessage());
    }

// 6. Obtiene los proveedores para el formulario
} elseif ($metodo === 'obtenerProveedoresAjax') {
    verificarAcceso([1]);
    
    $proveedores = $proveedorModel->listarTodos();
    error_log('[CxP] Proveedores encontrados: ' . count($proveedores));
    
    respuestaJson('exito', 'Proveedores obtenidos correctamente', [
        'proveedores' => $proveedores
    ]);

// 7. Guarda una nueva cuenta por pagar via AJAX
} elseif ($metodo === 'guardarManual') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $proveedorId = intval($_POST['proveedor_id'] ?? 0);
    $montoTotal = floatval($_POST['monto_total'] ?? 0);
    $fechaVencimiento = $_POST['fecha_vencimiento'] ?? '';
    
    if ($proveedorId < 1) {
        respuestaJson('error', 'Debe seleccionar un proveedor');
    }
    
    if ($montoTotal <= 0) {
        respuestaJson('error', 'El monto total debe ser mayor a cero');
    }
    
    if (empty($fechaVencimiento)) {
        respuestaJson('error', 'Debe indicar una fecha de vencimiento');
    }
    
    try {
        $cuentaId = $cuentaPagarModel->crearCuenta($proveedorId, $montoTotal, $fechaVencimiento);
        respuestaJson('exito', 'Cuenta por pagar creada exitosamente', [
            'cuenta_id' => $cuentaId
        ]);
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al crear la cuenta: ' . $e->getMessage());
    }

// 7. Eliminacion logica de una cuenta
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) {
        respuestaJson('error', 'ID invalido');
    }
    
    try {
        $cuentaPagarModel->eliminarCuenta($id);
        respuestaJson('exito', 'Cuenta eliminada correctamente');
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al eliminar la cuenta: ' . $e->getMessage());
    }

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}