<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class ComidaModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function getTiposActivos()
    {
        $sql = "SELECT tbtipocomidaid, tbtipocomidanombre
                FROM tbtipocomida
                WHERE tbtipocomidaestado = 1
                ORDER BY tbtipocomidanombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItemsActivos()
    {
        $sql = "SELECT c.tbcomidaid AS id, c.tbtipocomidaid, c.tbcomidanombre AS nombre,
                       c.tbcomidaimagenurl AS imagenUrl, t.tbtipocomidanombre AS tipoNombre
                FROM tbcomida c
                INNER JOIN tbtipocomida t ON c.tbtipocomidaid = t.tbtipocomidaid
                WHERE c.tbcomidaactivo = 1 AND t.tbtipocomidaestado = 1
                ORDER BY t.tbtipocomidanombre ASC, c.tbcomidanombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItem($id)
    {
        $sql = "SELECT c.tbcomidaid AS id, c.tbtipocomidaid, c.tbcomidanombre AS nombre,
                       c.tbcomidaimagenurl AS imagenUrl, t.tbtipocomidanombre AS tipoNombre
                FROM tbcomida c
                INNER JOIN tbtipocomida t ON c.tbtipocomidaid = t.tbtipocomidaid
                WHERE c.tbcomidaid = :id AND c.tbcomidaactivo = 1 AND t.tbtipocomidaestado = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function getItemsJuego($perfilId, $cantidad = 20, $reciclar = false)
    {
        $sql = "SELECT c.tbcomidaid AS id, c.tbtipocomidaid, c.tbcomidanombre AS nombre,
                       c.tbcomidaimagenurl AS imagenUrl, t.tbtipocomidanombre AS tipoNombre
                FROM tbcomida c
                INNER JOIN tbtipocomida t ON c.tbtipocomidaid = t.tbtipocomidaid
                WHERE c.tbcomidaactivo = 1 AND t.tbtipocomidaestado = 1";

        if (!$reciclar) {
            $sql .= " AND c.tbcomidaid NOT IN (
                        SELECT sw.tbcomidaid FROM tbcomidaswipe sw WHERE sw.tbperfilid = :perfilId)";
        }

        $sql .= " ORDER BY RAND() LIMIT " . (int) $cantidad;

        $stmt = $this->conexion->prepare($sql);
        if (!$reciclar) {
            $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrarSwipe($perfilId, $comidaId, $like)
    {
        $like = $like ? 1 : 0;
        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbcomidaswipe
                    (tbperfilid, tbcomidaid, tbcomidaswipelike, tbcomidaswipefecha)
                VALUES (:perfilId, :comidaId, :like, :ahora)
                ON DUPLICATE KEY UPDATE
                    tbcomidaswipelike = :like2,
                    tbcomidaswipefecha = :ahora2";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':comidaId', $comidaId, PDO::PARAM_INT);
        $stmt->bindValue(':like', $like, PDO::PARAM_INT);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->bindValue(':like2', $like, PDO::PARAM_INT);
        $stmt->bindValue(':ahora2', $ahora);

        return $stmt->execute();
    }
}