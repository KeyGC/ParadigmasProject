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

        if (!empty($existe['datos']['result'])) {
            $tamanoActual = $existe['datos']['result']['vectors']['size']
                ?? $existe['datos']['result']['config']['params']['vectors']['size']
                ?? null;

            if ($tamanoActual !== null && (int) $tamanoActual === (int) $tamanioVector) {
                return true;
            }

            $borrado = $this->ejecutar('DELETE', '/collections/' . $this->coleccion);

            if ($borrado['error']
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

        if (($crear['datos']['status'] ?? '') === 'error') {
            $verificar = $this->ejecutar('GET', '/collections/' . $this->coleccion);

            return !$verificar['error'] && !empty($verificar['datos']['result']);
        }

        return true;
    }

    public function guardarVector($tbperfilid, array $vector, $hashdatos, array $payloadExtra = [])
    {
        $cuerpo = [
            'points' => [[
                'id' => (int) $tbperfilid,
                'vector' => array_values(array_map('floatval', $vector)),
                'payload' => array_merge(['hashdatos' => $hashdatos], $payloadExtra)
            ]]
        ];

        $respuesta = $this->ejecutar('PUT', '/collections/' . $this->coleccion . '/points', $cuerpo);

        $status = $respuesta['datos']['status'] ?? null;

        if ($respuesta['error'] || $status === 'error' || (is_array($status) && !empty($status['error']))) {
            error_log("Qdrant: error al guardar vector en '{$this->coleccion}' (status=" . json_encode($status) . ")");
            return false;
        }

        return true;
    }

    public function obtenerPunto($tbperfilid)
    {
        $respuesta = $this->ejecutar('GET', '/collections/' . $this->coleccion . '/points/' . (int) $tbperfilid);

        if ($respuesta['error']) {
            return null;
        }

        $resultado = $respuesta['datos']['result'] ?? null;
        if (!is_array($resultado) || !isset($resultado['vector'])) {
            return null;
        }

        return $resultado;
    }

    public function buscarSimilares($vector, $limite = 5, $scoreThreshold = null)
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

        $respuesta = $this->ejecutar('POST', '/collections/' . $this->coleccion . '/points/search', $cuerpo);

        $status = $respuesta['datos']['status'] ?? null;

        if ($respuesta['error'] || $status === 'error' || (is_array($status) && !empty($status['error']))) {
            error_log("Qdrant: error al buscar en '{$this->coleccion}' (status=" . json_encode($status) . ")");
            return null;
        }

        $resultados = $respuesta['datos']['result'] ?? [];

        $perfiles = [];
        foreach ($resultados as $r) {
            if (!isset($r['id'])) {
                continue;
            }
            $perfiles[] = [
                'tbperfilid' => (int) $r['id'],
                'score' => (float) ($r['score'] ?? 0)
            ];
        }

        return $perfiles;
    }
}