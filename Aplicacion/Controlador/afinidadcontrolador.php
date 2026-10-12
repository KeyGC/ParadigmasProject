<?php
require_once __DIR__ . '/../Modelo/afinidadmodelo.php';
require_once __DIR__ . '/../Modelo/grupomodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$afinidadModelo = new AfinidadModelo();
$grupoModelo = new GrupoModelo();
$perfilId = (int) $_SESSION['perfil']['tbperfilid'];

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getPendientes':
        echo json_encode($afinidadModelo->pendientesDePerfil($perfilId));
        break;

    case 'getRecomendadas':
        $afinidadModelo->generarSiCorresponde();
        echo json_encode($afinidadModelo->misAfinidades($perfilId));
        break;

    case 'aceptar':
        $afinidadId = (int) ($_POST['afinidadId'] ?? 0);
        echo json_encode($afinidadModelo->aceptar($afinidadId, $perfilId));
        break;

    case 'rechazar':
        $afinidadId = (int) ($_POST['afinidadId'] ?? 0);
        echo json_encode($afinidadModelo->rechazar($afinidadId, $perfilId));
        break;

    case 'contador':
        $afinidadModelo->generarSiCorresponde();
        $pendientes = $afinidadModelo->contarPendientes($perfilId);
        $mensajes = $grupoModelo->contarNoLeidos($perfilId);
        echo json_encode([
            "exito" => true,
            "data" => [
                "pendientes" => $pendientes,
                "mensajes" => $mensajes,
                "total" => $pendientes + $mensajes
            ]
        ]);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}
