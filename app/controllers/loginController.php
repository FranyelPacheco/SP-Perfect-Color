<?php
// Archivo: loginController.php
// Controlador para autenticacion de usuarios

namespace App\Controllers;

use App\Models\UsuarioModel;
use function App\Helpers\respuestaJson;

$usuarioModel = new UsuarioModel();

if ($metodo === 'index') {
    // Si ya hay sesion activa, redirigir al dashboard
    if (isset($_SESSION['usuario_id'])) {
        header('Location: /SP%20Perfect%20Color/dashboard');
        exit;
    }

    require_once __DIR__ . '/../views/loginView.php';
} elseif ($metodo === 'iniciarSesion') {
    // Verificar que la peticion sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    // Obtener credenciales del formulario
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';

    // Validar campos obligatorios
    if (empty($correo) || empty($clave)) {
        respuestaJson('error', 'Todos los campos son obligatorios');
    }

    // Buscar usuario por correo
    $usuario = $usuarioModel->buscarPorCorreo($correo);

    if (!$usuario) {
        respuestaJson('error', 'Correo o clave incorrectos');
    }

    // Verificar la clave con password_verify
    if (!password_verify($clave, $usuario['password_hash'])) {
        respuestaJson('error', 'Correo o clave incorrectos');
    }

    // Verificar que el usuario este activo
    if (!$usuario['activo']) {
        respuestaJson('error', 'Usuario inactivo. Contacte al administrador');
    }

    // Establecer variables de sesion
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_correo'] = $usuario['correo'];
    $_SESSION['usuario_rol'] = $usuario['rol_id'];

    respuestaJson('exito', 'Inicio de sesion exitoso', [
        'redirect' => '/SP%20Perfect%20Color/dashboard'
    ]);
} elseif ($metodo === 'salir') {
    // Destruir todas las variables de sesion
    session_unset();
    session_destroy();

    // Redirigir al login
    header('Location: /SP%20Perfect%20Color/login');
    exit;
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
