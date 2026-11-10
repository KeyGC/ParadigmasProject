<?php
require_once __DIR__ . '/../Modelo/retomodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$retoModelo = new RetoModelo();
$perfilId = (int) $_SESSION['perfil']['tbperfilid'];
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getRetos':
        $filtro = $_GET['filtro'] ?? 'activos';
        $retos = $retoModelo->getRetos($filtro, $perfilId);
        echo json_encode(["exito" => true, "data" => $retos]);
        break;

    case 'getRetoDetalle':
        $retoId = (int) ($_GET['retoId'] ?? 0);
        if ($retoId <= 0) {
            echo json_encode(["exito" => false, "mensaje" => "ID de reto no válido."]);
            break;
        }
        $detalle = $retoModelo->getRetoDetalle($retoId, $perfilId);
        if (!$detalle) {
            echo json_encode(["exito" => false, "mensaje" => "Reto no encontrado."]);
            break;
        }
        echo json_encode(["exito" => true, "data" => $detalle]);
        break;

    case 'crearReto':
        $tematica = $_POST['tematica'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $inicioPart = $_POST['inicioPart'] ?? '';
        $finPart = $_POST['finPart'] ?? '';
        $inicioVot = $_POST['inicioVot'] ?? '';
        $finVot = $_POST['finVot'] ?? '';

        $resultado = $retoModelo->crearReto($perfilId, $tematica, $descripcion, $inicioPart, $finPart, $inicioVot, $finVot);
        echo json_encode($resultado);
        break;

    case 'inscribirFoto':
        $retoId = (int) ($_POST['retoId'] ?? 0);
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $archivo = $_FILES['foto'] ?? null;

        if (!$archivo || empty($archivo['tmp_name'])) {
            echo json_encode(["exito" => false, "mensaje" => "Debe subir una fotografía desde su dispositivo."]);
            break;
        }

        $resultado = $retoModelo->inscribirFotografia(
            $retoId,
            $perfilId,
            $titulo,
            $descripcion,
            $archivo['tmp_name'],
            $archivo['name'] ?? 'foto.jpg'
        );
        echo json_encode($resultado);
        break;

    case 'retirarFoto':
        $fotoId = (int) ($_POST['fotoId'] ?? 0);
        if ($fotoId <= 0) {
            echo json_encode(["exito" => false, "mensaje" => "ID de fotografía no válido."]);
            break;
        }
        $resultado = $retoModelo->retirarFotografia($fotoId, $perfilId);
        echo json_encode($resultado);
        break;

    case 'votar':
        $fotoId = (int) ($_POST['fotoId'] ?? 0);
        $estrellas = (int) ($_POST['estrellas'] ?? 0);

        if ($fotoId <= 0 || $estrellas < 1 || $estrellas > 5) {
            echo json_encode(["exito" => false, "mensaje" => "Parámetros de votación no válidos."]);
            break;
        }

        $resultado = $retoModelo->votarFotografia($fotoId, $perfilId, $estrellas);
        echo json_encode($resultado);
        break;

    case 'getRanking':
        $retoId = (int) ($_GET['retoId'] ?? 0);
        if ($retoId <= 0) {
            echo json_encode(["exito" => false, "mensaje" => "ID de reto no válido."]);
            break;
        }
        $ranking = $retoModelo->getRankingReto($retoId, $perfilId);
        if (!$ranking) {
            echo json_encode(["exito" => false, "mensaje" => "Reto no encontrado."]);
            break;
        }
        echo json_encode(["exito" => true, "data" => $ranking]);
        break;

    case 'getRetosAnteriores':
        $anteriores = $retoModelo->getRetosAnteriores();
        echo json_encode(["exito" => true, "data" => $anteriores]);
        break;

    case 'getInvitaciones':
        $invitaciones = $retoModelo->getInvitacionesPerfil($perfilId);
        $contador = $retoModelo->contarInvitacionesNoLeidas($perfilId);
        echo json_encode([
            "exito" => true,
            "data" => [
                "invitaciones" => $invitaciones,
                "noLeidas" => $contador
            ]
        ]);
        break;

    case 'marcarInvitacion':
        $invitacionId = (int) ($_POST['invitacionId'] ?? 0);
        if ($invitacionId <= 0) {
            echo json_encode(["exito" => false, "mensaje" => "ID inválido."]);
            break;
        }
        $ok = $retoModelo->marcarInvitacionLeida($invitacionId, $perfilId);
        echo json_encode(["exito" => (bool) $ok]);
        break;

    case 'marcarTodasInvitaciones':
        $ok = $retoModelo->marcarTodasInvitacionesLeidas($perfilId);
        echo json_encode(["exito" => (bool) $ok]);
        break;

    case 'getPerfilFotografico':
        $targetId = (int) ($_GET['perfilId'] ?? $perfilId);
        if ($targetId <= 0) {
            $targetId = $perfilId;
        }
        $perfilFoto = $retoModelo->getPerfilFotografico($targetId);
        echo json_encode(["exito" => true, "data" => $perfilFoto]);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida."]);
        break;
}
