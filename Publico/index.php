<?php
// Publico/index.php
require_once __DIR__ . '/../Configuracion/Configuracion.php';

$vista = $_GET['vista'] ?? 'login';

switch ($vista) {
    case 'registro':
        require_once APP_PATH . '/Vista/registro.php';
        break;
    case 'login':
    default:
        require_once APP_PATH . '/Vista/Login.php';
        break;
    case 'perfiles':
        require_once APP_PATH . '/Vista/perfiles.php';
        break;
    case 'cliente':
        require_once APP_PATH . '/Vista/cliente.php';
        break;
}