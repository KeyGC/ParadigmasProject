<?php

require_once __DIR__ . '/../Configuracion/configuracion.php';

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
        require_once APP_PATH . '/Vista/Cliente/login.php';
        break;
    case 'perfiles':
        require_once APP_PATH . '/Vista/Admin/perfiles.php';
        break;
    case 'cliente':
        require_once APP_PATH . '/Vista/Cliente/index.php';
        break;
    case 'perfil':
        require_once APP_PATH . '/Vista/Cliente/perfil.php';
        break;
    case 'cambiarContra':
        require_once APP_PATH . '/Vista/Cliente/cambiarcontra.php';
        break;
        
}