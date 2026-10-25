<?php
// Archivo: proveedorController.php
// Controlador para la gestion de proveedores

namespace App\Controllers;

use App\Models\ProveedorModel;
use function App\Helpers\{
    respuestaJson,
    verificarAcceso,
    verificarRolAdmin,
    soloPost,
    responder
};

$proveedorModel = new ProveedorModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de proveedores
if ($metodo === 'index') {
    verificarAcceso([1]);

    $contenidoVista = __DIR__ . '/../views/proveedorListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de proveedores en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAcceso([1]);

    $proveedores = $proveedorModel->listarTodos();

    respuestaJson('exito', 'Proveedores obtenidos correctamente', [
        'proveedores' => $proveedores
    ]);
// FUNCIÓN: buscarAjax
// OBJETIVO: Busca proveedores por término de búsqueda o devuelve todos
} elseif ($metodo === 'buscarAjax') {
    verificarAcceso([1]);

    $termino = trim($_GET['termino'] ?? '');

    if (empty($termino)) {
        $proveedores = $proveedorModel->listarTodos();
    } else {
        $proveedores = $proveedorModel->buscarProveedores($termino);
    }

    respuestaJson('exito', 'Busqueda completada', [
        'proveedores' => $proveedores
    ]);
// FUNCIÓN: verificarRifAjax
// OBJETIVO: Verifica en tiempo real si un RIF ya está registrado
} elseif ($metodo === 'verificarRifAjax') {
    verificarAcceso([1]);
    $rif = strtoupper(trim($_GET['rif'] ?? ''));
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($rif) && $proveedorModel->rifExiste($rif, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: verificarTelefonoAjax
// OBJETIVO: Verifica en tiempo real si un teléfono ya está registrado en proveedores
} elseif ($metodo === 'verificarTelefonoAjax') {
    verificarAcceso([1]);
    $telefono = trim($_GET['telefono'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($telefono) && $proveedorModel->telefonoExiste($telefono, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: verificarCorreoAjax
// OBJETIVO: Verifica en tiempo real si un correo ya está registrado en proveedores
} elseif ($metodo === 'verificarCorreoAjax') {
    verificarAcceso([1]);
    $correo = trim($_GET['correo'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($correo) && $proveedorModel->correoExiste($correo, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo proveedor o reactiva uno inactivo, delegando validaciones y transacción al modelo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    soloPost();

    $resultado = $proveedorModel->registrar($_POST);
    responder($resultado);

// FUNCIÓN: obtener
// OBJETIVO: Obtiene un proveedor por ID para edición
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();

    $id = intval($_GET['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de proveedor no valido');
    }

    $proveedor = $proveedorModel->buscarPorId($id);

    if ($proveedor) {
        respuestaJson('exito', 'Proveedor obtenido correctamente', $proveedor);
    } else {
        respuestaJson('error', 'Proveedor no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza un proveedor existente delegando al modelo
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $proveedorModel->modificar($id, $_POST);
    responder($resultado);

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un proveedor delegando al modelo
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $resultado = $proveedorModel->eliminar($id);
    responder($resultado);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
