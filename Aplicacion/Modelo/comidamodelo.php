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

    public function getListAdmin()
    {
        $sql = "SELECT c.tbcomidaid AS id, c.tbtipocomidaid, c.tbcomidanombre AS nombre,
                       c.tbcomidaimagenurl AS imagenUrl, c.tbcomidaactivo AS activo,
                       t.tbtipocomidanombre AS tipoNombre
                FROM tbcomida c
                INNER JOIN tbtipocomida t ON c.tbtipocomidaid = t.tbtipocomidaid
                ORDER BY t.tbtipocomidanombre ASC, c.tbcomidanombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItemAdmin($id)
    {
        $sql = "SELECT c.tbcomidaid AS id, c.tbtipocomidaid, c.tbcomidanombre AS nombre,
                       c.tbcomidaimagenurl AS imagenUrl, c.tbcomidaactivo AS activo,
                       t.tbtipocomidanombre AS tipoNombre
                FROM tbcomida c
                INNER JOIN tbtipocomida t ON c.tbtipocomidaid = t.tbtipocomidaid
                WHERE c.tbcomidaid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function existeNombre($nombre, $tipoId, $excluirId = null)
    {
        $sql = "SELECT tbcomidaid FROM tbcomida
                WHERE LOWER(tbcomidanombre) = LOWER(:nombre)
                  AND tbtipocomidaid = :tipoId";
        if ($excluirId !== null) {
            $sql .= " AND tbcomidaid <> :excluirId";
        }
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':tipoId', (int) $tipoId, PDO::PARAM_INT);
        if ($excluirId !== null) {
            $stmt->bindValue(':excluirId', (int) $excluirId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    public function insert($tipoId, $nombre, $imagenUrl)
    {
        $sql = "INSERT INTO tbcomida (tbtipocomidaid, tbcomidanombre, tbcomidaimagenurl)
                VALUES (:tipoId, :nombre, :imagenUrl)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':tipoId', (int) $tipoId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':imagenUrl', $imagenUrl);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function update($id, $tipoId, $nombre, $imagenUrl)
    {
        $sql = "UPDATE tbcomida SET
                    tbtipocomidaid = :tipoId,
                    tbcomidanombre = :nombre,
                    tbcomidaimagenurl = :imagenUrl
                WHERE tbcomidaid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':tipoId', (int) $tipoId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':imagenUrl', $imagenUrl);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbcomida SET tbcomidaactivo = NOT tbcomidaactivo WHERE tbcomidaid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function tieneSwipes($id)
    {
        $sql = "SELECT COUNT(*) AS total FROM tbcomidaswipe WHERE tbcomidaid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila && (int) $fila['total'] > 0;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM tbcomida WHERE tbcomidaid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}