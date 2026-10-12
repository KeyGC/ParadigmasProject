<?php
require_once __DIR__ . '/../Modelo/grupomodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$grupoModelo = new GrupoModelo();
$perfilId = (int) $_SESSION['perfil']['tbperfilid'];

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getGrupos':
        echo json_encode($grupoModelo->gruposDePerfil($perfilId));
        break;

    case 'getMensajesGrupo':
        $grupoId = (int) ($_GET['grupoId'] ?? 0);
        $desdeId = (int) ($_GET['desdeId'] ?? 0);
        echo json_encode($grupoModelo->getMensajesNuevos($grupoId, $perfilId, $desdeId));
        break;

    case 'enviarMensajeGrupo':
        $grupoId = (int) ($_POST['grupoId'] ?? 0);
        $texto = $_POST['texto'] ?? '';
        echo json_encode($grupoModelo->enviarMensaje($grupoId, $perfilId, $texto));
        break;

    case 'marcarAbierto':
        $grupoId = (int) ($_POST['grupoId'] ?? 0);
        echo json_encode($grupoModelo->marcarAbierto($grupoId, $perfilId));
        break;

    case 'getMiembros':
        $grupoId = (int) ($_GET['grupoId'] ?? 0);

        if (!$grupoModelo->esMiembro($grupoId, $perfilId)) {
            echo json_encode(["exito" => false, "mensaje" => "No perteneces a este grupo"]);
            break;
        }

        echo json_encode(["exito" => true, "data" => $grupoModelo->miembros($grupoId)]);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}
