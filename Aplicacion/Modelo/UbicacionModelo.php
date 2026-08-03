<?php

require_once __DIR__ . '/../../Configuracion/Basedatos.php';

class UbicacionModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    // Traer todas las provincias
    public function getProvincias()
    {
        $sql = "SELECT tbprovinciaid, tbprovincianombre FROM tbprovincia ORDER BY tbprovinciaid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Traer cantones de una provincia
    public function getCantonesPorProvincia($provinciaId)
    {
        $sql = "SELECT tbcantonid, tbcantonnombre FROM tbcanton WHERE tbprovinciaid = :provinciaId ORDER BY tbcantonnombre";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':provinciaId', $provinciaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Traer distritos de un cantón
    public function getDistritosPorCanton($cantonId)
    {
        $sql = "SELECT tbdistritoid, tbdistritonombre FROM tbdistrito WHERE tbcantonid = :cantonId ORDER BY tbdistritonombre";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':cantonId', $cantonId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Guardar la ubicación de un perfil
    public function insert($perfilId, $provinciaId, $cantonId, $distritoId, $lat, $lng)
    {
        $sql = "INSERT INTO tbubicacion 
                (tbperfilid, tbubicacionprovincia, tbubicacioncanton, tbubicaciondistrito, tbubicacionlatitud, tbubicacionlongitud) 
                VALUES (:perfilId, :provinciaId, :cantonId, :distritoId, :lat, :lng)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':provinciaId', $provinciaId, PDO::PARAM_INT);
        $stmt->bindValue(':cantonId', $cantonId, PDO::PARAM_INT);
        $stmt->bindValue(':distritoId', $distritoId, PDO::PARAM_INT);
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lng', $lng);
        return $stmt->execute();
    }

    // Obtener la ubicación completa (con nombres) de un perfil
    public function getPorPerfil($perfilId)
    {
        $sql = "SELECT
                u.tbubicacionid,
                u.tbperfilid,
                u.tbubicacionprovincia,
                u.tbubicacioncanton,
                u.tbubicaciondistrito,
                u.tbubicacionlatitud,
                u.tbubicacionlongitud,
                p.tbprovincianombre,
                c.tbcantonnombre,
                d.tbdistritonombre
            FROM tbubicacion u
            INNER JOIN tbprovincia p
                ON u.tbubicacionprovincia = p.tbprovinciaid
            INNER JOIN tbcanton c
                ON u.tbubicacioncanton = c.tbcantonid
            INNER JOIN tbdistrito d
                ON u.tbubicaciondistrito = d.tbdistritoid
            WHERE u.tbperfilid = :perfilId";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existeUbicacion($perfilId)
    {
        $sql = "SELECT COUNT(*) FROM tbubicacion WHERE tbperfilid = :perfilId";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }

    public function update($perfilId, $provinciaId, $cantonId, $distritoId, $lat, $lng)
    {
        $sql = "UPDATE tbubicacion
            SET tbubicacionprovincia = :provinciaId,
                tbubicacioncanton = :cantonId,
                tbubicaciondistrito = :distritoId,
                tbubicacionlatitud = :lat,
                tbubicacionlongitud = :lng
            WHERE tbperfilid = :perfilId";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':provinciaId', $provinciaId, PDO::PARAM_INT);
        $stmt->bindValue(':cantonId', $cantonId, PDO::PARAM_INT);
        $stmt->bindValue(':distritoId', $distritoId, PDO::PARAM_INT);
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lng', $lng);

        return $stmt->execute();
    }
}
