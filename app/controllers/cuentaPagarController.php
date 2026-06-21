<?php
// Archivo: cuentaPagarController.php
// Controlador para cuentas por pagar (Procedimental)

namespace App\Controllers;

use App\Models\CuentaPagarModel;
use App\Models\ProveedorModel;
use App\Models\TipoPagoModel;
use App\Models\BancoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarAcceso;
use function App\Helpers\verificarRolAdmin;
use \PDOException;

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

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca cuentas por pagar por término de búsqueda o devuelve todas
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
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $cuentaId = intval($_POST['cuenta_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $tipoPagoId = intval($_POST['id_tipo_pago'] ?? 0);
    $bancoId = !empty($_POST['id_banco']) ? intval($_POST['id_banco']) : null;
    $referencia = trim($_POST['referencia'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    
    if ($cuentaId < 1) {
        respuestaJson('error', 'Cuenta no valida');
    }
    
    if ($monto <= 0) {
        respuestaJson('error', 'El monto debe ser mayor a cero');
    }
    
    if ($tipoPagoId < 1) {
        respuestaJson('error', 'Debe seleccionar un tipo de pago');
    }
    
    if ($tipoPagoId === 2 || $tipoPagoId === 3) {
        if (empty($bancoId) || $bancoId < 1) {
            respuestaJson('error', 'Debe seleccionar un banco para transferencia o pago movil');
        }
        if (empty($referencia)) {
            respuestaJson('error', 'Debe ingresar el numero de referencia para transferencia o pago movil');
        }
    }
    
    try {
        $cuentaPagarModel->registrarPago($cuentaId, $monto, $tipoPagoId, $bancoId, $referencia, $fecha);
        respuestaJson('exito', 'Pago registrado exitosamente');
    } catch (PDOException $e) {
        error_log('Error al registrar pago CxP: ' . $e->getMessage());
        respuestaJson('error', 'Error al registrar el pago');
    }

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

// FUNCIÓN: obtenerProveedoresAjax
// OBJETIVO: Obtiene los proveedores activos para el formulario de creación manual
} elseif ($metodo === 'obtenerProveedoresAjax') {
    verificarAcceso([1]);
    
    $proveedores = $proveedorModel->listarTodos();
    
    respuestaJson('exito', 'Proveedores obtenidos correctamente', [
        'proveedores' => $proveedores
    ]);

// FUNCIÓN: guardarManual
// OBJETIVO: Crea una cuenta por pagar manualmente con proveedor, monto y fecha de vencimiento
} elseif ($metodo === 'guardarManual') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $proveedorId = intval($_POST['id_proveedor'] ?? 0);
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
        error_log('Error al crear CxP manual: ' . $e->getMessage());
        respuestaJson('error', 'Error al crear la cuenta');
    }

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de una cuenta por pagar (soft-delete)
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
        error_log('Error al eliminar CxP: ' . $e->getMessage());
        respuestaJson('error', 'Error al eliminar la cuenta');
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
