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

require_once __DIR__ . '/basedatos.php';