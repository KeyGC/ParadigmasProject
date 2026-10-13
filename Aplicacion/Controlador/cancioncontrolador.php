<?php

require_once __DIR__ . '/../Modelo/cancionmodelo.php';
require_once __DIR__ . '/../Modelo/reproduccionmodelo.php';
require_once __DIR__ . '/../Modelo/generomodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';
require_once __DIR__ . '/../Utilidades/validacion.php';

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

        $fechaLocal = $_POST['fechaLocal'] ?? null;
        $ok = $reproduccionModelo->incrementarContador($perfilId, (int)$cancionId, $fechaLocal);

        echo $ok
            ? json_encode(["exito" => true, "mensaje" => "Reproducción registrada"])
            : json_encode(["exito" => false, "mensaje" => "Error al registrar la reproducción"]);

        break;
    case 'getReproduccionesPorPerfil':
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }

        $perfilId = $_GET['perfilId'] ?? null;
        if (!$perfilId) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        $reproducciones = $reproduccionModelo->getPorPerfil($perfilId);
        echo json_encode(["exito" => true, "data" => $reproducciones]);
        break;
    case 'toggleReproduccionEstado':
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        echo $reproduccionModelo->toggleEstado($id)
            ? json_encode(["exito" => true, "mensaje" => "Estado actualizado correctamente"])
            : json_encode(["exito" => false, "mensaje" => "Error al actualizar el estado"]);

        break;
    case 'getListAdmin':
        exigirRol(['admin']);
        $canciones = $cancionModelo->getListAdmin();
        echo json_encode(["exito" => true, "data" => $canciones]);
        break;

    case 'getCancion':
        exigirRol(['admin']);
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        $cancion = $cancionModelo->getCancion($id);
        if ($cancion) {
            echo json_encode(["exito" => true, "data" => $cancion]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Canción no encontrada"]);
        }
        break;

    case 'getGenerosConConteo':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }
        $generos = $cancionModelo->getGenerosConConteo();
        echo json_encode(["exito" => true, "data" => $generos]);
        break;

    case 'getPlaylistPorGenero':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }
        $tbgeneroid = $_REQUEST['tbgeneroid'] ?? null;
        if (!$tbgeneroid) {
            echo json_encode(["exito" => false, "mensaje" => "Género no proporcionado"]);
            break;
        }
        $canciones = $cancionModelo->getCancionesPorGenero((int) $tbgeneroid);
        echo json_encode(["exito" => true, "data" => $canciones]);
        break;

    case 'getGenerosDisponibles':
        exigirRol(['admin']);
        $generoModelo = new GeneroModelo();
        echo json_encode(["exito" => true, "data" => $generoModelo->getList()]);
        break;

    case 'insert':
        exigirRol(['admin']);

        $generoId = $_POST['generoId'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $artista = trim($_POST['artista'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if (!$generoId) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "Debe seleccionar un género"]);
            break;
        }

        $error = campoRequerido($nombre, 'nombre') ?? campoRequerido($artista, 'artista')
            ?? campoRequerido($url, 'URL')
            ?? campoLongitud($nombre, 'nombre', 100) ?? campoLongitud($artista, 'artista', 100)
            ?? campoLongitud($url, 'URL', 255);
        if ($error) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $error]);
            break;
        }

        $errorUrl = urlValida($url);
        if ($errorUrl) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $errorUrl]);
            break;
        }

        $generoValido = (new GeneroModelo())->getGenero($generoId);
        if (!$generoValido) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "Debe seleccionar un género válido"]);
            break;
        }

        if ($cancionModelo->existeDuplicada($nombre, $artista)) {
            echo json_encode(["exito" => false, "tipo" => "duplicado", "mensaje" => "Ya existe una canción con ese nombre y artista"]);
            break;
        }

        $cancion = new Cancion(null, (int)$generoId, $nombre, $artista, $url, true);

        try {
            $id = $cancionModelo->insert($cancion);
            echo $id
                ? json_encode(["exito" => true, "mensaje" => "Canción creada correctamente", "id" => $id])
                : json_encode(["exito" => false, "mensaje" => "Error al crear la canción"]);
        } catch (PDOException $e) {
            error_log('cancioncontrolador insert: ' . $e->getMessage());
            echo json_encode(["exito" => false, "tipo" => "servidor", "mensaje" => "No se pudo crear la canción. Intente nuevamente."]);
        }

        break;

    case 'update':
        exigirRol(['admin']);

        $id = $_POST['id'] ?? null;
        $generoId = $_POST['generoId'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $artista = trim($_POST['artista'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if (!$id || !$generoId) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "Datos incompletos"]);
            break;
        }

        $error = campoRequerido($nombre, 'nombre') ?? campoRequerido($artista, 'artista')
            ?? campoRequerido($url, 'URL')
            ?? campoLongitud($nombre, 'nombre', 100) ?? campoLongitud($artista, 'artista', 100)
            ?? campoLongitud($url, 'URL', 255);
        if ($error) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $error]);
            break;
        }

        $errorUrl = urlValida($url);
        if ($errorUrl) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $errorUrl]);
            break;
        }

        $generoValido = (new GeneroModelo())->getGenero($generoId);
        if (!$generoValido) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "Debe seleccionar un género válido"]);
            break;
        }

        $cancionActual = $cancionModelo->getCancion($id);
        if (!$cancionActual) {
            echo json_encode(["exito" => false, "mensaje" => "Canción no encontrada"]);
            break;
        }

        if ($cancionModelo->existeDuplicada($nombre, $artista, $id)) {
            echo json_encode(["exito" => false, "tipo" => "duplicado", "mensaje" => "Ya existe una canción con ese nombre y artista"]);
            break;
        }

        $cancion = new Cancion($id, (int)$generoId, $nombre, $artista, $url, $cancionActual['tbcancionactivo']);

        try {
            echo $cancionModelo->update($cancion)
                ? json_encode(["exito" => true, "mensaje" => "Canción actualizada correctamente"])
                : json_encode(["exito" => false, "mensaje" => "Error al actualizar"]);
        } catch (PDOException $e) {
            error_log('cancioncontrolador update: ' . $e->getMessage());
            echo json_encode(["exito" => false, "tipo" => "servidor", "mensaje" => "No se pudo actualizar la canción. Intente nuevamente."]);
        }

        break;

    case 'toggleEstado':
        exigirRol(['admin']);

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        $cancionActual = $cancionModelo->getCancion($id);
        if (!$cancionActual) {
            echo json_encode(["exito" => false, "mensaje" => "Canción no encontrada"]);
            break;
        }

        echo $cancionModelo->toggleEstado($id)
            ? json_encode(["exito" => true, "mensaje" => "Estado actualizado correctamente"])
            : json_encode(["exito" => false, "mensaje" => "Error al actualizar el estado"]);

        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
        
}