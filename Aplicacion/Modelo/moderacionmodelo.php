<?php
require_once __DIR__ . '/../../Configuracion/configuracion.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';

class ModeracionModelo
{
    private const COLECCION = 'moderacion';

    private const VERSION_ARTIFACTO = 1;

    private const RUTA_ARTIFACTO = BASE_PATH . '/BaseDatos/Moderacion/moderacion_rubix.bin';

    private static $artefacto = null;

    private static $artefactoCargado = false;

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
        return BASE_PATH . '/BaseDatos/Moderacion/dataset_moderacion.csv';
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

    public static function construirTransformadores()
    {
        $ruta = self::artefactoRutaCsv();

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

        $dataset = new Rubix\ML\Datasets\Labeled(
            array_map(fn($t) => [$t], $textos),
            $categorias
        );

        // El orden del pipeline importa: TF-IDF solo acepta la matriz numérica
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
                'cantidad' => count($textos),
                'categorias' => array_values(array_unique($categorias)),
                'textoPorIndice' => $textos,
                'categoriaPorIndice' => $categorias,
                'matriz' => $matriz
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
                'categorias' => $datos['categorias'],
                'tamanoVocabulario' => count($datos['vocabulario']),
                'dimension' => $datos['dimension'],
                'datasetSha256' => self::sha256Dataset(),
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

        // Escritura atómica: rename evita dejar un .bin corrupto a medias.
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
                ? ['exito' => false, 'mensaje' => 'Artefacto de moderación no disponible.']
                : ['exito' => true, 'data' => self::$artefacto];
        }

        self::$artefactoCargado = true;

