<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\ProveedorModel;
use App\Models\InventarioModel;
use App\Models\CuentaPagarModel;
use App\Models\CuentaCobrarModel;
use function App\Helpers\verificarAutenticacion;

// FUNCIÓN: index
// OBJETIVO: Renderiza el panel principal con estadísticas (clientes, proveedores, stock, pagos del día, gráfica de ingresos)
// NOTA: Se cargan 5 modelos para obtener los indicadores del dashboard
if ($metodo === 'index') {
    verificarAutenticacion();

    $clienteModel = new ClienteModel();
    $proveedorModel = new ProveedorModel();
    $inventarioModel = new InventarioModel();
    $cuentaPagarModel = new CuentaPagarModel();
    $cuentaCobrarModel = new CuentaCobrarModel();

    $totalClientes = $clienteModel->contarTodos();
    $totalProveedores = $proveedorModel->contarTodos();
    $totalInsumos = $inventarioModel->contarTodos();
    $alertasStock = $inventarioModel->obtenerAlertasStockBajo();

    $pagosRealizadosHoy = $cuentaPagarModel->obtenerTotalPagosHoy();
    $pagosRecibidosHoy = $cuentaCobrarModel->obtenerTotalPagosHoy();

    $ingresosPorDia = $cuentaCobrarModel->obtenerPagosPorDia(7);

    $contenidoVista = __DIR__ . '/../views/dashboardView.php';

    require_once __DIR__ . '/../views/plantillaBase.php';
// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
