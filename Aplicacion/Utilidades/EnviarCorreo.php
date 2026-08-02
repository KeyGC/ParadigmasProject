<?php
// Aplicacion/Utilidades/EnviarCorreo.php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarContrasenaTemporal($correoDestino, $nombre, $contraTemporal) {
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'paradigmasproject@gmail.com';      
        $mail->Password   = 'teoh psbc ffph eboc'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom('paradigmasproject@gmail.com', 'Sistema de Perfiles');
        $mail->addAddress($correoDestino, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Tu contraseña temporal - Sistema de Perfiles';
        $mail->Body    = "
            <h2>¡Hola, {$nombre}!</h2>
            <p>Tu registro fue exitoso. Esta es tu contraseña temporal:</p>
            <p style='font-size: 18px; font-weight: bold; background: #f4f6f9; padding: 10px; border-radius: 5px;'>{$contraTemporal}</p>
            <p>Te recomendamos cambiarla luego de iniciar sesión.</p>
        ";
        $mail->AltBody = "Hola {$nombre}, tu contraseña temporal es: {$contraTemporal}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}