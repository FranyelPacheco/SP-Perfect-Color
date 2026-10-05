<?php
// ARCHIVO: correoHelper.php
// OBJETIVO: Funciones reutilizables para envío de correos vía SMTP utilizando PHPMailer

namespace App\Helpers;

use PHPMailer\PHPMailer\{
    PHPMailer,
    Exception
};

// FUNCIÓN: obtenerConfiguracionCorreo
// OBJETIVO: Cargar los parámetros de configuración SMTP
function obtenerConfiguracionCorreo(): array
{
    $rutaConfig = __DIR__ . '/../config/correoConfig.php';
    if (file_exists($rutaConfig)) {
        return require $rutaConfig;
    }
    return [
        'smtp_host'       => 'sandbox.smtp.mailtrap.io',
        'smtp_port'       => 2525,
        'smtp_auth'       => true,
        'smtp_user'       => '',
        'smtp_pass'       => '',
        'smtp_secure'     => 'tls',
        'smtp_from_email' => 'no-reply@spperfectcolor.com',
        'smtp_from_name'  => 'SP Perfect Color',
        'smtp_debug'      => false,
    ];
}

// FUNCIÓN: crearInstanciaMailer
// OBJETIVO: Instanciar y configurar el objeto PHPMailer con soporte UTF-8 y SMTP
function crearInstanciaMailer(): PHPMailer
{
    $config = obtenerConfiguracionCorreo();
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = $config['smtp_auth'];
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->Port       = (int)$config['smtp_port'];

    if (!empty($config['smtp_secure'])) {
        $mail->SMTPSecure = $config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);

    return $mail;
}

// FUNCIÓN: enviarCorreo
// OBJETIVO: Envía un correo electrónico HTML mediante PHPMailer
function enviarCorreo(string $destinatario, string $nombreDestinatario, string $asunto, string $cuerpoHtml, array $adjuntos = []): array
{
    try {
        $mail = crearInstanciaMailer();
        $mail->addAddress($destinatario, $nombreDestinatario);

        // Adjuntar archivos si se especificaron
        foreach ($adjuntos as $adjunto) {
            if (is_array($adjunto) && isset($adjunto['ruta'])) {
                $mail->addAttachment($adjunto['ruta'], $adjunto['nombre'] ?? '');
            } elseif (is_string($adjunto) && file_exists($adjunto)) {
                $mail->addAttachment($adjunto);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $cuerpoHtml));

        $mail->send();

        return [
            'exito'   => true,
            'mensaje' => 'Correo enviado exitosamente.'
        ];
    } catch (Exception $e) {
        error_log('[ERROR SMTP PHPMailer] No se pudo enviar el correo a ' . $destinatario . ': ' . $e->getMessage());
        return [
            'exito'   => false,
            'mensaje' => 'Error al enviar el correo: ' . $e->getMessage()
        ];
    } catch (\Throwable $t) {
        error_log('[ERROR PHPMailer] Error inesperado: ' . $t->getMessage());
        return [
            'exito'   => false,
            'mensaje' => 'Error inesperado al enviar el correo.'
        ];
    }
}

