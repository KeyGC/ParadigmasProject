<?php
require_once __DIR__ . '/../Utilidades/autenticacion.php';
require_once __DIR__ . '/../Modelo/cancionchatmodelo.php';

header('Content-Type: application/json; charset=utf-8');

exigirSesion();

$perfilId = (int) ($_SESSION['perfil']['tbperfilid'] ?? 0);
$cancionChatModelo = new CancionChatModelo();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getMensajes':
        $cancionId = (int) ($_GET['cancionId'] ?? 0);
        $desdeId = (int) ($_GET['desdeId'] ?? 0);

        $resultado = $cancionChatModelo->getMensajesNuevos($cancionId, $desdeId);
        if (!$resultado['exito']) {
            http_response_code(400);
        }
        echo json_encode($resultado);
        break;

    case 'enviarMensaje':
        $cancionId = (int) ($_POST['cancionId'] ?? 0);
        $texto = $_POST['texto'] ?? '';

        $resultado = $cancionChatModelo->enviarMensaje($cancionId, $perfilId, $texto);
        if (!$resultado['exito']) {
            http_response_code(400);
        }
        echo json_encode($resultado);
        break;

    default:
        http_response_code(400);
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no reconocida']);
        break;
}
