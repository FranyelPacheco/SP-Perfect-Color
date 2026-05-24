<?php
// Archivo: cajaController.php
// Controlador para la gestion de caja (Procedimental)

require_once __DIR__ . '/../models/CajaModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';

// Instancia limpia del modelo para usar de forma procedimental
$cajaModel = new CajaModel();

// 1. Muestra el panel de caja
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    $cajas = $cajaModel->listarCajas();
    
    $contenidoVista = __DIR__ . '/../views/cajaView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Abre una nueva caja via AJAX
} elseif ($metodo === 'abrirCaja') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $montoInicial = floatval($_POST['monto_inicial'] ?? 0);
    
    if ($montoInicial < 0) {
        respuestaJson('error', 'El monto inicial no puede ser negativo');
    }
    
    // Verificar que no tenga una caja abierta
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    
    if ($cajaAbierta) {
        respuestaJson('error', 'Ya tiene una caja abierta');
    }
    
    $cajaId = $cajaModel->abrirCaja($_SESSION['usuario_id'], $montoInicial);
    
    respuestaJson('exito', 'Caja abierta exitosamente', [
        'caja_id' => $cajaId
    ]);

// 3. Cierra la caja actual via AJAX
} elseif ($metodo === 'cerrarCaja') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Verificar que tenga una caja abierta
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    
    if (!$cajaAbierta) {
        respuestaJson('error', 'No tiene una caja abierta');
    }
    
    // Obtener resumen de la caja
    $resumen = $cajaModel->obtenerResumenCaja($cajaAbierta['id']);
    
    // Calcular monto final (inicial + ventas en efectivo)
    $montoFinal = $cajaAbierta['monto_inicial'] + $resumen['efectivo'];
    
    // Cerrar caja
    $cajaModel->cerrarCaja($cajaAbierta['id'], $montoFinal);
    
    respuestaJson('exito', 'Caja cerrada exitosamente', [
        'resumen' => $resumen,
        'monto_inicial' => $cajaAbierta['monto_inicial'],
        'monto_final' => $montoFinal,
        'fecha_apertura' => $cajaAbierta['fecha_apertura']
    ]);

// 4. Obtiene el estado actual de la caja
} elseif ($metodo === 'estadoAjax') {
    verificarAutenticacion();
    
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    
    if ($cajaAbierta) {
        $resumen = $cajaModel->obtenerResumenCaja($cajaAbierta['id']);
    }
    
    respuestaJson('exito', 'Estado de caja obtenido', [
        'caja_abierta' => $cajaAbierta ? true : false,
        'caja' => $cajaAbierta,
        'resumen' => $cajaAbierta ? $resumen : null
    ]);

// 5. Muestra reporte de cierre de caja
} elseif ($metodo === 'reporte') {
    verificarAutenticacion();
    
    $cajaId = intval($_GET['id'] ?? 0);
    
    if ($cajaId < 1) {
        header('Location: /SP%20Perfect%20Color/caja');
        exit;
    }
    
    // Obtener datos de la caja
    $cajas = $cajaModel->listarCajas();
    $caja = null;
    
    foreach ($cajas as $c) {
        if ($c['id'] == $cajaId) {
            $caja = $c;
            break;
        }
    }
    
    if (!$caja) {
        header('Location: /SP%20Perfect%20Color/caja');
        exit;
    }
    
    $resumen = $cajaModel->obtenerResumenCaja($cajaId);
    
    $contenidoVista = __DIR__ . '/../views/cajaReporteView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}