<?php
require_once __DIR__ . '/../Modelo/ubicacionmodelo.php';
require_once __DIR__ . '/../Modelo/perfilubicacionmodelo.php';
require_once __DIR__ . '/../Modelo/perfilmodelo.php';
require_once __DIR__ . '/../Modelo/eventomodelo.php';
require_once __DIR__ . '/../Utilidades/geolocalizacion.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new UbicacionModelo();
$accion = $_REQUEST['accion'] ?? '';

function validarCoordenadas($lat, $lng)
{
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return null;
    }

    $lat = (float) $lat;
    $lng = (float) $lng;

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }

    return [
        'lat' => sprintf('%.8f', $lat),
        'lng' => sprintf('%.8f', $lng)
    ];
}

function resolverUbicacionDesdeAddress($modelo, $address)
{
    // ---- Etapa 1: código postal nacional (P + CC + DD) ----
    $postcode = trim((string) ($address['postcode'] ?? ''));
    if (preg_match('/^(\d)(\d{2})(\d{2})$/', $postcode, $m)) {
        $provinciaId = (int) $m[1];
        $cantonId = (int) ($m[1] . $m[2]);
        $distritoId = (int) $postcode;

        $nombres = $modelo->getNombresPorIds($provinciaId, $cantonId, $distritoId);
        if ($nombres !== null) {
            return [
                'provinciaId' => $provinciaId,
                'cantonId' => $cantonId,
                'distritoId' => $distritoId,
                'origen' => 'codigoPostal'
            ] + $nombres;
        }
    }

    $candidatosProvincia = ['province', 'state'];
    $candidatosCanton = ['county', 'municipality'];
    $candidatosDistrito = [
        'village', 'town', 'city_district', 'municipality', 'borough',
        'suburb', 'quarter', 'neighbourhood', 'hamlet', 'city'
    ];

    foreach ($candidatosProvincia as $cp) {
        $nombreProvincia = normalizarTextoUbicacion($address[$cp] ?? '');
        if ($nombreProvincia === '') {
            continue;
        }

        foreach ($modelo->getProvincias() as $provincia) {
            if (normalizarTextoUbicacion($provincia['tbprovincianombre']) !== $nombreProvincia) {
                continue;
            }

            foreach ($candidatosCanton as $cc) {
                $nombreCanton = normalizarTextoUbicacion($address[$cc] ?? '');
                if ($nombreCanton === '') {
                    continue;
                }

                foreach ($modelo->getCantonesPorProvincia($provincia['tbprovinciaid']) as $canton) {
                    if (normalizarTextoUbicacion($canton['tbcantonnombre']) !== $nombreCanton) {
                        continue;
                    }

                    foreach ($candidatosDistrito as $cd) {
                        $nombreDistrito = normalizarTextoUbicacion($address[$cd] ?? '');
                        if ($nombreDistrito === '') {
                            continue;
                        }

                        foreach ($modelo->getDistritosPorCanton($canton['tbcantonid']) as $distrito) {
                            if (normalizarTextoUbicacion($distrito['tbdistritonombre']) !== $nombreDistrito) {
                                continue;
                            }

                            return [
                                'provinciaId' => (int) $provincia['tbprovinciaid'],
                                'cantonId' => (int) $canton['tbcantonid'],
                                'distritoId' => (int) $distrito['tbdistritoid'],
                                'origen' => 'nombres'
                            ] + $modelo->getNombresPorIds(
                                $provincia['tbprovinciaid'],
                                $canton['tbcantonid'],
                                $distrito['tbdistritoid']
                            );
                        }
                    }
                }
            }
        }
    }

    return null;
}

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
            echo json_encode(["exito" => false, "mensaje" => "Cantón no proporcionada"]);
            break;
        }
        $distritos = $modelo->getDistritosPorCanton($cantonId);
        echo json_encode(["exito" => true, "data" => $distritos]);
        break;

    case 'guardarUbicacion':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $provinciaId = $_POST['provinciaId'] ?? null;
        $cantonId = $_POST['cantonId'] ?? null;
        $distritoId = $_POST['distritoId'] ?? null;
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;

        if (!$provinciaId || !$cantonId || !$distritoId || !$lat || !$lng) {
            echo json_encode(["exito" => false, "mensaje" => "Faltan datos de ubicación"]);
            break;
        }

        $coordenadas = validarCoordenadas($lat, $lng);
        if ($coordenadas === null) {
            echo json_encode(["exito" => false, "mensaje" => "Las coordenadas son inválidas"]);
            break;
        }

        $nombres = $modelo->getNombresPorIds($provinciaId, $cantonId, $distritoId);
        if ($nombres === null) {
            echo json_encode(["exito" => false, "mensaje" => "La combinación de provincia, cantón y distrito no es válida"]);
            break;
        }

        $ubicacionId = $_SESSION['perfil']['tbubicacionid'];
        $perfilId = $_SESSION['perfil']['tbperfilid'];

        $conexion = Basedatos::conectar();

        try {
            $conexion->beginTransaction();

            $resultado = $modelo->update($ubicacionId, $provinciaId, $cantonId, $distritoId, $coordenadas['lat'], $coordenadas['lng']);
            if (!$resultado) {
                throw new RuntimeException("Error al actualizar tbubicacion");
            }

            $perfilUbicacionModelo = new PerfilUbicacionModelo();
            $data = $perfilUbicacionModelo->construirData(
                $nombres['provincia'],
                $nombres['canton'],
                $nombres['distrito'],
                $coordenadas['lat'],
                $coordenadas['lng'],
                date('Y-m-d'),
                date('H:i:s'),
                PerfilUbicacionModelo::TIPO_MANUAL
            );

            $idHistorico = $perfilUbicacionModelo->insertar($perfilId, $data, PerfilUbicacionModelo::TIPO_MANUAL);
            if ($idHistorico === false) {
                throw new RuntimeException("Error al registrar el histórico de ubicaciones");
            }

            $conexion->commit();

            echo json_encode([
                "exito" => true,
                "mensaje" => "Ubicación actualizada correctamente",
                "data" => ["tbperfilubicacionid" => (int) $idHistorico]
            ]);
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("guardarUbicacion (manual): " . $e->getMessage());
            echo json_encode(["exito" => false, "mensaje" => "Error al actualizar la ubicación"]);
        }

        break;

    case 'guardarAutomatica':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;

        $coordenadas = validarCoordenadas($lat, $lng);
        if ($coordenadas === null) {
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "Coordenadas inválidas, se omite la ubicación automática"]);
            break;
        }

        $perfilId = $_SESSION['perfil']['tbperfilid'];

        $perfilModelo = new PerfilModelo();
        $perfilUbicacionModelo = new PerfilUbicacionModelo();

        if (!$perfilUbicacionModelo->existePerfilActivo($perfilId)) {
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "El perfil no está disponible para registrar ubicación"]);
            break;
        }

        $address = reverseGeocodificar($coordenadas['lat'], $coordenadas['lng']);
        if ($address === null) {
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "No se pudo determinar la ubicación automática"]);
            break;
        }

        $ubicacion = resolverUbicacionDesdeAddress($modelo, $address);
        if ($ubicacion === null) {
            error_log("guardarAutomatica: sin coincidencia en catalogo para " . json_encode($address, JSON_UNESCAPED_UNICODE));
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "La ubicación detectada no corresponde al catálogo de provincias, cantones y distritos"]);
            break;
        }

        if ($perfilUbicacionModelo->esSimilarAUltima($perfilId, $coordenadas['lat'], $coordenadas['lng'])) {
            echo json_encode([
                "exito" => true,
                "guardado" => false,
                "duplicado" => true,
                "mensaje" => "La ubicación automática es similar a la última registrada, no se duplica el histórico"
            ]);
            break;
        }

        $data = $perfilUbicacionModelo->construirData(
            $ubicacion['provincia'],
            $ubicacion['canton'],
            $ubicacion['distrito'],
            $coordenadas['lat'],
            $coordenadas['lng'],
            date('Y-m-d'),
            date('H:i:s'),
            PerfilUbicacionModelo::TIPO_AUTOMATICA
        );

        $conexion = Basedatos::conectar();

        try {
            $conexion->beginTransaction();

            $nuevoUbicacionId = $modelo->insert(
                $ubicacion['provinciaId'],
                $ubicacion['cantonId'],
                $ubicacion['distritoId'],
                $coordenadas['lat'],
                $coordenadas['lng']
            );
            if (!$nuevoUbicacionId) {
                throw new RuntimeException("No se pudo insertar en tbubicacion");
            }

            if (!$perfilModelo->setUbicacion($perfilId, $nuevoUbicacionId)) {
                throw new RuntimeException("No se pudo actualizar tbperfil.tbubicacionid");
            }

            $idHistorico = $perfilUbicacionModelo->insertar($perfilId, $data, PerfilUbicacionModelo::TIPO_AUTOMATICA);
            if ($idHistorico === false) {
                throw new RuntimeException("No se pudo insertar el histórico en tbperfilubicacion");
            }

            $conexion->commit();

            $_SESSION['perfil']['tbubicacionid'] = (int) $nuevoUbicacionId;

            $coincidencias = [];
            try {
                $eventoModelo = new EventoModelo();
                $fechaHoraCaptura = date('Y-m-d H:i:s');
                $coincidencias = $eventoModelo->registrarPosibleAsistencia(
                    $perfilId,
                    $coordenadas['lat'],
                    $coordenadas['lng'],
                    $fechaHoraCaptura
                );
            } catch (Exception $e) {
                error_log("registrarPosibleAsistencia: " . $e->getMessage());
            }

            echo json_encode([
                "exito" => true,
                "guardado" => true,
                "mensaje" => "Ubicación automática guardada correctamente",
                "data" => [
                    "tbubicacionid" => (int) $nuevoUbicacionId,
                    "tbperfilubicacionid" => (int) $idHistorico,
                    "provincia" => $ubicacion['provincia'],
                    "canton" => $ubicacion['canton'],
                    "distrito" => $ubicacion['distrito'],
                    "origen" => $ubicacion['origen'],
                    "eventosCoincidentes" => $coincidencias
                ]
            ]);
        } catch (Exception $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("guardarAutomatica: " . $e->getMessage());
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "No se pudo guardar la ubicación automática"]);
        }

        break;

    case 'getHistoricoPerfil':
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
            echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
            break;
        }

        $idPerfil = $_GET['idPerfil'] ?? null;
        if (!$idPerfil) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        $perfilModelo = new PerfilModelo();
        $perfil = $perfilModelo->getPerfil($idPerfil);
        if (!$perfil) {
            echo json_encode(["exito" => false, "mensaje" => "Perfil no encontrado"]);
            break;
        }

        $perfilUbicacionModelo = new PerfilUbicacionModelo();
        $historico = $perfilUbicacionModelo->getListPorPerfil($idPerfil);

        echo json_encode([
            "exito" => true,
            "data" => [
                "perfil" => $perfil['tbperfilnombre'],
                "ubicaciones" => $historico
            ]
        ]);

        break;

    case 'getUbicacion':
        if (!isset($_SESSION['perfil'])) {
            echo json_encode(["exito" => false, "mensaje" => "Sesión no encontrada"]);
            break;
        }

        $ubicacionId = $_SESSION['perfil']['tbubicacionid'];
        $ubicacion = $modelo->getPorId($ubicacionId);

        echo json_encode(["exito" => true, "data" => $ubicacion]);

        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}