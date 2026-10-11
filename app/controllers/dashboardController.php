<?php

namespace App\Controllers;

use App\Models\{
    ClienteModel,
    ProveedorModel,
    InventarioModel,
    CuentaPagarModel,
    CuentaCobrarModel,
    NotaEntregaModel,
    TasaCambioModel
};
use function App\Helpers\verificarAutenticacion;

// FUNCIÓN: index
// OBJETIVO: Renderiza el panel principal con estadísticas, verificación de tasa del día y alertas
if ($metodo === 'index') {
    verificarAutenticacion();

    $clienteModel = new ClienteModel();
    $proveedorModel = new ProveedorModel();
    $inventarioModel = new InventarioModel();
    $cuentaPagarModel = new CuentaPagarModel();
    $cuentaCobrarModel = new CuentaCobrarModel();
    $notaEntregaModel = new NotaEntregaModel();
    $tasaCambioModel = new TasaCambioModel();

    $totalClientes = $clienteModel->contarTodos();
    $totalProveedores = $proveedorModel->contarTodos();
    $totalInsumos = $inventarioModel->contarTodos();
    $alertasStock = $inventarioModel->obtenerAlertasStockBajo();

    $pagosRealizadosHoy = $cuentaPagarModel->obtenerTotalPagosHoy();
    $pagosRecibidosHoy = $cuentaCobrarModel->obtenerTotalPagosHoy();

    $ventasMes = $notaEntregaModel->obtenerVentasMes();
    $topProductos = $notaEntregaModel->obtenerTopProductos(5);
    $clienteTopMes = $notaEntregaModel->obtenerClienteTopMes();

    $ingresosPorDia = $cuentaCobrarModel->obtenerPagosPorDia(7);

    // Verificación de tasa del día
    $tasaHoy = $tasaCambioModel->obtenerTasaHoy();
    $ultimaTasa = $tasaCambioModel->obtenerUltimaTasa();
    $alertaTasaPendiente = empty($tasaHoy);

    $contenidoVista = __DIR__ . '/../views/dashboardView.php';

    require_once __DIR__ . '/../views/plantillaBase.php';
// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
