<?php
require_once __DIR__ . '/../Modelo/deportemodelo.php';
require_once __DIR__ . '/../Modelo/perfildeportemodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';
require_once __DIR__ . '/../Utilidades/validacion.php';

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
        try {
            echo json_encode($perfilDeporteModelo->buscarCoincidencias((int) $_SESSION['perfil']['tbperfilid']));
        } catch (\Throwable $e) {
            error_log('deportecontrolador buscarCoincidencias: ' . $e->getMessage());
            echo json_encode(['exito' => false, 'mensaje' => 'No se pudieron calcular las afinidades de deporte en este momento.']);
        }
        break;

    case 'getListAdmin':
        exigirRol(['admin']);
        echo json_encode(["exito" => true, "data" => $deporteModelo->getListAdmin()]);
        break;

    case 'getItemAdmin':
        exigirRol(['admin']);
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        $item = $deporteModelo->getItemAdmin($id);
        echo $item
            ? json_encode(["exito" => true, "data" => $item])
            : json_encode(["exito" => false, "mensaje" => "Elemento no encontrado"]);
        break;

    case 'getTiposDisponibles':
        exigirRol(['admin']);
        echo json_encode(["exito" => true, "data" => $deporteModelo->getTiposActivos()]);
        break;

    case 'insert':
    case 'update':
        exigirRol(['admin']);

        $id = $accion === 'update' ? ($_POST['id'] ?? null) : null;
        $tipoId = $_POST['tipoId'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $imagenUrl = trim($_POST['imagenUrl'] ?? '');

        if ($accion === 'update' && !$id) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "Datos incompletos"]);
            break;
        }

        $error = campoRequerido($nombre, 'nombre') ?? campoLongitud($nombre, 'nombre', 200);
        if ($error) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $error]);
            break;
        }

        $tiposActivos = array_map('intval', array_column($deporteModelo->getTiposActivos(), 'tbtipodeporteid'));
        if (!$tipoId || !in_array((int) $tipoId, $tiposActivos, true)) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "Debe seleccionar un tipo válido"]);
            break;
        }

        if ($imagenUrl !== '') {
            $errorUrl = urlValida($imagenUrl);
            if ($errorUrl) {
                echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $errorUrl]);
                break;
            }
        }

        if ($deporteModelo->existeNombre($nombre, $tipoId, $id)) {
            echo json_encode(["exito" => false, "tipo" => "duplicado", "mensaje" => "Ya existe un elemento con ese nombre en ese tipo"]);
            break;
        }

        if ($accion === 'update' && !$deporteModelo->getItemAdmin($id)) {
            echo json_encode(["exito" => false, "mensaje" => "Elemento no encontrado"]);
            break;
        }

        try {
            if ($accion === 'insert') {
                $nuevoId = $deporteModelo->insert($tipoId, $nombre, $imagenUrl);
                echo $nuevoId
                    ? json_encode(["exito" => true, "mensaje" => "Elemento creado correctamente", "id" => $nuevoId])
                    : json_encode(["exito" => false, "mensaje" => "Error al crear el elemento"]);
            } else {
                echo $deporteModelo->update($id, $tipoId, $nombre, $imagenUrl)
                    ? json_encode(["exito" => true, "mensaje" => "Elemento actualizado correctamente"])
                    : json_encode(["exito" => false, "mensaje" => "Error al actualizar el elemento"]);
            }
        } catch (PDOException $e) {
            error_log('deportecontrolador ' . $accion . ': ' . $e->getMessage());
            echo json_encode(["exito" => false, "tipo" => "servidor", "mensaje" => "No se pudo guardar el elemento. Intente nuevamente."]);
        }
        break;

    case 'toggleEstado':
        exigirRol(['admin']);

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        if (!$deporteModelo->getItemAdmin($id)) {
            echo json_encode(["exito" => false, "mensaje" => "Elemento no encontrado"]);
            break;
        }

        echo $deporteModelo->toggleEstado($id)
            ? json_encode(["exito" => true, "mensaje" => "Estado actualizado correctamente"])
            : json_encode(["exito" => false, "mensaje" => "Error al actualizar el estado"]);
        break;

    case 'delete':
        exigirRol(['admin']);

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        if (!$deporteModelo->getItemAdmin($id)) {
            echo json_encode(["exito" => false, "mensaje" => "Elemento no encontrado"]);
            break;
        }

        if ($deporteModelo->tieneSwipes($id)) {
            echo json_encode(["exito" => false, "tipo" => "conflicto", "mensaje" => "Este elemento tiene swipes registrados. Puede desactivarlo en lugar de eliminarlo."]);
            break;
        }

        try {
            echo $deporteModelo->delete($id)
                ? json_encode(["exito" => true, "mensaje" => "Elemento eliminado correctamente"])
                : json_encode(["exito" => false, "mensaje" => "Error al eliminar el elemento"]);
        } catch (PDOException $e) {
            error_log('deportecontrolador delete: ' . $e->getMessage());
            echo json_encode(["exito" => false, "tipo" => "servidor", "mensaje" => "No se pudo eliminar el elemento. Intente nuevamente."]);
        }
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}