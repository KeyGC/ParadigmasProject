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
        $sql = "SELECT * FROM tbcancion WHERE tbcancionactivo = 1 ORDER BY tbcancionnombre ASC";
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
        $sql = "SELECT * FROM tbcancion WHERE tbgeneroid = :generoId AND tbcancionactivo = 1 ORDER BY tbcancionnombre ASC";
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
}