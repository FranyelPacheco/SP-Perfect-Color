<?php
require_once __DIR__ . '/app/helpers/envHelper.php';
require_once __DIR__ . '/vendor/autoload.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Toda petición POST debe traer el token de la sesión (cabecera o campo de formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], (string)$recibido)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['estado' => 'error', 'mensaje' => 'Sesión de seguridad expirada. Recargue la página e intente de nuevo.', 'datos' => null], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$objeto = new \App\Controllers\frontController();
