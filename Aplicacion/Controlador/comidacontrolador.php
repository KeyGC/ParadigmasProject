<?php
require_once __DIR__ . '/../Modelo/comidamodelo.php';
require_once __DIR__ . '/../Modelo/perfilcomidamodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
    exit;
}

$comidaModelo = new ComidaModelo();
$perfilComidaModelo = new PerfilComidaModelo();

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
            "data" => $comidaModelo->getItemsJuego((int) $_SESSION['perfil']['tbperfilid'], $cantidad, $reciclar)
        ]);
        break;

    case 'registrarSwipe':
        $comidaId = $_POST['comidaId'] ?? null;
        $gusto = $_POST['gusto'] ?? null;

        if (!$comidaId || !in_array($gusto, ['like', 'dislike'], true)) {
            echo json_encode(["exito" => false, "mensaje" => "Datos incompletos o inválidos"]);
            break;
        }

        if (!$comidaModelo->getItem((int) $comidaId)) {
            echo json_encode(["exito" => false, "mensaje" => "Comida no encontrada"]);
            break;
        }

        $ok = $comidaModelo->registrarSwipe(
            (int) $_SESSION['perfil']['tbperfilid'],
            (int) $comidaId,
            $gusto === 'like'
        );

        echo $ok
            ? json_encode(["exito" => true, "mensaje" => "Swipe registrado"])
            : json_encode(["exito" => false, "mensaje" => "Error al registrar el swipe"]);
        break;

    case 'generarPerfilado':
        echo json_encode($perfilComidaModelo->generarPerfilado((int) $_SESSION['perfil']['tbperfilid']));
        break;

    case 'buscarCoincidencias':
        echo json_encode($perfilComidaModelo->buscarCoincidencias((int) $_SESSION['perfil']['tbperfilid']));
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}