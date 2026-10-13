<?php

// Helpers de validación reutilizables. Cada función devuelve un mensaje de
// error (string) cuando el dato es inválido, o null cuando es aceptable, de
// modo que el controlador pueda responder con un mensaje específico.

function campoRequerido($valor, $etiqueta)
{
    if (trim((string) $valor) === '') {
        return "El campo $etiqueta es obligatorio";
    }
    return null;
}

function campoLongitud($valor, $etiqueta, $max, $min = 1)
{
    $texto = (string) $valor;
    $largo = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);

    if ($largo < $min) {
        return "El campo $etiqueta debe tener al menos $min caracteres";
    }
    if ($largo > $max) {
        return "El campo $etiqueta no puede superar $max caracteres";
    }
    return null;
}

function correoValido($correo)
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

function coordenadasValidas($latitud, $longitud)
{
    if (!is_numeric($latitud) || !is_numeric($longitud)) {
        return "Latitud y longitud deben ser valores numéricos";
    }

    $lat = (float) $latitud;
    $lng = (float) $longitud;

    if ($lat < -90 || $lat > 90) {
        return "La latitud debe estar entre -90 y 90";
    }
    if ($lng < -180 || $lng > 180) {
        return "La longitud debe estar entre -180 y 180";
    }
    return null;
}

function urlValida($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return "La URL no es válida";
    }
    $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($esquema, ['http', 'https'], true)) {
        return "La URL debe comenzar con http:// o https://";
    }
    return null;
}
