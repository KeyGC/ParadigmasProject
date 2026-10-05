<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class ComunidadModelo
{
    private $conexion;

    private $categorias = ['genero', 'comida', 'deporte'];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function mapear($fila)
    {
        return [
            'tbcomunidadid' => (int) $fila['tbcomunidadid'],
            'tbcomunidadcategoria' => $fila['tbcomunidadcategoria'],
            'tbcomunidadreferenciaid' => (int) $fila['tbcomunidadreferenciaid'],
            'tbcomunidadnombre' => $fila['tbcomunidadnombre'],
            'tbcomunidadestado' => (bool) $fila['tbcomunidadestado']
        ];
    }

    public function getComunidadesActivas()
    {
        $sql = "SELECT tbcomunidadid, tbcomunidadcategoria, tbcomunidadreferenciaid,
                       tbcomunidadnombre, tbcomunidadestado
                FROM tbcomunidad
                WHERE tbcomunidadestado = TRUE
                ORDER BY tbcomunidadcategoria ASC, tbcomunidadnombre ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        $comunidades = [];
        foreach ($stmt->fetchAll() as $fila) {
            $comunidades[] = $this->mapear($fila);
        }

        // Se agrupa en el orden del proyecto: musica, comida y deporte
        $agrupadas = [];
        foreach ($this->categorias as $categoria) {
            $agrupadas[$categoria] = [];
        }

        foreach ($comunidades as $comunidad) {
            $categoria = $comunidad['tbcomunidadcategoria'];
            if (!isset($agrupadas[$categoria])) {
                $agrupadas[$categoria] = [];
            }
            $agrupadas[$categoria][] = $comunidad;
        }

        return ['exito' => true, 'data' => $agrupadas];
    }

    public function getComunidadPorId($comunidadId)
    {
        $comunidadId = (int) $comunidadId;

        if ($comunidadId <= 0) {
            return ['exito' => false, 'mensaje' => 'Comunidad inválida.'];
        }

        $sql = "SELECT tbcomunidadid, tbcomunidadcategoria, tbcomunidadreferenciaid,
                       tbcomunidadnombre, tbcomunidadestado
                FROM tbcomunidad
                WHERE tbcomunidadid = :id AND tbcomunidadestado = TRUE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $comunidadId, PDO::PARAM_INT);
        $stmt->execute();

        $fila = $stmt->fetch();

        if (!$fila) {
            return ['exito' => false, 'mensaje' => 'La comunidad no existe o está inactiva.'];
        }

        return ['exito' => true, 'data' => $this->mapear($fila)];
    }
}