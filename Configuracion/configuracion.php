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
// 0.65 (con normalización de sinónimos) alcanza para literales, mayúsculas y
// las paráfrasis más cercanas; el margen frente a benignos se valida aparte.
define('MODERACION_UMBRAL', (float) (getenv('MODERACION_UMBRAL') ?: 0.65));

// Solo los bloqueos con confianza alta alimentan el aprendizaje incremental.
define('MODERACION_UMBRAL_APRENDIZAJE', (float) (getenv('MODERACION_UMBRAL_APRENDIZAJE') ?: 0.90));

define('MODERACION_TEXTO_BLOQUEADO', '[Mensaje bloqueado por contenido inapropiado]');

// Clasificador de temas de interes (musica/comida/deporte): la PUERTA por
// terminos distintivos decide; el umbral solo desempata cuando varios temas
// pasan la puerta. Calibrado con 43 benignos (0 falsos positivos) y 50
// tematicos nuevos (84% de aciertos) en Pruebas/temas_calibracion.php.
define('TEMAS_UMBRAL', (float) (getenv('TEMAS_UMBRAL') ?: 0.35));

// Senales minimas de un usuario en un tema para considerarlo afinidad.
define('TEMAS_MINIMO_MENSAJES', (int) (getenv('TEMAS_MINIMO_MENSAJES') ?: 3));

// Tope de propuestas de afinidad nuevas por usuario y por dia (America/Costa_Rica).
define('AFINIDAD_MAX_DIARIAS', (int) (getenv('AFINIDAD_MAX_DIARIAS') ?: 5));

// Dias de enfriamiento antes de volver a proponer una afinidad rechazada.
define('AFINIDAD_ENFRIAMIENTO_DIAS', (int) (getenv('AFINIDAD_ENFRIAMIENTO_DIAS') ?: 7));

require_once BASE_PATH . '/vendor/autoload.php';
require_once __DIR__ . '/basedatos.php';