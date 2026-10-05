<?php
// ARCHIVO: loginController.php
// OBJETIVO: Controlador para inicio de sesión, cierre de sesión y recuperación de contraseña vía SMTP

namespace App\Controllers;

use App\Models\UsuarioModel;
use function App\Helpers\{
    respuestaJson,
    obtenerSesionManager,
    validarCorreo,
    validarRequerido,
    enviarRecuperacionClave,
    enviarNotificacionCambioClave
};

$usuarioModel = new UsuarioModel();
$sesionManager = obtenerSesionManager();

const TOKEN_SECRET = 'SP_PERFECT_COLOR_RECOVERY_KEY_2026';

// FUNCIÓN: index
// OBJETIVO: Muestra el formulario de inicio de sesión; redirige al dashboard si ya hay sesión activa
if ($metodo === 'index') {
    if ($sesionManager->estaAutenticado()) {
        header('Location: /SP%20Perfect%20Color/dashboard');
        exit;
    }

    require_once __DIR__ . '/../views/loginView.php';

// FUNCIÓN: iniciarSesion
// OBJETIVO: Valida credenciales contra la BD e inicia sesión usando SessionTrait
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

    if (isset($usuario['rol_activo']) && (int)$usuario['rol_activo'] === 0) {
        respuestaJson('error', 'Su rol asignado se encuentra deshabilitado. Contacte al administrador');
    }

    // Iniciar sesión centralizada a través de SessionTrait
    $sesionManager->iniciarSesion($usuario);

    respuestaJson('exito', 'Inicio de sesion exitoso', [
        'redirect' => '/SP%20Perfect%20Color/dashboard'
    ]);

// FUNCIÓN: salir
// OBJETIVO: Cierra la sesión del usuario mediante SessionTrait y redirige al login
} elseif ($metodo === 'salir') {
    $sesionManager->cerrarSesion();
    header('Location: /SP%20Perfect%20Color/login');
    exit;

// FUNCIÓN: recuperarClave
// OBJETIVO: Recibe correo electrónico, genera token seguro y envía email vía PHPMailer
} elseif ($metodo === 'recuperarClave') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $correo = trim($_POST['correo'] ?? '');

    if (!validarRequerido($correo) || !validarCorreo($correo)) {
        respuestaJson('error', 'Por favor ingrese un correo electrónico válido');
    }

    $usuario = $usuarioModel->buscarPorCorreo($correo);

    // Si el usuario existe y está activo, generamos el enlace y enviamos el correo
    if ($usuario && (int)$usuario['activo'] === 1) {
        $expira = time() + 3600; // Validez de 1 hora
        $firma = hash_hmac('sha256', $usuario['id_usuario'] . $usuario['password_hash'] . $expira, TOKEN_SECRET);
        $payload = base64_encode(json_encode([
            'id'   => $usuario['id_usuario'],
            'exp'  => $expira,
            'hash' => $firma
        ]));

        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $enlace = "{$protocolo}{$host}/SP%20Perfect%20Color/login/restablecer?token=" . urlencode($payload);

        $resultadoEnvio = enviarRecuperacionClave($usuario['correo'], $usuario['nombre'], $enlace);

        if (!$resultadoEnvio['exito']) {
            error_log('[RECUPERACION CLAVE] No se pudo enviar el correo: ' . $resultadoEnvio['mensaje']);
        }
    }

    // Por seguridad (no enumerar correos existentes), se muestra siempre el mismo mensaje de éxito
    respuestaJson('exito', 'Si el correo electrónico se encuentra registrado en el sistema, recibirá las instrucciones de recuperación en su bandeja de entrada.');

// FUNCIÓN: restablecer
// OBJETIVO: Valida el token recibido en la URL y muestra el formulario de restablecimiento
} elseif ($metodo === 'restablecer') {
    $token = $_GET['token'] ?? '';
    $errorToken = null;
    $usuarioValido = null;

    if (empty($token)) {
        $errorToken = 'El enlace de recuperación no es válido o está incompleto.';
    } else {
        $data = json_decode(base64_decode($token), true);
        if (!$data || !isset($data['id'], $data['exp'], $data['hash'])) {
            $errorToken = 'El formato del enlace de recuperación es inválido.';
        } elseif (time() > $data['exp']) {
            $errorToken = 'Este enlace de recuperación ha expirado. Por favor solicite uno nuevo.';
        } else {
            $usuario = $usuarioModel->buscarPorId((int)$data['id']);
            if (!$usuario || (int)$usuario['activo'] !== 1) {
                $errorToken = 'El usuario no existe o se encuentra inactivo.';
            } else {
                $firmaEsperada = hash_hmac('sha256', $usuario['id_usuario'] . $usuario['password_hash'] . $data['exp'], TOKEN_SECRET);
                if (!hash_equals($firmaEsperada, $data['hash'])) {
                    $errorToken = 'El enlace de recuperación ya ha sido utilizado o ha sido invalidado.';
                } else {
                    $usuarioValido = $usuario;
                }
            }
        }
    }

    require_once __DIR__ . '/../views/restablecerClaveView.php';
    exit;

// FUNCIÓN: guardarNuevaClave
// OBJETIVO: Valida el token y actualiza la contraseña del usuario
} elseif ($metodo === 'guardarNuevaClave') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $token = $_POST['token'] ?? '';
    $nuevaClave = $_POST['nueva_clave'] ?? '';
    $confirmarClave = $_POST['confirmar_clave'] ?? '';

    if (empty($token)) {
        respuestaJson('error', 'Token de recuperación no válido');
    }

    if (!validarRequerido($nuevaClave) || strlen($nuevaClave) < 6) {
        respuestaJson('error', 'La nueva contraseña debe tener al menos 6 caracteres');
    }

    if ($nuevaClave !== $confirmarClave) {
        respuestaJson('error', 'Las contraseñas ingresadas no coinciden');
    }

    $data = json_decode(base64_decode($token), true);
    if (!$data || !isset($data['id'], $data['exp'], $data['hash'])) {
        respuestaJson('error', 'Enlace de recuperación inválido');
    }

    if (time() > $data['exp']) {
        respuestaJson('error', 'El enlace de recuperación ha expirado');
    }

    $usuario = $usuarioModel->buscarPorId((int)$data['id']);
    if (!$usuario || (int)$usuario['activo'] !== 1) {
        respuestaJson('error', 'Usuario no encontrado');
    }

    $firmaEsperada = hash_hmac('sha256', $usuario['id_usuario'] . $usuario['password_hash'] . $data['exp'], TOKEN_SECRET);
    if (!hash_equals($firmaEsperada, $data['hash'])) {
        respuestaJson('error', 'Este enlace ya fue utilizado o no es válido');
    }

    $nuevoHash = password_hash($nuevaClave, PASSWORD_DEFAULT);
    if ($usuarioModel->actualizarClave((int)$usuario['id_usuario'], $nuevoHash)) {
        enviarNotificacionCambioClave($usuario['correo'], $usuario['nombre']);
        respuestaJson('exito', 'Contraseña restablecida exitosamente. Redirigiendo...', [
            'redirect' => '/SP%20Perfect%20Color/login'
        ]);
    } else {
        respuestaJson('error', 'Error al guardar la nueva contraseña');
    }

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
