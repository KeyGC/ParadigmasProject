<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/concierto.php';

class ConciertoModelo
{
    const RADIO_COINCIDENCIA_METROS = 500;

    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function getList()
    {
        $sql = "SELECT c.tbconciertoid, c.tbgeneroid, g.tbgeneronombre, c.tbconciertonombre,
                       c.tbconciertoartista, c.tbconciertoubicacion, c.tbconciertolatitud,
                       c.tbconciertolongitud, c.tbconciertofecha, c.tbconciertohora, c.tbconciertoestado
                FROM tbconcierto c
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                ORDER BY c.tbconciertofecha DESC, c.tbconciertohora DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getConcierto($id)
    {
        $sql = "SELECT tbconciertoid, tbgeneroid, tbconciertonombre, tbconciertoartista,
                       tbconciertoubicacion, tbconciertolatitud, tbconciertolongitud,
                       tbconciertofecha, tbconciertohora, tbconciertoestado
                FROM tbconcierto
                WHERE tbconciertoid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function getConciertoByNombreYFecha($nombre, $fecha)
    {
        $sql = "SELECT tbconciertoid FROM tbconcierto
                WHERE tbconciertonombre = :nombre AND tbconciertofecha = :fecha";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function insert(Concierto $concierto)
    {
        $sql = "INSERT INTO tbconcierto
                    (tbgeneroid, tbconciertonombre, tbconciertoartista, tbconciertoubicacion,
                     tbconciertolatitud, tbconciertolongitud, tbconciertofecha, tbconciertohora)
                VALUES
                    (:generoId, :nombre, :artista, :ubicacion, :latitud, :longitud, :fecha, :hora)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $concierto->get_tbgeneroid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $concierto->get_tbconciertonombre());
        $stmt->bindValue(':artista', $concierto->get_tbconciertoartista());
        $stmt->bindValue(':ubicacion', $concierto->get_tbconciertoubicacion());
        $stmt->bindValue(':latitud', $concierto->get_tbconciertolatitud());
        $stmt->bindValue(':longitud', $concierto->get_tbconciertolongitud());
        $stmt->bindValue(':fecha', $concierto->get_tbconciertofecha());
        $stmt->bindValue(':hora', $concierto->get_tbconciertohora());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function update(Concierto $concierto)
    {
        $sql = "UPDATE tbconcierto SET
                    tbgeneroid = :generoId,
                    tbconciertonombre = :nombre,
                    tbconciertoartista = :artista,
                    tbconciertoubicacion = :ubicacion,
                    tbconciertolatitud = :latitud,
                    tbconciertolongitud = :longitud,
                    tbconciertofecha = :fecha,
                    tbconciertohora = :hora
                WHERE tbconciertoid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $concierto->get_tbgeneroid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $concierto->get_tbconciertonombre());
        $stmt->bindValue(':artista', $concierto->get_tbconciertoartista());
        $stmt->bindValue(':ubicacion', $concierto->get_tbconciertoubicacion());
        $stmt->bindValue(':latitud', $concierto->get_tbconciertolatitud());
        $stmt->bindValue(':longitud', $concierto->get_tbconciertolongitud());
        $stmt->bindValue(':fecha', $concierto->get_tbconciertofecha());
        $stmt->bindValue(':hora', $concierto->get_tbconciertohora());
        $stmt->bindValue(':id', $concierto->get_tbconciertoid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbconcierto SET tbconciertoestado = NOT tbconciertoestado WHERE tbconciertoid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
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

    public function getConciertosDelDia($fecha)
    {
        $sql = "SELECT * FROM tbconcierto WHERE tbconciertofecha = :fecha AND tbconciertoestado = TRUE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrarPosibleAsistencia($perfilId, $latitud, $longitud, $fechaHora)
    {
        $fecha = date('Y-m-d', strtotime($fechaHora));
        $conciertosDelDia = $this->getConciertosDelDia($fecha);

        $coincidencias = [];
        foreach ($conciertosDelDia as $concierto) {
            $distancia = $this->distanciaMetros(
                (float) $latitud,
                (float) $longitud,
                (float) $concierto['tbconciertolatitud'],
                (float) $concierto['tbconciertolongitud']
            );
            $coincide = $distancia <= self::RADIO_COINCIDENCIA_METROS;

            $sql = "INSERT INTO tbconciertoasistencia
                        (tbperfilid, tbconciertoid, tbconciertoasistenciafechahora,
                         tbconciertoasistencialatitud, tbconciertoasistencialongitud, tbconciertoasistenciacoincide)
                    VALUES
                        (:perfilId, :conciertoId, :fechaHora, :latitud, :longitud, :coincide)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
            $stmt->bindValue(':conciertoId', $concierto['tbconciertoid'], PDO::PARAM_INT);
            $stmt->bindValue(':fechaHora', $fechaHora);
            $stmt->bindValue(':latitud', $latitud);
            $stmt->bindValue(':longitud', $longitud);
            $stmt->bindValue(':coincide', $coincide, PDO::PARAM_BOOL);
            $stmt->execute();

            if ($coincide) {
                $coincidencias[] = $concierto['tbconciertoid'];
            }
        }

        return $coincidencias;
    }

    public function getGenerosAsistidosPorPerfil($perfilId)
    {
        $sql = "SELECT g.tbgeneronombre, COUNT(*) AS asistencias
                FROM tbconciertoasistencia ca
                INNER JOIN tbconcierto c ON ca.tbconciertoid = c.tbconciertoid
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                WHERE ca.tbperfilid = :perfilId AND ca.tbconciertoasistenciacoincide = TRUE
                GROUP BY g.tbgeneronombre
                ORDER BY asistencias DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}