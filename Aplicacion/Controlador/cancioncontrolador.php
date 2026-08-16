<?php

require_once __DIR__ . '/../Modelo/cancionmodelo.php';
require_once __DIR__ . '/../Modelo/reproduccionmodelo.php';

header('Content-Type: application/json; charset=utf-8');

$cancionModelo = new CancionModelo();
$reproduccionModelo = new ReproduccionModelo();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getCanciones':
        $generoId = $_GET['generoId'] ?? null;
        $canciones = $generoId
            ? $cancionModelo->getPorGenero($generoId)
            : $cancionModelo->getList();
        echo json_encode(["exito" => true, "data" => $canciones]);
        break;

    case 'registrarTiempo':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $perfilId = $_SESSION['perfil']['tbperfilid'];
        $cancionId = $_POST['cancionId'] ?? null;
        $segundos = $_POST['segundos'] ?? null;

        if (!$cancionId || !$segundos || (int)$segundos <= 0) {
            echo json_encode(["exito" => false, "mensaje" => "Datos incompletos o inválidos"]);
            break;
        }

        $ok = $reproduccionModelo->acumularTiempo($perfilId, (int)$cancionId, (int)$segundos);

        echo $ok
            ? json_encode(["exito" => true, "mensaje" => "Tiempo registrado"])
            : json_encode(["exito" => false, "mensaje" => "Error al registrar el tiempo"]);

        break;

    case 'registrarReproduccion':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $perfilId = $_SESSION['perfil']['tbperfilid'];
        $cancionId = $_POST['cancionId'] ?? null;

        if (!$cancionId) {
            echo json_encode(["exito" => false, "mensaje" => "Falta el id de la canción"]);
            break;
        }

        $ok = $reproduccionModelo->incrementarContador($perfilId, (int)$cancionId);

        echo $ok
            ? json_encode(["exito" => true, "mensaje" => "Reproducción registrada"])
            : json_encode(["exito" => false, "mensaje" => "Error al registrar la reproducción"]);

        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}