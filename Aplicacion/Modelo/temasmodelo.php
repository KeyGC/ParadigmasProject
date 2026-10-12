<?php
require_once __DIR__ . '/../../Configuracion/configuracion.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';

/**
 * Clasificador de temas de interes (musica/comida/deporte).
 *
 * Estrategia de dos etapas:
 *   1. PUERTA por terminos distintivos: un termino que aparece en el dataset
 *      bajo UN SOLO tema (mas el nombre del tema y alias minimos). Si el
 *      mensaje no contiene al menos uno, no se clasifica. Los terminos que
 *      aparecen en varios temas no abren la puerta por si solos.
 *   2. RANKING: entre los temas que pasan la puerta se vectoriza con TF-IDF
 *      propio (stoplist en espanol, sin el diccionario de sinonimos de
 *      moderacion) y se elige el de mayor similitud en la coleccion Qdrant
 *      'temas'. Se exige TEMAS_UMBRAL.
 *
 * El artefacto y el dataset viven en BaseDatos/Temas/.
 */
class TemasModelo
{
    private const COLECCION = 'temas';

    private const VERSION_ARTIFACTO = 2;

    private const RUTA_ARTIFACTO = BASE_PATH . '/BaseDatos/Temas/temas_rubix.bin';

    private static $artefacto = null;

    private static $artefactoCargado = false;

    private static $indiceAsegurado = false;

    private function cliente()
    {
        return new QdrantCliente(null, null, self::COLECCION);
    }

    public static function rutaArtefacto()
    {
        return self::RUTA_ARTIFACTO;
    }

    public static function existeArtefacto()
    {
        return is_file(self::RUTA_ARTIFACTO) && filesize(self::RUTA_ARTIFACTO) > 0;
    }

    private static function artefactoRutaCsv()
    {
        return BASE_PATH . '/BaseDatos/Temas/dataset_temas.csv';
    }

    public static function sha256Dataset()
    {
        $ruta = self::artefactoRutaCsv();

        if (!is_file($ruta)) {
            return null;
        }

        return hash_file('sha256', $ruta);
    }

    public static function versionRubix()
    {
        if (class_exists('Composer\\InstalledVersions')
            && method_exists('Composer\\InstalledVersions', 'getPrettyVersion')) {
            $version = @Composer\InstalledVersions::getPrettyVersion('rubix/ml');

            if (is_string($version) && $version !== '') {
                return $version;
            }
        }

        return defined('Rubix\\ML\\VERSION') ? Rubix\ML\VERSION : 'desconocida';
    }

    private static function stopwords()
    {
        static $cargadas = null;

        if ($cargadas !== null) {
            return $cargadas;
        }

        $archivo = __DIR__ . '/../Utilidades/stopwordsespanol.php';
        $lista = is_file($archivo) ? (require $archivo) : [];

        $cargadas = [];

        if (is_array($lista)) {
            foreach ($lista as $palabra) {
                $cargadas[(string) $palabra] = true;
            }
        }

        return $cargadas;
    }

    private static function alias()
    {
        return [
            'regueton' => 'Reggaetón',
            'reggaeton' => 'Reggaetón',
            'basquet' => 'Baloncesto',
            'basquetbol' => 'Baloncesto',
            'baloncesto' => 'Baloncesto',
            'futbol' => 'Fútbol',
            'balompie' => 'Fútbol',
            'trotar' => 'Running',
            'trote' => 'Running',
            'skate' => 'Skateboarding',
        ];
    }

