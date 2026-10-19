<?php

require_once __DIR__ . '/../Configuracion/configuracion.php';
require_once APP_PATH . '/Modelo/perfilmodelo.php';
require_once APP_PATH . '/Modelo/ubicacionmodelo.php';
require_once APP_PATH . '/Modelo/perfil.php';
require_once APP_PATH . '/Utilidades/qdrantcliente.php';
require_once APP_PATH . '/Modelo/perfilmusicalmodelo.php';
require_once APP_PATH . '/Modelo/reproduccionmodelo.php';
require_once APP_PATH . '/Modelo/cancionchatmodelo.php';
require_once APP_PATH . '/Modelo/cancionmodelo.php';

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



class QdrantClienteFalso
{
    public $punto = null;
    public $contadorGuardar = 0;
    public $contadorCrearColeccion = 0;

    public function crearColeccionSiNoExiste($tamanioVector)
    {
        $this->contadorCrearColeccion++;
        return true;
    }

    public function guardarVector($tbperfilid, array $vector, $hashdatos, array $payloadExtra = [])
    {
        $this->contadorGuardar++;
        return true;
    }

    public function obtenerPunto($tbperfilid)
    {
        if ($this->punto === null || (int) $tbperfilid !== (int) ($this->punto['id'] ?? null)) {
            return null;
        }

        return $this->punto;
    }

    public function buscarSimilares($vector, $limite = 5)
    {
        return [];
    }
}

class PerfilMusicalModeloPrueba extends PerfilMusicalModelo
{
    public $qdrantPrueba;

    protected function crearQdrantCliente()
    {
        return $this->qdrantPrueba;
    }

    public function calcularHashDatosPublico($perfilId)
    {
        return $this->calcularHashDatos($perfilId);
    }
}



function probarCalcularHashDatos($perfilModelo)
{
    seccion('PerfilMusicalModelo::calcularHashDatos');

    $conexion = Basedatos::conectar();

    $stmtC = $conexion->prepare("SELECT tbcancionid FROM tbcancion WHERE tbcancionactivo = 1 ORDER BY tbcancionid LIMIT 1");
    $stmtC->execute();
    $cancionId = (int) $stmtC->fetchColumn();

    $stmtU = $conexion->prepare("SELECT tbubicacionid FROM tbubicacion ORDER BY tbubicacionid LIMIT 1");
    $stmtU->execute();
    $ubicacionId = (int) $stmtU->fetchColumn();

    $sufijo = substr(bin2hex(random_bytes(4)), 0, 6);
    $perfil = new Perfil(null, "hash_{$sufijo}", 'bcdf1234', "hash_{$sufijo}@test.com", 0, $ubicacionId, 'cliente', true);
    $perfilId = $perfilModelo->insert($perfil);

    assertTrue($perfilId !== false && $perfilId > 0, 'Precondición: se crea un perfil de prueba');

    $reproduccionModelo = new ReproduccionModelo();
    $reproduccionModelo->acumularTiempo($perfilId, $cancionId, 10);

    $modelo = new PerfilMusicalModeloPrueba();

    $h1 = $modelo->calcularHashDatosPublico($perfilId);
    assertTrue(is_string($h1) && strlen($h1) === 64, 'calcularHashDatos() devuelve un sha256 (64 caracteres hex)');
    assertEquals($h1, $modelo->calcularHashDatosPublico($perfilId), 'calcularHashDatos() es determinista: mismos datos reproducen el mismo hash');

    $reproduccionModelo->acumularTiempo($perfilId, $cancionId, 5);
    $h2 = $modelo->calcularHashDatosPublico($perfilId);
    assertTrue($h1 !== $h2, 'calcularHashDatos() cambia el hash al modificar tbreproducciontiempo');

    $reproduccionModelo->incrementarContador($perfilId, $cancionId);
    $h3 = $modelo->calcularHashDatosPublico($perfilId);
    assertTrue($h3 !== $h1 && $h3 !== $h2, 'calcularHashDatos() cambia el hash al cambiar la data semanal');

    $stmtSem = $conexion->prepare("SELECT tbreproduccionsemanalid FROM tbreproduccion WHERE tbperfilid = :perfilId");
    $stmtSem->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
    $stmtSem->execute();
    $idsSemanal = $stmtSem->fetchAll(PDO::FETCH_COLUMN);

    $reproduccionModelo->deleteByPerfilId($perfilId);

    foreach ($idsSemanal as $idSemanal) {
        $stmtDel = $conexion->prepare("DELETE FROM tbreproduccionsemanal WHERE tbreproduccionsemanalid = :id");
        $stmtDel->bindValue(':id', (int) $idSemanal, PDO::PARAM_INT);
        $stmtDel->execute();
    }

    $perfilModelo->delete($perfilId);

    echo "  Limpieza: reproducciones y perfil de prueba (id {$perfilId}) eliminados.\n";
}

