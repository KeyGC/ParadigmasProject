<?php

require_once __DIR__ . '/../Modelo/generomodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';

header('Content-Type: application/json; charset=utf-8');

exigirRol(['admin']);

$modelo = new GeneroModelo();
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getList':
        $generos = $modelo->getList();
        echo json_encode(["exito" => true, "data" => $generos]);
        break;

    case 'getGenero':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["exito" => false, "mensaje" => "ID no proporcionado"]);
            break;
        }
        $genero = $modelo->getGenero($id);
        if ($genero) {
            echo json_encode(["exito" => true, "data" => $genero]);
        } else {
            echo json_encode(["exito" => false, "mensaje" => "Género no encontrado"]);
        }
        break;

    case 'insert':
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            echo json_encode(["exito" => false, "mensaje" => "El nombre es obligatorio"]);
            break;
        }

        $existente = $modelo->getGeneroByNombre($nombre);
        if ($existente) {
            echo json_encode(["exito" => false, "mensaje" => "Ya existe un género con ese nombre"]);
            break;
        }

        $genero = new Genero(null, $nombre);

        try {
            $id = $modelo->insert($genero);
            echo $id
                ? json_encode(["exito" => true, "mensaje" => "Género creado correctamente", "id" => $id])
                : json_encode(["exito" => false, "mensaje" => "Error al crear el género"]);
        } catch (PDOException $e) {
            echo json_encode(["exito" => false, "mensaje" => "Error al crear el género: " . $e->getMessage()]);
        }

        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');

        if (!$id || $nombre === '') {
            echo json_encode(["exito" => false, "mensaje" => "Datos incompletos"]);
            break;
        }

        $generoActual = $modelo->getGenero($id);
        if (!$generoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Género no encontrado"]);
            break;
        }

        $genero = new Genero($id, $nombre);

        try {
            echo $modelo->update($genero)
                ? json_encode(["exito" => true, "mensaje" => "Género actualizado correctamente"])
                : json_encode(["exito" => false, "mensaje" => "Error al actualizar el género"]);
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

        $generoActual = $modelo->getGenero($id);
        if (!$generoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Género no encontrado"]);
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