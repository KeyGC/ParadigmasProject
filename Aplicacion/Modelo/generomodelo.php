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
}