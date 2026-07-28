<?php
// Configuracion/Configuracion.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/Aplicacion');

require_once __DIR__ . '/Basedatos.php';