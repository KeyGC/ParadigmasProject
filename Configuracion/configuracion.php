<?php

date_default_timezone_set('America/Costa_Rica');

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/Aplicacion');

define('QDRANT_HOST', getenv('QDRANT_HOST') ?: 'c3023b3b-8305-41f6-bce5-61f701dbe408.sa-east-1-0.aws.cloud.qdrant.io');
define('QDRANT_PORT', getenv('QDRANT_PORT') ?: 6333);
define('QDRANT_API_KEY', getenv('QDRANT_API_KEY') ?: 'CAMBIAR_EN_ENV');

define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');

define('BIOMETRIA_UMBRAL', (float) (getenv('BIOMETRIA_UMBRAL') ?: 0.93));

// Moderacion de mensajes: similitud TF-IDF/Coseno contra el dataset semilla.
// 0.75 solo alcanza para coincidencias casi literales (ver notas del reporte).
define('MODERACION_UMBRAL', (float) (getenv('MODERACION_UMBRAL') ?: 0.75));

// Solo los bloqueos con confianza alta alimentan el aprendizaje incremental.
define('MODERACION_UMBRAL_APRENDIZAJE', (float) (getenv('MODERACION_UMBRAL_APRENDIZAJE') ?: 0.90));

define('MODERACION_TEXTO_BLOQUEADO', '[Mensaje bloqueado por contenido inapropiado]');

require_once BASE_PATH . '/vendor/autoload.php';
require_once __DIR__ . '/basedatos.php';