<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class ReproduccionModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function acumularTiempo($perfilId, $cancionId, $segundos)
    {
        $sql = "INSERT INTO tbreproduccion (tbperfilid, tbcancionid, tbreproducciontiempo)
                VALUES (:perfilId, :cancionId, :segundos)
                ON DUPLICATE KEY UPDATE
                    tbreproducciontiempo = tbreproducciontiempo + :segundos2";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->bindValue(':segundos', $segundos, PDO::PARAM_INT);
        $stmt->bindValue(':segundos2', $segundos, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function incrementarContador($perfilId, $cancionId)
    {
        $sql = "INSERT INTO tbreproduccion (tbperfilid, tbcancionid, tbreproduccioncontador)
                VALUES (:perfilId, :cancionId, 1)
                ON DUPLICATE KEY UPDATE
                    tbreproduccioncontador = tbreproduccioncontador + 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getTiempoPorPerfilYCancion($perfilId, $cancionId)
    {
        $sql = "SELECT tbreproducciontiempo FROM tbreproduccion WHERE tbperfilid = :perfilId AND tbcancionid = :cancionId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? (int)$fila['tbreproducciontiempo'] : 0;
    }

    public function deleteByPerfilId($perfilId)
    {
        $sql = "DELETE FROM tbreproduccion WHERE tbperfilid = :perfilId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}