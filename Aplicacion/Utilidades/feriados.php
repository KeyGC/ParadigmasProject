<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

function refrescarFeriados($anio)
{
    $conexion = Basedatos::conectar();

    $sqlCheck = "SELECT COUNT(*) as total FROM tbferiados WHERE tbferiadosanio = :anio";
    $stmt = $conexion->prepare($sqlCheck);
    $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->fetch()['total'] > 0) {
        return true; // ya están cacheados
    }

    $url = "https://date.nager.at/api/v3/publicholidays/{$anio}/CR";
    $contexto = stream_context_create(['http' => ['timeout' => 5]]);
    $respuesta = @file_get_contents($url, false, $contexto);

    if ($respuesta === false) {
        return false;
    }

    $feriados = json_decode($respuesta, true);
    if (!is_array($feriados)) {
        return false;
    }

    $sqlInsert = "INSERT IGNORE INTO tbferiados (tbferiadosfecha, tbferiadosnombre, tbferiadosanio)
                  VALUES (:fecha, :nombre, :anio)";
    $stmt = $conexion->prepare($sqlInsert);

    foreach ($feriados as $f) {
        $stmt->bindValue(':fecha', $f['date']);
        $stmt->bindValue(':nombre', $f['localName']);
        $stmt->bindValue(':anio', $anio, PDO::PARAM_INT);
        $stmt->execute();
    }

    return true;
}

function esFeriado($fecha)
{
    $conexion = Basedatos::conectar();
    $soloFecha = date('Y-m-d', strtotime($fecha));

    $sql = "SELECT COUNT(*) as total FROM tbferiados WHERE tbferiadosfecha = :fecha";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':fecha', $soloFecha);
    $stmt->execute();

    return $stmt->fetch()['total'] > 0;
}