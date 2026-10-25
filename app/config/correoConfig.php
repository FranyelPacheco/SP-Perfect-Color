<?php
// ARCHIVO: correoConfig.php
// OBJETIVO: Configuración de parámetros SMTP para envíos de correo con PHPMailer (valores desde .env)

return [
    'smtp_host'       => env('SMTP_HOST', 'smtp.gmail.com'),
    'smtp_port'       => (int)env('SMTP_PORT', 587),
    'smtp_auth'       => true,
    'smtp_user'       => env('SMTP_USER', ''),
    'smtp_pass'       => env('SMTP_PASS', ''),
    'smtp_secure'     => env('SMTP_SECURE', 'tls'),
    'smtp_from_email' => env('SMTP_FROM_EMAIL', ''),
    'smtp_from_name'  => env('SMTP_FROM_NAME', 'SP Perfect Color'),
    'smtp_debug'      => false,
];
