<?php

require_once __DIR__ . '/../Modelo/temasmodelo.php';

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

if ($soloDiagnostico) {
    $modelo = new TemasModelo();
    linea('Diagnóstico de temas');
    linea(json_encode($modelo->diagnostics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    exit(0);
}

linea('Siembra del clasificador de temas');
linea(str_repeat('=', 60));
linea('Rubix ML: ' . TemasModelo::versionRubix());
linea('Dataset: ' . BASE_PATH . '/BaseDatos/Temas/dataset_temas.csv');
linea('Artefacto: ' . TemasModelo::rutaArtefacto());
linea('');

$modelo = new TemasModelo();

if (TemasModelo::existeArtefacto() && !$reconstruir) {
    ok('Ya existe un artefacto ajustado. Se reutiliza (use --reconstruir para rehacerlo).');
} else {
    linea('1. Ajustando transformadores sobre el dataset...');
    $ajuste = TemasModelo::construirArtefacto();

    if (!$ajuste['exito']) {
        fallo($ajuste['mensaje']);
        exit(1);
    }

    $artefacto = $ajuste['data'];
    ok('Ejemplos: ' . $artefacto['cantidadEjemplos']);
    ok('Tipos: ' . implode(', ', $artefacto['tipos']));
    ok('Temas: ' . count($artefacto['temas']));
    ok('Vocabulario: ' . $artefacto['tamanoVocabulario'] . ' términos');
    ok('Dimensión del vector: ' . $artefacto['dimension']);
    ok('SHA-256 dataset: ' . $artefacto['datasetSha256']);

    linea('');
    linea('2. Persistiendo el artefacto...');
    $guardado = TemasModelo::guardarArtefacto($artefacto);

    if (!$guardado['exito']) {
        fallo($guardado['mensaje']);
        exit(1);
    }

    ok('Artefacto escrito (' . $guardado['data']['bytes'] . ' bytes)');
}

linea('');
linea('3. Preparando la colección Qdrant...');

$diagnostico = $modelo->diagnostics();

if (empty($diagnostico['artefacto']['cargado'])) {
    fallo($diagnostico['artefacto']['error'] ?? 'No se pudo cargar el artefacto.');
    exit(1);
}

$dimension = (int) $diagnostico['artefacto']['dimension'];
$cliente = new QdrantCliente(null, null, 'temas');

if (!$cliente->crearColeccionSiNoExiste($dimension)) {
    fallo('No se pudo crear/verificar la colección temas con dimensión ' . $dimension . '.');
    exit(1);
}

ok('Colección temas lista con dimensión ' . $dimension);

if (!$cliente->crearIndice('tema', 'keyword')) {
    fallo('No se pudo crear el índice de payload "tema".');
    exit(1);
}

ok('Índice de payload "tema" listo');

linea('');
linea('4. Leyendo el dataset y vectorizando con el artefacto persistido...');

$lectura = TemasModelo::leerDataset();

if (!$lectura['exito']) {
    fallo($lectura['mensaje']);
    exit(1);
}

ok('Filas leídas: ' . count($lectura['textos']));

$vectores = $modelo->vectorizarLote($lectura['textos']);

if ($vectores === null) {
    fallo('No se pudo vectorizar el dataset con el artefacto cargado.');
    exit(1);
}

$puntos = [];

foreach ($vectores as $indice => $vector) {
    $puntos[] = [
        'id' => TemasModelo::idPunto($lectura['textos'][$indice]),
        'vector' => $vector,
        'payload' => [
            'tipo' => $lectura['tipos'][$indice],
            'tema' => $lectura['temas'][$indice],
            'textoOriginal' => $lectura['textos'][$indice],
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

ok('Punto de verificación recuperado con ' . count($punto['vector']) . ' dimensiones');

$similares = $cliente->buscarSimilares($puntos[0]['vector'], 1);

if ($similares === null || empty($similares)) {
    fallo('La búsqueda de similitud no devolvió resultados.');
    exit(1);
}

ok('Similitud del primer ejemplo: ' . round((float) $similares[0]['score'], 6));

linea('');
linea('Siembra completada.');
