<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/comidamodelo.php';
require_once __DIR__ . '/temasmodelo.php';
require_once __DIR__ . '/../Utilidades/validacion.php';

class ComidaLinkModelo
{
    private $conexion;
    private $comidaModelo;
    private static $esquemaListo = false;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->comidaModelo = new ComidaModelo();
        $this->asegurarEsquema();
    }

    private function asegurarEsquema()
    {
        if (self::$esquemaListo) {
            return;
        }
        self::$esquemaListo = true;

        try {
            $this->conexion->exec("CREATE TABLE IF NOT EXISTS `tbmicrolinkcontrol` (
              `tbmicrolinkcontrolid` int NOT NULL AUTO_INCREMENT,
              `tbperfilid` int DEFAULT NULL,
              `tbmicrolinkcontrolfecha` date NOT NULL,
              `tbmicrolinkcontroltipo` varchar(20) NOT NULL,
              `tbmicrolinkcontrolfechahora` datetime NOT NULL,
              PRIMARY KEY (`tbmicrolinkcontrolid`),
              KEY `idx_microlink_fecha_tipo` (`tbmicrolinkcontrolfecha`, `tbmicrolinkcontroltipo`),
              KEY `idx_microlink_perfil_fecha` (`tbperfilid`, `tbmicrolinkcontrolfecha`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $tablas = ['tbmensaje', 'tbcomunidadmensaje', 'tbgrupomensaje', 'tbcancionchat'];
            foreach ($tablas as $tabla) {
                $colsChat = $this->conexion->query("SHOW COLUMNS FROM `{$tabla}` LIKE 'tbcomidaid'")->fetchAll();
                if (empty($colsChat)) {
                    $this->conexion->exec("ALTER TABLE `{$tabla}` ADD COLUMN `tbcomidaid` int DEFAULT NULL");
                }
            }

            $this->conexion->exec("INSERT INTO `tbtipocomida` (`tbtipocomidanombre`) SELECT 'Otros' WHERE NOT EXISTS (SELECT 1 FROM `tbtipocomida` WHERE LOWER(`tbtipocomidanombre`) IN ('otros', 'general'))");
        } catch (\Throwable $e) {
            error_log('ComidaLink: error al verificar esquema - ' . $e->getMessage());
        }
    }

    public function procesarMensaje($texto, $perfilId)
    {
        try {
            $url = $this->extraerUrl($texto);
            if (!$url || !$this->esDominioPermitido($url)) {
                return null;
            }

            $perfilId = (int) $perfilId;
            if ($perfilId > 0 && $this->alcanzoTopeComidasUsuario($perfilId)) {
                return null;
            }

            if ($this->alcanzoTopePeticionesGlobal()) {
                return null;
            }

            $this->registrarControl('peticion', $perfilId);

            $datos = $this->consultarMicrolink($url);
            if (!$datos) {
                $datos = $this->consultarDirecto($url);
            }
            if (!$datos) {
                return null;
            }

            $tituloOriginal = (string) ($datos['title'] ?? '');
            $titulo = $this->limpiarTitulo($tituloOriginal, $url);
            if ($titulo === '') {
                return null;
            }

            $errorNombre = campoRequerido($titulo, 'nombre') ?? campoLongitud($titulo, 'nombre', 200);
            if ($errorNombre) {
                return null;
            }

            $imagenUrl = '';
            if (!empty($datos['image']['url']) && urlValida($datos['image']['url']) === null) {
                $imagenUrl = $datos['image']['url'];
            } elseif (!empty($datos['image']) && is_string($datos['image']) && urlValida($datos['image']) === null) {
                $imagenUrl = $datos['image'];
            }

            if ($imagenUrl === '') {
                return null;
            }

            $porNombre = $this->comidaModelo->buscarPorNombre($titulo);
            if ($porNombre) {
                return $porNombre;
            }

            $desc = $this->limpiarDescripcion(is_string($datos['description'] ?? null) ? $datos['description'] : '');
            $slug = $this->extraerSlugUrl($url);

            $tipo = $this->deducirTipoComida($tituloOriginal, $desc, $slug);
            if (!$tipo || empty($tipo['id'])) {
                return null;
            }

            $nuevoId = $this->comidaModelo->insert($tipo['id'], $titulo, $imagenUrl);
            if (!$nuevoId) {
                return null;
            }

            if ($perfilId > 0) {
                $this->registrarControl('comida', $perfilId);
            }

            return [
                'id' => (int) $nuevoId,
                'nombre' => $titulo,
                'imagenUrl' => $imagenUrl,
                'tipoNombre' => $tipo['nombre']
            ];
        } catch (\Throwable $e) {
            error_log('ComidaLink: fallo al procesar - ' . $e->getMessage());
            return null;
        }
    }

    private function extraerUrl($texto)
    {
        if (!preg_match('#https?://[^\s<>"\'`]+#i', (string) $texto, $m)) {
            return null;
        }
        $url = rtrim($m[0], '.,;:!?)]}');
        return $this->normalizarUrl($url);
    }

    private function normalizarUrl($url)
    {
        $partes = parse_url($url);
        if (!isset($partes['host'])) {
            return $url;
        }

        $params = [];
        if (!empty($partes['query'])) {
            parse_str($partes['query'], $params);
        }

        $ignorar = ['from_challenge', 'from', 'fbclid', 'gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'ref', 'referral'];
        foreach ($ignorar as $clave) {
            unset($params[$clave]);
        }

        $base = ($partes['scheme'] ?? 'https') . '://' . $partes['host'] . ($partes['path'] ?? '');
        if (!empty($params)) {
            $base .= '?' . http_build_query($params);
        }

        return $base;
    }

    private function esDominioPermitido($url)
    {
        $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($esquema, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $permitidos = defined('COMIDA_DOMINIOS_PERMITIDOS') ? COMIDA_DOMINIOS_PERMITIDOS : [];

        foreach ($permitidos as $dominio) {
            $dominio = strtolower($dominio);
            if ($host === $dominio || str_ends_with($host, '.' . $dominio)) {
                return true;
            }
        }

        return false;
    }

    private function alcanzoTopePeticionesGlobal()
    {
        $max = defined('MICROLINK_TOPE_DIARIO_GLOBAL') ? MICROLINK_TOPE_DIARIO_GLOBAL : 0;
        if ($max <= 0) {
            return false;
        }

        $fecha = date('Y-m-d');
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbmicrolinkcontrol WHERE tbmicrolinkcontrolfecha = :fecha AND tbmicrolinkcontroltipo = 'peticion'");
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        return $total >= $max;
    }

    private function alcanzoTopeComidasUsuario($perfilId)
    {
        $max = defined('COMIDA_LINK_TOPE_DIARIO_USUARIO') ? COMIDA_LINK_TOPE_DIARIO_USUARIO : 0;
        if ($max <= 0) {
            return false;
        }

        $fecha = date('Y-m-d');
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbmicrolinkcontrol WHERE tbperfilid = :perfil AND tbmicrolinkcontrolfecha = :fecha AND tbmicrolinkcontroltipo = 'comida'");
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        return $total >= $max;
    }

    private function registrarControl($tipo, $perfilId = null)
    {
        $fecha = date('Y-m-d');
        $fechahora = date('Y-m-d H:i:s');
        $stmt = $this->conexion->prepare("INSERT INTO tbmicrolinkcontrol (tbperfilid, tbmicrolinkcontrolfecha, tbmicrolinkcontroltipo, tbmicrolinkcontrolfechahora) VALUES (:perfil, :fecha, :tipo, :fechahora)");
        $stmt->bindValue(':perfil', $perfilId ? (int) $perfilId : null, $perfilId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':fechahora', $fechahora);
        $stmt->execute();
    }

    private function consultarMicrolink($url)
    {
        $endpoint = 'https://api.microlink.io?url=' . urlencode($url);

        $ch = curl_init($endpoint);
        $headers = ['Accept: application/json'];

        if (defined('MICROLINK_API_KEY') && MICROLINK_API_KEY !== '' && MICROLINK_API_KEY !== 'CAMBIAR_EN_ENV') {
            $headers[] = 'x-api-key: ' . MICROLINK_API_KEY;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($respuesta === false || $error !== '' || $codigo !== 200) {
            error_log("ComidaLink: fallo peticion microlink (http={$codigo})");
            return null;
        }

        $json = json_decode($respuesta, true);
        if (!is_array($json) || ($json['status'] ?? '') !== 'success' || empty($json['data'])) {
            error_log('ComidaLink: respuesta no valida de microlink');
            return null;
        }

        if (isset($json['statusCode']) && (int) $json['statusCode'] >= 400) {
            error_log("ComidaLink: microlink devolvio statusCode={$json['statusCode']}");
            return null;
        }

        return $json['data'];
    }

    private function consultarDirecto($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]);

        $html = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($html === false || $codigo !== 200) {
            return null;
        }

        $titulo = '';
        if (preg_match('/<meta[^>]+property=[\'"]og:title[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $titulo = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
            $titulo = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $imagenUrl = '';
        if (preg_match('/<meta[^>]+property=[\'"]og:image[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $imagenUrl = trim($m[1]);
        }

        $desc = '';
        if (preg_match('/<meta[^>]+property=[\'"]og:description[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $desc = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (preg_match('/<meta[^>]+name=[\'"]description[\'"][^>]+content=[\'"]([^\'"]+)[\'"]/i', $html, $m)) {
            $desc = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($titulo === '') {
            return null;
        }

        return [
            'title' => $titulo,
            'image' => ['url' => $imagenUrl],
            'description' => $desc
        ];
    }

    private function limpiarTitulo($titulo, $url = '')
    {
        $titulo = trim((string) $titulo);
        if ($titulo === '' || preg_match('/^[a-zA-Z0-9_-]{12,}$/', $titulo)) {
            $slug = $this->extraerSlugUrl($url);
            if ($slug !== '') {
                return mb_substr(ucwords($slug), 0, 200, 'UTF-8');
            }
        }

        $titulo = preg_replace('/\s*[\|\-–—•·]\s*(Receta\s+de.*|Cookpad|Kiwilim[oó]n|Recetas?\s+de\s+cocina|Recetas?).*$/iu', '', $titulo);
        $titulo = preg_replace('/^(Receta\s+de\s+|Cómo\s+hacer\s+|Como\s+hacer\s+)/iu', '', $titulo);
        $titulo = preg_replace('/\s+Receta\s+de\s+.*$/iu', '', $titulo);
        $titulo = preg_replace('/\b(Cookpad|Kiwilim[oó]n)\b/iu', '', $titulo);
        $titulo = preg_replace('/\s+/', ' ', $titulo);
        $titulo = trim($titulo);

        return mb_substr($titulo, 0, 200, 'UTF-8');
    }

    private function limpiarDescripcion($desc)
    {
        $desc = trim((string) $desc);
        if ($desc === '') {
            return '';
        }

        $desc = preg_replace('/Guarda\s+esta\s+receta\s+para\s+encontrarla\s+m[aá]s\s+f[aá]cilmente[^\.]*\.?/iu', '', $desc);
        $desc = preg_replace('/\b(Cookpad|Kiwilim[oó]n)\b/iu', '', $desc);
        $desc = preg_replace('/\d+\s+(comentarios?|recetas?|pasos?|personas?)\b[^\.]*\.?/iu', '', $desc);
        $desc = preg_replace('/\s+/', ' ', $desc);

        return trim($desc);
    }

    private function extraerSlugUrl($url)
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segmentos = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
        if (empty($segmentos)) {
            return '';
        }

        $ultimo = urldecode(end($segmentos));
        $ultimo = preg_replace('/\d+/', ' ', $ultimo);
        $ultimo = str_replace(['-', '_', '/'], ' ', $ultimo);
        $ultimo = preg_replace('/\s+/', ' ', $ultimo);

        return trim($ultimo);
    }

    private function tokenizar($texto)
    {
        $norm = TemasModelo::normalizar($texto);
        if ($norm === '') {
            return [];
        }

        $archivo = BASE_PATH . '/Aplicacion/Utilidades/stopwordsespanol.php';
        $stop = is_file($archivo) ? array_flip((array) require $archivo) : [];

        $tokens = [];
        foreach (explode(' ', $norm) as $t) {
            if ($t === '' || mb_strlen($t, 'UTF-8') < 2 || isset($stop[$t])) {
                continue;
            }
            $tokens[] = $t;
        }

        return array_unique($tokens);
    }

    private function deducirTipoComida($tituloOriginal, $descripcion, $ruta)
    {
        $tipos = $this->comidaModelo->getTiposActivos();
        if (empty($tipos)) {
            error_log('ComidaLink: no hay tipos de comida activos');
            return null;
        }

        $catalogo = $this->comidaModelo->getNombresPorTipo();
        $comidasPorTipo = [];
        foreach ($catalogo as $item) {
            $idTipo = (int) $item['tbtipocomidaid'];
            $comidasPorTipo[$idTipo][] = $item['tbcomidanombre'];
        }

        $tokensTitulo = $this->tokenizar($tituloOriginal);
        $tokensRuta = $this->tokenizar($ruta);
        $tokensDesc = $this->tokenizar($descripcion);

        $setTitulo = array_flip($tokensTitulo);
        $setRuta = array_flip($tokensRuta);
        $setDesc = array_flip($tokensDesc);

        $puntajes = [];
        $generico = null;

        foreach ($tipos as $tipo) {
            $id = (int) $tipo['tbtipocomidaid'];
            $nombre = $tipo['tbtipocomidanombre'];
            $normNombre = TemasModelo::normalizar($nombre);

            if (in_array($normNombre, ['otros', 'general', 'varios', 'variado', 'variada'], true)) {
                $generico = ['id' => $id, 'nombre' => $nombre];
            }

            $score = 0;
            $tokensTipo = $this->tokenizar($nombre);
            foreach ($tokensTipo as $tok) {
                if (isset($setTitulo[$tok])) {
                    $score += 5;
                }
                if (isset($setRuta[$tok])) {
                    $score += 5;
                }
                if (isset($setDesc[$tok])) {
                    $score += 2;
                }
            }

            if (!empty($comidasPorTipo[$id])) {
                $tokensComidas = $this->tokenizar(implode(' ', $comidasPorTipo[$id]));
                foreach ($tokensComidas as $tok) {
                    if (isset($setTitulo[$tok])) {
                        $score += 3;
                    }
                    if (isset($setRuta[$tok])) {
                        $score += 3;
                    }
                    if (isset($setDesc[$tok])) {
                        $score += 1;
                    }
                }
            }

            $puntajes[] = [
                'id' => $id,
                'nombre' => $nombre,
                'score' => $score
            ];
        }

        usort($puntajes, fn($a, $b) => $b['score'] <=> $a['score']);

        $top = $puntajes[0];
        $segundo = $puntajes[1] ?? ['score' => 0];

        if ($top['score'] >= 2 && $top['score'] > $segundo['score']) {
            return ['id' => $top['id'], 'nombre' => $top['nombre']];
        }

        if ($generico) {
            return $generico;
        }

        error_log('ComidaLink: empate o sin coincidencia y no existe tipo generico');
        return null;
    }
}
