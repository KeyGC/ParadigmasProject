<?php
require_once __DIR__ . '/../Modelo/deportemodelo.php';
require_once __DIR__ . '/../Modelo/perfildeportemodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$deporteModelo = new DeporteModelo();
$perfilDeporteModelo = new PerfilDeporteModelo();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getItems':
        $cantidad = (int) ($_GET['cantidad'] ?? 20);
        if ($cantidad < 1 || $cantidad > 60) {
            $cantidad = 20;
        }
        $reciclar = ($_GET['reciclar'] ?? '0') === '1';

        echo json_encode([
            "exito" => true,
            "data" => $deporteModelo->getItemsJuego((int) $_SESSION['perfil']['tbperfilid'], $cantidad, $reciclar)
        ]);
        break;

    case 'registrarSwipe':
        $deporteId = $_POST['deporteId'] ?? null;
        $gusto = $_POST['gusto'] ?? null;

        if (!$deporteId || !in_array($gusto, ['like', 'dislike'], true)) {
            echo json_encode(["exito" => false, "mensaje" => "Datos incompletos o inválidos"]);
            break;
        }

        if (!$deporteModelo->getItem((int) $deporteId)) {
            echo json_encode(["exito" => false, "mensaje" => "Deporte no encontrado"]);
            break;
        }

        $ok = $deporteModelo->registrarSwipe(
            (int) $_SESSION['perfil']['tbperfilid'],
            (int) $deporteId,
            $gusto === 'like'
        );

        echo $ok
            ? json_encode(["exito" => true, "mensaje" => "Swipe registrado"])
            : json_encode(["exito" => false, "mensaje" => "Error al registrar el swipe"]);
        break;

    case 'generarPerfilado':
        echo json_encode($perfilDeporteModelo->generarPerfilado((int) $_SESSION['perfil']['tbperfilid']));
        break;

    case 'buscarCoincidencias':
        echo json_encode($perfilDeporteModelo->buscarCoincidencias((int) $_SESSION['perfil']['tbperfilid']));
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}