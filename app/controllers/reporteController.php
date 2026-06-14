<?php
namespace App\Controllers;

use App\Models\ReporteModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use App\Helpers\Validacion;

$reporteModel = new ReporteModel();

// 1. Muestra la pagina de reportes
if ($metodo === 'index') {
    verificarAutenticacion();

    $contenidoVista = __DIR__ . '/../views/reporteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene datos del reporte de ventas
} elseif ($metodo === 'ventasAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $ventas = $reporteModel->ventasPorRango($desde, $hasta);
    $porTipo = $reporteModel->totalVentasPorTipoPago($desde, $hasta);
    $porMetodo = $reporteModel->totalVentasPorMetodoPago($desde, $hasta);

    $total = 0;
    foreach ($ventas as $v) { $total += floatval($v['total']); }

    respuestaJson('exito', 'Ventas obtenidas', [
        'ventas' => $ventas,
        'total' => $total,
        'cantidad' => count($ventas),
        'por_tipo_pago' => $porTipo,
        'por_metodo_pago' => $porMetodo
    ]);

// 3. Obtiene datos del reporte de ingresos
} elseif ($metodo === 'ingresosAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $data = $reporteModel->ingresosPorRango($desde, $hasta);
    $porMetodo = $reporteModel->totalIngresosPorMetodoPago($desde, $hasta);

    $total = 0;
    foreach ($data['pagos'] as $p) { $total += floatval($p['monto']); }
    foreach ($data['directos'] as $d) { $total += floatval($d['monto']); }

    respuestaJson('exito', 'Ingresos obtenidos', [
        'pagos' => $data['pagos'],
        'directos' => $data['directos'],
        'total' => $total,
        'cantidad' => count($data['pagos']) + count($data['directos']),
        'por_metodo_pago' => $porMetodo
    ]);

// 4. Obtiene datos del reporte de egresos
} elseif ($metodo === 'egresosAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $egresos = $reporteModel->egresosPorRango($desde, $hasta);
    $porMetodo = $reporteModel->totalEgresosPorMetodoPago($desde, $hasta);

    $total = 0;
    foreach ($egresos as $e) { $total += floatval($e['monto']); }

    respuestaJson('exito', 'Egresos obtenidos', [
        'egresos' => $egresos,
        'total' => $total,
        'cantidad' => count($egresos),
        'por_metodo_pago' => $porMetodo
    ]);

// Fallback
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
