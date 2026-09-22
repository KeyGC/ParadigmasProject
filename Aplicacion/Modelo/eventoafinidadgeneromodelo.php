<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/perfilmusicalmodelo.php';
require_once __DIR__ . '/eventomodelo.php';
require_once __DIR__ . '/generomodelo.php';

class EventoAfinidadGeneroModelo
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
        $eventoModelo = new EventoModelo();
        $asistencias = $eventoModelo->getGenerosAsistidosPorPerfil($perfilId);

        $totalAsistencias = array_sum(array_column($asistencias, 'asistencias'));

        $porGenero = [];
        foreach ($asistencias as $a) {
            $porGenero[$a['tbgeneronombre']] = $totalAsistencias > 0
                ? $a['asistencias'] / $totalAsistencias
                : 0;
        }
        return $porGenero;
    }

    public function generarAfinidadGenero($perfilId)
    {
        $generoModelo = new GeneroModelo();
        $generosActivos = array_filter(
            $generoModelo->getList(),
            fn($g) => $g['tbgeneroestado'] == 1
        );

        $patronPorGenero = $this->obtenerSenalPatronMusical($perfilId);
        $asistenciaPorGenero = $this->obtenerSenalAsistenciaReal($perfilId);

        $afinidades = [];
        foreach ($generosActivos as $g) {
            $nombre = $g['tbgeneronombre'];

            $senalPatron = $patronPorGenero[$nombre] ?? 0;
            $senalAsistencia = $asistenciaPorGenero[$nombre] ?? 0;

            $score = ($senalPatron * $this->pesoPatronMusical)
                   + ($senalAsistencia * $this->pesoAsistenciaReal);

            $afinidades[] = [
                'tbgeneroid' => (int) $g['tbgeneroid'],
                'genero' => $nombre,
                'patronMusical' => round($senalPatron, 4),
                'asistenciaReal' => round($senalAsistencia, 4),
                'afinidad' => round($score, 4)
            ];
        }

        usort($afinidades, fn($a, $b) => $b['afinidad'] <=> $a['afinidad']);

        $vector = [];
        foreach ($afinidades as $a) {
            $vector[$a['tbgeneroid']] = $a['afinidad'];
        }

        return [
            'exito' => true,
            'perfilId' => (int) $perfilId,
            'tipoAfinidad' => 'genero',
            'afinidades' => $afinidades,
            'vector' => $vector
        ];
    }
}