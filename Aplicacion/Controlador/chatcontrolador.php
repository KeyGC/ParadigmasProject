<?php
require_once __DIR__ . '/../Modelo/conversacionmodelo.php';
require_once __DIR__ . '/../Modelo/mensajemodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$conversacionModelo = new ConversacionModelo();
$mensajeModelo = new MensajeModelo();
$perfilId = (int) $_SESSION['perfil']['tbperfilid'];

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getConversaciones':
        echo json_encode($conversacionModelo->getConversacionesPorPerfil($perfilId));
        break;

    case 'obtenerOCrearConversacion':
        $otroId = (int) ($_POST['perfilId'] ?? $_GET['perfilId'] ?? 0);

        if ($otroId <= 0) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil inválido"]);
            break;
        }

        echo json_encode($conversacionModelo->obtenerOCrear($perfilId, $otroId));
        break;

    case 'enviarMensaje':
        $conversacionId = (int) ($_POST['conversacionId'] ?? 0);
        $texto = $_POST['texto'] ?? '';

        echo json_encode($mensajeModelo->enviarMensaje($conversacionId, $perfilId, $texto));
        break;

    case 'getMensajesNuevos':
        $conversacionId = (int) ($_GET['conversacionId'] ?? 0);
        $desdeId = (int) ($_GET['desdeId'] ?? 0);

        if (!$conversacionModelo->esParticipante($conversacionId, $perfilId)) {
            echo json_encode(["exito" => false, "mensaje" => "No perteneces a esta conversación"]);
            break;
        }

        echo json_encode($mensajeModelo->getMensajesNuevos($conversacionId, $desdeId));
        break;

    case 'marcarLeidos':
        $conversacionId = (int) ($_POST['conversacionId'] ?? $_GET['conversacionId'] ?? 0);

        if (!$conversacionModelo->esParticipante($conversacionId, $perfilId)) {
            echo json_encode(["exito" => false, "mensaje" => "No perteneces a esta conversación"]);
            break;
        }

        echo json_encode($mensajeModelo->marcarLeidos($conversacionId, $perfilId));
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}