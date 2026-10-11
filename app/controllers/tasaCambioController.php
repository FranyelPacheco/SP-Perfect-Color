<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\TasaCambioModel;
use function App\Helpers\{
    respuestaJson,
    verificarRolAdmin
};

$tasaCambioModel = new TasaCambioModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado y gestión de tasas de cambio
if ($metodo === 'index') {
    verificarRolAdmin();

    $tasaHoy = $tasaCambioModel->obtenerTasaHoy();
    $ultimaTasa = $tasaCambioModel->obtenerUltimaTasa();

    $contenidoVista = __DIR__ . '/../views/tasaCambioListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve el listado de tasas en formato JSON para DataTables
} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();

    $tasas = $tasaCambioModel->listarTodas();
    respuestaJson('exito', 'Tasas de cambio obtenidas correctamente', [
        'tasas' => $tasas
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Registra una nueva tasa de cambio diaria
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $tasaRaw = trim($_POST['tasa'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');

    // Si no envían fecha, asumir la fecha actual
    if (empty($fecha)) {
        $fecha = date('Y-m-d');
    }

    // Normalizar coma a punto decimal
    $tasaRaw = str_replace(',', '.', $tasaRaw);
    $tasa = floatval($tasaRaw);

    if ($tasa <= 0) {
        respuestaJson('error', 'El valor de la tasa debe ser mayor a 0 (ej. 45.50)');
    }

    // Validar formato de fecha AAAA-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        respuestaJson('error', 'El formato de fecha es inválido (debe ser AAAA-MM-DD)');
    }

    // Verificar si ya existe tasa para este día
    if ($tasaCambioModel->fechaExiste($fecha)) {
        respuestaJson('error', 'Ya existe una tasa registrada para la fecha ' . date('d/m/Y', strtotime($fecha)) . '. Puede editarla desde el listado.');
    }

    if ($tasaCambioModel->insertar($tasa, $fecha)) {
        respuestaJson('exito', 'Tasa de cambio registrada exitosamente para el ' . date('d/m/Y', strtotime($fecha)));
    } else {
        respuestaJson('error', 'Error al registrar la tasa de cambio en la base de datos');
    }

// FUNCIÓN: obtener
// OBJETIVO: Obtiene una tasa por ID para cargarla en el modal de edición
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) {
        respuestaJson('error', 'ID de tasa no válido');
    }

    $tasa = $tasaCambioModel->buscarPorId($id);
    if ($tasa) {
        respuestaJson('exito', 'Tasa obtenida correctamente', $tasa);
    } else {
        respuestaJson('error', 'Tasa de cambio no encontrada');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza el valor o fecha de una tasa existente
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id_tasa_cambio'] ?? 0);
    $tasaRaw = trim($_POST['tasa'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

    if ($id < 1) {
        respuestaJson('error', 'ID de tasa no válido');
    }

    $tasaRaw = str_replace(',', '.', $tasaRaw);
    $tasa = floatval($tasaRaw);

    if ($tasa <= 0) {
        respuestaJson('error', 'El valor de la tasa debe ser mayor a 0');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        respuestaJson('error', 'Formato de fecha inválido');
    }

    // Verificar si la fecha pertenece a otro registro
    if ($tasaCambioModel->fechaExiste($fecha, $id)) {
        respuestaJson('error', 'Ya existe otro registro con la fecha ' . date('d/m/Y', strtotime($fecha)));
    }

    if ($tasaCambioModel->actualizar($id, $tasa, $fecha, $estado)) {
        respuestaJson('exito', 'Tasa de cambio actualizada correctamente');
    } else {
        respuestaJson('error', 'Error al actualizar la tasa de cambio');
    }

// FUNCIÓN: obtenerHoy
// OBJETIVO: Endpoint público/interno para consultar la tasa vigente de hoy
} elseif ($metodo === 'obtenerHoy') {
    $tasaHoy = $tasaCambioModel->obtenerTasaHoy();
    if ($tasaHoy) {
        respuestaJson('exito', 'Tasa de hoy obtenida', $tasaHoy);
    } else {
        $ultima = $tasaCambioModel->obtenerUltimaTasa();
        respuestaJson('info', 'No hay tasa registrada para hoy', [
            'tasa_hoy' => null,
            'ultima_tasa' => $ultima
        ]);
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
