<?php
// Archivo: cuentaCobrarController.php
// Controlador para cuentas por cobrar (Procedimental)

namespace App\Controllers;

use App\Models\CuentaCobrarModel;
use App\Core\ConexionBD;
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
    $tipoPagoId = intval($_POST['tipo_pago_id'] ?? 0);
    $bancoId = !empty($_POST['banco_id']) ? intval($_POST['banco_id']) : null;
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
    
    // Transferencia(2) o Pago Movil(3) requieren banco y referencia
    if ($tipoPagoId === 2 || $tipoPagoId === 3) {
        if (empty($bancoId) || $bancoId < 1) {
            respuestaJson('error', 'Debe seleccionar un banco para transferencia o pago movil');
        }
        if (empty($referencia)) {
            respuestaJson('error', 'Debe ingresar el numero de referencia para transferencia o pago movil');
        }
    }
    
    try {
        $cuentaCobrarModel->registrarPago($cuentaId, $monto, $tipoPagoId, $bancoId, $referencia, $fecha);
        respuestaJson('exito', 'Pago registrado exitosamente');
    } catch (PDOException $e) {
        respuestaJson('error', $e->getMessage());
    }

// 6. Obtiene tipos de pago y bancos para el modal
} elseif ($metodo === 'obtenerDatosPagoAjax') {
    verificarAutenticacion();
    
    $conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    $tiposPago = $conexion->query("SELECT id_tipo_pago, nombre FROM tipo_pago WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    $bancos = $conexion->query("SELECT id_banco, nombre FROM banco WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    
    respuestaJson('exito', 'Datos obtenidos', [
        'tipos_pago' => $tiposPago,
        'bancos' => $bancos
    ]);

// 7. Eliminacion logica de una cuenta
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