<?php

require_once __DIR__ . '/../Modelo/eventoafinidadgeneromodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new EventoAfinidadGeneroModelo();
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getAfinidadGenero':
        $idPerfil = $_GET['idPerfil'] ?? ($_SESSION['perfil']['tbperfilid'] ?? null);

        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        if ($_SESSION['perfil']['tbperfilrol'] !== 'admin' && (int)$idPerfil !== (int)$_SESSION['perfil']['tbperfilid']) {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }

        if (!$idPerfil) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        echo json_encode($modelo->generarAfinidadGenero($idPerfil));
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}