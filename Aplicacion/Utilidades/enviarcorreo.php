<?php


require_once __DIR__ . '/../../vendor/autoload.php';

function enviarContrasenaTemporal($correoDestino, $nombre, $contraTemporal) {
    if (!defined('BREVO_API_KEY') || BREVO_API_KEY === '') {
        error_log("Brevo: BREVO_API_KEY no configurada");
        return false;
    }

    $cuerpo = [
        'sender' => [
            'name' => 'Sistema de Perfiles',
            'email' => 'matchloveweb@gmail.com'
        ],
        'to' => [
            [
                'email' => $correoDestino,
                'name' => $nombre
            ]
        ],
        'subject' => 'Tu contraseña temporal - Sistema de Perfiles',
        'htmlContent' => "
            <h2>¡Hola, {$nombre}!</h2>
            <p>Tu registro fue exitoso. Esta es tu contraseña temporal:</p>
            <p style='font-size: 18px; font-weight: bold; background: #f4f6f9; padding: 10px; border-radius: 5px;'>{$contraTemporal}</p>
            <p>Te recomendamos cambiarla luego de iniciar sesión.</p>
        ",
        'textContent' => "Hola {$nombre}, tu contraseña temporal es: {$contraTemporal}"
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');

    $opciones = [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . BREVO_API_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_POSTFIELDS => json_encode($cuerpo)
    ];

    curl_setopt_array($ch, $opciones);

    $respuesta = curl_exec($ch);
    $errorCurl = curl_errno($ch);
    $codigoHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($respuesta === false || $errorCurl) {
        error_log("Brevo: fallo cURL (errno={$errorCurl}, " . curl_strerror($errorCurl ?: 0) . ")");
        return false;
    }

    if ($codigoHttp < 200 || $codigoHttp >= 300) {
        error_log("Brevo: error HTTP {$codigoHttp}: {$respuesta}");
        return false;
    }

    return true;
}