<?php

require_once __DIR__ . '/../Modelo/eventomodelo.php';
require_once __DIR__ . '/../Modelo/generomodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

exigirRol(['admin']);

$modelo = new EventoModelo();
$modeloGenero = new GeneroModelo();
$accion = $_REQUEST['accion'] ?? '';

function validarDatosEvento($generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora, $modeloGenero)
{
    if ($nombre === '' || $artista === '' || $ubicacion === '') {
        return "El nombre, artista y ubicación son obligatorios";
    }
    if (!$generoId || !$modeloGenero->getGenero($generoId)) {
        return "Debe seleccionar un género válido";
    }
    if (!is_numeric($latitud) || !is_numeric($longitud)) {
        return "Latitud y longitud deben ser valores numéricos";
    }
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        return "La fecha no es válida";
    }
    if (!DateTime::createFromFormat('H:i', $hora) && !DateTime::createFromFormat('H:i:s', $hora)) {
        return "La hora no es válida";
    }
    return null;
}

switch ($accion) {

    case 'getList':
        $eventos = $modelo->getList();
        echo json_encode(["exito" => true, "data" => $eventos]);
        break;

    case 'getEvento':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        $evento = $modelo->getEvento($id);
        if ($evento) {
            echo json_encode(["exito" => true, "data" => $evento]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Evento no encontrado"]);
        }
        break;

    case 'getUbicaciones':
        $eventoId = $_GET['eventoId'] ?? null;
        if (!$eventoId) {
            echo json_encode(["exito" => false, "mensaje" => "ID de evento no proporcionado"]);
            break;
        }
        $categoria = $_GET['categoria'] ?? null;
        $ubicaciones = $modelo->getUbicacionesPorEvento($eventoId, $categoria);
        echo json_encode(["exito" => true, "data" => $ubicaciones]);
        break;
        
    case 'insertUbicacion':
        $eventoId = $_POST['eventoId'] ?? null;
        $categoria = trim($_POST['categoria'] ?? '');
        $referenciaId = $_POST['referenciaId'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $latitud = $_POST['latitud'] ?? '';
        $longitud = $_POST['longitud'] ?? '';

        if (!$eventoId || $categoria === '' || $nombre === '') {
            echo json_encode(["exito" => false, "mensaje" => "Evento, categoría y nombre son obligatorios"]);
            break;
        }

        $eventoExiste = $modelo->getEvento($eventoId);
        if (!$eventoExiste) {
            echo json_encode(["exito" => false, "mensaje" => "El evento no existe"]);
            break;
        }

        $id = $modelo->insertUbicacion($eventoId, $categoria, $referenciaId, $nombre, $latitud, $longitud);
        echo $id
            ? json_encode(["exito" => true, "mensaje" => "Ubicación agregada correctamente", "id" => $id])
            : json_encode(["exito" => false, "mensaje" => "Error al agregar la ubicación"]);
        break;

    case 'toggleEstadoUbicacion':
        $ubicacionId = $_POST['ubicacionId'] ?? null;
        if (!$ubicacionId) {
            echo json_encode(["exito" => false, "mensaje" => "ID de ubicación no proporcionado"]);
            break;
        }
        echo $modelo->toggleEstadoUbicacion($ubicacionId)
            ? json_encode(["exito" => true, "mensaje" => "Estado actualizado correctamente"])
            : json_encode(["exito" => false, "mensaje" => "Error al actualizar el estado"]);
        break;

    case 'insert':
        $generoId = $_POST['generoId'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $artista = trim($_POST['artista'] ?? '');
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $latitud = $_POST['latitud'] ?? '';
        $longitud = $_POST['longitud'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';

        $error = validarDatosEvento($generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora, $modeloGenero);
        if ($error) {
            echo json_encode(["exito" => false, "mensaje" => $error]);
            break;
        }

        $existente = $modelo->getEventoByNombreYFecha($nombre, $fecha);
        if ($existente) {
            echo json_encode(["exito" => false, "mensaje" => "Ya existe un evento con ese nombre en esa fecha"]);
            break;
        }

        $evento = new Evento(null, $generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora);

        try {
            $id = $modelo->insert($evento);
            echo $id
                ? json_encode(["exito" => true, "mensaje" => "Evento creado correctamente", "id" => $id])
                : json_encode(["exito" => false, "mensaje" => "Error al crear el evento"]);
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear el evento: " . $e->getMessage()]);
        }
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $generoId = $_POST['generoId'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $artista = trim($_POST['artista'] ?? '');
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $latitud = $_POST['latitud'] ?? '';
        $longitud = $_POST['longitud'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';

        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        $error = validarDatosEvento($generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora, $modeloGenero);
        if ($error) {
            echo json_encode(["exito" => false, "mensaje" => $error]);
            break;
        }

        $eventoActual = $modelo->getEvento($id);
        if (!$eventoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Evento no encontrado"]);
            break;
        }

        $evento = new Evento($id, $generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora);

        try {
            echo $modelo->update($evento)
                ? json_encode(["exito" => true, "mensaje" => "Evento actualizado correctamente"])
                : json_encode(["exito" => false, "mensaje" => "Error al actualizar el evento"]);
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al actualizar: " . $e->getMessage()]);
        }
        break;

    case 'toggleEstado':
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }

        $eventoActual = $modelo->getEvento($id);
        if (!$eventoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Evento no encontrado"]);
            break;
        }

        echo $modelo->toggleEstado($id)
            ? json_encode(["exito" => true, "mensaje" => "Estado actualizado correctamente"])
            : json_encode(["exito" => false, "mensaje" => "Error al actualizar el estado"]);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}