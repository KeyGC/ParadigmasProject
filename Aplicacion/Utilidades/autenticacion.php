<?php

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

function exigirRol(array $rolesPermitidos)
{
    if (!usuarioTieneRol($rolesPermitidos)) {
        header('Location: index.php?vista=login');
        exit;
    }
}

function vistaHomePorRol()
{
    if (!usuarioAutenticado()) {
        return 'login';
    }
    return $_SESSION['perfil']['tbperfilrol'] === 'admin' ? 'perfiles' : 'cliente';
}