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
        $sql = "INSERT INTO tbreproduccion (tbperfilid, tbcancionid, tbreproducciontiempo, tbreproduccionultima)
                VALUES (:perfilId, :cancionId, :segundos, NOW())
                ON DUPLICATE KEY UPDATE
                    tbreproducciontiempo = tbreproducciontiempo + :segundos2,
                    tbreproduccionultima = NOW()";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->bindValue(':segundos', $segundos, PDO::PARAM_INT);
        $stmt->bindValue(':segundos2', $segundos, PDO::PARAM_INT);

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
}