<?php
require_once __DIR__ . '/../Modelo/afinidadmodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil']) || ($_SESSION['perfil']['tbperfilrol'] ?? '') !== 'admin') {
    echo json_encode(["exito" => false, "mensaje" => "Acceso denegado"]);
    exit;
}

$afinidadModelo = new AfinidadModelo();
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getResumen':
        echo json_encode($afinidadModelo->resumenAnalisis());
        break;

    case 'cerrar':
        $afinidadId = (int) ($_POST['afinidadId'] ?? 0);
        echo json_encode($afinidadModelo->cerrar($afinidadId));
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}
