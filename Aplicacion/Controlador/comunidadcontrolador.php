<?php
require_once __DIR__ . '/../Modelo/comunidadmodelo.php';
require_once __DIR__ . '/../Modelo/comunidadmensajemodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$comunidadModelo = new ComunidadModelo();
$comunidadMensajeModelo = new ComunidadMensajeModelo();
$perfilId = (int) $_SESSION['perfil']['tbperfilid'];

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getComunidades':
        echo json_encode($comunidadModelo->getComunidadesActivas());
        break;

    case 'getMensajesComunidad':
        $comunidadId = (int) ($_GET['comunidadId'] ?? 0);
        $desdeId = (int) ($_GET['desdeId'] ?? 0);

        if (!$comunidadModelo->getComunidadPorId($comunidadId)['exito']) {
            echo json_encode(["exito" => false, "mensaje" => "La comunidad no existe o está inactiva"]);
            break;
        }

        echo json_encode($comunidadMensajeModelo->getMensajesNuevos($comunidadId, $desdeId));
        break;

    case 'enviarMensajeComunidad':
        $comunidadId = (int) ($_POST['comunidadId'] ?? 0);
        $texto = $_POST['texto'] ?? '';

        echo json_encode($comunidadMensajeModelo->enviarMensaje($comunidadId, $perfilId, $texto));
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}