<?php

function obtenerTimezoneSistema()
{
    if (is_link('/etc/localtime')) {
        $destino = readlink('/etc/localtime');
        $partes = explode('/usr/share/zoneinfo/', $destino);

        if (isset($partes[1])) {
            return $partes[1];
        }
    }

    return null;
}

$timezoneSistema = obtenerTimezoneSistema();

if ($timezoneSistema) {
    date_default_timezone_set($timezoneSistema);
}

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/Aplicacion');

define('QDRANT_HOST', getenv('QDRANT_HOST') ?: 'c3023b3b-8305-41f6-bce5-61f701dbe408.sa-east-1-0.aws.cloud.qdrant.io');
define('QDRANT_PORT', getenv('QDRANT_PORT') ?: 6333);
define('QDRANT_API_KEY', getenv('QDRANT_API_KEY') ?: 'CAMBIAR_EN_ENV');

require_once BASE_PATH . '/vendor/autoload.php';
require_once __DIR__ . '/basedatos.php';