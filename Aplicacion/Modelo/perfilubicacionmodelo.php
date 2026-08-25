<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';

class PerfilUbicacionModelo
{
    const TIPO_AUTOMATICA = 'AUTOMATICA';
    const TIPO_MANUAL = 'MANUAL';

    const TOLERANCIA_METROS = 50;

    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function existePerfilActivo($perfilId)
    {
        $sql = "SELECT COUNT(*) AS total FROM tbperfil
                WHERE tbperfilid = :perfilId AND tbperfilactivo = TRUE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila !== false && (int) $fila['total'] > 0;
    }

    public function construirData($provincia, $canton, $distrito, $latitud, $longitud, $fecha, $hora, $tipo)
    {
        return implode('-', [
            $provincia,
            $canton,
            $distrito,
            sprintf('%.8f', $latitud),
            sprintf('%.8f', $longitud),
            $fecha,
            $hora,
            $tipo
        ]);
    }

    public function parsearData($data)
    {
        $patron = '/^(.+)-(.+)-(.+)-(-?\d+(?:\.\d+)?)-(-?\d+(?:\.\d+)?)-(\d{4}-\d{2}-\d{2})-(\d{2}:\d{2}:\d{2})-(AUTOMATICA|MANUAL)$/';

        if (!preg_match($patron, trim((string) $data), $m)) {
            return null;
        }

        return [
            'provincia' => $m[1],
            'canton' => $m[2],
            'distrito' => $m[3],
            'latitud' => $m[4],
            'longitud' => $m[5],
            'fecha' => $m[6],
            'hora' => $m[7],
            'tipo' => $m[8]
        ];
    }

    public function insertar($perfilId, $data, $tipo)
    {
        if ($tipo !== self::TIPO_AUTOMATICA && $tipo !== self::TIPO_MANUAL) {
            return false;
        }

        if (!$this->existePerfilActivo($perfilId)) {
            return false;
        }

        if (!$this->validarFechaHoraEnData($data)) {
            return false;
        }

        $sql = "INSERT INTO tbperfilubicacion (tbperfilid, tbperfilubicaciondata, tbperfilubicacionestado)
                VALUES (:perfilId, :data, TRUE)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':data', $data);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    private function validarFechaHoraEnData($data)
    {
        $parseado = $this->parsearData($data);
        if ($parseado === null) {
            return false;
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $parseado['fecha']);
        $hora = DateTime::createFromFormat('H:i:s', $parseado['hora']);

        return $fecha !== false && $hora !== false;
    }

    public function getUltimo($perfilId)
    {
        $sql = "SELECT tbperfilubicacionid, tbperfilid, tbperfilubicaciondata, tbperfilubicacionestado
                FROM tbperfilubicacion
                WHERE tbperfilid = :perfilId AND tbperfilubicacionestado = TRUE
                ORDER BY tbperfilubicacionid DESC
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    public function esSimilarAUltima($perfilId, $latitud, $longitud)
    {
        $ultimo = $this->getUltimo($perfilId);
        if ($ultimo === null) {
            return false;
        }

        $parseado = $this->parsearData($ultimo['tbperfilubicaciondata']);
        if ($parseado === null) {
            return false;
        }

        $distancia = $this->distanciaMetros(
            (float) $latitud,
            (float) $longitud,
            (float) $parseado['latitud'],
            (float) $parseado['longitud']
        );

        return $distancia <= self::TOLERANCIA_METROS;
    }

    private function distanciaMetros($lat1, $lng1, $lat2, $lng2)
    {
        $radioTierra = 6371000;

        $lat1rad = deg2rad($lat1);
        $lat2rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1rad) * cos($lat2rad) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radioTierra * $c;
    }

    public function getListPorPerfil($perfilId)
    {
        $sql = "SELECT tbperfilubicacionid, tbperfilid, tbperfilubicaciondata, tbperfilubicacionestado
                FROM tbperfilubicacion
                WHERE tbperfilid = :perfilId AND tbperfilubicacionestado = TRUE
                ORDER BY tbperfilubicacionid DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        $historico = [];
        foreach ($stmt->fetchAll() as $fila) {
            $registro = $this->parsearData($fila['tbperfilubicaciondata']);
            if ($registro === null) {
                continue;
            }
            $registro['tbperfilubicacionid'] = $fila['tbperfilubicacionid'];
            $historico[] = $registro;
        }

        return $historico;
    }
}
