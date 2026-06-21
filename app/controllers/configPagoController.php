<?php

namespace App\Controllers;

use function App\Helpers\verificarRolAdmin;

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista combinada de configuración de pago (bancos + tipos de pago)
// NOTA: La vista carga dos DataTables lado a lado en cards separadas
if ($metodo === 'index') {
    verificarRolAdmin();

    $pageTitle = 'SP Perfect Color - Configuración de Pago';
    $pageDescription = 'Gestión de bancos y tipos de pago - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/configPagoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
