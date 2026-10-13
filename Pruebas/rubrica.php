<?php

require_once __DIR__ . '/../Configuracion/configuracion.php';
require_once APP_PATH . '/Utilidades/validacion.php';
require_once APP_PATH . '/Utilidades/autenticacion.php';
require_once APP_PATH . '/Modelo/perfilmodelo.php';
require_once APP_PATH . '/Modelo/generomodelo.php';
require_once APP_PATH . '/Modelo/eventomodelo.php';
require_once APP_PATH . '/Modelo/cancionmodelo.php';
require_once APP_PATH . '/Modelo/ubicacionmodelo.php';

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

function seccion($titulo)
{
    echo "\n=== $titulo ===\n";
}

seccion('Validaciones de servidor');

assertTrue(campoRequerido('   ', 'nombre') !== null, 'campoRequerido: rechaza cadena con solo espacios');
assertTrue(campoRequerido('', 'nombre') !== null, 'campoRequerido: rechaza cadena vacía');
assertTrue(campoRequerido('Ana', 'nombre') === null, 'campoRequerido: acepta un valor válido');

assertTrue(campoLongitud('corto', 'nombre', 3) !== null, 'campoLongitud: rechaza exceso de longitud');
assertTrue(campoLongitud('abc', 'nombre', 3) === null, 'campoLongitud: acepta longitud en el límite');

assertTrue(correoValido('no-es-correo') === false, 'correoValido: rechaza correo sin formato');
assertTrue(correoValido('ana@test.com') === true, 'correoValido: acepta correo válido');

assertTrue(coordenadasValidas(91, 0) !== null, 'coordenadasValidas: rechaza latitud fuera de rango');
assertTrue(coordenadasValidas(0, 181) !== null, 'coordenadasValidas: rechaza longitud fuera de rango');
assertTrue(coordenadasValidas('abc', 0) !== null, 'coordenadasValidas: rechaza valores no numéricos');
assertTrue(coordenadasValidas(9.93, -84.08) === null, 'coordenadasValidas: acepta coordenadas válidas');

assertTrue(urlValida('javascript:alert(1)') !== null, 'urlValida: rechaza esquema no http(s)');
assertTrue(urlValida('https://ejemplo.com/cancion') === null, 'urlValida: acepta URL http(s) válida');

seccion('Detección de duplicados en PHP');

$conexion = Basedatos::conectar();
$sufijo = substr(bin2hex(random_bytes(4)), 0, 6);

$perfilModelo = new PerfilModelo();
$generoModelo = new GeneroModelo();
$eventoModelo = new EventoModelo();
$cancionModelo = new CancionModelo();
$ubicacionModelo = new UbicacionModelo();

$perfilId = null;
$generoId = null;
$eventoId = null;
$cancionId = null;

try {
    // Perfil: nombre y correo únicos sin distinguir mayúsculas.
    $nombre = "rubrica_$sufijo";
    $correo = "rubrica_$sufijo@test.com";
    $ubicacionId = $ubicacionModelo->insert(null, null, null, null, null);
    $perfil = new Perfil(null, $nombre, 'bcdf1234', $correo, 1, $ubicacionId, 'cliente', true);
    $perfilId = $perfilModelo->insert($perfil);

    assertTrue($perfilId !== false && $perfilId > 0, 'Perfil: se crea un registro de prueba');
    assertTrue($perfilModelo->existeNombre($nombre) === true, 'Perfil: detecta nombre existente');
    assertTrue($perfilModelo->existeNombre(strtoupper($nombre)) === true, 'Perfil: detecta nombre sin distinguir mayúsculas');
    assertTrue($perfilModelo->existeCorreo(strtoupper($correo)) === true, 'Perfil: detecta correo sin distinguir mayúsculas');
    assertTrue($perfilModelo->existeNombre($nombre, $perfilId) === false, 'Perfil: al editarse a sí mismo no se marca duplicado');
    assertTrue($perfilModelo->existeCorreo($correo, $perfilId) === false, 'Perfil: su propio correo no se marca duplicado');
    assertTrue($perfilModelo->existeNombre("rubrica_no_existe_$sufijo") === false, 'Perfil: nombre inexistente no se marca duplicado');

    // Género.
    $nombreGenero = "Genero_$sufijo";
    $generoId = $generoModelo->insert(new Genero(null, $nombreGenero));
    assertTrue($generoId > 0, 'Género: se crea un registro de prueba');
    assertTrue($generoModelo->existeNombre(strtolower($nombreGenero)) === true, 'Género: detecta duplicado sin distinguir mayúsculas');
    assertTrue($generoModelo->existeNombre($nombreGenero, $generoId) === false, 'Género: al editarse a sí mismo no se marca duplicado');

    // Evento (nombre + fecha).
    $nombreEvento = "Evento_$sufijo";
    $fechaEvento = '2000-01-01';
    $eventoId = $eventoModelo->insert(new Evento(null, $generoId, $nombreEvento, 'Artista', 'Lugar', 9.93, -84.08, $fechaEvento, '20:00'));
    assertTrue($eventoId > 0, 'Evento: se crea un registro de prueba');
    assertTrue($eventoModelo->existeEvento(strtoupper($nombreEvento), $fechaEvento) === true, 'Evento: detecta duplicado sin distinguir mayúsculas');
    assertTrue($eventoModelo->existeEvento($nombreEvento, $fechaEvento, $eventoId) === false, 'Evento: al editarse a sí mismo no se marca duplicado');
    assertTrue($eventoModelo->existeEvento($nombreEvento, '2001-02-02') === false, 'Evento: distinta fecha no se marca duplicado');

    // Canción (nombre + artista).
    $nombreCancion = "Cancion_$sufijo";
    $cancionId = $cancionModelo->insert(new Cancion(null, $generoId, $nombreCancion, 'Artista', 'https://ejemplo.com/a.mp3', true));
    assertTrue($cancionId > 0, 'Canción: se crea un registro de prueba');
    assertTrue($cancionModelo->existeDuplicada(strtoupper($nombreCancion), 'artista') === true, 'Canción: detecta duplicado sin distinguir mayúsculas');
    assertTrue($cancionModelo->existeDuplicada($nombreCancion, 'Artista', $cancionId) === false, 'Canción: al editarse a sí misma no se marca duplicado');
} finally {
    seccion('Limpieza de datos de prueba');
    if ($cancionId !== null) {
        $stmt = $conexion->prepare("DELETE FROM tbcancion WHERE tbcancionid = :id");
        $stmt->bindValue(':id', (int) $cancionId, PDO::PARAM_INT);
        $stmt->execute();
    }
    if ($eventoId !== null) {
        $stmt = $conexion->prepare("DELETE FROM tbevento WHERE tbeventoid = :id");
        $stmt->bindValue(':id', (int) $eventoId, PDO::PARAM_INT);
        $stmt->execute();
    }
    if ($generoId !== null) {
        $stmt = $conexion->prepare("DELETE FROM tbgenero WHERE tbgeneroid = :id");
        $stmt->bindValue(':id', (int) $generoId, PDO::PARAM_INT);
        $stmt->execute();
    }
    if ($perfilId !== null) {
        $perfilModelo->delete($perfilId);
    }
    echo "  Datos de prueba eliminados (sufijo $sufijo).\n";
}

