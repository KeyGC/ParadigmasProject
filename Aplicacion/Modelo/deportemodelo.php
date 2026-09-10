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
}