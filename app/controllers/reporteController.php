<?php
namespace App\Controllers;

use App\Models\ReporteModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\generarPDF;
use function App\Helpers\generarExcel;

$reporteModel = new ReporteModel();

// 1. Muestra la pagina de reportes
if ($metodo === 'index') {
    verificarAutenticacion();

    $contenidoVista = __DIR__ . '/../views/reporteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene datos del reporte de notas de entrega
} elseif ($metodo === 'ventasAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $ventas = $reporteModel->ventasPorRango($desde, $hasta);

    $total = 0;
    foreach ($ventas as $v) { $total += floatval($v['total']); }

    respuestaJson('exito', 'Ventas obtenidas', [
        'ventas' => $ventas,
        'total' => $total,
        'cantidad' => count($ventas)
    ]);

// 3. Obtiene datos del reporte de cuentas por cobrar pendientes
} elseif ($metodo === 'carteraCxcAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $cuentas = $reporteModel->carteraCxc($desde, $hasta);

    $totalSaldo = 0;
    foreach ($cuentas as $c) { $totalSaldo += floatval($c['saldo_pendiente']); }

    respuestaJson('exito', 'Cartera obtenida', [
        'cuentas' => $cuentas,
        'total_saldo' => $totalSaldo,
        'cantidad' => count($cuentas)
    ]);

// 4. Exporta el reporte actual a PDF
} elseif ($metodo === 'exportarPdfAjax') {
    verificarAutenticacion();
    $tipo = $_GET['tipo'] ?? '';
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    generarPDF($tipo, $desde, $hasta);

// 5. Exporta el reporte actual a Excel
} elseif ($metodo === 'exportarExcelAjax') {
    verificarAutenticacion();
    $tipo = $_GET['tipo'] ?? '';
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    generarExcel($tipo, $desde, $hasta);

// Fallback
} else {
    require_once __DIR__ . '/../views/error404View.php';
}