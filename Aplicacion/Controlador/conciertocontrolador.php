<?php

require_once __DIR__ . '/../Modelo/conciertomodelo.php';
require_once __DIR__ . '/../Modelo/generomodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

exigirRol(['admin']);

$modelo = new ConciertoModelo();
$modeloGenero = new GeneroModelo();
$accion = $_REQUEST['accion'] ?? '';

function validarDatosConcierto($generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora, $modeloGenero)
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
        $conciertos = $modelo->getList();
        echo json_encode(["exito" => true, "data" => $conciertos]);
        break;

    case 'getConcierto':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        $concierto = $modelo->getConcierto($id);
        if ($concierto) {
            echo json_encode(["exito" => true, "data" => $concierto]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Concierto no encontrado"]);
        }
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

        $error = validarDatosConcierto($generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora, $modeloGenero);
        if ($error) {
            echo json_encode(["exito" => false, "mensaje" => $error]);
            break;
        }

        $existente = $modelo->getConciertoByNombreYFecha($nombre, $fecha);
        if ($existente) {
            echo json_encode(["exito" => false, "mensaje" => "Ya existe un concierto con ese nombre en esa fecha"]);
            break;
        }

        $concierto = new Concierto(null, $generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora);

        try {
            $id = $modelo->insert($concierto);
            echo $id
                ? json_encode(["exito" => true, "mensaje" => "Concierto creado correctamente", "id" => $id])
                : json_encode(["exito" => false, "mensaje" => "Error al crear el concierto"]);
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear el concierto: " . $e->getMessage()]);
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

        $error = validarDatosConcierto($generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora, $modeloGenero);
        if ($error) {
            echo json_encode(["exito" => false, "mensaje" => $error]);
            break;
        }

        $conciertoActual = $modelo->getConcierto($id);
        if (!$conciertoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Concierto no encontrado"]);
            break;
        }

        $concierto = new Concierto($id, $generoId, $nombre, $artista, $ubicacion, $latitud, $longitud, $fecha, $hora);

        try {
            echo $modelo->update($concierto)
                ? json_encode(["exito" => true, "mensaje" => "Concierto actualizado correctamente"])
                : json_encode(["exito" => false, "mensaje" => "Error al actualizar el concierto"]);
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

        $conciertoActual = $modelo->getConcierto($id);
        if (!$conciertoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Concierto no encontrado"]);
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