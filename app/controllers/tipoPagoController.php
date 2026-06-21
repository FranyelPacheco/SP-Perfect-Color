<?php

namespace App\Controllers;

use App\Models\TipoPagoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarRolAdmin;

$tipoPagoModel = new TipoPagoModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de tipos de pago
if ($metodo === 'index') {
    verificarRolAdmin();

    $contenidoVista = __DIR__ . '/../views/tipoPagoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve listado JSON de todos los tipos de pago para DataTable
} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();

    $tipos = $tipoPagoModel->listarTodos();
    respuestaJson('exito', 'Tipos de pago obtenidos correctamente', [
        'tipos_pago' => $tipos
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un tipo de pago nuevo o reactiva uno inactivo con el mismo nombre
// NOTA: Verifica existencia de tipo inactivo antes de insertar
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        respuestaJson('error', 'El nombre del tipo de pago es obligatorio');
    }

    $inactivoId = $tipoPagoModel->buscarInactivoPorNombre($nombre);
    if ($inactivoId) {
        if ($tipoPagoModel->actualizar($inactivoId, $nombre, 1)) {
            respuestaJson('exito', 'Tipo de pago reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el tipo de pago');
        }
    }

    if ($tipoPagoModel->insertar($nombre)) {
        respuestaJson('exito', 'Tipo de pago creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el tipo de pago');
    }

// FUNCIÓN: obtener
// OBJETIVO: Devuelve los datos de un tipo de pago por ID en formato JSON
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de tipo de pago no valido');

    $tipo = $tipoPagoModel->buscarPorId($id);
    if ($tipo) {
        respuestaJson('exito', 'Tipo de pago obtenido correctamente', $tipo);
    } else {
        respuestaJson('error', 'Tipo de pago no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza los datos de un tipo de pago existente
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $activo = intval($_POST['activo'] ?? 1);

    if ($id < 1) respuestaJson('error', 'ID de tipo de pago no valido');
    if (empty($nombre)) respuestaJson('error', 'El nombre del tipo de pago es obligatorio');

    if ($tipoPagoModel->actualizar($id, $nombre, $activo)) {
        respuestaJson('exito', 'Tipo de pago actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el tipo de pago');
    }

// FUNCIÓN: eliminar
// OBJETIVO: Elimina (soft-delete) un tipo de pago por ID
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de tipo de pago no valido');

    if ($tipoPagoModel->eliminar($id)) {
        respuestaJson('exito', 'Tipo de pago eliminado exitosamente');
    } else {
        respuestaJson('error', 'Error al eliminar el tipo de pago');
    }

// FUNCIÓN: toggleActivo
// OBJETIVO: Alterna el estado activo/inactivo de un tipo de pago
} elseif ($metodo === 'toggleActivo') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de tipo de pago no valido');

    if ($tipoPagoModel->toggleActivo($id)) {
        respuestaJson('exito', 'Estado cambiado exitosamente');
    } else {
        respuestaJson('error', 'Error al cambiar el estado');
    }

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
