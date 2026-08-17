<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';

class UbicacionModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function getProvincias()
    {
        $sql = "SELECT tbprovinciaid, tbprovincianombre FROM tbprovincia WHERE tbprovinciaestado = 1 ORDER BY tbprovinciaid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCantonesPorProvincia($provinciaId)
    {
        $sql = "SELECT tbcantonid, tbcantonnombre FROM tbcanton WHERE tbprovinciaid = :provinciaId AND tbcantonestado = 1 ORDER BY tbcantonnombre";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':provinciaId', $provinciaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDistritosPorCanton($cantonId)
    {
        $sql = "SELECT tbdistritoid, tbdistritonombre FROM tbdistrito WHERE tbcantonid = :cantonId AND tbdistritoestado = 1 ORDER BY tbdistritonombre";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':cantonId', $cantonId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Crea una ubicación nueva e independiente, devuelve su id (se usa al registrar un perfil)
    public function insert($provinciaId, $cantonId, $distritoId, $lat, $lng)
    {
        $sql = "INSERT INTO tbubicacion
                (tbubicacionprovincia, tbubicacioncanton, tbubicaciondistrito, tbubicacionlatitud, tbubicacionlongitud)
                VALUES (:provinciaId, :cantonId, :distritoId, :lat, :lng)";
        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':provinciaId', $provinciaId, $provinciaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':cantonId', $cantonId, $cantonId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':distritoId', $distritoId, $distritoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':lat', $lat, $lat === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':lng', $lng, $lng === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    
    public function update($ubicacionId, $provinciaId, $cantonId, $distritoId, $lat, $lng)
    {
        $sql = "UPDATE tbubicacion
            SET tbubicacionprovincia = :provinciaId,
                tbubicacioncanton = :cantonId,
                tbubicaciondistrito = :distritoId,
                tbubicacionlatitud = :lat,
                tbubicacionlongitud = :lng
            WHERE tbubicacionid = :ubicacionId";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':ubicacionId', $ubicacionId, PDO::PARAM_INT);
        $stmt->bindValue(':provinciaId', $provinciaId, PDO::PARAM_INT);
        $stmt->bindValue(':cantonId', $cantonId, PDO::PARAM_INT);
        $stmt->bindValue(':distritoId', $distritoId, PDO::PARAM_INT);
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lng', $lng);

        return $stmt->execute();
    }

    
    public function getPorId($ubicacionId)
    {
        $sql = "SELECT
                u.tbubicacionid,
                u.tbubicacionprovincia,
                u.tbubicacioncanton,
                u.tbubicaciondistrito,
                u.tbubicacionlatitud,
                u.tbubicacionlongitud,
                u.tbubicacionestado,
                p.tbprovincianombre,
                c.tbcantonnombre,
                d.tbdistritonombre
            FROM tbubicacion u
            LEFT JOIN tbprovincia p ON u.tbubicacionprovincia = p.tbprovinciaid
            LEFT JOIN tbcanton c ON u.tbubicacioncanton = c.tbcantonid
            LEFT JOIN tbdistrito d ON u.tbubicaciondistrito = d.tbdistritoid
            WHERE u.tbubicacionid = :ubicacionId";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':ubicacionId', $ubicacionId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($ubicacionId)
    {
        $sql = "DELETE FROM tbubicacion WHERE tbubicacionid = :ubicacionId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':ubicacionId', $ubicacionId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function setEstado($ubicacionId, $estado)
    {
        $sql = "UPDATE tbubicacion SET tbubicacionestado = :estado WHERE tbubicacionid = :ubicacionId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':estado', $estado, PDO::PARAM_BOOL);
        $stmt->bindValue(':ubicacionId', $ubicacionId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
