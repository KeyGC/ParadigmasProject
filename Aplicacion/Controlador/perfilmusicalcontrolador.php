<?php
require_once __DIR__ . '/../Modelo/perfilmusicalmodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

$modelo = new PerfilMusicalModelo();
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getPerfilado':
        $perfilId = $_GET['perfilId'] ?? null;
        if (!$perfilId) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        $resultado = $modelo->generarPerfilado($perfilId);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}