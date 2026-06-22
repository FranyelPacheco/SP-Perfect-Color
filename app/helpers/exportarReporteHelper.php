<?php
// ARCHIVO: exportarReporteHelper.php
// OBJETIVO: Generar y descargar reportes en formato PDF (Dompdf) y Excel (OpenSpout)

namespace App\Helpers;

use Dompdf\Dompdf;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

use App\Models\ReporteModel;

// FUNCIÓN: generarPDF
// OBJETIVO: Construir una tabla HTML con los datos del reporte y descargarla como PDF (A4 horizontal)
// NOTA: Soporta dos tipos de reporte: 'ventas' (Notas de Entrega) y 'carteraCxc' (Cuentas por Cobrar)
function generarPDF($tipo, $desde, $hasta, $filtros = [])
{
    $modelo = new ReporteModel();
    $idCliente = $filtros['id_cliente'] ?? null;
    $condicion = $filtros['condicion'] ?? null;
    $idTipoPago = $filtros['id_tipo_pago'] ?? null;
    $estadoCxc = $filtros['estado_cxc'] ?? null;

    if ($tipo === 'ventas') {
        $datos = $modelo->ventasPorRango($desde, $hasta, $idCliente, $condicion, $idTipoPago);
        $titulo = 'Reporte de Notas de Entrega';
        $encabezados = ['Fecha', 'Cliente', 'Cedula', 'Total', 'Metodo Pago', 'Estado'];
    } else {
        $datos = $modelo->carteraCxc($desde, $hasta, $idCliente, $estadoCxc);
        $titulo = 'Reporte de Cuentas por Cobrar Pendientes';
        $encabezados = ['Cliente', 'Cedula', 'Monto Total', 'Saldo Pendiente', 'Vencimiento', 'Estado'];
    }

    $html = '<h1 style="text-align:center;">' . $titulo . '</h1>';
    $html .= '<p style="text-align:center;">Desde: ' . $desde . ' - Hasta: ' . $hasta . '</p>';
    $html .= '<p style="text-align:center;">Total registros: ' . count($datos) . '</p>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;border-collapse:collapse;">';
    $html .= '<thead><tr style="background:#1D4ED8;color:#fff;">';
    foreach ($encabezados as $e) {
        $html .= '<th>' . $e . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $html .= '<tr>';
        if ($tipo === 'ventas') {
            $html .= '<td>' . $fila['fecha'] . '</td>';
            $html .= '<td>' . ($fila['cliente_nombre'] ?? '-') . '</td>';
            $html .= '<td>' . ($fila['cliente_cedula'] ?? '-') . '</td>';
            $html .= '<td>$ ' . number_format($fila['total'], 2) . '</td>';
            $html .= '<td>' . ($fila['tipo_pago_nombre'] ?? '-') . '</td>';
            $html .= '<td>' . $fila['estado'] . '</td>';
        } else {
            $html .= '<td>' . ($fila['cliente_nombre'] ?? '-') . '</td>';
            $html .= '<td>' . ($fila['cliente_cedula'] ?? '-') . '</td>';
            $html .= '<td>$ ' . number_format($fila['monto_total'], 2) . '</td>';
            $html .= '<td>$ ' . number_format($fila['saldo_pendiente'], 2) . '</td>';
            $html .= '<td>' . ($fila['fecha_vencimiento'] ?? '-') . '</td>';
            $html .= '<td>' . $fila['estado'] . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('reporte_' . $tipo . '_' . $desde . '_a_' . $hasta . '.pdf', ['Attachment' => true]);
    exit;
}

// FUNCIÓN: generarExcel
// OBJETIVO: Construir un archivo XLSX con los datos del reporte y forzar su descarga en el navegador
// NOTA: Usa OpenSpout Writer; los encabezados y datos varían según el tipo de reporte
function generarExcel($tipo, $desde, $hasta, $filtros = [])
{
    $modelo = new ReporteModel();
    $idCliente = $filtros['id_cliente'] ?? null;
    $condicion = $filtros['condicion'] ?? null;
    $idTipoPago = $filtros['id_tipo_pago'] ?? null;
    $estadoCxc = $filtros['estado_cxc'] ?? null;

    if ($tipo === 'ventas') {
        $datos = $modelo->ventasPorRango($desde, $hasta, $idCliente, $condicion, $idTipoPago);
        $encabezados = ['Fecha', 'Cliente', 'Cedula', 'Total', 'Metodo Pago', 'Estado'];
    } else {
        $datos = $modelo->carteraCxc($desde, $hasta, $idCliente, $estadoCxc);
        $encabezados = ['Cliente', 'Cedula', 'Monto Total', 'Saldo Pendiente', 'Vencimiento', 'Estado'];
    }

    $writer = new Writer();
    $writer->openToBrowser('reporte_' . $tipo . '_' . $desde . '_a_' . $hasta . '.xlsx');
    $writer->addRow(Row::fromValues($encabezados));

    foreach ($datos as $fila) {
        if ($tipo === 'ventas') {
            $writer->addRow(Row::fromValues([
                $fila['fecha'],
                $fila['cliente_nombre'] ?? '-',
                $fila['cliente_cedula'] ?? '-',
                floatval($fila['total']),
                $fila['tipo_pago_nombre'] ?? '-',
                $fila['estado']
            ]));
        } else {
            $writer->addRow(Row::fromValues([
                $fila['cliente_nombre'] ?? '-',
                $fila['cliente_cedula'] ?? '-',
                floatval($fila['monto_total']),
                floatval($fila['saldo_pendiente']),
                $fila['fecha_vencimiento'] ?? '-',
                $fila['estado']
            ]));
        }
    }

    $writer->close();
    exit;
}
