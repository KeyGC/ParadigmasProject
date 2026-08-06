<?php
// COMPILAR: php pruebas.php

require_once __DIR__ . '/Configuracion/Configuracion.php';
require_once APP_PATH . '/Modelo/PerfilModelo.php';
require_once APP_PATH . '/Modelo/UbicacionModelo.php';
require_once APP_PATH . '/Modelo/Perfil.php';

header('Content-Type: text/plain; charset=utf-8');



$totalPruebas = 0;
$totalFallos  = 0;

function assertTrue($condicion, $mensaje)
{
    global $totalPruebas, $totalFallos;
    $totalPruebas++;

    if ($condicion) {
        echo "  [OK]   $mensaje\n";
    } else {
        $totalFallos++;
        echo "  [FAIL] $mensaje\n";
    }
}

function assertEquals($esperado, $actual, $mensaje)
{
    $condicion = $esperado == $actual;
    $detalle = $condicion ? '' : " (esperado: " . var_export($esperado, true) . ", obtenido: " . var_export($actual, true) . ")";
    assertTrue($condicion, $mensaje . $detalle);
}

function seccion($titulo)
{
    echo "\n=== $titulo ===\n";
}



$sufijo = substr(bin2hex(random_bytes(4)), 0, 6);
$nombrePrueba  = "prueba_$sufijo";
$correoPrueba  = "prueba_$sufijo@test.com";
$contraPrueba  = "abcd1234"; // cumple: 8-16 chars, sin vocales... (ojo: tiene vocal 'a')
// Nota: la regla real prohíbe vocales, usamos algo que la cumpla:
$contraPrueba  = "bcdfgh12"; // sin vocales, 4 letras, 2 numeros -> ajustar si hace falta 4 numeros
$contraPrueba  = "bcdf1234"; // 4 letras, 4 numeros, sin vocales, sin repetidos consecutivos

$ubicacionModelo = new UbicacionModelo();
$perfilModelo    = new PerfilModelo();

$ubicacionIdCreada = null;
$perfilIdCreado    = null;



function probarGetProvincias(UbicacionModelo $modelo)
{
    seccion('UbicacionModelo::getProvincias');

    $provincias = $modelo->getProvincias();

    assertTrue(is_array($provincias), 'getProvincias() devuelve un array');
    assertTrue(count($provincias) > 0, 'getProvincias() devuelve al menos una provincia');

    if (count($provincias) > 0) {
        $primera = $provincias[0];
        assertTrue(isset($primera['tbprovinciaid']), 'Cada provincia tiene tbprovinciaid');
        assertTrue(isset($primera['tbprovincianombre']), 'Cada provincia tiene tbprovincianombre');
    }

    return $provincias;
}

function probarGetCantones(UbicacionModelo $modelo, $provinciaId)
{
    seccion('UbicacionModelo::getCantonesPorProvincia');

    $cantones = $modelo->getCantonesPorProvincia($provinciaId);

    assertTrue(is_array($cantones), 'getCantonesPorProvincia() devuelve un array');
    assertTrue(count($cantones) > 0, "getCantonesPorProvincia($provinciaId) devuelve al menos un cantón");

   
    $vacio = $modelo->getCantonesPorProvincia(999999);
    assertEquals(0, count($vacio), 'getCantonesPorProvincia() con id inexistente devuelve array vacío');

    return $cantones;
}

function probarGetDistritos(UbicacionModelo $modelo, $cantonId)
{
    seccion('UbicacionModelo::getDistritosPorCanton');

    $distritos = $modelo->getDistritosPorCanton($cantonId);

    assertTrue(is_array($distritos), 'getDistritosPorCanton() devuelve un array');
    assertTrue(count($distritos) > 0, "getDistritosPorCanton($cantonId) devuelve al menos un distrito");

    $vacio = $modelo->getDistritosPorCanton(999999);
    assertEquals(0, count($vacio), 'getDistritosPorCanton() con id inexistente devuelve array vacío');

    return $distritos;
}

