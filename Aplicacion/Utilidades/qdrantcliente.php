<?php

class QdrantCliente
{
    private $host;
    private $port;

    private $coleccion;

    private $tiempoConexion = 4;

    private $tiempoPeticion = 8;

    private static $handleCurl = null;

    public function __construct($host = null, $port = null, $coleccion = 'perfiles')
    {
        $this->host = $host ?: QDRANT_HOST;
        $this->port = $port ?: QDRANT_PORT;
        $this->coleccion = $coleccion;
    }

    private function obtenerHandle()
    {
        if (self::$handleCurl === null) {
            $handle = curl_init();
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->tiempoConexion,
                CURLOPT_TIMEOUT => $this->tiempoPeticion,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'api-key: ' . QDRANT_API_KEY
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_TCP_KEEPALIVE => 1
            ]);
            self::$handleCurl = $handle;
        }
        return self::$handleCurl;
    }

    public function cerrarConexion()
    {
        if (self::$handleCurl !== null) {
            curl_close(self::$handleCurl);
            self::$handleCurl = null;
        }
    }

    private function ejecutar($metodo, $ruta, $cuerpo = null)
    {
        $ch = $this->obtenerHandle();

        curl_setopt($ch, CURLOPT_URL, "https://{$this->host}:{$this->port}{$ruta}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo !== null ? json_encode($cuerpo) : null);

        $respuesta = curl_exec($ch);
        $errorCurl = curl_errno($ch);
        $codigoHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($respuesta === false || $errorCurl) {
            error_log("Qdrant: fallo cURL (errno={$errorCurl}, " . curl_strerror($errorCurl ?: 0) . ")");
            return ['codigo' => $codigoHttp, 'datos' => null, 'error' => true];
        }

        $datos = json_decode($respuesta, true);
        if (!is_array($datos)) {
            error_log("Qdrant: respuesta JSON invalida (http={$codigoHttp})");
            return ['codigo' => $codigoHttp, 'datos' => null, 'error' => true];
        }

        return ['codigo' => $codigoHttp, 'datos' => $datos, 'error' => false];
    }

    public function crearColeccionSiNoExiste($tamanioVector)
    {
        $existe = $this->ejecutar('GET', '/collections/' . $this->coleccion);

        if ($existe['error']) {
            return false;
        }

        $noExiste = (int) $existe['codigo'] === 404;

        if (!$noExiste && !empty($existe['datos']['result'])) {
            $tamanoActual = $existe['datos']['result']['vectors']['size']
                ?? $existe['datos']['result']['config']['params']['vectors']['size']
                ?? null;

            if ($tamanoActual !== null && (int) $tamanoActual === (int) $tamanioVector) {
                return true;
            }

            $borrado = $this->ejecutar('DELETE', '/collections/' . $this->coleccion);

            if ($borrado['error'] || (int) $borrado['codigo'] >= 400
                || (($borrado['datos']['result'] ?? false) !== true
                    && ($borrado['datos']['status'] ?? '') !== 'ok')) {
                return false;
            }
        }

        $cuerpo = [
            'vectors' => [
                'size' => (int) $tamanioVector,
                'distance' => 'Cosine'
            ]
        ];

        $crear = $this->ejecutar('PUT', '/collections/' . $this->coleccion, $cuerpo);

        if ($crear['error']) {
            return false;
        }

        if ((int) $crear['codigo'] >= 400 && (int) $crear['codigo'] !== 409) {
            error_log("Qdrant: rechazó la creación de '{$this->coleccion}' (http={$crear['codigo']})");
            return false;
        }

        if (($crear['datos']['status'] ?? '') === 'error') {
            $verificar = $this->ejecutar('GET', '/collections/' . $this->coleccion);

            return !$verificar['error']
                && !empty($verificar['datos']['result'])
                && (int) $verificar['codigo'] < 400;
        }

        return true;
    }

    public function crearIndice($campo, $esquema = 'keyword')
    {
        $cuerpo = [
            'field_name' => (string) $campo,
            'field_schema' => $esquema
        ];

        $respuesta = $this->ejecutar('PUT', '/collections/' . $this->coleccion . '/index', $cuerpo);

        if ($respuesta['error'] || (int) $respuesta['codigo'] >= 400) {
            error_log("Qdrant: no se pudo crear índice '{$campo}' en '{$this->coleccion}' (http={$respuesta['codigo']})");
            return false;
        }

        return true;
    }

    public function guardarPunto($id, array $vector, array $payload = [])
    {
        $cuerpo = [
            'points' => [[
                'id' => (int) $id,
                'vector' => array_values(array_map('floatval', $vector)),
                'payload' => $payload
            ]]
        ];

        $respuesta = $this->ejecutar('PUT', '/collections/' . $this->coleccion . '/points', $cuerpo);

        $status = $respuesta['datos']['status'] ?? null;

        if ($respuesta['error'] || (int) $respuesta['codigo'] >= 400
            || $status === 'error' || (is_array($status) && !empty($status['error']))) {
            error_log("Qdrant: error al guardar punto en '{$this->coleccion}' (http={$respuesta['codigo']}, status=" . json_encode($status) . ")");
            return false;
        }

        return true;
    }

    public function guardarPuntos(array $puntos)
    {
        if (empty($puntos)) {
            return true;
        }

        $cuerpo = ['points' => []];

        foreach ($puntos as $punto) {
            if (!isset($punto['id'], $punto['vector'])) {
                continue;
            }
            $cuerpo['points'][] = [
                'id' => (int) $punto['id'],
                'vector' => array_values(array_map('floatval', $punto['vector'])),
                'payload' => $punto['payload'] ?? []
            ];
        }

        if (empty($cuerpo['points'])) {
            return true;
        }

        $respuesta = $this->ejecutar('PUT', '/collections/' . $this->coleccion . '/points', $cuerpo);

        $status = $respuesta['datos']['status'] ?? null;

        if ($respuesta['error'] || (int) $respuesta['codigo'] >= 400
            || $status === 'error' || (is_array($status) && !empty($status['error']))) {
            error_log("Qdrant: error al guardar lote en '{$this->coleccion}' (http={$respuesta['codigo']}, status=" . json_encode($status) . ")");
            return false;
        }

        return true;
    }

    public function guardarVector($tbperfilid, array $vector, $hashdatos, array $payloadExtra = [])
    {
        return $this->guardarPunto(
            $tbperfilid,
            $vector,
            array_merge(['hashdatos' => $hashdatos], $payloadExtra)
        );
    }

    public function obtenerPunto($tbperfilid)
    {
        $respuesta = $this->ejecutar('GET', '/collections/' . $this->coleccion . '/points/' . (int) $tbperfilid);

        if ($respuesta['error'] || (int) $respuesta['codigo'] >= 300) {
            return null;
        }

        $resultado = $respuesta['datos']['result'] ?? null;
        if (!is_array($resultado) || !isset($resultado['vector'])) {
            return null;
        }

        return $resultado;
    }

    public function buscarSimilares($vector, $limite = 5, $scoreThreshold = null, $filtro = null)
    {
        $cuerpo = [
            'vector' => array_values(array_map('floatval', $vector)),
            'limit' => (int) $limite,
            'with_payload' => true,
            'with_vectors' => false
        ];

        if ($scoreThreshold !== null) {
            $cuerpo['score_threshold'] = (float) $scoreThreshold;
        }

        if (is_array($filtro) && !empty($filtro)) {
            $cuerpo['filter'] = $filtro;
        }

        $respuesta = $this->ejecutar('POST', '/collections/' . $this->coleccion . '/points/search', $cuerpo);

        $status = $respuesta['datos']['status'] ?? null;

        if ($respuesta['error'] || (int) $respuesta['codigo'] >= 400
            || $status === 'error' || (is_array($status) && !empty($status['error']))) {
            error_log("Qdrant: error al buscar en '{$this->coleccion}' (http={$respuesta['codigo']}, status=" . json_encode($status) . ")");
            return null;
        }

        $resultados = $respuesta['datos']['result'] ?? [];

        if (!is_array($resultados)) {
            return [];
        }

        $perfiles = [];
        foreach ($resultados as $r) {
            if (!isset($r['id'])) {
                continue;
            }
            $fila = [
                'tbperfilid' => (int) $r['id'],
                'score' => (float) ($r['score'] ?? 0),
                'payload' => is_array($r['payload'] ?? null) ? $r['payload'] : []
            ];

            // Atajo para consumidores que solo esperan metadatos planos
            foreach ($fila['payload'] as $clave => $valor) {
                if (!array_key_exists($clave, $fila)) {
                    $fila[$clave] = $valor;
                }
            }

            $perfiles[] = $fila;
        }

        return $perfiles;
    }
}