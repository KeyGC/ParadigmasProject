<?php

require_once __DIR__ . '/../Modelo/perfilaccesomodelo.php';
require_once __DIR__ . '/../Modelo/perfilmodelo.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new PerfilAccesoModelo();
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getMatriz':
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }

        $idPerfil = $_GET['idPerfil'] ?? null;
        if (!$idPerfil) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        $perfilModelo = new PerfilModelo();
        $perfil = $perfilModelo->getPerfil($idPerfil);
        if (!$perfil) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
            break;
        }

        $matriz = $modelo->getMatriz($idPerfil);

        echo json_encode([
            "exito" => true,
            "data" => [
                "perfil" => $perfil['tbperfilnombre'],
                "fechaPrimera" => $matriz['fechaPrimera'] ?? null,
                "fechaUltima" => $matriz['fechaUltima'] ?? null,
                "semanas" => $matriz['semanas'] ?? []
            ]
        ]);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}