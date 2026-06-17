<?php

namespace App\Controllers;

use function App\Helpers\verificarRolAdmin;

if ($metodo === 'index') {
    verificarRolAdmin();

    $pageTitle = 'SP Perfect Color - Configuración de Pago';
    $pageDescription = 'Gestión de bancos y tipos de pago - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/configPagoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
