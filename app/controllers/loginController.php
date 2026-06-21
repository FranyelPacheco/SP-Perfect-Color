<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use function App\Helpers\respuestaJson;

$usuarioModel = new UsuarioModel();

// FUNCIÓN: index
// OBJETIVO: Muestra el formulario de inicio de sesión; redirige al dashboard si ya hay sesión activa
if ($metodo === 'index') {
    if (isset($_SESSION['id_usuario'])) {
        header('Location: /SP%20Perfect%20Color/dashboard');
        exit;
    }

    require_once __DIR__ . '/../views/loginView.php';

// FUNCIÓN: iniciarSesion
// OBJETIVO: Valida credenciales (correo + clave) contra la BD, inicia sesión si son correctas
// NOTA: Usa password_verify para comparar la clave; verifica también que el usuario esté activo
} elseif ($metodo === 'iniciarSesion') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if (empty($correo) || empty($clave)) {
        respuestaJson('error', 'Todos los campos son obligatorios');
    }

    $usuario = $usuarioModel->buscarPorCorreo($correo);

    if (!$usuario) {
        respuestaJson('error', 'Correo o clave incorrectos');
    }

    if (!password_verify($clave, $usuario['password_hash'])) {
        respuestaJson('error', 'Correo o clave incorrectos');
    }

    if (!$usuario['activo']) {
        respuestaJson('error', 'Usuario inactivo. Contacte al administrador');
    }

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_correo'] = $usuario['correo'];
    $_SESSION['usuario_rol'] = $usuario['id_rol'];

    respuestaJson('exito', 'Inicio de sesion exitoso', [
        'redirect' => '/SP%20Perfect%20Color/dashboard'
    ]);

// FUNCIÓN: salir
// OBJETIVO: Cierra la sesión del usuario y redirige al login
} elseif ($metodo === 'salir') {
    session_unset();
    session_destroy();

    header('Location: /SP%20Perfect%20Color/login');
    exit;

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
