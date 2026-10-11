<?php
// ARCHIVO: notaEntregaPdfHelper.php
// OBJETIVO: Generar documento PDF profesional para Notas de Entrega / Comprobantes de Venta

namespace App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Genera y descarga o transmite el PDF de la Nota de Entrega
 *
 * @param array $nota Datos de cabecera de la nota de entrega
 * @param array $detalle Lista de items despachados
 * @param float|null $tasaBs Tasa de cambio oficial USD -> Bs.
 * @param bool $descargar True para forzar descarga, False para vista previa en navegador
 */
function generarPdfNotaEntrega(array $nota, array $detalle, ?float $tasaBs = null, bool $descargar = false): void
{
    // Cargar logo en base64 para compatibilidad total con Dompdf
    $logoPath = dirname(__DIR__, 2) . '/assets/images/logo.png';
    $logoSrc = '';
    if (file_exists($logoPath)) {
        $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $idNota = str_pad((string)($nota['id_nota_entrega'] ?? 0), 6, '0', STR_PAD_LEFT);
    $fechaEmision = !empty($nota['fecha']) ? date('d/m/Y h:i A', strtotime($nota['fecha'])) : date('d/m/Y');
    $clienteNombre = htmlspecialchars($nota['cliente_nombre'] ?? 'Consumidor Final');
    $clienteCedula = htmlspecialchars($nota['cliente_cedula'] ?? 'N/A');
    $clienteTelefono = htmlspecialchars($nota['cliente_telefonos'] ?? 'N/A');
    $clienteDireccion = htmlspecialchars($nota['cliente_direccion'] ?? 'Ciudad');
    $vendedor = htmlspecialchars($nota['usuario_nombre'] ?? 'Administración');
    $condicionPago = strtoupper($nota['condicion_pago'] ?? 'CONTADO');
    $tipoPago = htmlspecialchars($nota['tipo_pago_nombre'] ?? 'Efectivo');
    $totalUsd = (float)($nota['total'] ?? 0);
    $totalBs = $tasaBs ? ($totalUsd * $tasaBs) : 0;

    $bancoInfo = '';
    if (!empty($nota['id_banco'])) {
        $bancoInfo = ' | Banco ID: ' . $nota['id_banco'];
    }
    if (!empty($nota['referencia'])) {
        $bancoInfo .= ' - Ref: ' . htmlspecialchars($nota['referencia']);
    }

    $filasHtml = '';
    $i = 1;
    foreach ($detalle as $item) {
        $codigo = htmlspecialchars($item['insumo_codigo'] ?? 'ITEM-' . $i);
        $nombre = htmlspecialchars($item['insumo_nombre'] ?? $item['descripcion'] ?? 'Producto');
        $cant = (float)($item['cantidad'] ?? 0);
        $puUsd = (float)($item['precio_unitario'] ?? 0);
        $subUsd = (float)($item['subtotal'] ?? ($cant * $puUsd));
        $subBs = $tasaBs ? ($subUsd * $tasaBs) : 0;

        $cantFormateada = (floor($cant) == $cant) ? number_format($cant, 0) : number_format($cant, 4, ',', '.');
        $puFormateado = number_format($puUsd, 2, ',', '.');
        $subUsdFormateado = number_format($subUsd, 2, ',', '.');
        $subBsFormateado = $tasaBs ? number_format($subBs, 2, ',', '.') : '-';

        $bgFila = ($i % 2 === 0) ? '#f8fafc' : '#ffffff';

        $filasHtml .= "
        <tr style=\"background-color: {$bgFila};\">
            <td style=\"text-align: center; padding: 7px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px;\">{$i}</td>
            <td style=\"padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: bold; color: #1e3a8a;\">{$codigo}</td>
            <td style=\"padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px;\">{$nombre}</td>
            <td style=\"text-align: center; padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: bold;\">{$cantFormateada}</td>
            <td style=\"text-align: right; padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px;\">\$ {$puFormateado}</td>
            <td style=\"text-align: right; padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: bold;\">\$ {$subUsdFormateado}</td>
            " . ($tasaBs ? "<td style=\"text-align: right; padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; color: #475569;\">Bs. {$subBsFormateado}</td>" : "") . "
        </tr>";
        $i++;
    }

    $tasaHtmlHeader = $tasaBs ? "<th style=\"text-align: right; padding: 8px; color: #ffffff; font-size: 10px;\">Subtotal (Bs.)</th>" : "";

    $bloqueTasa = "";
    if ($tasaBs && $tasaBs > 0) {
        $tasaFormateada = number_format($tasaBs, 4, ',', '.');
        $bloqueTasa = "
        <tr>
            <td style=\"padding: 4px 8px; text-align: right; color: #64748b; font-size: 10px;\">Tasa Oficial Ref.:</td>
            <td style=\"padding: 4px 8px; text-align: right; font-weight: bold; color: #0f172a; font-size: 10px;\">Bs. {$tasaFormateada} / \$</td>
        </tr>
        <tr>
            <td style=\"padding: 6px 8px; text-align: right; font-weight: bold; color: #1e3a8a; font-size: 12px;\">TOTAL EN BOLÍVARES:</td>
            <td style=\"padding: 6px 8px; text-align: right; font-weight: bold; color: #1e3a8a; font-size: 13px;\">Bs. " . number_format($totalBs, 2, ',', '.') . "</td>
        </tr>";
    }

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Entrega #NE-{$idNota}</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-rif {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 3px;
        }
        .company-info {
            font-size: 9.5px;
            color: #64748b;
            line-height: 1.35;
        }
        .doc-box {
            background-color: #f8fafc;
            border: 2px solid #1e3a8a;
            border-radius: 6px;
            padding: 10px 14px;
            text-align: center;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .doc-number {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 6px;
        }
        .doc-meta {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.4;
        }
        .info-card-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .info-card-table td {
            padding: 10px 14px;
            vertical-align: top;
            width: 50%;
        }
        .info-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .info-item {
            font-size: 10px;
            margin-bottom: 3px;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table thead tr {
            background-color: #1e3a8a;
        }
        .items-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .totals-table td {
            vertical-align: top;
        }
        .notes-box {
            background-color: #f1f5f9;
            border-left: 3px solid #1e3a8a;
            padding: 8px 12px;
            font-size: 9px;
            color: #475569;
            line-height: 1.4;
            margin-right: 20px;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }
        .signature-line {
            border-top: 1px dashed #64748b;
            width: 80%;
            margin: 0 auto 6px auto;
        }
        .signature-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
            text-align: center;
        }
        .signature-sub {
            font-size: 8.5px;
            color: #64748b;
            text-align: center;
        }
        .watermark-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- ENCABEZADO FISCAL Y EMPRESARIAL -->
    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="{$logoSrc}" style="height: 65px; max-width: 120px;" alt="Logo">
            </td>
            <td style="width: 48%; padding-left: 10px;">
                <div class="company-name">SP Perfect Color, C.A.</div>
                <div class="company-rif">RIF: J-50348291-0</div>
                <div class="company-info">
                    Especialistas en Colorimetría Automotriz e Industrial<br>
                    Av. Principal Los Guayos, C.C. Industrial Valencia, Local 12<br>
                    Valencia, Carabobo - Venezuela | Teléf: +58 (412) 555-0199<br>
                    Email: administracion@perfectcolor.com
                </div>
            </td>
            <td style="width: 32%;">
                <div class="doc-box">
                    <div class="doc-title">NOTA DE ENTREGA</div>
                    <div class="doc-number">N° NE-{$idNota}</div>
                    <div class="doc-meta">
                        <strong>Fecha:</strong> {$fechaEmision}<br>
                        <strong>Condición:</strong> {$condicionPago}<br>
                        <strong>Ref. Venta:</strong> VTA-{$idNota}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TARJETA DE DATOS DEL CLIENTE Y DESPACHO -->
    <table class="info-card-table">
        <tr>
            <td>
                <div class="info-title">Datos del Cliente</div>
                <div class="info-item"><span class="info-label">Cliente / Razón Social:</span> <strong>{$clienteNombre}</strong></div>
                <div class="info-item"><span class="info-label">Cédula / RIF:</span> {$clienteCedula}</div>
                <div class="info-item"><span class="info-label">Teléfono:</span> {$clienteTelefono}</div>
                <div class="info-item"><span class="info-label">Dirección de Entrega:</span> {$clienteDireccion}</div>
            </td>
            <td style="border-left: 1px solid #cbd5e1;">
                <div class="info-title">Condiciones de Despacho</div>
                <div class="info-item"><span class="info-label">Vendedor / Asesor:</span> {$vendedor}</div>
                <div class="info-item"><span class="info-label">Método de Pago:</span> {$tipoPago}{$bancoInfo}</div>
                <div class="info-item"><span class="info-label">Estado de Operación:</span> Despachado / Conforme</div>
                <div class="info-item"><span class="info-label">Moneda Base:</span> Dólares Americanos (USD)</div>
            </td>
        </tr>
    </table>

    <!-- TABLA DE ARTÍCULOS Y PRODUCTOS -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center; padding: 8px 6px; color: #ffffff;">#</th>
                <th style="width: 15%; text-align: left; padding: 8px; color: #ffffff;">Código</th>
                <th style="width: 40%; text-align: left; padding: 8px; color: #ffffff;">Descripción del Producto / Fórmula</th>
                <th style="width: 10%; text-align: center; padding: 8px; color: #ffffff;">Cant.</th>
                <th style="width: 15%; text-align: right; padding: 8px; color: #ffffff;">P. Unit ($)</th>
                <th style="width: 15%; text-align: right; padding: 8px; color: #ffffff;">Subtotal ($)</th>
                {$tasaHtmlHeader}
            </tr>
        </thead>
        <tbody>
            {$filasHtml}
        </tbody>
    </table>

    <!-- TOTALES Y CONVERSIÓN -->
    <table class="totals-table">
        <tr>
            <td style="width: 55%;">
                <div class="notes-box">
                    <strong>TÉRMINOS Y CONDICIONES DE GARANTÍA:</strong><br>
                    • La mercancía viaja por cuenta y riesgo del cliente.<br>
                    • Revise el tono y preparación del color sobre muestra física antes de aplicar.<br>
                    • No se aceptan cambios ni devoluciones de fórmulas personalizadas pasadas 24h.<br>
                    • Este documento certifica la recepción de los productos detallados en perfecto estado.
                </div>
            </td>
            <td style="width: 45%;">
                <table style="width: 100%; border-collapse: collapse; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <tr>
                        <td style="padding: 6px 8px; text-align: right; color: #475569; font-size: 10px;">Subtotal Items:</td>
                        <td style="padding: 6px 8px; text-align: right; font-weight: bold; font-size: 10px;">\$ " . number_format($totalUsd, 2, ',', '.') . "</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 8px; text-align: right; color: #475569; font-size: 10px;">Descuentos / Ajustes:</td>
                        <td style="padding: 4px 8px; text-align: right; font-size: 10px;">\$ 0,00</td>
                    </tr>
                    <tr style="background-color: #1e3a8a; color: #ffffff;">
                        <td style="padding: 8px; text-align: right; font-weight: bold; font-size: 12px; color: #ffffff;">TOTAL A PAGAR (USD):</td>
                        <td style="padding: 8px; text-align: right; font-weight: bold; font-size: 13px; color: #ffffff;">\$ " . number_format($totalUsd, 2, ',', '.') . "</td>
                    </tr>
                    {$bloqueTasa}
                </table>
            </td>
        </tr>
    </table>

    <!-- FIRMAS DE CONFORMIDAD -->
    <table class="signatures-table">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line"></div>
                <div class="signature-title">SP PERFECT COLOR, C.A.</div>
                <div class="signature-sub">Despachado por / Sello Autorizado</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line"></div>
                <div class="signature-title">RECIBIDO CONFORME</div>
                <div class="signature-sub">Firma del Cliente / C.I. / Fecha</div>
            </td>
        </tr>
    </table>

    <!-- PIE DE PÁGINA -->
    <div class="watermark-footer">
        Documento generado electrónicamente por SP Perfect Color Gestión Comercial &bull; Fecha de impresión: " . date('d/m/Y H:i:s') . " &bull; Página 1 de 1
    </div>

</body>
</html>
HTML;

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    $nombreArchivo = 'Nota_Entrega_NE-' . $idNota . '.pdf';
    $dompdf->stream($nombreArchivo, ['Attachment' => $descargar ? 1 : 0]);
    exit;
}
