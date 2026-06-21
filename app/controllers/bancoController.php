<?php

namespace App\Controllers;

use App\Models\BancoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarRolAdmin;

$bancoModel = new BancoModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de bancos
if ($metodo === 'index') {
    verificarRolAdmin();

    $contenidoVista = __DIR__ . '/../views/bancoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve listado JSON de todos los bancos para DataTable
} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();

    $bancos = $bancoModel->listarTodos();
    respuestaJson('exito', 'Bancos obtenidos correctamente', [
        'bancos' => $bancos
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un banco nuevo o reactiva uno inactivo con el mismo nombre
// NOTA: Verifica existencia de banco inactivo antes de insertar
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        respuestaJson('error', 'El nombre del banco es obligatorio');
    }

    $inactivoId = $bancoModel->buscarInactivoPorNombre($nombre);
    if ($inactivoId) {
        if ($bancoModel->actualizar($inactivoId, $nombre, 1)) {
            respuestaJson('exito', 'Banco reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el banco');
        }
    }

    if ($bancoModel->insertar($nombre)) {
        respuestaJson('exito', 'Banco creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el banco');
    }

// FUNCIÓN: obtener
// OBJETIVO: Devuelve los datos de un banco por ID en formato JSON
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de banco no valido');

    $banco = $bancoModel->buscarPorId($id);
    if ($banco) {
        respuestaJson('exito', 'Banco obtenido correctamente', $banco);
    } else {
        respuestaJson('error', 'Banco no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza los datos de un banco existente
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $activo = intval($_POST['activo'] ?? 1);

    if ($id < 1) respuestaJson('error', 'ID de banco no valido');
    if (empty($nombre)) respuestaJson('error', 'El nombre del banco es obligatorio');

    if ($bancoModel->actualizar($id, $nombre, $activo)) {
        respuestaJson('exito', 'Banco actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el banco');
    }

// FUNCIÓN: eliminar
// OBJETIVO: Elimina (soft-delete) un banco por ID
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de banco no valido');

    if ($bancoModel->eliminar($id)) {
        respuestaJson('exito', 'Banco eliminado exitosamente');
    } else {
        respuestaJson('error', 'Error al eliminar el banco');
    }

// FUNCIÓN: toggleActivo
// OBJETIVO: Alterna el estado activo/inactivo de un banco
} elseif ($metodo === 'toggleActivo') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de banco no valido');

    if ($bancoModel->toggleActivo($id)) {
        respuestaJson('exito', 'Estado cambiado exitosamente');
    } else {
        respuestaJson('error', 'Error al cambiar el estado');
    }

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
