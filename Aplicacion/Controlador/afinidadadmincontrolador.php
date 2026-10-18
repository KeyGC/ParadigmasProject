<?php
require_once __DIR__ . '/../Modelo/afinidadmodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

exigirSesion();
exigirRol(['admin']);


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
