<?php
// Archivo: dashboardController.php
// Controlador para el panel principal

namespace App\Controllers;

use function App\Helpers\verificarAutenticacion;

if ($metodo === 'index') {
    // Verificar que el usuario haya iniciado sesion
    verificarAutenticacion();

    // Definir la vista de contenido que se cargara en la plantilla
    $contenidoVista = __DIR__ . '/../views/dashboardView.php';

    // Cargar la plantilla base
    require_once __DIR__ . '/../views/plantillaBase.php';
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
