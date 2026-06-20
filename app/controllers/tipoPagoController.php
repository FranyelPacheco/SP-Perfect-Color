<?php

namespace App\Controllers;

use App\Models\TipoPagoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarRolAdmin;

$tipoPagoModel = new TipoPagoModel();

if ($metodo === 'index') {
    verificarRolAdmin();

    $contenidoVista = __DIR__ . '/../views/tipoPagoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();

    $tipos = $tipoPagoModel->listarTodos();
    respuestaJson('exito', 'Tipos de pago obtenidos correctamente', [
        'tipos_pago' => $tipos
    ]);

} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        respuestaJson('error', 'El nombre del tipo de pago es obligatorio');
    }

    // Si existe un tipo de pago inactivo con el mismo nombre, reactivarlo
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

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
