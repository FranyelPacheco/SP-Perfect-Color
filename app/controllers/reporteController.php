<?php
namespace App\Controllers;

use App\Models\ReporteModel;
use App\Models\ClienteModel;
use App\Models\TipoPagoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\generarPDF;
use function App\Helpers\generarExcel;
use function App\Helpers\validarFecha;

$reporteModel = new ReporteModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la página principal de reportes con selector de tipo, rango de fechas y filtros
if ($metodo === 'index') {
    verificarAutenticacion();

    $clienteModel = new ClienteModel();
    $tipoPagoModel = new TipoPagoModel();
    $clientes = $clienteModel->listarTodos();
    $tiposPago = $tipoPagoModel->listarTodos();

    $contenidoVista = __DIR__ . '/../views/reporteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: ventasAjax
// OBJETIVO: Devuelve JSON con las notas de entrega (ventas) filtradas por rango de fechas y filtros adicionales
} elseif ($metodo === 'ventasAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $idCliente = !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
    $condicion = !empty($_GET['condicion']) ? $_GET['condicion'] : null;
    $idTipoPago = !empty($_GET['id_tipo_pago']) ? intval($_GET['id_tipo_pago']) : null;

    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }

    $ventas = $reporteModel->ventasPorRango($desde, $hasta, $idCliente, $condicion, $idTipoPago);

    $total = 0;
    foreach ($ventas as $v) { $total += floatval($v['total']); }

    respuestaJson('exito', 'Ventas obtenidas', [
        'ventas' => $ventas,
        'total' => $total,
        'cantidad' => count($ventas)
    ]);

// FUNCIÓN: carteraCxcAjax
// OBJETIVO: Devuelve JSON con las cuentas por cobrar filtradas por rango de fechas y filtros adicionales
} elseif ($metodo === 'carteraCxcAjax') {
    verificarAutenticacion();

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $idCliente = !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
    $estado = !empty($_GET['estado_cxc']) ? $_GET['estado_cxc'] : null;

    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }

    $cuentas = $reporteModel->carteraCxc($desde, $hasta, $idCliente, $estado);

    $totalSaldo = 0;
    foreach ($cuentas as $c) { $totalSaldo += floatval($c['saldo_pendiente']); }

    respuestaJson('exito', 'Cartera obtenida', [
        'cuentas' => $cuentas,
        'total_saldo' => $totalSaldo,
        'cantidad' => count($cuentas)
    ]);

// FUNCIÓN: exportarPdfAjax
// OBJETIVO: Genera y descarga un archivo PDF del reporte seleccionado (ventas o carteraCxc)
// NOTA: Usa window.location.href desde el cliente porque la respuesta es binaria; Dompdf renderiza en horizontal
} elseif ($metodo === 'exportarPdfAjax') {
    verificarAutenticacion();
    $tipo = $_GET['tipo'] ?? '';
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $tiposValidos = ['ventas', 'carteraCxc'];
    if (!in_array($tipo, $tiposValidos)) {
        respuestaJson('error', 'Tipo de reporte no valido');
    }
    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $filtros = [
        'id_cliente' => !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null,
        'condicion' => !empty($_GET['condicion']) ? $_GET['condicion'] : null,
        'id_tipo_pago' => !empty($_GET['id_tipo_pago']) ? intval($_GET['id_tipo_pago']) : null,
        'estado_cxc' => !empty($_GET['estado_cxc']) ? $_GET['estado_cxc'] : null,
    ];
    generarPDF($tipo, $desde, $hasta, $filtros);

// FUNCIÓN: exportarExcelAjax
// OBJETIVO: Genera y descarga un archivo Excel (XLSX) del reporte seleccionado (ventas o carteraCxc)
// NOTA: Usa OpenSpout Writer; la descarga se maneja desde el servidor con headers adecuados
} elseif ($metodo === 'exportarExcelAjax') {
    verificarAutenticacion();
    $tipo = $_GET['tipo'] ?? '';
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $tiposValidos = ['ventas', 'carteraCxc'];
    if (!in_array($tipo, $tiposValidos)) {
        respuestaJson('error', 'Tipo de reporte no valido');
    }
    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $filtros = [
        'id_cliente' => !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null,
        'condicion' => !empty($_GET['condicion']) ? $_GET['condicion'] : null,
        'id_tipo_pago' => !empty($_GET['id_tipo_pago']) ? intval($_GET['id_tipo_pago']) : null,
        'estado_cxc' => !empty($_GET['estado_cxc']) ? $_GET['estado_cxc'] : null,
    ];
    generarExcel($tipo, $desde, $hasta, $filtros);

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
