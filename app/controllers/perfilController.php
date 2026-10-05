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
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }
    if (!validarCorreo($correo)) {
        respuestaJson('error', 'El correo electrónico no es válido');
    }

    if ($usuarioModel->correoExiste($correo, $idUsuario)) {
        respuestaJson('error', 'El correo electrónico ya se encuentra registrado por otro usuario');
    }

    $usuarioActual = $usuarioModel->buscarPorId($idUsuario);
    if (!$usuarioActual) {
        respuestaJson('error', 'Usuario no encontrado');
    }

    $idRol = (int)$usuarioActual['id_rol'];
    $activo = (int)$usuarioActual['activo'];

    if ($usuarioModel->actualizarUsuario($idUsuario, $nombre, $correo, $idRol, $activo)) {
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['usuario_correo'] = $correo;
        respuestaJson('exito', 'Datos de perfil actualizados exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar los datos de perfil');
    }

// FUNCIÓN: cambiarClave
// OBJETIVO: Cambia la contraseña solicitando y verificando la contraseña actual
} elseif ($metodo === 'cambiarClave') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
    $claveActual = $_POST['clave_actual'] ?? '';
    $nuevaClave = $_POST['nueva_clave'] ?? '';
    $confirmarClave = $_POST['confirmar_clave'] ?? '';

    if (!validarRequerido($claveActual)) {
        respuestaJson('error', 'Debe ingresar su contraseña actual');
    }
    if (!validarRequerido($nuevaClave)) {
        respuestaJson('error', 'Debe ingresar la nueva contraseña');
    }
    if (strlen($nuevaClave) < 6) {
        respuestaJson('error', 'La nueva contraseña debe tener al menos 6 caracteres');
    }
    if ($nuevaClave !== $confirmarClave) {
        respuestaJson('error', 'La confirmación de la nueva contraseña no coincide');
    }

    $usuarioActual = $usuarioModel->buscarPorId($idUsuario);
    if (!$usuarioActual) {
        respuestaJson('error', 'Usuario no encontrado');
    }

    if (!password_verify($claveActual, $usuarioActual['password_hash'])) {
        respuestaJson('error', 'La contraseña actual ingresada es incorrecta');
    }

    $nuevoHash = password_hash($nuevaClave, PASSWORD_DEFAULT);
    if ($usuarioModel->actualizarClave($idUsuario, $nuevoHash)) {
        // Enviar notificación de seguridad por correo vía SMTP (PHPMailer)
        enviarNotificacionCambioClave($usuarioActual['correo'], $usuarioActual['nombre']);
        respuestaJson('exito', 'Contraseña modificada exitosamente. Se ha enviado un aviso a su correo.');
    } else {
        respuestaJson('error', 'Error al actualizar la contraseña');
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}
