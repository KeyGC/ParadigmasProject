<?php

require_once __DIR__ . '/../Modelo/generomodelo.php';
require_once __DIR__ . '/../Utilidades/autenticacion.php';
require_once __DIR__ . '/../Utilidades/validacion.php';

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

        $error = campoRequerido($nombre, 'nombre') ?? campoLongitud($nombre, 'nombre', 60);
        if ($error) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $error]);
            break;
        }

        if ($modelo->existeNombre($nombre)) {
            echo json_encode(["exito" => false, "tipo" => "duplicado", "mensaje" => "Ya existe un género con ese nombre"]);
            break;
        }

        $genero = new Genero(null, $nombre);

        try {
            $id = $modelo->insert($genero);
            echo $id
                ? json_encode(["exito" => true, "mensaje" => "Género creado correctamente", "id" => $id])
                : json_encode(["exito" => false, "mensaje" => "Error al crear el género"]);
        } catch (PDOException $e) {
            error_log('generocontrolador insert: ' . $e->getMessage());
            echo json_encode(["exito" => false, "tipo" => "servidor", "mensaje" => "No se pudo crear el género. Intente nuevamente."]);
        }

        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');

        if (!$id) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => "ID no proporcionado"]);
            break;
        }

        $error = campoRequerido($nombre, 'nombre') ?? campoLongitud($nombre, 'nombre', 60);
        if ($error) {
            echo json_encode(["exito" => false, "tipo" => "validacion", "mensaje" => $error]);
            break;
        }

        $generoActual = $modelo->getGenero($id);
        if (!$generoActual) {
            echo json_encode(["exito" => false, "mensaje" => "Género no encontrado"]);
            break;
        }

        if ($modelo->existeNombre($nombre, $id)) {
            echo json_encode(["exito" => false, "tipo" => "duplicado", "mensaje" => "Ya existe un género con ese nombre"]);
            break;
        }

        $genero = new Genero($id, $nombre);

        try {
            echo $modelo->update($genero)
                ? json_encode(["exito" => true, "mensaje" => "Género actualizado correctamente"])
                : json_encode(["exito" => false, "mensaje" => "Error al actualizar el género"]);
        } catch (PDOException $e) {
            error_log('generocontrolador update: ' . $e->getMessage());
            echo json_encode(["exito" => false, "tipo" => "servidor", "mensaje" => "No se pudo actualizar el género. Intente nuevamente."]);
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