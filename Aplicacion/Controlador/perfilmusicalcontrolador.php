<?php
require_once __DIR__ . '/../Modelo/perfilmusicalmodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

exigirSesion();
exigirRol(['admin']);


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