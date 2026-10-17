<?php
// ARCHIVO: perfilController.php
// OBJETIVO: Controlador para la visualización y gestión del perfil propio del usuario autenticado y cambio seguro de contraseña

namespace App\Controllers;

use App\Models\UsuarioModel;
use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    validarRequerido,
    validarCorreo,
    validarClave,
    enviarNotificacionCambioClave
};

$usuarioModel = new UsuarioModel();

// FUNCIÓN: index
// OBJETIVO: Cargar los datos del usuario logueado y mostrar la vista de Mi Perfil
if ($metodo === 'index') {
    verificarAutenticacion();

    $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
    $usuario = $usuarioModel->buscarConRolPorId($idUsuario);

    if (!$usuario) {
        header('Location: /SP%20Perfect%20Color/login/salir');
        exit;
    }

    $pageTitle = 'SP Perfect Color - Mi Perfil';
    $pageDescription = 'Consulta y actualización de perfil de usuario y seguridad';
    $contenidoVista = __DIR__ . '/../views/perfilView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
    exit;

// FUNCIÓN: actualizarDatos
// OBJETIVO: Actualiza el nombre y correo del usuario en sesión
} elseif ($metodo === 'actualizarDatos') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
    $resultado = $usuarioModel->actualizarPerfil($idUsuario, $_POST);
    if ($resultado['exito']) {
        $_SESSION['usuario_nombre'] = $resultado['datos']['nombre'];
        $_SESSION['usuario_correo'] = $resultado['datos']['correo'];
        respuestaJson('exito', $resultado['mensaje']);
    } else {
        respuestaJson('error', $resultado['mensaje']);
    }

// FUNCIÓN: cambiarClave
// OBJETIVO: Cambia la contraseña solicitando y verificando la contraseña actual
} elseif ($metodo === 'cambiarClave') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
    $resultado = $usuarioModel->cambiarClavePerfil($idUsuario, $_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
