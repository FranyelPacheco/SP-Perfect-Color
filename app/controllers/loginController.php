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
    soloPost,
    responderYContinuar
};

$usuarioModel = new UsuarioModel();
$sesionManager = obtenerSesionManager();

if (!defined('TOKEN_SECRET')) {
    define('TOKEN_SECRET', env('TOKEN_SECRET', ''));
}

// FUNCIÓN: intentosLogin
// OBJETIVO: Cuenta los intentos fallidos por IP y correo en una ventana de 15 minutos (archivo bloqueado por web en app/logs)
// NOTA: accion 'bloqueado' consulta, 'fallo' suma un intento y 'exito' limpia el contador
function intentosLogin(string $clave, string $accion): bool
{
    $limite = 5;
    $ventana = 900;
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $archivo = $dir . '/intentos_login.json';
    $h = @fopen($archivo, 'c+');
    if (!$h) {
        return false;
    }
    flock($h, LOCK_EX);
    $datos = json_decode(stream_get_contents($h) ?: '[]', true) ?: [];
    $ahora = time();
    foreach ($datos as $k => $lista) {
        $datos[$k] = array_values(array_filter($lista, fn($t) => $t > $ahora - $ventana));
        if (!$datos[$k]) {
            unset($datos[$k]);
        }
    }
    if ($accion === 'fallo') {
        $datos[$clave][] = $ahora;
    } elseif ($accion === 'exito') {
        unset($datos[$clave]);
    }
    $bloqueado = count($datos[$clave] ?? []) >= $limite;
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, json_encode($datos));
    flock($h, LOCK_UN);
    fclose($h);
    return $bloqueado;
}

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
    soloPost();

    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';

    $claveIntentos = sha1(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . strtolower($correo));
    if (intentosLogin($claveIntentos, 'bloqueado')) {
        respuestaJson('error', 'Demasiados intentos fallidos. Espere 15 minutos antes de volver a intentarlo.');
    }

    $resultado = $usuarioModel->autenticar($correo, $clave);
    if (!$resultado['exito']) {
        intentosLogin($claveIntentos, 'fallo');
        respuestaJson('error', $resultado['mensaje']);
    }
    intentosLogin($claveIntentos, 'exito');

    // Iniciar sesión centralizada a través de SessionTrait
    $sesionManager->iniciarSesion($resultado['usuario']);

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

    $mensajeGenerico = 'Si el correo electrónico se encuentra registrado en el sistema, recibirá las instrucciones de recuperación en su bandeja de entrada.';
    $enlace = null;

    // Si el usuario existe y está activo, se prepara el enlace firmado
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
    }

    // Se responde de inmediato (mismo mensaje exista o no el correo, sin diferencia de tiempos) y el envío SMTP sigue en segundo plano
    responderYContinuar('exito', $mensajeGenerico);

    if ($enlace !== null) {
        $resultadoEnvio = enviarRecuperacionClave($usuario['correo'], $usuario['nombre'], $enlace);
        if (!$resultadoEnvio['exito']) {
            $dirLogs = __DIR__ . '/../logs';
            if (!is_dir($dirLogs)) {
                @mkdir($dirLogs, 0777, true);
            }
            $logMsg = '[' . date('Y-m-d H:i:s') . '] Falló envío a ' . $usuario['correo'] . ': ' . $resultadoEnvio['mensaje'] . PHP_EOL;
            @file_put_contents($dirLogs . '/recuperacion.log', $logMsg, FILE_APPEND);
            error_log('[RECUPERACION CLAVE] ' . $logMsg);
        }
    }
    exit;

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

    $resultado = $usuarioModel->restablecerClaveConToken($token, $nuevaClave, $confirmarClave, TOKEN_SECRET);
    if ($resultado['exito']) {
        respuestaJson('exito', $resultado['mensaje'], [
            'redirect' => '/SP%20Perfect%20Color/login'
        ]);
    } else {
        respuestaJson('error', $resultado['mensaje']);
    }

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}