<?php

namespace App\Controllers;

use App\Models\BancoModel;
use function App\Helpers\{
    respuestaJson,
    verificarRolAdmin,
    soloPost,
    responder
};

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
// OBJETIVO: Crea un banco nuevo o reactiva uno inactivo delegando lógica al modelo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    soloPost();

    $resultado = $bancoModel->registrar($_POST);
    responder($resultado);

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
// OBJETIVO: Actualiza los datos de un banco existente delegando validaciones al modelo
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $bancoModel->modificar($id, $_POST);
    responder($resultado);

// FUNCIÓN: eliminar
// OBJETIVO: Elimina (soft-delete) un banco por ID delegando al modelo
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $bancoModel->darDeBaja($id);
    responder($resultado);

// FUNCIÓN: toggleActivo
// OBJETIVO: Alterna el estado activo/inactivo de un banco
} elseif ($metodo === 'toggleActivo') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $bancoModel->cambiarEstado($id);
    responder($resultado);

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
