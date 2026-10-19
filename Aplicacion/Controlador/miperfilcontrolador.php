<?php

require_once APP_PATH . '/Modelo/perfilmodelo.php';
require_once APP_PATH . '/Modelo/ubicacionmodelo.php';
require_once APP_PATH . '/Modelo/perfilmusicalmodelo.php';
require_once APP_PATH . '/Modelo/perfilcomidamodelo.php';
require_once APP_PATH . '/Modelo/perfildeportemodelo.php';

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

        try {
            $musicalModelo = new PerfilMusicalModelo();
            $perfilado = $musicalModelo->generarPerfilado($perfilId);
        } catch (\Throwable $e) {
            error_log('miperfilcontrolador musica: ' . $e->getMessage());
            $perfilado = ['exito' => false, 'mensaje' => 'Aún no tenemos suficiente información sobre tus gustos musicales.'];
        }

        $gustosMusicales = [];
        if (!empty($perfilado['exito']) && !empty($perfilado['resultados'])) {
            foreach ($perfilado['resultados'] as $resultado) {
                if (!empty($resultado['texto'])) {
                    $gustosMusicales[] = $resultado['texto'];
                }
            }
        }

        try {
            $comidaModelo = new PerfilComidaModelo();
            $perfiladoComida = $comidaModelo->generarPerfilado($perfilId);
        } catch (\Throwable $e) {
            error_log('miperfilcontrolador comida: ' . $e->getMessage());
            $perfiladoComida = ['exito' => false, 'mensaje' => 'Aún no tenemos suficiente información sobre tus gustos gastronómicos.'];
        }

        $gustosGastronomicos = [];
        if (!empty($perfiladoComida['exito']) && !empty($perfiladoComida['resultados'])) {
            foreach ($perfiladoComida['resultados'] as $i => $r) {
                if (!empty($r['texto'])) {
                    $gustosGastronomicos[] = $r['texto'];
                } elseif (!empty($r['tipo'])) {
                    $pct = isset($r['porcentaje']) ? " ({$r['porcentaje']}%)" : '';
                    $gustosGastronomicos[] = '#' . ($i + 1) . ' ' . $r['tipo'] . $pct;
                }
            }
        }

        try {
            $deporteModelo = new PerfilDeporteModelo();
            $perfiladoDeporte = $deporteModelo->generarPerfilado($perfilId);
        } catch (\Throwable $e) {
            error_log('miperfilcontrolador deporte: ' . $e->getMessage());
            $perfiladoDeporte = ['exito' => false, 'mensaje' => 'Aún no tenemos suficiente información sobre tus gustos deportivos.'];
        }

        $gustosDeportivos = [];
        if (!empty($perfiladoDeporte['exito']) && !empty($perfiladoDeporte['resultados'])) {
            foreach ($perfiladoDeporte['resultados'] as $i => $r) {
                if (!empty($r['texto'])) {
                    $gustosDeportivos[] = $r['texto'];
                } elseif (!empty($r['tipo'])) {
                    $pct = isset($r['porcentaje']) ? " ({$r['porcentaje']}%)" : '';
                    $gustosDeportivos[] = '#' . ($i + 1) . ' ' . $r['tipo'] . $pct;
                }
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
                    : $perfilado['mensaje'],

                "gustosGastronomicosDisponible" => $perfiladoComida['exito'],
                "gustosGastronomicos" => $gustosGastronomicos,
                "gustosGastronomicosMensaje" => $perfiladoComida['exito']
                    ? null
                    : ($perfiladoComida['mensaje'] ?? 'Aún no tenemos suficiente información sobre tus gustos gastronómicos.'),

                "gustosDeportivosDisponible" => $perfiladoDeporte['exito'],
                "gustosDeportivos" => $gustosDeportivos,
                "gustosDeportivosMensaje" => $perfiladoDeporte['exito']
                    ? null
                    : ($perfiladoDeporte['mensaje'] ?? 'Aún no tenemos suficiente información sobre tus gustos deportivos.')
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