<?php

require_once APP_PATH . '/Modelo/perfilmodelo.php';
require_once APP_PATH . '/Modelo/ubicacionmodelo.php';
require_once APP_PATH . '/Modelo/perfilmusicalmodelo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil'])) {
    echo json_encode([
        "exito" => false,
        "mensaje" => "No autorizado"
    ]);
    exit;
}

$perfilId = (int) $_SESSION['perfil']['tbperfilid'];

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'getResumen':

        $perfilModelo = new PerfilModelo();
        $perfil = $perfilModelo->getPerfil($perfilId);

        if (!$perfil) {
            echo json_encode([
                "exito" => false,
                "mensaje" => "Perfil no encontrado"
            ]);
            exit;
        }

        // Ubicación
        $ubicacion = null;

        if (!empty($perfil['tbubicacionid'])) {

            $ubicacionModelo = new UbicacionModelo();

            $ubicacionData = $ubicacionModelo->getPorId(
                $perfil['tbubicacionid']
            );

            if ($ubicacionData && !empty($ubicacionData['tbprovincianombre'])) {

                $ubicacion = implode(', ', array_filter([
                    $ubicacionData['tbdistritonombre'] ?? null,
                    $ubicacionData['tbcantonnombre'] ?? null,
                    $ubicacionData['tbprovincianombre'] ?? null
                ]));
            }
        }

        // Perfil musical
        $musicalModelo = new PerfilMusicalModelo();

        $perfilado = $musicalModelo->generarPerfilado($perfilId);

        $gustosMusicales = [];

        if ($perfilado['exito']) {

            foreach ($perfilado['resultados'] as $resultado) {
                $gustosMusicales[] = $resultado['texto'];
            }
        }

        echo json_encode([
            "exito" => true,
            "data" => [
                "nombre" => $perfil['tbperfilnombre'],
                "correo" => $perfil['tbperfilcorreo'],
                "ubicacion" => $ubicacion,

                "gustosMusicalesDisponible" => $perfilado['exito'],

                "gustosMusicales" => $gustosMusicales,

                "gustosMusicalesMensaje" => $perfilado['exito']
                    ? null
                    : $perfilado['mensaje']
            ]
        ]);

        break;

    default:

        echo json_encode([
            "exito" => false,
            "mensaje" => "Acción no reconocida"
        ]);

        break;
}