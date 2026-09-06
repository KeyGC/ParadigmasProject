<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/perfilmusicalmodelo.php';
require_once __DIR__ . '/conciertomodelo.php';
require_once __DIR__ . '/generomodelo.php';

class ConciertoPrioridadModelo
{
    private $conexion;

    private $pesoPatronMusical = 0.6;
    private $pesoAsistenciaReal = 0.4;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function obtenerSenalPatronMusical($perfilId)
    {
        $perfilMusicalModelo = new PerfilMusicalModelo();
        $perfilado = $perfilMusicalModelo->generarPerfilado($perfilId);

        $porGenero = [];
        if ($perfilado['exito']) {
            foreach ($perfilado['resultados'] as $r) {
                $confianza = $r['confianza'] / 100;
                $porGenero[$r['genero']] = max($porGenero[$r['genero']] ?? 0, $confianza);
            }
        }
        return $porGenero;
    }

    private function obtenerSenalAsistenciaReal($perfilId)
    {
        $conciertoModelo = new ConciertoModelo();
        $asistencias = $conciertoModelo->getGenerosAsistidosPorPerfil($perfilId);

        $totalAsistencias = array_sum(array_column($asistencias, 'asistencias'));

        $porGenero = [];
        foreach ($asistencias as $a) {
            $porGenero[$a['tbgeneronombre']] = $totalAsistencias > 0
                ? $a['asistencias'] / $totalAsistencias
                : 0;
        }
        return $porGenero;
    }

    public function generarPrioridad($perfilId)
    {
        $generoModelo = new GeneroModelo();
        $generosActivos = array_filter(
            $generoModelo->getList(),
            fn($g) => $g['tbgeneroestado'] == 1
        );

        $patronPorGenero = $this->obtenerSenalPatronMusical($perfilId);
        $asistenciaPorGenero = $this->obtenerSenalAsistenciaReal($perfilId);

        $prioridades = [];
        foreach ($generosActivos as $g) {
            $nombre = $g['tbgeneronombre'];

            $senalPatron = $patronPorGenero[$nombre] ?? 0;
            $senalAsistencia = $asistenciaPorGenero[$nombre] ?? 0;

            $score = ($senalPatron * $this->pesoPatronMusical)
                   + ($senalAsistencia * $this->pesoAsistenciaReal);

            $prioridades[] = [
                'tbgeneroid' => (int) $g['tbgeneroid'],
                'genero' => $nombre,
                'patronMusical' => round($senalPatron, 4),
                'asistenciaReal' => round($senalAsistencia, 4),
                'prioridad' => round($score, 4)
            ];
        }

        usort($prioridades, fn($a, $b) => $b['prioridad'] <=> $a['prioridad']);

        $vector = [];
        foreach ($prioridades as $p) {
            $vector[$p['tbgeneroid']] = $p['prioridad'];
        }

        return [
            'exito' => true,
            'perfilId' => (int) $perfilId,
            'prioridades' => $prioridades,
            'vector' => $vector
        ];
    }
}