seccion('No filtración de errores internos');

$configuracion = file_get_contents(APP_PATH . '/../Configuracion/configuracion.php');
$basedatos = file_get_contents(APP_PATH . '/../Configuracion/basedatos.php');

assertTrue(strpos($configuracion, "ini_set('display_errors', '0')") !== false, 'configuracion.php: display_errors está desactivado');
assertTrue(strpos($configuracion, 'set_exception_handler') !== false, 'configuracion.php: existe manejador global de excepciones');
assertTrue(strpos($configuracion, 'set_error_handler') !== false, 'configuracion.php: existe manejador global de errores');
assertTrue(strpos($basedatos, 'die(') === false, 'basedatos.php: no utiliza die() con el mensaje de conexión');

$rutas = array_merge(
    glob(APP_PATH . '/Controlador/*.php'),
    glob(APP_PATH . '/Modelo/*.php')
);

$patronesProhibidos = [
    'die($e->getMessage',
    'die("Error de conexión: " . $e->getMessage',
];
$filtraciones = [];
$camposMessage = 0;

foreach ($rutas as $ruta) {
    $contenido = file_get_contents($ruta);
    foreach ($patronesProhibidos as $patron) {
        if (strpos($contenido, $patron) !== false) {
            $filtraciones[] = basename($ruta) . ' -> ' . $patron;
        }
    }
    // Mensajes que se devuelven al cliente con getMessage() directo.
    if (preg_match('/"mensaje"\s*=>\s*[^;]*getMessage\(\)/s', $contenido)) {
        $camposMessage++;
    }
}

assertTrue(count($filtraciones) === 0, 'Ningún archivo invoca die() con el mensaje de excepción (' . count($filtraciones) . ' hallazgos)');
assertTrue($camposMessage === 0, 'Ningún controlador/modelo devuelve $e->getMessage() en el campo mensaje (' . $camposMessage . ' hallazgos)');

$autenticacion = file_get_contents(APP_PATH . '/Utilidades/autenticacion.php');
assertTrue(strpos($autenticacion, 'http_response_code') !== false, 'autenticacion.php: la denegación responde con estado HTTP');
assertTrue(strpos($autenticacion, "'tipo'") !== false, 'autenticacion.php: la denegación incluye el tipo de error');
assertTrue(strpos($autenticacion, 'esPeticionApi') !== false, 'autenticacion.php: distingue peticiones de API de vistas HTML');

$perfilControlador = file_get_contents(APP_PATH . '/Controlador/perfilcontrolador.php');
assertTrue(substr_count($perfilControlador, "exigirRol(['admin'])") >= 4, 'perfilcontrolador.php: las operaciones administrativas exigen rol admin');
assertTrue(strpos($perfilControlador, 'session_regenerate_id(true)') !== false, 'perfilcontrolador.php: regenera el id de sesión al iniciar sesión');

seccion('Resumen');
echo "Total de aserciones: $totalPruebas\n";
echo "Fallos:              $totalFallos\n";
echo ($totalFallos === 0 ? "RESULTADO: TODAS LAS PRUEBAS PASARON\n" : "RESULTADO: HAY PRUEBAS FALLIDAS\n");

exit($totalFallos === 0 ? 0 : 1);