    /**
     * Normaliza a minusculas y sin acentos, dejando solo [a-z0-9] separados
     * por espacios. Base de la tokenizacion para la puerta y el ranking.
     */
    public static function normalizar($texto)
    {
        $texto = mb_strtolower(trim((string) $texto));

        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c'
        ]);

        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Tokens utiles (>=2 caracteres, sin stopwords).
     */
    private static function tokens($texto)
    {
        $normalizado = self::normalizar($texto);

        if ($normalizado === '') {
            return [];
        }

        $stop = self::stopwords();
        $tokens = [];

        foreach (explode(' ', $normalizado) as $token) {
            if ($token === '' || mb_strlen($token) < 2 || isset($stop[$token])) {
                continue;
            }
            $tokens[] = $token;
        }

        return $tokens;
    }

    private static function limpiarParaVectorizar($texto)
    {
        $tokens = self::tokens($texto);

        return empty($tokens) ? '' : implode(' ', $tokens);
    }

    /**
     * Lee BaseDatos/Temas/dataset_temas.csv (texto,tipo,tema) con fgetcsv
     * porque algunos textos traen comas entrecomilladas.
     */
    public static function leerDataset()
    {
        $ruta = self::artefactoRutaCsv();

        if (!is_file($ruta)) {
            return ['exito' => false, 'mensaje' => 'No existe el dataset de temas.'];
        }

        $manejador = fopen($ruta, 'r');

        if ($manejador === false) {
            return ['exito' => false, 'mensaje' => 'No se pudo abrir el dataset de temas.'];
        }

        fgetcsv($manejador, null, ',', '"', '');

        $textos = [];
        $tipos = [];
        $temas = [];

        while (($fila = fgetcsv($manejador, null, ',', '"', '')) !== false) {
            if (count($fila) < 3) {
                continue;
            }

            $texto = trim((string) $fila[0]);
            $tipo = strtolower(trim((string) $fila[1]));
            $tema = trim((string) $fila[2]);

            if ($texto === '' || $tipo === '' || $tema === '') {
                continue;
            }

            $textos[] = $texto;
            $tipos[] = $tipo;
            $temas[] = $tema;
        }

        fclose($manejador);

        if (empty($textos)) {
            return ['exito' => false, 'mensaje' => 'El dataset de temas está vacío.'];
        }

        return ['exito' => true, 'textos' => $textos, 'tipos' => $tipos, 'temas' => $temas];
    }

    /**
     * Terminos distintivos: aparecen bajo un solo tema. Se suman el nombre
     * del tema (sin palabras de categoria) y los alias minimos.
     */
    private static function construirLexico(array $lectura)
    {
        $porTermino = [];

        foreach ($lectura['textos'] as $indice => $texto) {
            $tema = (string) $lectura['temas'][$indice];

            foreach (array_unique(self::tokens($texto)) as $token) {
                $porTermino[$token][$tema] = ($porTermino[$token][$tema] ?? 0) + 1;
            }
        }

        $lexico = [];

        // Distintivo y con evidencia: aparece en un solo tema y en al menos
        // dos ejemplos de ese tema (descarta coincidencias aisladas).
        foreach ($porTermino as $token => $temasDelToken) {
            if (count($temasDelToken) === 1) {
                $tema = array_key_first($temasDelToken);

                if ($temasDelToken[$tema] >= 2) {
                    $lexico[$token] = $tema;
                }
            }
        }

        // El nombre del tema abre la puerta; se excluyen las palabras de
        // categoria para no clasificar por el mero uso de "comida"/"deporte".
        $excluir = ['genero' => true, 'comida' => true, 'deporte' => true,
            'musica' => true, 'musical' => true, 'comunidad' => true];

        foreach (array_unique($lectura['temas']) as $tema) {
            foreach (self::tokens($tema) as $token) {
                if (isset($excluir[$token]) || !isset($porTermino[$token])) {
                    continue;
                }
                $lexico[$token] = $tema;
            }
        }

        foreach (self::alias() as $token => $tema) {
            $lexico[$token] = $tema;
        }

        return $lexico;
    }

    public static function construirTransformadores()
    {
        $lectura = self::leerDataset();

        if (!$lectura['exito']) {
            return $lectura;
        }

        $limpiados = array_map(fn($t) => self::limpiarParaVectorizar($t), $lectura['textos']);

        $dataset = new Rubix\ML\Datasets\Labeled(
            array_map(fn($t) => [$t], $limpiados),
            $lectura['tipos']
        );

        // El orden del pipeline importa: TF-IDF solo acepta la matriz numerica
        // que produce el vectorizador, nunca el texto original.
        $vectorizador = new Rubix\ML\Transformers\WordCountVectorizer();
        $vectorizador->fit($dataset);

        $matriz = $dataset->samples();
        $vectorizador->transform($matriz);

        $tfidf = new Rubix\ML\Transformers\TfIdfTransformer(1.0, false);
        $tfidf->fit(new Rubix\ML\Datasets\Unlabeled($matriz));
        $tfidf->transform($matriz);

        $vocabulario = $vectorizador->vocabularies()[0] ?? [];
        $dimension = count($matriz[0] ?? []);

        if ($dimension < 1 || count($vocabulario) < 1) {
            return ['exito' => false, 'mensaje' => 'El vectorizador produjo un vocabulario vacío.'];
        }

        return [
            'exito' => true,
            'data' => [
                'vectorizador' => $vectorizador,
                'tfidf' => $tfidf,
                'vocabulario' => $vocabulario,
                'dimension' => $dimension,
                'cantidad' => count($lectura['textos']),
                'tipos' => array_values(array_unique($lectura['tipos'])),
                'temas' => array_values(array_unique($lectura['temas'])),
                'lexico' => self::construirLexico($lectura)
            ]
        ];
    }

    public static function construirArtefacto()
    {
        $ajuste = self::construirTransformadores();

        if (!$ajuste['exito']) {
            return $ajuste;
        }

        $datos = $ajuste['data'];

        return [
            'exito' => true,
            'data' => [
                'version' => self::VERSION_ARTIFACTO,
                'rubixVersion' => self::versionRubix(),
                'cantidadEjemplos' => $datos['cantidad'],
                'tipos' => $datos['tipos'],
                'temas' => $datos['temas'],
                'tamanoVocabulario' => count($datos['vocabulario']),
                'dimension' => $datos['dimension'],
                'datasetSha256' => self::sha256Dataset(),
                'lexico' => $datos['lexico'],
                'vectorizador' => serialize($datos['vectorizador']),
                'tfidf' => serialize($datos['tfidf'])
            ]
        ];
    }

    public static function guardarArtefacto(array $artefacto)
    {
        $ruta = self::RUTA_ARTIFACTO;
        $temporal = $ruta . '.tmp';

        if (file_put_contents($temporal, serialize($artefacto)) === false) {
            return ['exito' => false, 'mensaje' => 'No se pudo escribir el artefacto temporal.'];
        }

        // Escritura atomica: rename evita dejar un .bin corrupto a medias.
        if (!rename($temporal, $ruta)) {
            @unlink($temporal);
            return ['exito' => false, 'mensaje' => 'No se pudo promover el artefacto temporal.'];
        }

        @chmod($ruta, 0644);

        self::$artefacto = null;
        self::$artefactoCargado = false;

        return ['exito' => true, 'data' => ['ruta' => $ruta, 'bytes' => filesize($ruta)]];
    }

    private static function cargarArtefacto()
    {
        if (self::$artefactoCargado) {
            return self::$artefacto === null
                ? ['exito' => false, 'mensaje' => 'Artefacto de temas no disponible.']
                : ['exito' => true, 'data' => self::$artefacto];
        }

        self::$artefactoCargado = true;

        if (!self::existeArtefacto()) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'Artefacto de temas no encontrado.'];
        }

        $crudo = @file_get_contents(self::RUTA_ARTIFACTO);

        if ($crudo === false) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'No se pudo leer el artefacto de temas.'];
        }

        $artefacto = @unserialize($crudo, ['allowed_classes' => true]);

        if (!is_array($artefacto)
            || ($artefacto['version'] ?? null) !== self::VERSION_ARTIFACTO
            || !isset($artefacto['vectorizador'], $artefacto['tfidf'], $artefacto['dimension'], $artefacto['lexico'])
            || (int) $artefacto['dimension'] < 1
            || !is_array($artefacto['lexico'])) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'Artefacto de temas corrupto o de otra versión.'];
        }

        $vectorizador = @unserialize($artefacto['vectorizador']);
        $tfidf = @unserialize($artefacto['tfidf']);

        if (!$vectorizador instanceof Rubix\ML\Transformers\WordCountVectorizer
            || !$tfidf instanceof Rubix\ML\Transformers\TfIdfTransformer) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'Los transformadores del artefacto no son válidos.'];
        }

        self::$artefacto = [
            'dimension' => (int) $artefacto['dimension'],
            'tamanoVocabulario' => (int) ($artefacto['tamanoVocabulario'] ?? 0),
            'cantidadEjemplos' => (int) ($artefacto['cantidadEjemplos'] ?? 0),
            'tipos' => $artefacto['tipos'] ?? [],
            'temas' => $artefacto['temas'] ?? [],
            'lexico' => $artefacto['lexico'],
            'datasetSha256' => $artefacto['datasetSha256'] ?? null,
            'vectorizador' => $vectorizador,
            'tfidf' => $tfidf
        ];

        return ['exito' => true, 'data' => self::$artefacto];
    }

    private function vectorizarCon($artefacto, $texto)
    {
        $muestras = [[self::limpiarParaVectorizar($texto)]];

        $artefacto['vectorizador']->transform($muestras);
        $artefacto['tfidf']->transform($muestras);

        $vector = $muestras[0];

        if (count($vector) !== $artefacto['dimension']) {
            return null;
        }

        return array_values(array_map('floatval', $vector));
    }

    public static function idPunto($texto)
    {
        return (int) sprintf('%u', crc32('temas|' . mb_strtolower(trim((string) $texto))));
    }

    public function vectorizarTexto($texto)
    {
        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            return null;
        }

        return $this->vectorizarCon($carga['data'], $texto);
    }

    public function vectorizarLote(array $textos)
    {
        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            return null;
        }

        $artefacto = $carga['data'];
        $muestras = [];

        foreach ($textos as $texto) {
            $muestras[] = [self::limpiarParaVectorizar($texto)];
        }

        if (empty($muestras)) {
            return [];
        }

        $artefacto['vectorizador']->transform($muestras);
        $artefacto['tfidf']->transform($muestras);

        $vectores = [];

        foreach ($muestras as $vector) {
            if (count($vector) !== $artefacto['dimension']) {
                return null;
            }
            $vectores[] = array_values(array_map('floatval', $vector));
        }

        return $vectores;
    }

    public function estaDisponible()
    {
        return self::cargarArtefacto()['exito'];
    }

    public function dimensionEsperada()
    {
        $carga = self::cargarArtefacto();

        return $carga['exito'] ? $carga['data']['dimension'] : null;
    }

    /**
     * Devuelve los temas candidatos que abren la puerta para el texto.
     */
    public function temasCandidatos($texto)
    {
        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            return [];
        }

        $lexico = $carga['data']['lexico'];
        $candidatos = [];

        foreach (self::tokens($texto) as $token) {
            if (mb_strlen($token) < 3) {
                continue;
            }
            if (isset($lexico[$token])) {
                $candidatos[$lexico[$token]] = true;
            }
        }

        return array_keys($candidatos);
    }

    /**
     * Clasifica el texto (puerta + ranking). Devuelve:
     *  - clasificado=true con tipo/tema cuando supera TEMAS_UMBRAL
     *  - clasificado=false cuando no abre la puerta o no supera el umbral
     *  - exito=false cuando el modelo/Qdrant no responden (fail-open arriba)
     *
     * Con $ignorarUmbral=true se salta el umbral (solo para calibracion).
     */
    public function clasificarTema($texto, $ignorarUmbral = false)
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return ['exito' => false, 'mensaje' => 'Texto vacío.', 'clasificado' => false];
        }

        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            return ['exito' => false, 'mensaje' => $carga['mensaje'], 'clasificado' => false];
        }

        $artefacto = $carga['data'];
        $candidatos = $this->temasCandidatos($texto);

        if (empty($candidatos)) {
            return [
                'exito' => true,
                'clasificado' => false,
                'tipo' => null,
                'tema' => null,
                'score' => 0.0,
                'candidatos' => [],
                'motivo' => 'sin_termino_distintivo'
            ];
        }

        $vector = $this->vectorizarCon($artefacto, $texto);

        if ($vector === null) {
            return ['exito' => false, 'mensaje' => 'No se pudo vectorizar el texto.', 'clasificado' => false];
        }

        $qdrant = $this->cliente();

        if (!$qdrant->crearColeccionSiNoExiste($artefacto['dimension'])) {
            return ['exito' => false, 'mensaje' => 'No se pudo preparar la colección de temas.', 'clasificado' => false];
        }

        if (!self::$indiceAsegurado) {
            // Qdrant exige un indice keyword para filtrar por 'tema'.
            $qdrant->crearIndice('tema', 'keyword');
            self::$indiceAsegurado = true;
        }

        $filtro = ['must' => [['key' => 'tema', 'match' => ['any' => array_values($candidatos)]]]];
        $similares = $qdrant->buscarSimilares($vector, 1, null, $filtro);

        if ($similares === null) {
            return ['exito' => false, 'mensaje' => 'Qdrant no respondió la consulta de temas.', 'clasificado' => false];
        }

        if (empty($similares)) {
            return [
                'exito' => true,
                'clasificado' => false,
                'tipo' => null,
                'tema' => null,
                'score' => 0.0,
                'candidatos' => $candidatos,
                'motivo' => 'sin_coincidencia'
            ];
        }

        $mejor = $similares[0];
        $score = (float) ($mejor['score'] ?? 0.0);
        $tema = $mejor['tema'] ?? ($mejor['payload']['tema'] ?? null);
        $tipo = $mejor['tipo'] ?? ($mejor['payload']['tipo'] ?? null);

        // Con un solo candidato la puerta ya decidio: no se exige umbral. El
        // umbral solo desempata cuando hay varios temas candidatos.
        $esMultiple = count($candidatos) > 1;

        if ($tema === null || $score <= 0.0 || ($esMultiple && !$ignorarUmbral && $score < TEMAS_UMBRAL)) {
            return [
                'exito' => true,
                'clasificado' => false,
                'tipo' => null,
                'tema' => null,
                'score' => $score,
                'candidatos' => $candidatos,
                'motivo' => 'bajo_umbral'
            ];
        }

        return [
            'exito' => true,
            'clasificado' => true,
            'tipo' => strtolower((string) $tipo),
            'tema' => (string) $tema,
            'score' => $score,
            'candidatos' => $candidatos,
            'idPunto' => self::idPunto($texto)
        ];
    }

    public function diagnostics()
    {
        $salida = [
            'artefacto' => [
                'ruta' => self::RUTA_ARTIFACTO,
                'existe' => self::existeArtefacto(),
                'sha256DatasetActual' => self::sha256Dataset()
            ],
            'coleccion' => self::COLECCION,
            'umbral' => TEMAS_UMBRAL,
            'minimoMensajes' => TEMAS_MINIMO_MENSAJES
        ];

        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            $salida['artefacto']['cargado'] = false;
            $salida['artefacto']['error'] = $carga['mensaje'];
            return $salida;
        }

        $artefacto = $carga['data'];

        $salida['artefacto']['cargado'] = true;
        $salida['artefacto']['version'] = self::VERSION_ARTIFACTO;
        $salida['artefacto']['cantidadEjemplos'] = $artefacto['cantidadEjemplos'];
        $salida['artefacto']['dimension'] = $artefacto['dimension'];
        $salida['artefacto']['tamanoVocabulario'] = $artefacto['tamanoVocabulario'];
        $salida['artefacto']['tipos'] = $artefacto['tipos'];
        $salida['artefacto']['cantidadTemas'] = count($artefacto['temas']);
        $salida['artefacto']['terminosDistintivos'] = count($artefacto['lexico']);


        return $salida;
    }
}
