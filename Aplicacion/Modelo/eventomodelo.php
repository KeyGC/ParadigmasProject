<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/evento.php';

class EventoModelo
{
    const RADIO_COINCIDENCIA_METROS = 500;

    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function getList()
    {
        $sql = "SELECT e.tbeventoid, e.tbgeneroid, g.tbgeneronombre, e.tbeventonombre,
                       e.tbeventoartista, e.tbeventoubicaciongeneral, e.tbeventolatitud,
                       e.tbeventolongitud, e.tbeventofecha, e.tbeventohora, e.tbeventoestado
                FROM tbevento e
                INNER JOIN tbgenero g ON e.tbgeneroid = g.tbgeneroid
                ORDER BY e.tbeventofecha DESC, e.tbeventohora DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getEvento($id)
    {
        $sql = "SELECT tbeventoid, tbgeneroid, tbeventonombre, tbeventoartista,
                       tbeventoubicaciongeneral, tbeventolatitud, tbeventolongitud,
                       tbeventofecha, tbeventohora, tbeventoestado
                FROM tbevento
                WHERE tbeventoid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function getEventoByNombreYFecha($nombre, $fecha)
    {
        $sql = "SELECT tbeventoid FROM tbevento
                WHERE tbeventonombre = :nombre AND tbeventofecha = :fecha";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }

    public function insert(Evento $evento)
    {
        $sql = "INSERT INTO tbevento
                    (tbgeneroid, tbeventonombre, tbeventoartista, tbeventoubicaciongeneral,
                     tbeventolatitud, tbeventolongitud, tbeventofecha, tbeventohora)
                VALUES
                    (:generoId, :nombre, :artista, :ubicacion, :latitud, :longitud, :fecha, :hora)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $evento->get_tbgeneroid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $evento->get_tbeventonombre());
        $stmt->bindValue(':artista', $evento->get_tbeventoartista());
        $stmt->bindValue(':ubicacion', $evento->get_tbeventoubicaciongeneral());
        $stmt->bindValue(':latitud', $evento->get_tbeventolatitud());
        $stmt->bindValue(':longitud', $evento->get_tbeventolongitud());
        $stmt->bindValue(':fecha', $evento->get_tbeventofecha());
        $stmt->bindValue(':hora', $evento->get_tbeventohora());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function update(Evento $evento)
    {
        $sql = "UPDATE tbevento SET
                    tbgeneroid = :generoId,
                    tbeventonombre = :nombre,
                    tbeventoartista = :artista,
                    tbeventoubicaciongeneral = :ubicacion,
                    tbeventolatitud = :latitud,
                    tbeventolongitud = :longitud,
                    tbeventofecha = :fecha,
                    tbeventohora = :hora
                WHERE tbeventoid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $evento->get_tbgeneroid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $evento->get_tbeventonombre());
        $stmt->bindValue(':artista', $evento->get_tbeventoartista());
        $stmt->bindValue(':ubicacion', $evento->get_tbeventoubicaciongeneral());
        $stmt->bindValue(':latitud', $evento->get_tbeventolatitud());
        $stmt->bindValue(':longitud', $evento->get_tbeventolongitud());
        $stmt->bindValue(':fecha', $evento->get_tbeventofecha());
        $stmt->bindValue(':hora', $evento->get_tbeventohora());
        $stmt->bindValue(':id', $evento->get_tbeventoid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbevento SET tbeventoestado = NOT tbeventoestado WHERE tbeventoid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Devuelve la lista de ubicaciones específicas dentro de un evento (por ahora, solo categoría 'comida')
    public function getUbicacionesPorEvento($eventoId, $categoria = null)
    {
        $sql = "SELECT tbeventoubicacionid, tbeventoid, tbeventoubicacioncategoria,
                       tbeventoubicacionreferenciaid, tbeventoubicacionnombre,
                       tbeventoubicacionlatitud, tbeventoubicacionlongitud, tbeventoubicacionestado
                FROM tbeventoubicacion
                WHERE tbeventoid = :eventoId AND tbeventoubicacionestado = 1";

        if ($categoria !== null) {
            $sql .= " AND tbeventoubicacioncategoria = :categoria";
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':eventoId', $eventoId, PDO::PARAM_INT);
        if ($categoria !== null) {
            $stmt->bindValue(':categoria', $categoria);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insertUbicacion($eventoId, $categoria, $referenciaId, $nombre, $latitud, $longitud)
    {
        $sql = "INSERT INTO tbeventoubicacion
                    (tbeventoid, tbeventoubicacioncategoria, tbeventoubicacionreferenciaid,
                     tbeventoubicacionnombre, tbeventoubicacionlatitud, tbeventoubicacionlongitud)
                VALUES
                    (:eventoId, :categoria, :referenciaId, :nombre, :latitud, :longitud)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':eventoId', $eventoId, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $categoria);
        $stmt->bindValue(':referenciaId', $referenciaId ?: null, $referenciaId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':latitud', $latitud ?: null);
        $stmt->bindValue(':longitud', $longitud ?: null);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function toggleEstadoUbicacion($ubicacionId)
    {
        $sql = "UPDATE tbeventoubicacion
                SET tbeventoubicacionestado = NOT tbeventoubicacionestado
                WHERE tbeventoubicacionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $ubicacionId, PDO::PARAM_INT);
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

    public function getEventosDelDia($fecha)
    {
        $sql = "SELECT * FROM tbevento WHERE tbeventofecha = :fecha AND tbeventoestado = TRUE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrarPosibleAsistencia($perfilId, $latitud, $longitud, $fechaHora)
    {
        $fecha = date('Y-m-d', strtotime($fechaHora));
        $eventosDelDia = $this->getEventosDelDia($fecha);

        $coincidencias = [];
        foreach ($eventosDelDia as $evento) {
            $distancia = $this->distanciaMetros(
                (float) $latitud,
                (float) $longitud,
                (float) $evento['tbeventolatitud'],
                (float) $evento['tbeventolongitud']
            );
            $coincide = $distancia <= self::RADIO_COINCIDENCIA_METROS;

            $sql = "INSERT INTO tbeventoasistencia
                        (tbperfilid, tbeventoid, tbeventoasistenciafechahora,
                         tbeventoasistencialatitud, tbeventoasistencialongitud, tbeventoasistenciacoincide)
                    VALUES
                        (:perfilId, :eventoId, :fechaHora, :latitud, :longitud, :coincide)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
            $stmt->bindValue(':eventoId', $evento['tbeventoid'], PDO::PARAM_INT);
            $stmt->bindValue(':fechaHora', $fechaHora);
            $stmt->bindValue(':latitud', $latitud);
            $stmt->bindValue(':longitud', $longitud);
            $stmt->bindValue(':coincide', $coincide, PDO::PARAM_BOOL);
            $stmt->execute();

            if ($coincide) {
                $coincidencias[] = $evento['tbeventoid'];
            }
        }

        return $coincidencias;
    }

    public function getGenerosAsistidosPorPerfil($perfilId)
    {
        $sql = "SELECT g.tbgeneronombre, COUNT(*) AS asistencias
                FROM tbeventoasistencia ea
                INNER JOIN tbevento e ON ea.tbeventoid = e.tbeventoid
                INNER JOIN tbgenero g ON e.tbgeneroid = g.tbgeneroid
                WHERE ea.tbperfilid = :perfilId AND ea.tbeventoasistenciacoincide = TRUE
                GROUP BY g.tbgeneronombre
                ORDER BY asistencias DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}