<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/genero.php';

class GeneroModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function mapearFila($fila)
    {
        return (new Genero($fila['tbgeneroid'], $fila['tbgeneronombre']))->toArray();
    }

    public function getList()
    {
        $sql = "SELECT * FROM tbgenero ORDER BY tbgeneronombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $generos = [];
        foreach ($filas as $fila) {
            $generos[] = $this->mapearFila($fila);
        }
        return $generos;
    }

    public function getGenero($id)
    {
        $sql = "SELECT * FROM tbgenero WHERE tbgeneroid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? $this->mapearFila($fila) : null;
    }

    public function getGeneroByNombre($nombre)
    {
        $sql = "SELECT * FROM tbgenero WHERE tbgeneronombre = :nombre";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? $this->mapearFila($fila) : null;
    }

    public function insert(Genero $genero)
    {
        $sql = "INSERT INTO tbgenero (tbgeneronombre) VALUES (:nombre)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $genero->get_tbgeneronombre());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function update(Genero $genero)
    {
        $sql = "UPDATE tbgenero SET tbgeneronombre = :nombre WHERE tbgeneroid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $genero->get_tbgeneronombre());
        $stmt->bindValue(':id', $genero->get_tbgeneroid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbgenero SET tbgeneroestado = NOT tbgeneroestado WHERE tbgeneroid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}