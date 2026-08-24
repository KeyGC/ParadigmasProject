<?php

// Utilidad de Reverse Geocoding (coordenadas -> provincia/cantón/distrito)
//
// Servicio elegido: Nominatim (https://nominatim.openstreetmap.org)
// - Es el servicio oficial de OpenStreetMap, el mismo proyecto cuyos mapas
//   ya usa la aplicación (Leaflet + tiles de OSM), por lo que hay coherencia
//   de datos y no se depende de un proveedor distinto.
// - Gratuito y NO requiere API Key (nada que hardcodear ni filtrar).
// - Política de uso: máximo ~1 petición/segundo y enviar un User-Agent
//   identificativo; ambas condiciones se respetan aquí.
// - Si en el futuro se migrara a un servicio con API Key, la clave debe
//   definirse SOLO en este archivo como constante del servidor y jamás
//   enviarse al JavaScript.

define('GEO_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/reverse');
define('GEO_USER_AGENT', 'ParadigmasProject/1.0 (UbicacionAutomatica)');
define('GEO_TIMEOUT_SEGUNDOS', 8);

// Quita mayúsculas, acentos y espacios sobrantes para poder comparar los
// nombres devueltos por Nominatim contra los nombres guardados en
// tbprovincia / tbcanton / tbdistrito sin depender del collation de MySQL.
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

// Llama a Nominatim y devuelve el bloque 'address' crudo del servicio, o
// null si falla la petición/respuesta. La interpretación de ese bloque se
// hace en el controlador (resolución en dos etapas: código postal verificado
// contra el catálogo y, como respaldo, coincidencia por nombres).
//
// Campos relevantes que entrega Nominatim para Costa Rica:
//   province / state  -> provincia
//   county            -> cantón (OJO: en algunas zonas del país NO viene)
//   village/town/city_district/city/neighbourhood/... -> distrito aprox.
//   postcode          -> código postal nacional de 5 dígitos PCCDD,
//                        con EXACTAMENTE la misma codificación del catálogo:
//                        provincia = 1er dígito, cantón = 3 primeros,
//                        distrito = 5 dígitos completos (ej. 10103 = Hospital)
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