function probarUbicacionInsertUpdateGet(UbicacionModelo $modelo, $provinciaId, $cantonId, $distritoId)
{
    seccion('UbicacionModelo::insert / update / getPorId');

    // insert
    $lat = 9.9300;
    $lng = -84.0800;
    $id = $modelo->insert($provinciaId, $cantonId, $distritoId, $lat, $lng);

    assertTrue($id !== false && $id > 0, 'insert() crea una ubicación y devuelve un id válido');

    // getPorId
    $ubicacion = $modelo->getPorId($id);
    assertTrue($ubicacion !== false, 'getPorId() encuentra la ubicación recién creada');
    if ($ubicacion) {
        assertEquals($provinciaId, $ubicacion['tbubicacionprovincia'], 'getPorId() devuelve la provincia correcta');
        assertEquals($cantonId, $ubicacion['tbubicacioncanton'], 'getPorId() devuelve el cantón correcto');
        assertEquals($distritoId, $ubicacion['tbubicaciondistrito'], 'getPorId() devuelve el distrito correcto');
        assertTrue(isset($ubicacion['tbprovincianombre']), 'getPorId() incluye el nombre de la provincia (join)');
    }


    $nuevaLat = 10.0000;
    $nuevaLng = -84.5000;
    $actualizado = $modelo->update($id, $provinciaId, $cantonId, $distritoId, $nuevaLat, $nuevaLng);
    assertTrue($actualizado, 'update() retorna true al actualizar coordenadas');

    $ubicacionActualizada = $modelo->getPorId($id);
    assertEquals($nuevaLat, $ubicacionActualizada['tbubicacionlatitud'], 'update() persiste la nueva latitud');
    assertEquals($nuevaLng, $ubicacionActualizada['tbubicacionlongitud'], 'update() persiste la nueva longitud');

    return $id;
}


function probarPerfilInsert(PerfilModelo $modelo, $ubicacionId, $nombre, $contra, $correo)
{
    seccion('PerfilModelo::insert');

    $perfil = new Perfil(null, $nombre, $contra, $correo, 0, $ubicacionId, true);
    $id = $modelo->insert($perfil);

    assertTrue($id !== false && $id > 0, 'insert() crea un perfil y devuelve un id válido');

    return $id;
}

function probarPerfilGetPerfil(PerfilModelo $modelo, $id, $nombreEsperado, $correoEsperado)
{
    seccion('PerfilModelo::getPerfil');

    $perfil = $modelo->getPerfil($id);

    assertTrue($perfil !== null, 'getPerfil() encuentra el perfil recién creado');
    if ($perfil) {
        assertEquals($nombreEsperado, $perfil['tbperfilnombre'], 'getPerfil() devuelve el nombre correcto');
        assertEquals($correoEsperado, $perfil['tbperfilcorreo'], 'getPerfil() devuelve el correo correcto');
    }

    $inexistente = $modelo->getPerfil(999999999);
    assertTrue($inexistente === null, 'getPerfil() con id inexistente devuelve null');
}

function probarPerfilGetPerfilByNombre(PerfilModelo $modelo, $nombre, $idEsperado)
{
    seccion('PerfilModelo::getPerfilByNombre');

    $perfil = $modelo->getPerfilByNombre($nombre);

    assertTrue($perfil !== null, 'getPerfilByNombre() encuentra el perfil por nombre');
    if ($perfil) {
        assertEquals($idEsperado, $perfil['tbperfilid'], 'getPerfilByNombre() devuelve el id correcto');
    }

    $inexistente = $modelo->getPerfilByNombre('nombre_que_no_deberia_existir_' . uniqid());
    assertTrue($inexistente === null, 'getPerfilByNombre() con nombre inexistente devuelve null');
}

function probarPerfilLogin(PerfilModelo $modelo, $nombre, $contraCorrecta)
{
    seccion('PerfilModelo::login');

    $ok = $modelo->login($nombre, $contraCorrecta);
    assertTrue($ok !== null, 'login() con credenciales correctas devuelve el perfil');

    $mal = $modelo->login($nombre, 'contraseñaIncorrecta1');
    assertTrue($mal === null, 'login() con contraseña incorrecta devuelve null');

    $usuarioInexistente = $modelo->login('usuario_que_no_existe_' . uniqid(), $contraCorrecta);
    assertTrue($usuarioInexistente === null, 'login() con usuario inexistente devuelve null');
}

