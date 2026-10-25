<?php

namespace App\Controllers;

use App\Models\TipoPagoModel;
use function App\Helpers\{
    respuestaJson,
    verificarRolAdmin,
    soloPost,
    responder
};

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
// OBJETIVO: Crea un tipo de pago nuevo o reactiva uno inactivo delegando al modelo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    soloPost();

    $resultado = $tipoPagoModel->registrar($_POST);
    responder($resultado);

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
// OBJETIVO: Actualiza los datos de un tipo de pago existente delegando al modelo
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $tipoPagoModel->modificar($id, $_POST);
    responder($resultado);

// FUNCIÓN: eliminar
// OBJETIVO: Elimina (soft-delete) un tipo de pago por ID delegando al modelo
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $tipoPagoModel->darDeBaja($id);
    responder($resultado);

// FUNCIÓN: toggleActivo
// OBJETIVO: Alterna el estado activo/inactivo de un tipo de pago
} elseif ($metodo === 'toggleActivo') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $tipoPagoModel->cambiarEstado($id);
    responder($resultado);

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