        if (!self::existeArtefacto()) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'Artefacto de moderación no encontrado.'];
        }

        $crudo = @file_get_contents(self::RUTA_ARTIFACTO);

        if ($crudo === false) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'No se pudo leer el artefacto de moderación.'];
        }

        $artefacto = @unserialize($crudo, ['allowed_classes' => true]);

        if (!is_array($artefacto)
            || ($artefacto['version'] ?? null) !== self::VERSION_ARTIFACTO
            || !isset($artefacto['vectorizador'], $artefacto['tfidf'], $artefacto['dimension'])
            || (int) $artefacto['dimension'] < 1) {
            self::$artefacto = null;
            return ['exito' => false, 'mensaje' => 'Artefacto de moderación corrupto o de otra versión.'];
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
            'categorias' => $artefacto['categorias'] ?? [],
            'datasetSha256' => $artefacto['datasetSha256'] ?? null,
            'vectorizador' => $vectorizador,
            'tfidf' => $tfidf
        ];

        return ['exito' => true, 'data' => self::$artefacto];
    }

    private function vectorizarCon($artefacto, $texto)
    {
        $muestras = [[(string) $texto]];

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
        return (int) sprintf('%u', crc32('moderacion|' . mb_strtolower(trim((string) $texto))));
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
            $muestras[] = [(string) $texto];
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

    public function evaluar($texto)
    {
        $texto = trim((string) $texto);

        $base = [
            'exito' => false,
            'bloqueado' => true,
            'categoria' => null,
            'score' => 0.0,
            'textoOriginal' => $texto,
            // 'mensaje' es el diagnostico interno del fallo; lo que ve el
            // cliente es siempre textoEnmascarado cuando va bloqueado.
            'mensaje' => null,
            'textoEnmascarado' => MODERACION_TEXTO_BLOQUEADO,
            'motivo' => 'moderacion_no_disponible'
        ];

        if ($texto === '') {
            return array_merge($base, ['exito' => true, 'bloqueado' => false, 'motivo' => 'texto_vacio']);
        }

        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            // fail-closed: sin modelo no se entrega el mensaje
            return array_merge($base, ['mensaje' => $carga['mensaje']]);
        }

        $artefacto = $carga['data'];
        $vector = $this->vectorizarCon($artefacto, $texto);

        if ($vector === null) {
            return array_merge($base, ['mensaje' => 'No se pudo vectorizar el mensaje con el modelo cargado.']);
        }

        $qdrant = $this->cliente();

        if (!$qdrant->crearColeccionSiNoExiste($artefacto['dimension'])) {
            return array_merge($base, ['mensaje' => 'No se pudo preparar la colección de moderación en Qdrant.']);
        }

        $similares = $qdrant->buscarSimilares($vector, 1);

        if ($similares === null) {
            // fail-closed: sin veredicto de Qdrant no se entrega el mensaje
            return array_merge($base, ['mensaje' => 'Qdrant no respondió la consulta de similitud.']);
        }

        if (empty($similares)) {
            return array_merge($base, ['exito' => true, 'bloqueado' => false, 'motivo' => 'sin_coincidencia']);
        }

        $mejor = $similares[0];
        $score = (float) ($mejor['score'] ?? 0.0);
        $categoria = $mejor['categoria'] ?? null;

        if ($categoria === null && isset($mejor['payload']) && is_array($mejor['payload'])) {
            $categoria = $mejor['payload']['categoria'] ?? null;
        }

        $bloqueado = $score >= MODERACION_UMBRAL;

        return [
            'exito' => true,
            'bloqueado' => $bloqueado,
            'categoria' => $categoria !== null ? (string) $categoria : null,
            'score' => $score,
            'textoOriginal' => $texto,
            'textoEnmascarado' => $bloqueado ? MODERACION_TEXTO_BLOQUEADO : null,
            'motivo' => $bloqueado ? 'similitud_alta' : 'similitud_baja',
            'idPunto' => self::idPunto($texto)
        ];
    }

    public function aprenderDeConfirmado($texto, $categoria)
    {
        $texto = trim((string) $texto);
        $categoria = strtolower(trim((string) $categoria));

        if ($texto === '' || $categoria === '') {
            return ['exito' => false, 'mensaje' => 'Texto o categoría vacíos para el aprendizaje.'];
        }

        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            return ['exito' => false, 'mensaje' => $carga['mensaje']];
        }

        $artefacto = $carga['data'];
        $vector = $this->vectorizarCon($artefacto, $texto);

        if ($vector === null) {
            return ['exito' => false, 'mensaje' => 'No se pudo vectorizar el texto confirmado.'];
        }

        $qdrant = $this->cliente();

        if (!$qdrant->crearColeccionSiNoExiste($artefacto['dimension'])) {
            return ['exito' => false, 'mensaje' => 'No se pudo preparar la colección de moderación.'];
        }

        $guardado = $qdrant->guardarPunto(self::idPunto($texto), $vector, [
            'categoria' => $categoria,
            'textoOriginal' => $texto,
            'origen' => 'aprendizaje'
        ]);

        if (!$guardado) {
            return ['exito' => false, 'mensaje' => 'Qdrant rechazó el vector aprendido.'];
        }

        return ['exito' => true, 'data' => ['id' => self::idPunto($texto), 'categoria' => $categoria]];
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
            'umbral' => MODERACION_UMBRAL,
            'umbralAprendizaje' => MODERACION_UMBRAL_APRENDIZAJE,
            'textoBloqueado' => MODERACION_TEXTO_BLOQUEADO
        ];

        $carga = self::cargarArtefacto();

        if (!$carga['exito']) {
            $salida['artefacto']['error'] = $carga['mensaje'];
            return $salida;
        }

        $artefacto = $carga['data'];
        $salida['artefacto']['cargado'] = true;
        $salida['artefacto']['cantidadEjemplos'] = $artefacto['cantidadEjemplos'];
        $salida['artefacto']['tamanoVocabulario'] = $artefacto['tamanoVocabulario'];
        $salida['artefacto']['dimension'] = $artefacto['dimension'];
        $salida['artefacto']['categorias'] = $artefacto['categorias'];
        $salida['artefacto']['sha256Guardado'] = $artefacto['datasetSha256'];
        $salida['artefacto']['coincideDataset'] = $artefacto['datasetSha256'] === self::sha256Dataset();

        return $salida;
    }
}