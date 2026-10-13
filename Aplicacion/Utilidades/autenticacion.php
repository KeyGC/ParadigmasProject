<?php

if (!function_exists('esPeticionApi')) {
    function esPeticionApi()
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        return strpos($script, 'api') === 0;
    }
}

function usuarioAutenticado()
{
    return isset($_SESSION['perfil']);
}

function usuarioTieneRol(array $rolesPermitidos)
{
    if (!usuarioAutenticado()) {
        return false;
    }
    return in_array($_SESSION['perfil']['tbperfilrol'], $rolesPermitidos, true);
}

function denegarAcceso($autenticado)
{
    if (esPeticionApi()) {
        http_response_code($autenticado ? 403 : 401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'exito' => false,
            'tipo' => $autenticado ? 'permiso' : 'autenticacion',
            'mensaje' => $autenticado
                ? 'No tiene permisos para realizar esta acción.'
                : 'Sesión no iniciada. Inicie sesión para continuar.'
        ]);
        exit;
    }

    header('Location: index.php?vista=login');
    exit;
}

function exigirSesion()
{
    if (!usuarioAutenticado()) {
        denegarAcceso(false);
    }
}

function exigirRol(array $rolesPermitidos)
{
    if (!usuarioTieneRol($rolesPermitidos)) {
        denegarAcceso(usuarioAutenticado());
    }
}

function vistaHomePorRol()
{
    if (!usuarioAutenticado()) {
        return 'login';
    }
    return $_SESSION['perfil']['tbperfilrol'] === 'admin' ? 'perfiles' : 'cliente';
}