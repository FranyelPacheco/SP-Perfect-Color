<?php
// Archivo: dashboardController.php
// Controlador para el panel principal

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\ProveedorModel;
use App\Models\InventarioModel;
use App\Models\CuentaPagarModel;
use App\Models\CuentaCobrarModel;
use function App\Helpers\verificarAutenticacion;

if ($metodo === 'index') {
    // Verificar que el usuario haya iniciado sesion
    verificarAutenticacion();

    // Obtener estadisticas para el dashboard
    $clienteModel = new ClienteModel();
    $proveedorModel = new ProveedorModel();
    $inventarioModel = new InventarioModel();
    $cuentaPagarModel = new CuentaPagarModel();
    $cuentaCobrarModel = new CuentaCobrarModel();

    $totalClientes = count($clienteModel->listarTodos());
    $totalProveedores = count($proveedorModel->listarTodos());
    $totalInsumos = count($inventarioModel->listarTodos());
    $alertasStock = $inventarioModel->obtenerAlertasStockBajo();

    $pagosRealizadosHoy = $cuentaPagarModel->obtenerTotalPagosHoy();
    $pagosRecibidosHoy = $cuentaCobrarModel->obtenerTotalPagosHoy();

    $ingresosPorDia = $cuentaCobrarModel->obtenerPagosPorDia(7);

    // Definir la vista de contenido que se cargara en la plantilla
    $contenidoVista = __DIR__ . '/../views/dashboardView.php';

    // Cargar la plantilla base
    require_once __DIR__ . '/../views/plantillaBase.php';
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
