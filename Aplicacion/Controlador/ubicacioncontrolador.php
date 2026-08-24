<?php
// Aplicacion/Controlador/UbicacionControlador.php
require_once __DIR__ . '/../Modelo/ubicacionmodelo.php';
require_once __DIR__ . '/../Modelo/perfilubicacionmodelo.php';
require_once __DIR__ . '/../Modelo/perfilmodelo.php';
require_once __DIR__ . '/../Utilidades/geolocalizacion.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new UbicacionModelo();
$accion = $_REQUEST['accion'] ?? '';

// Valida que lat/lng sean numéricos y estén en rango.
// Devuelve las coordenadas normalizadas a 8 decimales (igual que las columnas
// decimal(10,8) de tbubicacion) o null si son inválidas.
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

// Resuelve la ubicación administrativa a partir del bloque 'address' crudo
// de Nominatim, en DOS ETAPAS. En ambas etapas los IDs se BUSCAN y VERIFICAN
// contra tbprovincia/tbcanton/tbdistrito con consultas preparadas (nunca se
// asumen ni se confía en datos del navegador).
//
// Etapa 1 - CÓDIGO POSTAL: los códigos postales de Costa Rica (PCCDD)
// usan la misma codificación del catálogo: provincia = 1er dígito,
// cantón = 3 primeros dígitos, distrito = 5 dígitos. Es determinístico y
// resuelve zonas donde Nominatim no entrega el nombre del cantón (ej.
// centro de San José). El ID derivado SIEMPRE se verifica contra la BD.
//
// Etapa 2 - NOMBRES (respaldo): prueba combinaciones ordenadas de campos
// candidatos de Nominatim contra los nombres normalizados del catálogo.
//
// Devuelve [provinciaId, cantonId, distritoId, provincia, canton, distrito,
// origen] o null si no se pudo resolver una combinación VÁLIDA completa.
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

    // ---- Etapa 2: coincidencia por nombres con candidatos ordenados ----
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

        // Validación de coordenadas desde PHP
        $coordenadas = validarCoordenadas($lat, $lng);
        if ($coordenadas === null) {
            echo json_encode(["exito" => false, "mensaje" => "Las coordenadas son inválidas"]);
            break;
        }

        // Validación de integridad desde PHP: los IDs deben existir, estar
        // activos y respetar la jerarquía provincia -> cantón -> distrito
        $nombres = $modelo->getNombresPorIds($provinciaId, $cantonId, $distritoId);
        if ($nombres === null) {
            echo json_encode(["exito" => false, "mensaje" => "La combinación de provincia, cantón y distrito no es válida"]);
            break;
        }

        $ubicacionId = $_SESSION['perfil']['tbubicacionid'];
        $perfilId = $_SESSION['perfil']['tbperfilid'];

        // El cambio de la ubicación actual y su registro histórico deben ser
        // consistentes entre sí, por eso van en una transacción PDO
        $conexion = Basedatos::conectar();

        try {
            $conexion->beginTransaction();

            $resultado = $modelo->update($ubicacionId, $provinciaId, $cantonId, $distritoId, $coordenadas['lat'], $coordenadas['lng']);
            if (!$resultado) {
                throw new RuntimeException("Error al actualizar tbubicacion");
            }

            // Histórico MANUAL: fotografía inmutable de esta ubicación
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

    // UBICACIÓN AUTOMÁTICA (GPS + Reverse Geocoding).
    // Nunca interrumpe el funcionamiento de la aplicación: ante cualquier
    // problema responde con exito=true y guardado=false.
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

        // Validación de la relación lógica con tbperfil desde PHP
        if (!$perfilUbicacionModelo->existePerfilActivo($perfilId)) {
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "El perfil no está disponible para registrar ubicación"]);
            break;
        }

        // Reverse Geocoding: coordenadas -> datos crudos de ubicación.
        // Si el servicio falla NO se guarda nada (ni datos incompletos).
        $address = reverseGeocodificar($coordenadas['lat'], $coordenadas['lng']);
        if ($address === null) {
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "No se pudo determinar la ubicación automática"]);
            break;
        }

        // Resolución verificada contra el catálogo (código postal + nombres)
        $ubicacion = resolverUbicacionDesdeAddress($modelo, $address);
        if ($ubicacion === null) {
            error_log("guardarAutomatica: sin coincidencia en catalogo para " . json_encode($address, JSON_UNESCAPED_UNICODE));
            echo json_encode(["exito" => true, "guardado" => false, "mensaje" => "La ubicación detectada no corresponde al catálogo de provincias, cantones y distritos"]);
            break;
        }

        // Anti-duplicados: si el punto prácticamente no cambió respecto a la
        // última ubicación registrada, no se crean filas innecesarias
        if ($perfilUbicacionModelo->esSimilarAUltima($perfilId, $coordenadas['lat'], $coordenadas['lng'])) {
            echo json_encode([
                "exito" => true,
                "guardado" => false,
                "duplicado" => true,
                "mensaje" => "La ubicación automática es similar a la última registrada, no se duplica el histórico"
            ]);
            break;
        }

        // El histórico usa los nombres EXACTOS del catálogo verificado
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

        // Tres operaciones relacionadas deben quedar consistentes entre sí,
        // por eso se ejecutan dentro de una transacción PDO
        $conexion = Basedatos::conectar();

        try {
            $conexion->beginTransaction();

            // 1) Nueva fila concreta en tbubicacion
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

            // 2) La nueva ubicación pasa a ser la ACTUAL del perfil
            if (!$perfilModelo->setUbicacion($perfilId, $nuevoUbicacionId)) {
                throw new RuntimeException("No se pudo actualizar tbperfil.tbubicacionid");
            }

            // 3) Nuevo registro del histórico como AUTOMATICA
            $idHistorico = $perfilUbicacionModelo->insertar($perfilId, $data, PerfilUbicacionModelo::TIPO_AUTOMATICA);
            if ($idHistorico === false) {
                throw new RuntimeException("No se pudo insertar el histórico en tbperfilubicacion");
            }

            $conexion->commit();

            // Refresca la sesión para que el resto de vistas use la ubicación nueva
            $_SESSION['perfil']['tbubicacionid'] = (int) $nuevoUbicacionId;

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
                    "origen" => $ubicacion['origen']
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

    // HISTÓRICO DE UBICACIONES: exclusivo del administrador.
    // Devuelve el histórico de cualquier perfil indicando su idPerfil,
    // siguiendo el mismo patrón de getMatriz en perfilaccesocontrolador.php
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
