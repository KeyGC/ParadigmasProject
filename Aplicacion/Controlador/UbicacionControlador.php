<?php
// Aplicacion/Controlador/UbicacionControlador.php
require_once __DIR__ . '/../Modelo/UbicacionModelo.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new UbicacionModelo();
$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getProvincias':
        $provincias = $modelo->getProvincias();
        echo json_encode(["exito" => true, "data" => $provincias]);
        break;

    case 'getCantones':
        $provinciaId = $_GET['provinciaId'] ?? null;
        if (!$provinciaId) {
            echo json_encode(["exito" => false, "mensaje" => "Provincia no proporcionada"]);
            break;
        }
        $cantones = $modelo->getCantonesPorProvincia($provinciaId);
        echo json_encode(["exito" => true, "data" => $cantones]);
        break;

    case 'getDistritos':
        $cantonId = $_GET['cantonId'] ?? null;
        if (!$cantonId) {
            echo json_encode(["exito" => false, "mensaje" => "Cantón no proporcionado"]);
            break;
        }
        $distritos = $modelo->getDistritosPorCanton($cantonId);
        echo json_encode(["exito" => true, "data" => $distritos]);
        break;

    case 'guardarUbicacion':

        $perfilId = $_POST['perfilId'] ?? null;
        $provinciaId = $_POST['provinciaId'] ?? null;
        $cantonId = $_POST['cantonId'] ?? null;
        $distritoId = $_POST['distritoId'] ?? null;
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;

        if (!$perfilId || !$provinciaId || !$cantonId || !$distritoId || !$lat || !$lng) {
            echo json_encode([
                "exito" => false,
                "mensaje" => "Faltan datos de ubicación"
            ]);
            break;
        }

        if ($modelo->existeUbicacion($perfilId)) {

            $resultado = $modelo->update(
                $perfilId,
                $provinciaId,
                $cantonId,
                $distritoId,
                $lat,
                $lng
            );

            $mensaje = "Ubicación actualizada correctamente";
        } else {

            $resultado = $modelo->insert(
                $perfilId,
                $provinciaId,
                $cantonId,
                $distritoId,
                $lat,
                $lng
            );

            $mensaje = "Ubicación guardada correctamente";
        }

        echo json_encode([
            "exito" => $resultado,
            "mensaje" => $mensaje
        ]);

        break;
    case 'getUbicacion':

        $perfilId = $_GET['perfilId'];

        $ubicacion = $modelo->getPorPerfil($perfilId);

        echo json_encode([
            "exito" => true,
            "data" => $ubicacion
        ]);

        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}
