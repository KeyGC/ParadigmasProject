<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class DeporteModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    public function getTiposActivos()
    {
        $sql = "SELECT tbtipodeporteid, tbtipodeportenombre
                FROM tbtipodeporte
                WHERE tbtipodeporteestado = 1
                ORDER BY tbtipodeportenombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItemsActivos()
    {
        $sql = "SELECT d.tbdeporteid AS id, d.tbtipodeporteid, d.tbdeportenombre AS nombre,
                       d.tbdeporteimagenurl AS imagenUrl, t.tbtipodeportenombre AS tipoNombre
                FROM tbdeporte d
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                WHERE d.tbdeporteactivo = 1 AND t.tbtipodeporteestado = 1
                ORDER BY t.tbtipodeportenombre ASC, d.tbdeportenombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItem($id)
    {
        $sql = "SELECT d.tbdeporteid AS id, d.tbtipodeporteid, d.tbdeportenombre AS nombre,
                       d.tbdeporteimagenurl AS imagenUrl, t.tbtipodeportenombre AS tipoNombre
                FROM tbdeporte d
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                WHERE d.tbdeporteid = :id AND d.tbdeporteactivo = 1 AND t.tbtipodeporteestado = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function getItemsJuego($perfilId, $cantidad = 20, $reciclar = false)
    {
        $sql = "SELECT d.tbdeporteid AS id, d.tbtipodeporteid, d.tbdeportenombre AS nombre,
                       d.tbdeporteimagenurl AS imagenUrl, t.tbtipodeportenombre AS tipoNombre
                FROM tbdeporte d
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                WHERE d.tbdeporteactivo = 1 AND t.tbtipodeporteestado = 1";

        if (!$reciclar) {
            $sql .= " AND d.tbdeporteid NOT IN (
                        SELECT sw.tbdeporteid FROM tbdeporteswipe sw WHERE sw.tbperfilid = :perfilId)";
        }

        $sql .= " ORDER BY RAND() LIMIT " . (int) $cantidad;

        $stmt = $this->conexion->prepare($sql);
        if (!$reciclar) {
            $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registrarSwipe($perfilId, $deporteId, $like)
    {
        $like = $like ? 1 : 0;
        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbdeporteswipe
                    (tbperfilid, tbdeporteid, tbdeporteswipelike, tbdeporteswipefecha)
                VALUES (:perfilId, :deporteId, :like, :ahora)
                ON DUPLICATE KEY UPDATE
                    tbdeporteswipelike = :like2,
                    tbdeporteswipefecha = :ahora2";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':deporteId', $deporteId, PDO::PARAM_INT);
        $stmt->bindValue(':like', $like, PDO::PARAM_INT);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->bindValue(':like2', $like, PDO::PARAM_INT);
        $stmt->bindValue(':ahora2', $ahora);

        return $stmt->execute();
    }

    public function getListAdmin()
    {
        $sql = "SELECT d.tbdeporteid AS id, d.tbtipodeporteid, d.tbdeportenombre AS nombre,
                       d.tbdeporteimagenurl AS imagenUrl, d.tbdeporteactivo AS activo,
                       t.tbtipodeportenombre AS tipoNombre
                FROM tbdeporte d
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                ORDER BY t.tbtipodeportenombre ASC, d.tbdeportenombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItemAdmin($id)
    {
        $sql = "SELECT d.tbdeporteid AS id, d.tbtipodeporteid, d.tbdeportenombre AS nombre,
                       d.tbdeporteimagenurl AS imagenUrl, d.tbdeporteactivo AS activo,
                       t.tbtipodeportenombre AS tipoNombre
                FROM tbdeporte d
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                WHERE d.tbdeporteid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function existeNombre($nombre, $tipoId, $excluirId = null)
    {
        $sql = "SELECT tbdeporteid FROM tbdeporte
                WHERE LOWER(tbdeportenombre) = LOWER(:nombre)
                  AND tbtipodeporteid = :tipoId";
        if ($excluirId !== null) {
            $sql .= " AND tbdeporteid <> :excluirId";
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
        $sql = "INSERT INTO tbdeporte (tbtipodeporteid, tbdeportenombre, tbdeporteimagenurl)
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
        $sql = "UPDATE tbdeporte SET
                    tbtipodeporteid = :tipoId,
                    tbdeportenombre = :nombre,
                    tbdeporteimagenurl = :imagenUrl
                WHERE tbdeporteid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':tipoId', (int) $tipoId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':imagenUrl', $imagenUrl);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbdeporte SET tbdeporteactivo = NOT tbdeporteactivo WHERE tbdeporteid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function tieneSwipes($id)
    {
        $sql = "SELECT COUNT(*) AS total FROM tbdeporteswipe WHERE tbdeporteid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila && (int) $fila['total'] > 0;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM tbdeporte WHERE tbdeporteid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}