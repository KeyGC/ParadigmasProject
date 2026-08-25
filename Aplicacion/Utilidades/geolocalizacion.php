<?php
define('GEO_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/reverse');
define('GEO_USER_AGENT', 'ParadigmasProject/1.0 (UbicacionAutomatica)');
define('GEO_TIMEOUT_SEGUNDOS', 8);

function normalizarTextoUbicacion($texto)
{
    $texto = trim((string) $texto);
    if ($texto === '') {
        return '';
    }

    $texto = mb_strtolower($texto, 'UTF-8');

    $acentos = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c'
    ];
    $texto = strtr($texto, $acentos);

    // Colapsa espacios múltiples
    $texto = preg_replace('/\s+/', ' ', $texto);

    return $texto;
}

function reverseGeocodificar($latitud, $longitud)
{
    $query = http_build_query([
        'format' => 'jsonv2',
        'lat' => $latitud,
        'lon' => $longitud,
        'addressdetails' => 1,
        'zoom' => 14,
        'accept-language' => 'es'
    ]);

    $ch = curl_init(GEO_NOMINATIM_URL . '?' . $query);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => GEO_TIMEOUT_SEGUNDOS,
        CURLOPT_USERAGENT => GEO_USER_AGENT,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $respuesta = curl_exec($ch);
    $errorCurl = curl_errno($ch);
    $codigoHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($respuesta === false || $errorCurl || $codigoHttp !== 200) {
        error_log("Reverse geocoding fallo (http={$codigoHttp}, curl=" . curl_strerror($errorCurl ?: 0) . ")");
        return null;
    }

    $datos = json_decode($respuesta, true);
    if (!is_array($datos) || !isset($datos['address']) || !is_array($datos['address'])) {
        error_log('Reverse geocoding: respuesta JSON invalida');
        return null;
    }

    return $datos['address'];
}
