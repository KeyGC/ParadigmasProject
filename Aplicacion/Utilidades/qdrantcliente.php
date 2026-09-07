<?php

class QdrantCliente
{
    private $host;
    private $port;

    private $tiempoConexion = 4;

    private $tiempoPeticion = 8;

    public function __construct($host = null, $port = null)
    {
        $this->host = $host ?: QDRANT_HOST;
        $this->port = $port ?: QDRANT_PORT;
    }

    private function ejecutar($metodo, $ruta, $cuerpo = null)
    {
        $cabeceras = [
            'Content-Type: application/json',
            'api-key: ' . QDRANT_API_KEY
        ];

        $ch = curl_init("https://{$this->host}:{$this->port}{$ruta}");

        $opciones = [
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->tiempoConexion,
            CURLOPT_TIMEOUT => $this->tiempoPeticion,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ];

        if ($cuerpo !== null) {
            $opciones[CURLOPT_POSTFIELDS] = json_encode($cuerpo);
        }

        curl_setopt_array($ch, $opciones);

        $respuesta = curl_exec($ch);
        $errorCurl = curl_errno($ch);
        $codigoHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

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
        $existe = $this->ejecutar('GET', '/collections/perfiles');

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

            $borrado = $this->ejecutar('DELETE', '/collections/perfiles');

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

        $crear = $this->ejecutar('PUT', '/collections/perfiles', $cuerpo);

        if ($crear['error']) {
            return false;
        }

        if (($crear['datos']['status'] ?? '') === 'error') {
            $verificar = $this->ejecutar('GET', '/collections/perfiles');

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

        $respuesta = $this->ejecutar('PUT', '/collections/perfiles/points', $cuerpo);

        return !$respuesta['error'] && ($respuesta['datos']['status'] ?? '') !== 'error';
    }

    public function obtenerPunto($tbperfilid)
    {
        $respuesta = $this->ejecutar('GET', '/collections/perfiles/points/' . (int) $tbperfilid);

        if ($respuesta['error']) {
            return null;
        }

        $resultado = $respuesta['datos']['result'] ?? null;
        if (!is_array($resultado) || !isset($resultado['vector'])) {
            return null;
        }

        return $resultado;
    }

    public function buscarSimilares($vector, $limite = 5)
    {
        $cuerpo = [
            'vector' => array_values(array_map('floatval', $vector)),
            'limit' => (int) $limite,
            'with_payload' => true,
            'with_vectors' => false
        ];

        $respuesta = $this->ejecutar('POST', '/collections/perfiles/points/search', $cuerpo);

        if ($respuesta['error'] || ($respuesta['datos']['status'] ?? '') === 'error') {
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