<?php
/**
 * Siembra del modelo de moderación.
 *
 * Uso (desde la raíz del proyecto):
 *   php Aplicacion/Utilidades/sembrarmoderacion.php
 *   php Aplicacion/Utilidades/sembrarmoderacion.php --diagnostico
 *   php Aplicacion/Utilidades/sembrarmoderacion.php --reconstruir
 *
 * El ajuste de los transformadores ocurre UNA sola vez sobre las 300 filas del
 * dataset y queda serializado en BaseDatos/Moderacion/moderacion_rubix.bin.
 * En producción nadie vuelve a entrenar: los requests solo deserializan.
 */

require_once __DIR__ . '/../Modelo/moderacionmodelo.php';

$opciones = array_slice($argv, 1);
$soloDiagnostico = in_array('--diagnostico', $opciones, true);
$reconstruir = in_array('--reconstruir', $opciones, true);

function linea($texto = '')
{
    echo $texto . PHP_EOL;
}

function ok($texto)
{
    linea('  [ok] ' . $texto);
}

function fallo($texto)
{
    linea('  [!!] ' . $texto);
}

/**
 * Lee el CSV con fgetcsv porque algunos textos contienen comas y están
 * entrecomillados; exploding por coma partiría filas.
 */
function leerDataset()
{
    $ruta = BASE_PATH . '/BaseDatos/Moderacion/dataset_moderacion.csv';

    if (!is_file($ruta)) {
        return ['exito' => false, 'mensaje' => 'No existe el dataset de moderación.'];
    }

    $manejador = fopen($ruta, 'r');

    if ($manejador === false) {
        return ['exito' => false, 'mensaje' => 'No se pudo abrir el dataset de moderación.'];
    }

    fgetcsv($manejador, null, ',', '"', '');

    $textos = [];
    $categorias = [];

    while (($fila = fgetcsv($manejador, null, ',', '"', '')) !== false) {
        if (count($fila) < 2) {
            continue;
        }

        $texto = trim((string) $fila[0]);
        $categoria = strtolower(trim((string) $fila[1]));

        if ($texto === '' || $categoria === '') {
            continue;
        }

        $textos[] = $texto;
        $categorias[] = $categoria;
    }

    fclose($manejador);

    if (empty($textos)) {
        return ['exito' => false, 'mensaje' => 'El dataset de moderación está vacío.'];
    }

    return ['exito' => true, 'textos' => $textos, 'categorias' => $categorias];
}

if ($soloDiagnostico) {
    $modelo = new ModeracionModelo();
    linea('Diagnóstico de moderación');
    linea(json_encode($modelo->diagnostics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    exit(0);
}

linea('Siembra del modelo de moderación');
linea(str_repeat('=', 60));
linea('Rubix ML: ' . ModeracionModelo::versionRubix());
linea('Dataset: ' . BASE_PATH . '/BaseDatos/Moderacion/dataset_moderacion.csv');
linea('Artefacto: ' . ModeracionModelo::rutaArtefacto());
linea('');

$modelo = new ModeracionModelo();

if (ModeracionModelo::existeArtefacto() && !$reconstruir) {
    ok('Ya existe un artefacto ajustado. Se reutiliza (use --reconstruir para rehacerlo).');
    $artefacto = null;
} else {
    linea('1. Ajustando transformadores sobre el dataset...');
    $ajuste = ModeracionModelo::construirArtefacto();

    if (!$ajuste['exito']) {
        fallo($ajuste['mensaje']);
        exit(1);
    }

    $artefacto = $ajuste['data'];
    ok('Ejemplos: ' . $artefacto['cantidadEjemplos']);
    ok('Categorías: ' . implode(', ', $artefacto['categorias']));
    ok('Vocabulario: ' . $artefacto['tamanoVocabulario'] . ' términos');
    ok('Dimensión del vector: ' . $artefacto['dimension']);
    ok('SHA-256 dataset: ' . $artefacto['datasetSha256']);

    linea('');
    linea('2. Persistiendo el artefacto...');
    $guardado = ModeracionModelo::guardarArtefacto($artefacto);

    if (!$guardado['exito']) {
        fallo($guardado['mensaje']);
        exit(1);
    }

    ok('Artefacto escrito (' . $guardado['data']['bytes'] . ' bytes)');
    $artefacto = null;
}

linea('');
linea('3. Preparando la colección Qdrant...');

$diagnostico = $modelo->diagnostics();

if (empty($diagnostico['artefacto']['cargado'])) {
    fallo($diagnostico['artefacto']['error'] ?? 'No se pudo cargar el artefacto.');
    exit(1);
}

$dimension = (int) $diagnostico['artefacto']['dimension'];
$cliente = new QdrantCliente(null, null, 'moderacion');

if (!$cliente->crearColeccionSiNoExiste($dimension)) {
    fallo('No se pudo crear/verificar la colección moderacion con dimensión ' . $dimension . '.');
    exit(1);
}

ok('Colección moderacion lista con dimensión ' . $dimension);

linea('');
linea('4. Leyendo el dataset y vectorizando con el artefacto persistido...');

$lectura = leerDataset();

if (!$lectura['exito']) {
    fallo($lectura['mensaje']);
    exit(1);
}

ok('Filas leídas: ' . count($lectura['textos']));

// Se vectoriza con el artefacto ya persistido: no se reentrena nada aquí.
$vectores = $modelo->vectorizarLote($lectura['textos']);

if ($vectores === null) {
    fallo('No se pudo vectorizar el dataset con el artefacto cargado.');
    exit(1);
}

$puntos = [];

foreach ($vectores as $indice => $vector) {
    $texto = $lectura['textos'][$indice];
    $puntos[] = [
        'id' => ModeracionModelo::idPunto($texto),
        'vector' => $vector,
        'payload' => [
            'categoria' => $lectura['categorias'][$indice],
            'textoOriginal' => $texto,
            'origen' => 'dataset'
        ]
    ];
}

$ids = array_column($puntos, 'id');

if (count(array_unique($ids)) !== count($ids)) {
    fallo('Colisión de IDs al derivarlos del texto; el sembrado se cancela.');
    exit(1);
}

linea('');
linea('5. Subiendo vectores a Qdrant...');

if (!$cliente->guardarPuntos($puntos)) {
    fallo('Qdrant rechazó el lote de vectores.');
    exit(1);
}

ok(count($puntos) . ' vectores enviados en un lote');

linea('');
linea('6. Verificación...');

$punto = $cliente->obtenerPunto($puntos[0]['id']);

if ($punto === null || count($punto['vector'] ?? []) !== $dimension) {
    fallo('El punto de verificación no coincide con la dimensión esperada.');
    exit(1);
}

ok('Punto de verificación recovered con ' . count($punto['vector']) . ' dimensiones');

$similares = $cliente->buscarSimilares($puntos[0]['vector'], 1);

if ($similares === null || empty($similares)) {
    fallo('La búsqueda de similitud no devolvió resultados.');
    exit(1);
}

ok('Similitud del primer ejemplo: ' . round((float) $similares[0]['score'], 6));

linea('');
linea('Siembra completada.');
linea('El frontend todavía no muestra el marcador de bloqueo; el enmascarado');
linea('ocurre en el modelo, así que aparecerá solo al leer los mensajes.');