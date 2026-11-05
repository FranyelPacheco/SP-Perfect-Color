<?php
// VISTA: notaEntregaFormView.php
// OBJETIVO: Formulario para crear nota de entrega desde un presupuesto aprobado
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="h4 mb-0">Nota de Entrega #<?php echo $presupuesto['id_presupuesto']; ?></h2>
    <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary" title="Volver" aria-label="Volver" data-bs-toggle="tooltip"><i class="bi bi-arrow-left"></i></a>
</div>

<form id="formularioNotaEntrega">
    <input type="hidden" id="presupuestoId" name="id_presupuesto" value="<?php echo $presupuesto['id_presupuesto']; ?>">

    <!-- Bloque de Decisión: Aprobar o Rechazar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Decisión de Entrega y Venta</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label for="decisionNota" class="form-label fw-bold">Estado / Decisión sobre la Nota de Entrega <span class="text-danger">*</span></label>
                    <select id="decisionNota" name="decision" class="form-select form-select-lg fw-semibold" onchange="toggleDecision()">
                        <option value="aprobar" selected>✅ Aprobar y Despachar (Registrar Cobro / Crédito)</option>
                        <option value="rechazar">❌ Rechazar Nota / Presupuesto</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div id="alertaRechazo" class="alert alert-warning mb-0 d-none">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Al rechazar la nota de entrega, la venta no se procesará, no se descontará inventario y no se generarán cobros.
                    </div>
                    <div id="alertaAprobacion" class="alert alert-info mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>Al aprobar, el inventario se descontará automáticamente y se formalizará el cobro o la cuenta por cobrar.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Datos del Cliente y Condiciones de Pago -->
    <div class="card border-0 shadow-sm mb-4" id="seccionClienteYPago">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i>Condiciones de Pago y Cobro</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="clienteNota" class="form-label fw-semibold">Cliente</label>
                <select id="clienteNota" name="id_cliente" class="form-select" required>
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Presupuesto de origen:</small>
                    <a href="/SP%20Perfect%20Color/presupuesto/ver?id=<?php echo $presupuesto['id_presupuesto']; ?>" class="text-decoration-none fw-semibold">#<?php echo $presupuesto['id_presupuesto']; ?> - <?php echo htmlspecialchars($presupuesto['cliente_nombre'] ?? ''); ?></a>
                </div>
