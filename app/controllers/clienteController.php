<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use function App\Helpers\{
    respuestaJson,
    verificarPermiso,
    verificarRolAdmin,
    soloPost,
    responder
};

$clienteModel = new ClienteModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de clientes con datos precargados
if ($metodo === 'index') {
    verificarPermiso('cliente');

    $clientes = $clienteModel->listarTodos();

    $contenidoVista = __DIR__ . '/../views/clienteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve listado JSON de todos los clientes para DataTable
} elseif ($metodo === 'listarAjax') {
    verificarPermiso('cliente');

    $clientes = $clienteModel->listarTodos();

    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca clientes por término de búsqueda (cédula, nombre, apellido) y devuelve JSON
} elseif ($metodo === 'buscarAjax') {
    verificarPermiso('cliente');

    $termino = trim($_GET['termino'] ?? '');

    if (empty($termino)) {
        $clientes = $clienteModel->listarTodos();
    } else {
        $clientes = $clienteModel->buscarClientes($termino);
    }

    respuestaJson('exito', 'Busqueda completada', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: verificarCedulaAjax
// OBJETIVO: Verifica en tiempo real si una cédula ya está registrada
} elseif ($metodo === 'verificarCedulaAjax') {
    verificarPermiso('cliente');
    $cedula = trim($_GET['cedula'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($cedula) && $clienteModel->cedulaExiste($cedula, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: verificarTelefonoAjax
// OBJETIVO: Verifica en tiempo real si un teléfono ya está registrado en clientes
} elseif ($metodo === 'verificarTelefonoAjax') {
    verificarPermiso('cliente');
    $telefono = trim($_GET['telefono'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($telefono) && $clienteModel->telefonoExiste($telefono, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: verificarCorreoAjax
// OBJETIVO: Verifica en tiempo real si un correo ya está registrado en clientes
} elseif ($metodo === 'verificarCorreoAjax') {
    verificarPermiso('cliente');
    $correo = trim($_GET['correo'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($correo) && $clienteModel->correoExiste($correo, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: guardar
// OBJETIVO: Enruta la creación o reactivación de un cliente delegando validaciones y transacción al modelo
} elseif ($metodo === 'guardar') {
    verificarPermiso('cliente');

    soloPost();

    $resultado = $clienteModel->registrar($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje'], $resultado);

// FUNCIÓN: obtener
// OBJETIVO: Devuelve los datos de un cliente por ID en formato JSON (incluye teléfonos)
} elseif ($metodo === 'obtener') {
    verificarPermiso('cliente');

    $id = intval($_GET['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de cliente no valido');
    }

    $cliente = $clienteModel->buscarPorId($id);

    if ($cliente) {
        respuestaJson('exito', 'Cliente obtenido correctamente', $cliente);
    } else {
        respuestaJson('error', 'Cliente no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Enruta la actualización de un cliente delegando validaciones y reemplazo atómico de teléfonos al modelo
} elseif ($metodo === 'actualizar') {
    verificarPermiso('cliente');

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $clienteModel->modificar($id, $_POST);
    responder($resultado);

// FUNCIÓN: eliminar
// OBJETIVO: Enruta la eliminación lógica (soft-delete) de un cliente delegando las reglas de cuentas pendientes al modelo
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $clienteModel->eliminar($id);
    responder($resultado);

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}

