<?php

namespace App\Controllers;

use App\Models\BancoModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarRolAdmin;

$bancoModel = new BancoModel();

if ($metodo === 'index') {
    verificarRolAdmin();

    $pageTitle = 'SP Perfect Color - Bancos';
    $pageDescription = 'Gestión de bancos - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/bancoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();

    $bancos = $bancoModel->listarTodos();
    respuestaJson('exito', 'Bancos obtenidos correctamente', [
        'bancos' => $bancos
    ]);

} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    if (empty($nombre)) {
        respuestaJson('error', 'El nombre del banco es obligatorio');
    }

    if ($bancoModel->insertar($nombre)) {
        respuestaJson('exito', 'Banco creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el banco');
    }

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

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
