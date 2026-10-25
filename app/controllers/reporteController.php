<?php
namespace App\Controllers;

use App\Models\{
    ReporteModel,
    ClienteModel,
    ProveedorModel,
    TipoPagoModel
};
use function App\Helpers\{
    respuestaJson,
    verificarPermiso,
    generarPDF,
    generarExcel,
    validarFecha
};

$reporteModel = new ReporteModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la página principal de reportes con selector de tipo, rango de fechas y filtros
if ($metodo === 'index') {
    verificarPermiso('reporte');

    $clienteModel = new ClienteModel();
    $proveedorModel = new ProveedorModel();
    $tipoPagoModel = new TipoPagoModel();
    $clientes = $clienteModel->listarTodos();
    $proveedores = $proveedorModel->listarTodos();
    $tiposPago = $tipoPagoModel->listarTodos();

    $contenidoVista = __DIR__ . '/../views/reporteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: ventasAjax
// OBJETIVO: Devuelve JSON con las notas de entrega (ventas) filtradas por rango de fechas y filtros adicionales
} elseif ($metodo === 'ventasAjax') {
    verificarPermiso('reporte');

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $idCliente = !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
    $condicion = !empty($_GET['condicion']) ? $_GET['condicion'] : null;
    $idTipoPago = !empty($_GET['id_tipo_pago']) ? intval($_GET['id_tipo_pago']) : null;

    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $hoy = date('Y-m-d');
    if ($desde > $hoy || $hasta > $hoy) {
        respuestaJson('error', 'Las fechas no pueden ser posteriores a la fecha actual (' . $hoy . ')');
    }

    $ventas = $reporteModel->ventasPorRango($desde, $hasta, $idCliente, $condicion, $idTipoPago);

    $total = 0;
    foreach ($ventas as $v) { $total += floatval($v['total']); }

    respuestaJson('exito', 'Ventas obtenidas', [
        'ventas' => $ventas,
        'total' => $total,
        'cantidad' => count($ventas)
    ]);

// FUNCIÓN: comprasAjax
// OBJETIVO: Devuelve JSON con las recepciones de compras a proveedores filtradas por rango de fechas
} elseif ($metodo === 'comprasAjax') {
    verificarPermiso('reporte');

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $idProveedor = !empty($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : null;
    $condicion = !empty($_GET['condicion']) ? $_GET['condicion'] : null;

    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $hoy = date('Y-m-d');
    if ($desde > $hoy || $hasta > $hoy) {
        respuestaJson('error', 'Las fechas no pueden ser posteriores a la fecha actual (' . $hoy . ')');
    }

    $compras = $reporteModel->comprasPorRango($desde, $hasta, $idProveedor, $condicion);

    $total = 0;
    foreach ($compras as $c) { $total += floatval($c['total']); }

    respuestaJson('exito', 'Compras obtenidas', [
        'compras' => $compras,
        'total' => $total,
        'cantidad' => count($compras)
    ]);

// FUNCIÓN: ambasAjax
// OBJETIVO: Devuelve JSON consolidado con ventas y compras para ver balance y flujo operativo
} elseif ($metodo === 'ambasAjax') {
    verificarPermiso('reporte');

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $hoy = date('Y-m-d');
    if ($desde > $hoy || $hasta > $hoy) {
        respuestaJson('error', 'Las fechas no pueden ser posteriores a la fecha actual (' . $hoy . ')');
    }

    $ambas = $reporteModel->ambasPorRango($desde, $hasta);

    $totalIngresos = 0;
    $totalEgresos = 0;
    foreach ($ambas as $row) {
        $totalIngresos += floatval($row['monto_ingreso']);
        $totalEgresos += floatval($row['monto_egreso']);
    }
    $balanceNeto = $totalIngresos - $totalEgresos;

    respuestaJson('exito', 'Flujo consolidado obtenido', [
        'items' => $ambas,
        'total_ingresos' => $totalIngresos,
        'total_egresos' => $totalEgresos,
        'balance_neto' => $balanceNeto,
        'cantidad' => count($ambas)
    ]);

// FUNCIÓN: carteraCxcAjax
// OBJETIVO: Devuelve JSON con las cuentas por cobrar filtradas por rango de fechas y filtros adicionales
} elseif ($metodo === 'carteraCxcAjax') {
    verificarPermiso('reporte');

    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $idCliente = !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
    $estado = !empty($_GET['estado_cxc']) ? $_GET['estado_cxc'] : null;

    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $hoy = date('Y-m-d');
    if ($desde > $hoy || $hasta > $hoy) {
        respuestaJson('error', 'Las fechas no pueden ser posteriores a la fecha actual (' . $hoy . ')');
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
// OBJETIVO: Genera y descarga un archivo PDF del reporte seleccionado (ventas, compras, ambas o carteraCxc)
} elseif ($metodo === 'exportarPdfAjax') {
    verificarPermiso('reporte');
    $tipo = $_GET['tipo'] ?? '';
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $tiposValidos = ['ventas', 'compras', 'ambas', 'carteraCxc'];
    if (!in_array($tipo, $tiposValidos)) {
        respuestaJson('error', 'Tipo de reporte no valido');
    }
    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $hoy = date('Y-m-d');
    if ($desde > $hoy || $hasta > $hoy) {
        respuestaJson('error', 'Las fechas no pueden ser posteriores a la fecha actual (' . $hoy . ')');
    }
    $filtros = [
        'id_cliente' => !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null,
        'id_proveedor' => !empty($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : null,
        'condicion' => !empty($_GET['condicion']) ? $_GET['condicion'] : null,
        'id_tipo_pago' => !empty($_GET['id_tipo_pago']) ? intval($_GET['id_tipo_pago']) : null,
        'estado_cxc' => !empty($_GET['estado_cxc']) ? $_GET['estado_cxc'] : null,
    ];
    generarPDF($tipo, $desde, $hasta, $filtros);

// FUNCIÓN: exportarExcelAjax
// OBJETIVO: Genera y descarga un archivo Excel (XLSX) del reporte seleccionado (ventas, compras, ambas o carteraCxc)
} elseif ($metodo === 'exportarExcelAjax') {
    verificarPermiso('reporte');
    $tipo = $_GET['tipo'] ?? '';
    $desde = $_GET['desde'] ?? date('Y-m-01');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');

    $tiposValidos = ['ventas', 'compras', 'ambas', 'carteraCxc'];
    if (!in_array($tipo, $tiposValidos)) {
        respuestaJson('error', 'Tipo de reporte no valido');
    }
    if (!validarFecha($desde) || !validarFecha($hasta)) {
        respuestaJson('error', 'Formato de fecha invalido (use YYYY-MM-DD)');
    }
    $hoy = date('Y-m-d');
    if ($desde > $hoy || $hasta > $hoy) {
        respuestaJson('error', 'Las fechas no pueden ser posteriores a la fecha actual (' . $hoy . ')');
    }
    $filtros = [
        'id_cliente' => !empty($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null,
        'id_proveedor' => !empty($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : null,
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