function probarGenerarPerfiladoCache()
{
    seccion('PerfilMusicalModelo::generarPerfilado (hit de caché en Qdrant)');

    $conexion = Basedatos::conectar();

    $stmtP = $conexion->prepare("SELECT tbperfilid FROM tbperfil WHERE tbperfilactivo = 1 ORDER BY tbperfilid LIMIT 1");
    $stmtP->execute();
    $perfilId = (int) $stmtP->fetchColumn();

    assertTrue($perfilId > 0, 'Precondición: existe al menos un perfil activo en la BD');

    $resultadosGuardados = [
        [
            'tipo' => 'especifico',
            'dia' => 'Lun',
            'franja' => 'Noche',
            'genero' => 'Pop',
            'confianza' => 92.5,
            'soporte' => 10,
            'texto' => 'Me gusta escuchar Pop los lunes en la noche'
        ]
    ];

    $modelo = new PerfilMusicalModeloPrueba();
    $hash = $modelo->calcularHashDatosPublico($perfilId);

    $falso = new QdrantClienteFalso();
    $falso->punto = [
        'id' => $perfilId,
        'vector' => [0.9, 0.1, 0.0],
        'payload' => [
            'hashdatos' => $hash,
            'totalEventos' => 12,
            'resultados' => $resultadosGuardados
        ]
    ];
    $modelo->qdrantPrueba = $falso;

    $res = $modelo->generarPerfilado($perfilId);

    assertTrue(($res['exito'] ?? false) === true, 'generarPerfilado() con hash coincidente responde exito = true');
    assertTrue(($res['desdeCache'] ?? false) === true, 'generarPerfilado() entrega desdeCache = true sin reentrenar');
    assertEquals(12, ($res['totalEventos'] ?? null), 'generarPerfilado() reusa el totalEventos guardado en Qdrant');
    assertEquals(count($resultadosGuardados), count($res['resultados'] ?? []), 'generarPerfilado() devuelve los resultados guardados en Qdrant');
    assertEquals(0, $falso->contadorGuardar, 'generarPerfilado() no volvió a entrenar (guardarVector no fue llamado)');
    assertEquals(0, $falso->contadorCrearColeccion, 'generarPerfilado() no volvió a crear la colección');
}

