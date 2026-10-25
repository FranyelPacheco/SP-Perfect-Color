<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\TasaCambioModel;
use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    verificarRolAdmin,
    responder
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
// OBJETIVO: Registra una nueva tasa de cambio diaria delegando la lógica al modelo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $resultado = $tasaCambioModel->registrar($_POST);
    responder($resultado);

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
// OBJETIVO: Actualiza el valor o fecha de una tasa existente delegando la lógica al modelo
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id_tasa_cambio'] ?? 0);
    $resultado = $tasaCambioModel->modificar($id, $_POST);
    responder($resultado);

// FUNCIÓN: obtenerHoy
// OBJETIVO: Endpoint público/interno para consultar la tasa vigente de hoy
} elseif ($metodo === 'obtenerHoy') {
    verificarAutenticacion();
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

// FUNCIÓN: verificarFechaAjax
// OBJETIVO: Verifica en tiempo real si ya existe una tasa registrada para la fecha dada
} elseif ($metodo === 'verificarFechaAjax') {
    verificarRolAdmin();

    $fecha = trim($_GET['fecha'] ?? '');
    $idExcluir = intval($_GET['id'] ?? 0);

    if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        respuestaJson('exito', 'Fecha no válida aún', ['existe' => false]);
    }

    $existe = $tasaCambioModel->fechaExiste($fecha, $idExcluir > 0 ? $idExcluir : null);
    respuestaJson('exito', 'Verificación completada', [
        'existe' => $existe,
        'fecha_formateada' => date('d/m/Y', strtotime($fecha))
    ]);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
