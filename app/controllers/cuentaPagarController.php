<?php
// Archivo: cuentaPagarController.php
// Controlador para cuentas por pagar (Procedimental)

namespace App\Controllers;

use PDOException;
use App\Models\{
    CuentaPagarModel,
    ProveedorModel,
    TipoPagoModel,
    BancoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarAcceso,
    verificarRolAdmin,
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
    
    soloPost();
    
    $resultado = $cuentaPagarModel->registrarCuentaManual($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado['datos'] ?? null);

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de una cuenta por pagar (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();
    
    soloPost();
    
    $id = intval($_POST['id'] ?? 0);
    $resultado = $cuentaPagarModel->darDeBaja($id);
    responder($resultado);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