function probarQdrantClienteApagado()
{
    seccion('QdrantCliente con servicio apagado');

    $archivoLog = tempnam(sys_get_temp_dir(), 'qdrant_prueba_');
    ini_set('error_log', $archivoLog);

    $cliente = new QdrantCliente('127.0.0.1', 1);

    $guardado = $cliente->guardarVector(999, [1.0, 0.0], 'hashdeprueba');
    assertTrue($guardado === false, 'guardarVector() devuelve false sin lanzar excepción cuando Qdrant no responde');

    $punto = $cliente->obtenerPunto(999);
    assertTrue($punto === null, 'obtenerPunto() devuelve null sin lanzar excepción cuando Qdrant no responde');

    $similares = $cliente->buscarSimilares([1.0, 0.0]);
    assertTrue($similares === null, 'buscarSimilares() devuelve null sin lanzar excepción cuando Qdrant no responde');

    $coleccion = $cliente->crearColeccionSiNoExiste(2);
    assertTrue($coleccion === false, 'crearColeccionSiNoExiste() devuelve false sin lanzar excepción cuando Qdrant no responde');

    $contenidoLog = file_get_contents($archivoLog);
    assertTrue(strpos($contenidoLog, 'Qdrant') !== false, 'el error queda registrado vía error_log()');

    ini_restore('error_log');
    @unlink($archivoLog);
}



$sufijo = substr(bin2hex(random_bytes(4)), 0, 6);


