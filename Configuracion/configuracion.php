<?php

date_default_timezone_set('America/Costa_Rica');

session_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!function_exists('esPeticionApi')) {
    function esPeticionApi()
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        return strpos($script, 'api') === 0;
    }
}

if (PHP_SAPI !== 'cli') {
    $mensajeErrorServidor = 'Ocurrió un error inesperado en el servidor. Intente nuevamente.';

    set_exception_handler(function (Throwable $e) use ($mensajeErrorServidor) {
        error_log('[excepcion] ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        if (headers_sent()) {
            return;
        }
        http_response_code(500);
        if (esPeticionApi()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'tipo' => 'servidor', 'mensaje' => $mensajeErrorServidor]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif;padding:2rem"><h2>Ocurrió un error inesperado</h2><p>Intente nuevamente más tarde o regrese al <a href="index.php">inicio</a>.</p></body></html>';
        }
        exit;
    });

    register_shutdown_function(function () use ($mensajeErrorServidor) {
        $error = error_get_last();
        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }
        error_log('[fatal] ' . $error['message'] . ' en ' . $error['file'] . ':' . $error['line']);
        if (headers_sent()) {
            return;
        }
        http_response_code(500);
        if (esPeticionApi()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['exito' => false, 'tipo' => 'servidor', 'mensaje' => $mensajeErrorServidor]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif;padding:2rem"><h2>Ocurrió un error inesperado</h2></body></html>';
        }
    });

    set_error_handler(function ($severidad, $mensaje, $archivo, $linea) {
        if (!(error_reporting() & $severidad)) {
            return false;
        }
        error_log('[php] ' . $mensaje . ' en ' . $archivo . ':' . $linea);
        return true;
    });
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/Aplicacion');

define('QDRANT_HOST', getenv('QDRANT_HOST') ?: 'c3023b3b-8305-41f6-bce5-61f701dbe408.sa-east-1-0.aws.cloud.qdrant.io');
define('QDRANT_PORT', getenv('QDRANT_PORT') ?: 6333);
define('QDRANT_API_KEY', getenv('QDRANT_API_KEY') ?: 'CAMBIAR_EN_ENV');

define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');

define('BIOMETRIA_UMBRAL', (float) (getenv('BIOMETRIA_UMBRAL') ?: 0.93));

define('MODERACION_UMBRAL', (float) (getenv('MODERACION_UMBRAL') ?: 0.65));

define('MODERACION_UMBRAL_APRENDIZAJE', (float) (getenv('MODERACION_UMBRAL_APRENDIZAJE') ?: 0.90));

define('MODERACION_TEXTO_BLOQUEADO', '[Mensaje bloqueado por contenido inapropiado]');

// Clasificador de temas de interes (musica/comida/deporte)
define('TEMAS_UMBRAL', (float) (getenv('TEMAS_UMBRAL') ?: 0.35));

// Senales minimas de un usuario en un tema para considerarlo afinidad.
define('TEMAS_MINIMO_MENSAJES', (int) (getenv('TEMAS_MINIMO_MENSAJES') ?: 3));

// Tope de propuestas de afinidad nuevas por usuario y por dia (America/Costa_Rica).
define('AFINIDAD_MAX_DIARIAS', (int) (getenv('AFINIDAD_MAX_DIARIAS') ?: 5));

// Dias de enfriamiento antes de volver a proponer una afinidad rechazada.
define('AFINIDAD_ENFRIAMIENTO_DIAS', (int) (getenv('AFINIDAD_ENFRIAMIENTO_DIAS') ?: 7));

require_once BASE_PATH . '/vendor/autoload.php';
require_once __DIR__ . '/basedatos.php';