<?php
$tasaHoyNotaForm = (float)($tasaCambioGlobal ?? 0);
$totalNotaFormUsd = (float)($presupuesto['total'] ?? 0);
$totalNotaFormBs = $totalNotaFormUsd * $tasaHoyNotaForm;
?>
                <div class="col-md-4">
                    <small class="text-muted d-block">Total a Pagar:</small>
                    <span class="fw-bold fs-4 text-success" id="totalPresupuestoTexto">$ <?php echo number_format($totalNotaFormUsd, 2, ',', '.'); ?></span>
                    <?php if ($tasaHoyNotaForm > 0): ?>
                        <div class="text-muted small">
                            Equiv: <strong class="text-primary" id="totalPresupuestoBsTexto">Bs. <?php echo number_format($totalNotaFormBs, 2, ',', '.'); ?></strong>
                            <span class="badge bg-light text-secondary border ms-1">Tasa: <?php echo number_format($tasaHoyNotaForm, 2, ',', '.'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="condicionPago" class="form-label fw-semibold">Condición de Pago <span class="text-danger">*</span></label>
                    <select id="condicionPago" name="condicion_pago" class="form-select" onchange="toggleCondicionPago()">
                        <option value="contado" selected>Contado (Pago Inmediato)</option>
                        <option value="credito">Crédito (Cuenta por Cobrar)</option>
                    </select>
                </div>

                <!-- CONTADO -->
                <div class="col-md-4" id="contenedorTipoPago" style="display:block">
                    <label for="tipoPago" class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                    <select id="tipoPago" name="id_tipo_pago" class="form-select">
                        <option value="">Seleccione...</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorBanco" style="display:none">
                    <label for="bancoPago" class="form-label fw-semibold">Banco Receptor <span class="text-danger">*</span></label>
                    <select id="bancoPago" name="id_banco" class="form-select">
                        <option value="">Seleccione un banco...</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorReferencia" style="display:none">
                    <label for="referenciaPago" class="form-label fw-semibold">Referencia (últimos 5 dígitos) <span class="text-danger">*</span></label>
                    <input type="text" id="referenciaPago" name="referencia" class="form-control" placeholder="Ej: 48215">
                </div>

                <!-- CRÉDITO -->
                <div class="col-md-4" id="contenedorVencimiento" style="display:none">
                    <label for="fechaVencimiento" class="form-label fw-semibold">Fecha de Vencimiento <span class="text-danger">*</span></label>
                    <input type="date" id="fechaVencimiento" name="fecha_vencimiento" class="form-control">
                </div>
                <div class="col-md-4" id="contenedorMontoInicial" style="display:none">
                    <label for="montoInicial" class="form-label fw-semibold">Pago / Abono Inicial ($)</label>
                    <input type="number" step="0.01" min="0" max="<?php echo $totalNotaFormUsd; ?>" id="montoInicial" name="monto_inicial" class="form-control" placeholder="0.00">
                    <div class="d-flex justify-content-between align-items-center small mt-1">
                        <span class="text-muted">Opcional (Pagos recibidos)</span>
                        <span class="text-primary fw-semibold" id="montoInicialBsTexto">Bs. 0,00</span>
                    </div>
                </div>
                <div class="col-md-4" id="contenedorSaldoRestante" style="display:none">
                    <label class="form-label fw-semibold">Saldo Pendiente a Cobrar</label>
                    <div class="form-control bg-light fw-bold text-danger fs-5" id="textoSaldoRestante">$ <?php echo number_format($totalNotaFormUsd, 2, ',', '.'); ?></div>
                    <div class="text-muted small mt-1" id="textoSaldoRestanteBs">Equiv: Bs. <?php echo number_format($totalNotaFormBs, 2, ',', '.'); ?></div>
                </div>
                <div class="col-md-4" id="contenedorTipoPagoInicial" style="display:none">
                    <label for="tipoPagoInicial" class="form-label fw-semibold">Método de Abono Inicial <span class="text-danger">*</span></label>
                    <select id="tipoPagoInicial" name="id_tipo_pago_inicial" class="form-select">
                        <option value="">Seleccione...</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorBancoInicial" style="display:none">
                    <label for="bancoPagoInicial" class="form-label fw-semibold">Banco Receptor (Inicial) <span class="text-danger">*</span></label>
                    <select id="bancoPagoInicial" name="id_banco_inicial" class="form-select">
                        <option value="">Seleccione banco...</option>
                    </select>
                </div>
                <div class="col-md-4" id="contenedorReferenciaInicial" style="display:none">
                    <label for="referenciaPagoInicial" class="form-label fw-semibold">Referencia (últimos 5 dígitos) <span class="text-danger">*</span></label>
                    <input type="text" id="referenciaPagoInicial" name="referencia_inicial" class="form-control" placeholder="Ej: 48215">
                </div>
            </div>
        </div>
    </div>

    <!-- Items del Presupuesto a Despachar -->
    <div class="card border-0 shadow-sm mb-4" id="seccionItemsDespacho">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-primary"></i>Items a Despachar del Inventario</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tablaItemsNota" class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaItems">
                        <tr id="filaVacia">
                            <td colspan="6" class="text-center text-muted">Cargando items del presupuesto...</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total:</td>
                            <td id="totalNota" class="fw-bold fs-5 text-dark">$ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="mensajeErrorNota" class="alert alert-danger d-none"></div>

    <div class="d-flex justify-content-end gap-2 barra-accion-fija">
        <a href="/SP%20Perfect%20Color/notaEntrega" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
        <button type="submit" class="btn btn-success btn-lg" id="btnSubmitNota">
            <i class="bi bi-check-lg me-2"></i>Aprobar y Registrar Entrega
        </button>
    </div>
</form>

<script>
var totalVentaBase = <?php echo (float)$presupuesto['total']; ?>;

var FORMATO_BS = { minimumFractionDigits: 2, maximumFractionDigits: 2 };

// Muestra u oculta contenedores y marca como requeridos los campos indicados
function mostrarGrupo(selectoresContenedor, visible) {
    $(selectoresContenedor).css('display', visible ? 'block' : 'none');
}

function toggleDecision() {
    var rechazar = $('#decisionNota').val() === 'rechazar';

    mostrarGrupo('#seccionClienteYPago, #seccionItemsDespacho', !rechazar);
    $('#alertaRechazo').toggleClass('d-none', !rechazar);
    $('#alertaAprobacion').toggleClass('d-none', rechazar);
    $('#clienteNota').prop('required', !rechazar);
    $('#btnSubmitNota')
        .attr('class', rechazar ? 'btn btn-danger btn-lg' : 'btn btn-success btn-lg')
        .html(rechazar ? '<i class="bi bi-x-circle me-1"></i>Rechazar' : '<i class="bi bi-check-lg me-1"></i>Aprobar');

    if (!rechazar) toggleCondicionPago();
}

function toggleCondicionPago() {
    var $sel = $('#condicionPago');
    if (!$sel.length) return;
    var $inpV = $('#fechaVencimiento');

    if ($sel.val() === 'credito') {
        mostrarGrupo('#contenedorVencimiento, #contenedorMontoInicial, #contenedorSaldoRestante', true);
        mostrarGrupo('#contenedorTipoPago, #contenedorBanco, #contenedorReferencia', false);
        $('#tipoPago').prop('required', false);
        $inpV.prop('required', true);
        if (!$inpV.val()) {
            var fecha = new Date();
            fecha.setDate(fecha.getDate() + 10);
            $inpV.val(fecha.toISOString().split('T')[0]);
        }
        actualizarCamposInicial();
    } else {
        mostrarGrupo('#contenedorTipoPago', true);
        mostrarGrupo('#contenedorVencimiento, #contenedorMontoInicial, #contenedorSaldoRestante, #contenedorTipoPagoInicial, #contenedorBancoInicial, #contenedorReferenciaInicial', false);
        $inpV.prop('required', false);
        $('#tipoPago').prop('required', true);
        toggleBancoRefContado();
    }
}

function toggleBancoRefContado() {
    var $tipoPago = $('#tipoPago');
    if (!$tipoPago.length) return;
    var val = parseInt($tipoPago.val() || 0);
    // 1 = Efectivo
    var requiere = !(val === 1 || val === 0);
    mostrarGrupo('#contenedorBanco, #contenedorReferencia', requiere);
    $('#bancoPago, #referenciaPago').prop('required', requiere);
}

function actualizarCamposInicial() {
    var tasa = parseFloat(window.TASA_CAMBIO_HOY || 0);
    var montoIni = parseFloat($('#montoInicial').val() || 0) || 0;
    var saldoRestante = Math.max(0, totalVentaBase - montoIni);

    $('#textoSaldoRestante').text('$ ' + saldoRestante.toLocaleString('es-VE', FORMATO_BS));
    if (tasa > 0) {
        $('#textoSaldoRestanteBs').text('Equiv: Bs. ' + (saldoRestante * tasa).toLocaleString('es-VE', FORMATO_BS));
        $('#montoInicialBsTexto').text('Bs. ' + (montoIni * tasa).toLocaleString('es-VE', FORMATO_BS));
    }

    mostrarGrupo('#contenedorTipoPagoInicial', montoIni > 0);
    $('#tipoPagoInicial').prop('required', montoIni > 0);
    if (montoIni > 0) {
        toggleBancoRefInicial();
    } else {
        mostrarGrupo('#contenedorBancoInicial, #contenedorReferenciaInicial', false);
    }
}

function toggleBancoRefInicial() {
    var $selTIni = $('#tipoPagoInicial');
    if (!$selTIni.length) return;
    var val = parseInt($selTIni.val() || 0);
    var requiere = !(val === 1 || val === 0);
    mostrarGrupo('#contenedorBancoInicial, #contenedorReferenciaInicial', requiere);
    $('#bancoPagoInicial, #referenciaPagoInicial').prop('required', requiere);
}

$(function() {
    $('#tipoPago').on('change', toggleBancoRefContado);
    $('#montoInicial').on('input', actualizarCamposInicial);
    $('#tipoPagoInicial').on('change', toggleBancoRefInicial);
    toggleDecision();
});

toggleCondicionPago();
var presupuestoDetalle = <?php echo json_encode($detalle); ?>;
var presupuestoClienteId = <?php echo (int)$presupuesto['id_cliente']; ?>;
</script>
<script src="/SP%20Perfect%20Color/assets/js/notaEntregaForm.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/notaEntregaForm.js'); ?>"></script>