function probarCancionChatModelo()
{
    seccion('CancionChatModelo');

    $modelo = new CancionChatModelo();
    $db = Basedatos::conectar();

    /* ── Datos de prueba propios ── */
    // Buscar una canción activa real
    $stmtCancion = $db->query('SELECT tbcancionid FROM tbcancion WHERE tbcancionactivo = 1 LIMIT 1');
    $filaCan = $stmtCancion->fetch(PDO::FETCH_ASSOC);

    if (!$filaCan) {
        echo "  [AVISO] No hay canciones activas en la BD; se omiten las pruebas de CancionChatModelo.\n";
        return;
    }
    $cancionId = (int) $filaCan['tbcancionid'];

    // Crear perfil de prueba propio
    $sufijoCc = substr(bin2hex(random_bytes(4)), 0, 6);
    $stmtUbic = $db->query('SELECT tbubicacionid FROM tbubicacion LIMIT 1');
    $filaUbic = $stmtUbic->fetch(PDO::FETCH_ASSOC);
    $ubicId = $filaUbic ? (int) $filaUbic['tbubicacionid'] : 1;

    $stmtIns = $db->prepare(
        'INSERT INTO tbperfil (tbubicacionid, tbperfilnombre, tbperfilcontra, tbperfilcorreo, tbperfilrol, tbperfilactivo)
         VALUES (:ubic, :nombre, :contra, :correo, \'cliente\', 1)'
    );
    $stmtIns->bindValue(':ubic', $ubicId, PDO::PARAM_INT);
    $stmtIns->bindValue(':nombre', 'cc_prueba_' . $sufijoCc);
    $stmtIns->bindValue(':contra', password_hash('test1234', PASSWORD_BCRYPT));
    $stmtIns->bindValue(':correo', 'cc_' . $sufijoCc . '@test.com');
    $stmtIns->execute();
    $perfilId = (int) $db->lastInsertId();

    assertTrue($perfilId > 0, 'CancionChat: se crea un perfil de prueba propio');

    /* ── Validaciones de entrada ── */
    $resVacio = $modelo->enviarMensaje($cancionId, $perfilId, '   ');
    assertTrue($resVacio['exito'] === false, 'CancionChat: rechaza mensaje vacío');
    assertTrue(isset($resVacio['mensaje']), 'CancionChat: devuelve mensaje de error en vacío');

    $resLargo = $modelo->enviarMensaje($cancionId, $perfilId, str_repeat('a', 301));
    assertTrue($resLargo['exito'] === false, 'CancionChat: rechaza mensaje de más de 300 caracteres');

    $resCancionMala = $modelo->enviarMensaje(0, $perfilId, 'Hola');
    assertTrue($resCancionMala['exito'] === false, 'CancionChat: rechaza cancionId = 0');

    $resCancionInexistente = $modelo->enviarMensaje(999999, $perfilId, 'Hola');
    assertTrue($resCancionInexistente['exito'] === false, 'CancionChat: rechaza canción inexistente');

    $resPerfilMalo = $modelo->enviarMensaje($cancionId, 0, 'Hola');
    assertTrue($resPerfilMalo['exito'] === false, 'CancionChat: rechaza perfilId = 0');

    /* ── Envío exitoso ── */
    $textoOk = 'Mensaje de prueba ' . $sufijoCc;
    $resOk = $modelo->enviarMensaje($cancionId, $perfilId, $textoOk);
    assertTrue($resOk['exito'] === true, 'CancionChat: envío exitoso devuelve exito = true');
    assertTrue(isset($resOk['data']['tbcancionchatid']), 'CancionChat: devuelve tbcancionchatid');
    $mensajeId = (int) ($resOk['data']['tbcancionchatid'] ?? 0);
    assertTrue($mensajeId > 0, 'CancionChat: tbcancionchatid es positivo');
    assertEquals($cancionId, $resOk['data']['tbcancionid'] ?? null, 'CancionChat: devuelve el cancionId correcto');
    assertEquals($perfilId, $resOk['data']['tbperfilid'] ?? null, 'CancionChat: devuelve el perfilId correcto');
    assertTrue(isset($resOk['data']['tbcancionchatfechahora']), 'CancionChat: devuelve fecha/hora');

    /* ── getMensajesNuevos: carga inicial (desdeId = 0) ── */
    $resMensajes = $modelo->getMensajesNuevos($cancionId, 0);
    assertTrue($resMensajes['exito'] === true, 'CancionChat: getMensajesNuevos devuelve exito = true');
    assertTrue(is_array($resMensajes['data']), 'CancionChat: getMensajesNuevos devuelve array');

    $encontrado = false;
    foreach ($resMensajes['data'] as $m) {
        if ((int) ($m['tbcancionchatid'] ?? 0) === $mensajeId) {
            $encontrado = true;
            break;
        }
    }
    assertTrue($encontrado, 'CancionChat: el mensaje recién enviado aparece en getMensajesNuevos');

    /* ── getMensajesNuevos: solo nuevos (desdeId = mensajeId) ── */
    $resSoloNuevos = $modelo->getMensajesNuevos($cancionId, $mensajeId);
    assertTrue($resSoloNuevos['exito'] === true, 'CancionChat: getMensajesNuevos con desdeId correcto');
    $noRepite = true;
    foreach ($resSoloNuevos['data'] as $m) {
        if ((int) ($m['tbcancionchatid'] ?? 0) === $mensajeId) {
            $noRepite = false;
            break;
        }
    }
    assertTrue($noRepite, 'CancionChat: getMensajesNuevos con desdeId no repite mensajes anteriores');

    /* ── getMensajesNuevos: canción inválida ── */
    $resMala = $modelo->getMensajesNuevos(0, 0);
    assertTrue($resMala['exito'] === false, 'CancionChat: getMensajesNuevos rechaza cancionId = 0');

    /* ── Limpieza propia ── */
    $stmtDelMsg = $db->prepare('DELETE FROM tbcancionchat WHERE tbperfilid = :pid');
    $stmtDelMsg->bindValue(':pid', $perfilId, PDO::PARAM_INT);
    $stmtDelMsg->execute();

    $stmtDelPerfil = $db->prepare('DELETE FROM tbperfil WHERE tbperfilid = :pid');
    $stmtDelPerfil->bindValue(':pid', $perfilId, PDO::PARAM_INT);
    $stmtDelPerfil->execute();

    echo "  Datos de prueba de CancionChat ($perfilId) eliminados.\n";
}

$nombrePrueba  = "prueba_$sufijo";
$correoPrueba  = "prueba_$sufijo@test.com";
$contraPrueba  = "abcd1234"; 
$contraPrueba  = "bcdfgh12"; 
$contraPrueba  = "bcdf1234"; 

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

    probarCalcularHashDatos($perfilModelo);
    probarGenerarPerfiladoCache();
    probarQdrantClienteApagado();
    probarCancionChatModelo();

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