// FUNCIÓN: plantillaBaseCorreo
// OBJETIVO: Generar diseño HTML con la identidad visual corporativa de SP Perfect Color
function plantillaBaseCorreo(string $titulo, string $contenidoHtml): string
{
    $anio = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333333;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #1E3A5F; padding: 25px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">SP Perfect Color</h1>
                            <p style="color: #cbd5e1; margin: 5px 0 0 0; font-size: 13px;">Sistema de Gestión Administrativa</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px; line-height: 1.6;">
                            {$contenidoHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
                            <p style="margin: 0;">&copy; {$anio} SP Perfect Color. Todos los derechos reservados.</p>
                            <p style="margin: 4px 0 0 0;">Este es un mensaje automático, por favor no responda a este correo.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

// FUNCIÓN: enviarRecuperacionClave
// OBJETIVO: Envía correo con código o enlace para recuperación de acceso
function enviarRecuperacionClave(string $destinatario, string $nombre, string $enlaceRecuperacion): array
{
    $asunto = 'Recuperación de Contraseña - SP Perfect Color';
    $contenido = <<<HTML
    <h2 style="color: #1E3A5F; margin-top: 0; font-size: 20px;">Hola, {$nombre}</h2>
    <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta en el sistema de gestión <strong>SP Perfect Color</strong>.</p>
    <p>Para ingresar su nueva contraseña, por favor haga clic en el siguiente botón:</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{$enlaceRecuperacion}" style="background-color: #1E3A5F; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; display: inline-block;">Restablecer Contraseña</a>
    </div>
    <p style="color: #64748b; font-size: 13px;">Si no ha solicitado este cambio, puede ignorar este mensaje de manera segura. Su contraseña actual no sufrirá modificaciones.</p>
    <p style="color: #64748b; font-size: 13px;">Este enlace es válido temporalmente.</p>
HTML;

    $cuerpoHtml = plantillaBaseCorreo($asunto, $contenido);
    return enviarCorreo($destinatario, $nombre, $asunto, $cuerpoHtml);
}

// FUNCIÓN: enviarNotificacionCambioClave
// OBJETIVO: Envía un correo notificando que la contraseña de la cuenta ha sido modificada
function enviarNotificacionCambioClave(string $destinatario, string $nombre): array
{
    $asunto = 'Seguridad: Su contraseña ha sido cambiada - SP Perfect Color';
    $fecha = date('d/m/Y H:i:s');
    $contenido = <<<HTML
    <h2 style="color: #1E3A5F; margin-top: 0; font-size: 20px;">Hola, {$nombre}</h2>
    <p>Le informamos que la contraseña de su cuenta en <strong>SP Perfect Color</strong> ha sido cambiada exitosamente el <strong>{$fecha}</strong>.</p>
    <div style="background-color: #f1f5f9; border-left: 4px solid #1E3A5F; padding: 12px 16px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0; font-size: 14px; color: #334155;"><strong>¿Fue usted?</strong> Si usted realizó este cambio, puede ignorar este aviso de seguridad.</p>
    </div>
    <p style="color: #e11d48; font-size: 13px; font-weight: 600;"><strong>¿No fue usted?</strong> Si no reconoce este cambio, comuníquese de inmediato con el Administrador del sistema para proteger su cuenta.</p>
HTML;

    $cuerpoHtml = plantillaBaseCorreo($asunto, $contenido);
    return enviarCorreo($destinatario, $nombre, $asunto, $cuerpoHtml);
}

// FUNCIÓN: enviarCredencialesUsuario
// OBJETIVO: Envía correo de bienvenida con las credenciales de acceso cuando el Administrador registra a un usuario
function enviarCredencialesUsuario(string $destinatario, string $nombre, string $claveTemporal): array
{
    $asunto = 'Bienvenido a SP Perfect Color - Sus credenciales de acceso';
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $enlaceLogin = "{$protocolo}{$host}/SP%20Perfect%20Color/login";

    $contenido = <<<HTML
    <h2 style="color: #1E3A5F; margin-top: 0; font-size: 20px;">¡Bienvenido, {$nombre}!</h2>
    <p>Se le ha creado una cuenta de acceso en el sistema de gestión administrativa de <strong>SP Perfect Color</strong>.</p>
    <p>Sus datos de inicio de sesión son:</p>
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px 20px; margin: 20px 0;">
        <p style="margin: 0 0 8px 0; font-size: 14px;"><strong>Usuario / Correo:</strong> <span style="color: #1E3A5F;">{$destinatario}</span></p>
        <p style="margin: 0; font-size: 14px;"><strong>Contraseña inicial:</strong> <code style="background-color: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 15px; color: #0f172a;">{$claveTemporal}</code></p>
    </div>
    <div style="text-align: center; margin: 25px 0;">
        <a href="{$enlaceLogin}" style="background-color: #1E3A5F; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; display: inline-block;">Ingresar al Sistema</a>
    </div>
    <p style="color: #64748b; font-size: 13px;">Le recomendamos ingresar a <strong>Mi Perfil</strong> para cambiar su contraseña por una de su preferencia.</p>
HTML;

    $cuerpoHtml = plantillaBaseCorreo($asunto, $contenido);
    return enviarCorreo($destinatario, $nombre, $asunto, $cuerpoHtml);
}


