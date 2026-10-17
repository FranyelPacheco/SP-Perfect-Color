<?php
// ARCHIVO: exportarReporteHelper.php
// OBJETIVO: Generar y descargar reportes en formato PDF (Dompdf) y Excel (OpenSpout)

namespace App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;

use App\Models\ReporteModel;

// FUNCIÓN: generarPDF
// OBJETIVO: Construir un reporte HTML institucional de alta fidelidad y descargarlo como PDF (A4 horizontal)
function generarPDF($tipo, $desde, $hasta, $filtros = [])
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $modelo = new ReporteModel();
    $idCliente = $filtros['id_cliente'] ?? null;
    $idProveedor = $filtros['id_proveedor'] ?? null;
    $condicion = $filtros['condicion'] ?? null;
    $idTipoPago = $filtros['id_tipo_pago'] ?? null;
    $estadoCxc = $filtros['estado_cxc'] ?? null;

    if ($tipo === 'ventas') {
        $datos = $modelo->ventasPorRango($desde, $hasta, $idCliente, $condicion, $idTipoPago);
        $titulo = 'Reporte General de Ventas (Notas de Entrega)';
        $encabezados = ['Fecha', 'Cliente', 'Cédula / RIF', 'Total ($)', 'Método de Pago', 'Condición'];
    } elseif ($tipo === 'compras') {
        $datos = $modelo->comprasPorRango($desde, $hasta, $idProveedor, $condicion);
        $titulo = 'Reporte de Compras (Recepción de Insumos)';
        $encabezados = ['Fecha', 'N° Compra', 'Proveedor', 'RIF', 'Total ($)', 'Condición', 'Estado Pago'];
    } elseif ($tipo === 'ambas') {
        $datos = $modelo->ambasPorRango($desde, $hasta);
        $titulo = 'Reporte Consolidado de Flujo (Ventas y Compras)';
        $encabezados = ['Fecha', 'Tipo', 'N° Doc', 'Tercero (Cliente / Proveedor)', 'Cédula / RIF', 'Condición', 'Ingreso ($)', 'Egreso ($)'];
    } else {
        $datos = $modelo->carteraCxc($desde, $hasta, $idCliente, $estadoCxc);
        $titulo = 'Reporte de Cuentas por Cobrar (Cartera)';
        $encabezados = ['Cliente', 'Cédula / RIF', 'Monto Total ($)', 'Saldo Pendiente ($)', 'Vencimiento', 'Situación'];
    }

    // Logo corporativo en base64
    $logoPath = dirname(__DIR__, 2) . '/assets/images/logo.png';
    $logoSrc = '';
    if (file_exists($logoPath)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $fechaGenerado = date('d/m/Y h:i A');
    $totalRegistros = count($datos);
    $totalMontoUsd = 0.0;
    $totalSaldoUsd = 0.0;
    $totalIngresosUsd = 0.0;
    $totalEgresosUsd = 0.0;

    $filasHtml = '';
    foreach ($datos as $idx => $fila) {
        $bg = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
        $filasHtml .= '<tr style="background-color: ' . $bg . ';">';

        if ($tipo === 'ventas') {
            $monto = (float)($fila['total'] ?? 0);
            $totalMontoUsd += $monto;
            $fechaFmt = !empty($fila['fecha']) ? date('d/m/Y', strtotime($fila['fecha'])) : '-';

            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . $fechaFmt . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 500;">' . htmlspecialchars($fila['cliente_nombre'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . htmlspecialchars($fila['cliente_cedula'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; font-weight: bold; color: #1e3a8a;">$ ' . number_format($monto, 2, ',', '.') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . htmlspecialchars($fila['tipo_pago_nombre'] ?? 'Efectivo') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;"><span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: ' . ($fila['condicion_pago'] === 'contado' ? '#dcfce7; color: #166534;' : '#fef9c3; color: #854d0e;') . '">' . ucfirst($fila['condicion_pago'] ?? '-') . '</span></td>';

        } elseif ($tipo === 'compras') {
            $monto = (float)($fila['total'] ?? 0);
            $totalMontoUsd += $monto;
            $fechaFmt = !empty($fila['fecha']) ? date('d/m/Y', strtotime($fila['fecha'])) : '-';

            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . $fechaFmt . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center; font-weight: 600;">#' . htmlspecialchars($fila['id_nota_entrega'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 500;">' . htmlspecialchars($fila['proveedor_nombre'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . htmlspecialchars($fila['proveedor_rif'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; font-weight: bold; color: #dc2626;">$ ' . number_format($monto, 2, ',', '.') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;"><span style="font-size: 9px; padding: 2px 6px; border-radius: 4px; background: ' . ($fila['condicion_pago'] === 'Contado' ? '#dcfce7; color: #166534;' : '#fef9c3; color: #854d0e;') . '">' . $fila['condicion_pago'] . '</span></td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . ucfirst($fila['situacion_pago'] ?? '-') . '</td>';

        } elseif ($tipo === 'ambas') {
            $ingreso = (float)($fila['monto_ingreso'] ?? 0);
            $egreso = (float)($fila['monto_egreso'] ?? 0);
            $totalIngresosUsd += $ingreso;
            $totalEgresosUsd += $egreso;
            $fechaFmt = !empty($fila['fecha']) ? date('d/m/Y', strtotime($fila['fecha'])) : '-';
            $esVenta = ($fila['tipo_transaccion'] === 'Venta');
            $badgeColor = $esVenta ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;';

            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . $fechaFmt . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;"><span style="font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 4px; ' . $badgeColor . '">' . $fila['tipo_transaccion'] . '</span></td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">#' . htmlspecialchars($fila['doc_numero'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 500;">' . htmlspecialchars($fila['tercero_nombre'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . htmlspecialchars($fila['tercero_doc'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . htmlspecialchars($fila['condicion'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; font-weight: bold; color: ' . ($ingreso > 0 ? '#16a34a' : '#94a3b8') . ';">' . ($ingreso > 0 ? '$ ' . number_format($ingreso, 2, ',', '.') : '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; font-weight: bold; color: ' . ($egreso > 0 ? '#dc2626' : '#94a3b8') . ';">' . ($egreso > 0 ? '$ ' . number_format($egreso, 2, ',', '.') : '-') . '</td>';

        } else {
            $monto = (float)($fila['monto_total'] ?? 0);
            $saldo = (float)($fila['saldo_pendiente'] ?? 0);
            $totalMontoUsd += $monto;
            $totalSaldoUsd += $saldo;
            $vencFmt = !empty($fila['fecha_vencimiento']) ? date('d/m/Y', strtotime($fila['fecha_vencimiento'])) : '-';

            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: 500;">' . htmlspecialchars($fila['cliente_nombre'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . htmlspecialchars($fila['cliente_cedula'] ?? '-') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right;">$ ' . number_format($monto, 2, ',', '.') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; font-weight: bold; color: #dc2626;">$ ' . number_format($saldo, 2, ',', '.') . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . $vencFmt . '</td>';
            $filasHtml .= '<td style="padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">' . ucfirst($fila['estado'] ?? '-') . '</td>';
        }

        $filasHtml .= '</tr>';
    }

    // Fila de totales
    if ($tipo === 'ventas') {
        $filaTotales = '
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="3" style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #1e293b;">TOTAL GENERAL:</td>
            <td style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #1e3a8a;">$ ' . number_format($totalMontoUsd, 2, ',', '.') . '</td>
            <td colspan="2" style="border-top: 2px solid #cbd5e1;"></td>
        </tr>';
    } elseif ($tipo === 'compras') {
        $filaTotales = '
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="4" style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #1e293b;">TOTAL COMPRAS:</td>
            <td style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #dc2626;">$ ' . number_format($totalMontoUsd, 2, ',', '.') . '</td>
            <td colspan="2" style="border-top: 2px solid #cbd5e1;"></td>
        </tr>';
    } elseif ($tipo === 'ambas') {
        $balanceNeto = $totalIngresosUsd - $totalEgresosUsd;
        $colorBalance = $balanceNeto >= 0 ? '#16a34a' : '#dc2626';
        $filaTotales = '
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="6" style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #1e293b;">TOTALES:</td>
            <td style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #16a34a;">$ ' . number_format($totalIngresosUsd, 2, ',', '.') . '</td>
            <td style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #dc2626;">$ ' . number_format($totalEgresosUsd, 2, ',', '.') . '</td>
        </tr>
        <tr style="background-color: #e2e8f0; font-weight: bold;">
            <td colspan="6" style="padding: 9px 8px; text-align: right; border-top: 1px solid #cbd5e1; font-size: 11px; color: #0f172a;">BALANCE OPERATIVO NETO:</td>
            <td colspan="2" style="padding: 9px 8px; text-align: right; border-top: 1px solid #cbd5e1; font-size: 12px; color: ' . $colorBalance . ';">$ ' . number_format($balanceNeto, 2, ',', '.') . '</td>
        </tr>';
    } else {
        $filaTotales = '
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="2" style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #1e293b;">TOTALES:</td>
            <td style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px;">$ ' . number_format($totalMontoUsd, 2, ',', '.') . '</td>
            <td style="padding: 9px 8px; text-align: right; border-top: 2px solid #cbd5e1; font-size: 11px; color: #dc2626;">$ ' . number_format($totalSaldoUsd, 2, ',', '.') . '</td>
            <td colspan="2" style="border-top: 2px solid #cbd5e1;"></td>
        </tr>';
    }

    $thHeaders = '';
    foreach ($encabezados as $e) {
        $thHeaders .= '<th style="padding: 8px; text-align: center; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">' . $e . '</th>';
    }

    $logoHtml = '';
    if ($logoSrc !== '') {
        $logoHtml = '<td style="padding-right: 12px; width: 60px;"><img src="' . $logoSrc . '" style="height: 48px;" alt="Logo"></td>';
    }

    // Tarjetas KPI según el tipo
    $kpisHtml = '';
    if ($tipo === 'ventas') {
        $kpisHtml = '
        <tr>
            <td style="width: 50%; padding: 0 8px 0 0;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #1d4ed8; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL DE VENTAS</span>
                    <strong style="font-size: 16px; color: #1e293b;">' . $totalRegistros . '</strong>
                </div>
            </td>
            <td style="width: 50%; padding: 0 0 0 8px;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #16a34a; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">MONTO TOTAL FACTURADO</span>
                    <strong style="font-size: 16px; color: #1e3a8a;">$ ' . number_format($totalMontoUsd, 2, ',', '.') . '</strong>
                </div>
            </td>
        </tr>';
    } elseif ($tipo === 'compras') {
        $kpisHtml = '
        <tr>
            <td style="width: 50%; padding: 0 8px 0 0;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #1d4ed8; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL DE COMPRAS</span>
                    <strong style="font-size: 16px; color: #1e293b;">' . $totalRegistros . '</strong>
                </div>
            </td>
            <td style="width: 50%; padding: 0 0 0 8px;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #dc2626; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL GASTO EN INSUMOS</span>
                    <strong style="font-size: 16px; color: #dc2626;">$ ' . number_format($totalMontoUsd, 2, ',', '.') . '</strong>
                </div>
            </td>
        </tr>';
    } elseif ($tipo === 'ambas') {
        $balanceNeto = $totalIngresosUsd - $totalEgresosUsd;
        $colorBalance = $balanceNeto >= 0 ? '#16a34a' : '#dc2626';
        $kpisHtml = '
        <tr>
            <td style="width: 33.33%; padding: 0 4px 0 0;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #16a34a; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL INGRESOS (VENTAS)</span>
                    <strong style="font-size: 15px; color: #16a34a;">$ ' . number_format($totalIngresosUsd, 2, ',', '.') . '</strong>
                </div>
            </td>
            <td style="width: 33.33%; padding: 0 4px;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #dc2626; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL EGRESOS (COMPRAS)</span>
                    <strong style="font-size: 15px; color: #dc2626;">$ ' . number_format($totalEgresosUsd, 2, ',', '.') . '</strong>
                </div>
            </td>
            <td style="width: 33.33%; padding: 0 0 0 4px;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid ' . $colorBalance . '; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">BALANCE NETO</span>
                    <strong style="font-size: 15px; color: ' . $colorBalance . ';">$ ' . number_format($balanceNeto, 2, ',', '.') . '</strong>
                </div>
            </td>
        </tr>';
    } else {
        $kpisHtml = '
        <tr>
            <td style="width: 50%; padding: 0 8px 0 0;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #1d4ed8; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL DE CUENTAS</span>
                    <strong style="font-size: 16px; color: #1e293b;">' . $totalRegistros . '</strong>
                </div>
            </td>
            <td style="width: 50%; padding: 0 0 0 8px;">
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-left: 4px solid #dc2626; padding: 8px 12px; border-radius: 4px;">
                    <span style="font-size: 10px; color: #64748b; display: block;">TOTAL SALDO PENDIENTE</span>
                    <strong style="font-size: 16px; color: #dc2626;">$ ' . number_format($totalSaldoUsd, 2, ',', '.') . '</strong>
                </div>
            </td>
        </tr>';
    }

    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($titulo) . '</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 9.5px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .report-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 8px 12px;
            text-align: right;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 3px 0;
        }
        .report-meta {
            font-size: 9.5px;
            color: #475569;
            margin: 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table thead tr {
            background-color: #1d4ed8;
            color: #ffffff;
        }
        .footer-note {
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                <table style="border-collapse: collapse;">
                    <tr>
                        ' . $logoHtml . '
                        <td>
                            <div class="company-name">SP PERFECT COLOR, C.A.</div>
                            <div class="company-subtitle">Colorimetría Automotriz &amp; Acabados Profesionales | RIF: J-30982542-0</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%; vertical-align: middle;">
                <div class="report-box">
                    <div class="report-title">' . htmlspecialchars($titulo) . '</div>
                    <p class="report-meta"><strong>Rango:</strong> ' . htmlspecialchars($desde) . ' al ' . htmlspecialchars($hasta) . '</p>
                    <p class="report-meta"><strong>Emitido:</strong> ' . $fechaGenerado . '</p>
                </div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        ' . $kpisHtml . '
    </table>

    <table class="data-table">
        <thead>
            <tr>
                ' . $thHeaders . '
            </tr>
        </thead>
        <tbody>
            ' . $filasHtml . '
            ' . $filaTotales . '
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Sistema de Gestión Administrativa SP Perfect Color.
    </div>
</body>
</html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $dompdf->stream('reporte_' . $tipo . '_' . $desde . '_a_' . $hasta . '.pdf', ['Attachment' => true]);
    exit;
}

// FUNCIÓN: generarExcel
// OBJETIVO: Construir un archivo XLSX profesional con estilos corporativos y descargarlo de inmediato
function generarExcel($tipo, $desde, $hasta, $filtros = [])
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $modelo = new ReporteModel();
    $idCliente = $filtros['id_cliente'] ?? null;
    $idProveedor = $filtros['id_proveedor'] ?? null;
    $condicion = $filtros['condicion'] ?? null;
    $idTipoPago = $filtros['id_tipo_pago'] ?? null;
    $estadoCxc = $filtros['estado_cxc'] ?? null;

    if ($tipo === 'ventas') {
        $datos = $modelo->ventasPorRango($desde, $hasta, $idCliente, $condicion, $idTipoPago);
        $titulo = 'Reporte General de Ventas (Notas de Entrega)';
        $encabezados = ['Fecha', 'Cliente', 'Cédula / RIF', 'Total ($)', 'Método de Pago', 'Condición'];
    } elseif ($tipo === 'compras') {
        $datos = $modelo->comprasPorRango($desde, $hasta, $idProveedor, $condicion);
        $titulo = 'Reporte de Compras (Recepción de Insumos)';
        $encabezados = ['Fecha', 'N° Compra', 'Proveedor', 'RIF', 'Total ($)', 'Condición', 'Estado Pago'];
    } elseif ($tipo === 'ambas') {
        $datos = $modelo->ambasPorRango($desde, $hasta);
        $titulo = 'Reporte Consolidado de Flujo (Ventas y Compras)';
        $encabezados = ['Fecha', 'Tipo', 'N° Doc', 'Tercero (Cliente / Proveedor)', 'Cédula / RIF', 'Condición', 'Ingreso ($)', 'Egreso ($)'];
    } else {
        $datos = $modelo->carteraCxc($desde, $hasta, $idCliente, $estadoCxc);
        $titulo = 'Reporte de Cuentas por Cobrar (Cartera)';
        $encabezados = ['Cliente', 'Cédula / RIF', 'Monto Total ($)', 'Saldo Pendiente ($)', 'Vencimiento', 'Situación'];
    }

    // Estilos OpenSpout
    $titleStyle = (new Style())
        ->setFontBold()
        ->setFontSize(14)
        ->setFontColor(Color::toARGB('1E3A8A'));

    $subTitleStyle = (new Style())
        ->setFontSize(10)
        ->setFontColor(Color::toARGB('475569'));

    $headerStyle = (new Style())
        ->setFontBold()
        ->setFontSize(11)
        ->setFontColor(Color::toARGB('FFFFFF'))
        ->setBackgroundColor(Color::toARGB('1D4ED8'));

    $totalStyle = (new Style())
        ->setFontBold()
        ->setFontSize(11)
        ->setBackgroundColor(Color::toARGB('F1F5F9'));

    $balanceStyle = (new Style())
        ->setFontBold()
        ->setFontSize(11)
        ->setBackgroundColor(Color::toARGB('E2E8F0'));

    $writer = new Writer();
    $writer->openToBrowser('reporte_' . $tipo . '_' . $desde . '_a_' . $hasta . '.xlsx');

    // Encabezado corporativo
    $writer->addRow(Row::fromValues(['SP PERFECT COLOR, C.A. - RIF: J-30982542-0'], $titleStyle));
    $writer->addRow(Row::fromValues([$titulo], (new Style())->setFontBold()->setFontSize(12)));
    $writer->addRow(Row::fromValues(['Período: ' . $desde . ' al ' . $hasta . ' | Generado: ' . date('d/m/Y h:i A')], $subTitleStyle));
    $writer->addRow(Row::fromValues([])); // Fila vacía separadora

    // Encabezados de tabla
    $writer->addRow(Row::fromValues($encabezados, $headerStyle));

    $totalMonto = 0.0;
    $totalSaldo = 0.0;
    $totalIngresos = 0.0;
    $totalEgresos = 0.0;

    foreach ($datos as $fila) {
        if ($tipo === 'ventas') {
            $monto = floatval($fila['total'] ?? 0);
            $totalMonto += $monto;

            $writer->addRow(Row::fromValues([
                $fila['fecha'],
                $fila['cliente_nombre'] ?? '-',
                $fila['cliente_cedula'] ?? '-',
                $monto,
                $fila['tipo_pago_nombre'] ?? 'Efectivo',
                ucfirst($fila['condicion_pago'] ?? '-')
            ]));

        } elseif ($tipo === 'compras') {
            $monto = floatval($fila['total'] ?? 0);
            $totalMonto += $monto;

            $writer->addRow(Row::fromValues([
                $fila['fecha'],
                '#' . ($fila['id_nota_entrega'] ?? '-'),
                $fila['proveedor_nombre'] ?? '-',
                $fila['proveedor_rif'] ?? '-',
                $monto,
                $fila['condicion_pago'] ?? '-',
                ucfirst($fila['situacion_pago'] ?? '-')
            ]));

        } elseif ($tipo === 'ambas') {
            $ingreso = floatval($fila['monto_ingreso'] ?? 0);
            $egreso = floatval($fila['monto_egreso'] ?? 0);
            $totalIngresos += $ingreso;
            $totalEgresos += $egreso;

            $writer->addRow(Row::fromValues([
                $fila['fecha'],
                $fila['tipo_transaccion'],
                '#' . ($fila['doc_numero'] ?? '-'),
                $fila['tercero_nombre'] ?? '-',
                $fila['tercero_doc'] ?? '-',
                $fila['condicion'] ?? '-',
                $ingreso,
                $egreso
            ]));

        } else {
            $monto = floatval($fila['monto_total'] ?? 0);
            $saldo = floatval($fila['saldo_pendiente'] ?? 0);
            $totalMonto += $monto;
            $totalSaldo += $saldo;

            $writer->addRow(Row::fromValues([
                $fila['cliente_nombre'] ?? '-',
                $fila['cliente_cedula'] ?? '-',
                $monto,
                $saldo,
                $fila['fecha_vencimiento'] ?? '-',
                ucfirst($fila['estado'] ?? '-')
            ]));
        }
    }

    // Fila de totales
    if ($tipo === 'ventas') {
        $writer->addRow(Row::fromValues([
            'TOTAL GENERAL',
            '',
            '',
            $totalMonto,
            '',
            ''
        ], $totalStyle));
    } elseif ($tipo === 'compras') {
        $writer->addRow(Row::fromValues([
            'TOTAL COMPRAS',
            '',
            '',
            '',
            $totalMonto,
            '',
            ''
        ], $totalStyle));
    } elseif ($tipo === 'ambas') {
        $balanceNeto = $totalIngresos - $totalEgresos;
        $writer->addRow(Row::fromValues([
            'TOTALES',
            '',
            '',
            '',
            '',
            '',
            $totalIngresos,
            $totalEgresos
        ], $totalStyle));
        $writer->addRow(Row::fromValues([
            'BALANCE OPERATIVO NETO',
            '',
            '',
            '',
            '',
            '',
            $balanceNeto,
            ''
        ], $balanceStyle));
    } else {
        $writer->addRow(Row::fromValues([
            'TOTALES',
            '',
            $totalMonto,
            $totalSaldo,
            '',
            ''
        ], $totalStyle));
    }

    $writer->close();
    exit;
}
