<?php

namespace App\Controllers;

use App\Models\TipoPagoModel;
use function App\Helpers\{
    respuestaJson,
    verificarRolAdmin
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $resultado = $tipoPagoModel->registrar($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $resultado = $tipoPagoModel->modificar($id, $_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

// FUNCIÓN: eliminar
// OBJETIVO: Elimina (soft-delete) un tipo de pago por ID delegando al modelo
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $resultado = $tipoPagoModel->darDeBaja($id);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

// FUNCIÓN: toggleActivo
// OBJETIVO: Alterna el estado activo/inactivo de un tipo de pago
} elseif ($metodo === 'toggleActivo') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $resultado = $tipoPagoModel->cambiarEstado($id);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
