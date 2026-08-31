<?php

require_once __DIR__ . '/../Configuracion/configuracion.php';

$vista = $_GET['vista'] ?? 'login';

// Cerrar sesión
if ($vista === 'logout') {
    session_destroy();
    header('Location: index.php?vista=login');
    exit;
}

// Vistas públicas, no requieren sesión
$vistasPublicas = ['login', 'registro'];

// Vistas exclusivas de admin
$vistasAdmin = ['perfiles', 'perfilaccesos', 'perfilreproducciones', 'canciones', 'generos', 'perfilado'];

// Vistas exclusivas de cliente
$vistasCliente = ['cliente', 'perfil', 'cambiarContra'];

if (!in_array($vista, $vistasPublicas, true)) {

    // Cualquier vista no pública requiere sesión activa
    if (!isset($_SESSION['perfil'])) {
        header('Location: index.php?vista=login');
        exit;
    }

    $rol = $_SESSION['perfil']['tbperfilrol'] ?? 'cliente';

    // Un cliente no puede acceder a vistas de admin
    if (in_array($vista, $vistasAdmin, true) && $rol !== 'admin') {
        header('Location: index.php?vista=cliente');
        exit;
    }

    // Un admin no puede acceder a vistas de cliente
    if (in_array($vista, $vistasCliente, true) && $rol === 'admin') {
        header('Location: index.php?vista=perfiles');
        exit;
    }
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
    case 'perfilaccesos':
        require_once APP_PATH . '/Vista/Admin/perfilaccesos.php';
        break;
    case 'perfilreproducciones':
        require_once APP_PATH . '/Vista/Admin/perfilreproducciones.php';
        break;
    case 'perfilubicaciones':
        require_once APP_PATH . '/Vista/Admin/perfilubicaciones.php';
        break;
    case 'perfilado':
        require_once APP_PATH . '/Vista/Admin/perfilado.php';
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
    case 'canciones':
        require_once APP_PATH . '/Vista/Admin/canciones.php';
        break;
    case 'generos':
        require_once APP_PATH . '/Vista/Admin/generos.php';
        break;
}