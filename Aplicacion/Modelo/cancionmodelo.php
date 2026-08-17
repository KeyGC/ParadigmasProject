<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/cancion.php';

class CancionModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function mapearFila($fila)
    {
        return (new Cancion(
            $fila['tbcancionid'],
            $fila['tbgeneroid'],
            $fila['tbcancionnombre'],
            $fila['tbcancionartista'],
            $fila['tbcancionurl'],
            $fila['tbcancionactivo']
        ))->toArray();
    }

    public function getList()
    {
        $sql = "SELECT c.* FROM tbcancion c
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                WHERE c.tbcancionactivo = 1 AND g.tbgeneroestado = 1
                ORDER BY c.tbcancionnombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $canciones = [];
        foreach ($filas as $fila) {
            $canciones[] = $this->mapearFila($fila);
        }
        return $canciones;
    }

    public function getPorGenero($generoId)
    {
        $sql = "SELECT c.* FROM tbcancion c
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                WHERE c.tbgeneroid = :generoId AND c.tbcancionactivo = 1 AND g.tbgeneroestado = 1
                ORDER BY c.tbcancionnombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $generoId, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $canciones = [];
        foreach ($filas as $fila) {
            $canciones[] = $this->mapearFila($fila);
        }
        return $canciones;
    }

    public function getListAdmin()
    {
        $sql = "SELECT c.*, g.tbgeneronombre FROM tbcancion c
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                ORDER BY c.tbcancionnombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $canciones = [];
        foreach ($filas as $fila) {
            $cancion = $this->mapearFila($fila);
            $cancion['tbgeneronombre'] = $fila['tbgeneronombre'];
            $canciones[] = $cancion;
        }
        return $canciones;
    }

    public function getCancion($id)
    {
        $sql = "SELECT * FROM tbcancion WHERE tbcancionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? $this->mapearFila($fila) : null;
    }

    public function insert(Cancion $cancion)
    {
        $sql = "INSERT INTO tbcancion (tbgeneroid, tbcancionnombre, tbcancionartista, tbcancionurl)
                VALUES (:generoId, :nombre, :artista, :url)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $cancion->get_tbgeneroid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $cancion->get_tbcancionnombre());
        $stmt->bindValue(':artista', $cancion->get_tbcancionartista());
        $stmt->bindValue(':url', $cancion->get_tbcancionurl());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function update(Cancion $cancion)
    {
        $sql = "UPDATE tbcancion SET
                    tbgeneroid = :generoId,
                    tbcancionnombre = :nombre,
                    tbcancionartista = :artista,
                    tbcancionurl = :url
                WHERE tbcancionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':generoId', $cancion->get_tbgeneroid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $cancion->get_tbcancionnombre());
        $stmt->bindValue(':artista', $cancion->get_tbcancionartista());
        $stmt->bindValue(':url', $cancion->get_tbcancionurl());
        $stmt->bindValue(':id', $cancion->get_tbcancionid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbcancion SET tbcancionactivo = NOT tbcancionactivo WHERE tbcancionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}