<?php

require_once __DIR__ . '/../Configuracion/configuracion.php';
require_once __DIR__ . '/../Aplicacion/Utilidades/autenticacion.php';

$vista = $_GET['vista'] ?? 'login';

// Cerrar sesión
if ($vista === 'logout') {
    session_destroy();
    header('Location: index.php?vista=login');
    exit;
}

// Mapa de permisos por vista
$permisos = [
    'login'         => 'publica',
    'registro'      => 'publica',
    'cliente'       => ['cliente'],
    'perfil'        => ['cliente'],
    'cambiarContra' => ['cliente', 'admin'],
    'perfiles'      => ['admin'],
];

// Si ya hay sesión activa y trata de ir a login/registro, mandarlo a su home
if (usuarioAutenticado() && in_array($vista, ['login', 'registro'], true)) {
    header('Location: index.php?vista=' . vistaHomePorRol());
    exit;
}

$rolesRequeridos = $permisos[$vista] ?? 'publica';
if ($rolesRequeridos !== 'publica') {
    exigirRol($rolesRequeridos);
}

switch ($vista) {
    case 'registro':
        require_once APP_PATH . '/Vista/Cliente/registro.php';
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