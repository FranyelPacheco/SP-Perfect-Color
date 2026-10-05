<?php
// ARCHIVO: correoConfig.php
// OBJETIVO: Configuración de parámetros SMTP para envíos de correo con PHPMailer

return [
    'smtp_host'       => 'smtp.gmail.com',
    'smtp_port'       => 587,
    'smtp_auth'       => true,
    'smtp_user'       => 'franyel.pachecocv@gmail.com',
    'smtp_pass'       => 'lfjhpahnyjdylsix',
    'smtp_secure'     => 'tls',
    'smtp_from_email' => 'franyel.pachecocv@gmail.com',
    'smtp_from_name'  => 'SP Perfect Color',
    'smtp_debug'      => false,
];