function probarPerfilUpdate(PerfilModelo $modelo, $id, $ubicacionId)
{
    seccion('PerfilModelo::update');

    $perfilActual = $modelo->getPerfil($id);
    assertTrue($perfilActual !== null, 'Precondición: el perfil existe antes de actualizar');

    $nuevoCorreo = 'actualizado_' . uniqid() . '@test.com';

    $perfilModificado = new Perfil(
        $id,
        $perfilActual['tbperfilnombre'],
        $perfilActual['tbperfilcontra'],
        $nuevoCorreo,
        1, 
        $ubicacionId,
        $perfilActual['tbperfilactivo']
    );

    $resultado = $modelo->update($perfilModificado);
    assertTrue($resultado, 'update() retorna true');

    $perfilRecargado = $modelo->getPerfil($id);
    assertEquals($nuevoCorreo, $perfilRecargado['tbperfilcorreo'], 'update() persiste el nuevo correo');
    assertEquals(1, $perfilRecargado['tbperfilcambiocontra'], 'update() persiste tbperfilcambiocontra');
}

function probarPerfilGetList(PerfilModelo $modelo, $idQueDebeEstar)
{
    seccion('PerfilModelo::getList');

    $lista = $modelo->getList();

    assertTrue(is_array($lista), 'getList() devuelve un array');

    $encontrado = false;
    foreach ($lista as $p) {
        if ($p['tbperfilid'] == $idQueDebeEstar) {
            $encontrado = true;
            break;
        }
    }
    assertTrue($encontrado, 'getList() incluye el perfil de prueba recién creado');
}

function probarPerfilDelete(PerfilModelo $modelo, $id)
{
    seccion('PerfilModelo::delete');

    $resultado = $modelo->delete($id);
    assertTrue($resultado, 'delete() retorna true');

    $perfilBorrado = $modelo->getPerfil($id);
    assertTrue($perfilBorrado === null, 'getPerfil() ya no encuentra el perfil después de delete()');
}



echo "Iniciando pruebas...\n";
echo "Usuario de prueba: $nombrePrueba\n";

try {
  
    $provincias = probarGetProvincias($ubicacionModelo);

    if (count($provincias) > 0) {
        $provinciaId = $provincias[0]['tbprovinciaid'];
        $cantones = probarGetCantones($ubicacionModelo, $provinciaId);

        if (count($cantones) > 0) {
            $cantonId = $cantones[0]['tbcantonid'];
            $distritos = probarGetDistritos($ubicacionModelo, $cantonId);

            if (count($distritos) > 0) {
                $distritoId = $distritos[0]['tbdistritoid'];

                $ubicacionIdCreada = probarUbicacionInsertUpdateGet(
                    $ubicacionModelo,
                    $provinciaId,
                    $cantonId,
                    $distritoId
                );
            }
        }
    }


    if ($ubicacionIdCreada) {
        $perfilIdCreado = probarPerfilInsert(
            $perfilModelo,
            $ubicacionIdCreada,
            $nombrePrueba,
            $contraPrueba,
            $correoPrueba
        );

        if ($perfilIdCreado) {
            probarPerfilGetPerfil($perfilModelo, $perfilIdCreado, $nombrePrueba, $correoPrueba);
            probarPerfilGetPerfilByNombre($perfilModelo, $nombrePrueba, $perfilIdCreado);
            probarPerfilLogin($perfilModelo, $nombrePrueba, $contraPrueba);
            probarPerfilUpdate($perfilModelo, $perfilIdCreado, $ubicacionIdCreada);
            probarPerfilGetList($perfilModelo, $perfilIdCreado);
            probarPerfilDelete($perfilModelo, $perfilIdCreado);
            $perfilIdCreado = null; // ya se borró, evitar doble limpieza
        }
    } else {
        echo "\n[AVISO] No se pudo crear una ubicación de prueba (revisa que existan provincias/cantones/distritos en la BD). Se omiten las pruebas de PerfilModelo.\n";
    }

} catch (Throwable $e) {
    $totalFallos++;
    echo "\n[EXCEPCIÓN] " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {

    seccion('Limpieza');

    if ($perfilIdCreado) {
        $perfilModelo->delete($perfilIdCreado);
        echo "  Perfil de prueba $perfilIdCreado eliminado en limpieza.\n";
    }


    if ($ubicacionIdCreada) {
        echo "  Ubicación de prueba $ubicacionIdCreada NO fue eliminada (UbicacionModelo no tiene delete()).\n";
    }
}



seccion('Resumen');
echo "Total de aserciones: $totalPruebas\n";
echo "Fallos:              $totalFallos\n";
echo ($totalFallos === 0 ? "RESULTADO: TODAS LAS PRUEBAS PASARON\n" : "RESULTADO: HAY PRUEBAS FALLIDAS\n");