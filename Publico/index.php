<?php
// Publico/index.php
require_once __DIR__ . '/../Configuracion/Configuracion.php';

$vista = $_GET['vista'] ?? 'login';

// Cerrar sesión
if ($vista === 'logout') {
    session_destroy();
    header('Location: index.php?vista=login');
    exit;
}

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
    case 'perfil':
        require_once APP_PATH . '/Vista/perfil.php';
        break;
    case 'cambiarContra':
        require_once APP_PATH . '/Vista/cambiarContra.php';
        break;
        
}