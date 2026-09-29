<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';
require_once __DIR__ . '/../Utilidades/feriados.php';
require_once __DIR__ . '/perfilmodelo.php';
require_once __DIR__ . '/perfilaccesomodelo.php';

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Classifiers\MultilayerPerceptron;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\NeuralNet\Layers\Activation;
use Rubix\ML\NeuralNet\ActivationFunctions\ReLU;
use Rubix\ML\NeuralNet\Optimizers\Adam;

class PerfilDeporteModelo
{
    private $conexion;

    private $coleccion = 'deporte';

    private $minimoSwipes = 8;

    private $minimoEventos = 8;

    private $diasSemana = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];

    private $diasNombreCompleto = [
        'Lun' => 'lunes', 'Mar' => 'martes', 'Mie' => 'miércoles',
        'Jue' => 'jueves', 'Vie' => 'viernes', 'Sab' => 'sábado', 'Dom' => 'domingo'
    ];

    private $franjas = ['Madrugada', 'Manana', 'Tarde', 'Noche'];

    private $franjasNombre = [
        'Madrugada' => 'la madrugada', 'Manana' => 'la mañana',
        'Tarde' => 'la tarde', 'Noche' => 'la noche'
    ];

    private $franjasHoliday = ['madrugada' => 2, 'manana' => 8, 'tarde' => 14, 'noche' => 20];

    private $franjasDefault = ['madrugada' => 0, 'manana' => 6, 'tarde' => 12, 'noche' => 18];

    private $umbralConcentracion = 0.45;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    protected function crearQdrantCliente()
    {
        return new QdrantCliente(null, null, $this->coleccion);
    }

    protected function calcularHashDatos($perfilId)
    {
        $sql = "SELECT tbdeporteid, tbdeporteswipelike, tbdeporteswipefecha
                FROM tbdeporteswipe
                WHERE tbperfilid = :perfilId
                ORDER BY tbdeporteswipeid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return hash('sha256', serialize($stmt->fetchAll()));
    }

    private function obtenerConteoPorTipo($perfilId)
    {
        $sql = "SELECT d.tbtipodeporteid, t.tbtipodeportenombre,
                       SUM(s.tbdeporteswipelike) AS likes,
                       COUNT(*) AS total
                FROM tbdeporteswipe s
                INNER JOIN tbdeporte d ON s.tbdeporteid = d.tbdeporteid
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                WHERE s.tbperfilid = :perfilId
                GROUP BY d.tbtipodeporteid, t.tbtipodeportenombre
                ORDER BY d.tbtipodeporteid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function construirVectorPerfil(array $conteoPorTipo, $totalLikes)
    {
        $sql = "SELECT tbtipodeporteid FROM tbtipodeporte WHERE tbtipodeporteestado = 1 ORDER BY tbtipodeporteid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $tipos = $stmt->fetchAll();

        $likesPorTipo = [];
        foreach ($conteoPorTipo as $fila) {
            $likesPorTipo[(int) $fila['tbtipodeporteid']] = (int) $fila['likes'];
        }

        $vector = [];
        foreach ($tipos as $tipo) {
            $vector[] = $totalLikes > 0 ? ($likesPorTipo[(int) $tipo['tbtipodeporteid']] ?? 0) / $totalLikes : 0.0;
        }

        $norma = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        if ($norma > 0) {
            $vector = array_map(fn($v) => $v / $norma, $vector);
        }

        return array_values($vector);
    }

    private function vectorizar($dia, $franja)
    {
        $vector = [];
        foreach ($this->diasSemana as $d) {
            $vector[] = ($d === $dia) ? 1.0 : 0.0;
        }
        foreach ($this->franjas as $f) {
            $vector[] = ($f === $franja) ? 1.0 : 0.0;
        }
        return $vector;
    }

    private function obtenerEventos($perfilId)
    {
        $franjasPorPerfil = $this->calcularFranjasPersonalizadas($perfilId);

        refrescarFeriados((int) date('Y'));

        $sql = "SELECT d.tbtipodeporteid, t.tbtipodeportenombre, s.tbdeporteswipefecha
                FROM tbdeporteswipe s
                INNER JOIN tbdeporte d ON s.tbdeporteid = d.tbdeporteid
                INNER JOIN tbtipodeporte t ON d.tbtipodeporteid = t.tbtipodeporteid
                WHERE s.tbperfilid = :perfilId AND s.tbdeporteswipelike = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $eventos = [];
        foreach ($filas as $fila) {
            $fecha = $fila['tbdeporteswipefecha'];
            $dia = $this->diasSemana[(int) date('N', strtotime($fecha)) - 1] ?? 'Lun';
            $hora = (int) date('H', strtotime($fecha));
            $franjasAUsar = esFeriado($fecha) ? $this->franjasHoliday : $franjasPorPerfil;

            $eventos[] = [
                'dia' => $dia,
                'franja' => $this->clasificarHora($hora, $franjasAUsar),
                'tipo' => $fila['tbtipodeportenombre'],
                'peso' => 1
            ];
        }
        return $eventos;
    }

    private function evaluarGrid($estimator)
    {
        $combos = [];
        $samplesGrid = [];
        foreach ($this->diasSemana as $dia) {
            foreach ($this->franjas as $franja) {
                $combos[] = ['dia' => $dia, 'franja' => $franja];
                $samplesGrid[] = $this->vectorizar($dia, $franja);
            }
        }

        $gridDataset = new Unlabeled($samplesGrid);
        $probabilidades = $estimator->proba($gridDataset);

        $probPorCombo = [];
        foreach ($combos as $i => $combo) {
            $clave = $combo['dia'] . '|' . $combo['franja'];
            $probPorCombo[$clave] = $probabilidades[$i];
        }
        return $probPorCombo;
    }

    private function generarCandidatosEspecificos($probPorCombo, $conteoPorCombo, $pesoPorCombo)
    {
        $candidatos = [];
        foreach ($this->diasSemana as $dia) {
            foreach ($this->franjas as $franja) {
                $clave = $dia . '|' . $franja;
                $conteo = $conteoPorCombo[$clave] ?? 0;
                if ($conteo < 1) continue;

                $probs = $probPorCombo[$clave];
                arsort($probs);
                $genero = array_key_first($probs);
                $confianza = $probs[$genero];

                $candidatos[] = [
                    'tipo' => 'especifico',
                    'dia' => $dia,
                    'franja' => $franja,
                    'genero' => $genero,
                    'confianza' => $confianza,
                    'soporte' => $pesoPorCombo[$clave] ?? 0,
                    'texto' => "Prefiero practicar {$genero} los {$this->diasNombreCompleto[$dia]} en {$this->franjasNombre[$franja]}"
                ];
            }
        }
        return $candidatos;
    }

    private function generarCandidatosPorFranja($probPorCombo, $conteoPorCombo, $pesoPorCombo, $especificos)
    {
        $candidatos = [];
        foreach ($this->franjas as $franja) {
            $pesoTotal = 0;
            $acumulado = [];
            $pesoPorDia = [];

            foreach ($this->diasSemana as $dia) {
                $clave = $dia . '|' . $franja;
                $conteo = $conteoPorCombo[$clave] ?? 0;
                if ($conteo < 1) continue;

                $peso = $pesoPorCombo[$clave] ?? 0;
                $pesoPorDia[$dia] = $peso;
                $pesoTotal += $peso;

                foreach ($probPorCombo[$clave] as $genero => $prob) {
                    $acumulado[$genero] = ($acumulado[$genero] ?? 0) + $prob * $peso;
                }
            }

            if ($pesoTotal <= 0) continue;

            foreach ($acumulado as $genero => $suma) {
                $acumulado[$genero] = $suma / $pesoTotal;
            }
            arsort($acumulado);
            $genero = array_key_first($acumulado);
            $confianza = $acumulado[$genero];

            $pesoMaxDia = !empty($pesoPorDia) ? max($pesoPorDia) : 0;
            $concentracion = $pesoTotal > 0 ? $pesoMaxDia / $pesoTotal : 0;

            if ($concentracion >= $this->umbralConcentracion) {
                $diaConcentrado = array_search($pesoMaxDia, $pesoPorDia);
                foreach ($especificos as $c) {
                    if ($c['dia'] === $diaConcentrado && $c['franja'] === $franja) {
                        $candidatos[] = $c;
                        break;
                    }
                }
                continue;
            }

            $candidatos[] = [
                'tipo' => 'franja',
                'dia' => null,
                'franja' => $franja,
                'genero' => $genero,
                'confianza' => $confianza,
                'soporte' => $pesoTotal,
                'texto' => "Prefiero practicar {$genero} en {$this->franjasNombre[$franja]}, sin importar el día"
            ];
        }
        return $candidatos;
    }

    private function generarCandidatosPorDia($probPorCombo, $conteoPorCombo, $pesoPorCombo, $especificos)
    {
        $candidatos = [];
        foreach ($this->diasSemana as $dia) {
            $pesoTotal = 0;
            $acumulado = [];
            $pesoPorFranja = [];

            foreach ($this->franjas as $franja) {
                $clave = $dia . '|' . $franja;
                $conteo = $conteoPorCombo[$clave] ?? 0;
                if ($conteo < 1) continue;

                $peso = $pesoPorCombo[$clave] ?? 0;
                $pesoPorFranja[$franja] = $peso;
                $pesoTotal += $peso;

                foreach ($probPorCombo[$clave] as $genero => $prob) {
                    $acumulado[$genero] = ($acumulado[$genero] ?? 0) + $prob * $peso;
                }
            }

            if ($pesoTotal <= 0) continue;

            foreach ($acumulado as $genero => $suma) {
                $acumulado[$genero] = $suma / $pesoTotal;
            }
            arsort($acumulado);
            $genero = array_key_first($acumulado);
            $confianza = $acumulado[$genero];

            $pesoMaxFranja = !empty($pesoPorFranja) ? max($pesoPorFranja) : 0;
            $concentracion = $pesoTotal > 0 ? $pesoMaxFranja / $pesoTotal : 0;

            if ($concentracion >= $this->umbralConcentracion) {
                $franjaConcentrada = array_search($pesoMaxFranja, $pesoPorFranja);
                foreach ($especificos as $c) {
                    if ($c['dia'] === $dia && $c['franja'] === $franjaConcentrada) {
                        $candidatos[] = $c;
                        break;
                    }
                }
                continue;
            }

            $candidatos[] = [
                'tipo' => 'dia',
                'dia' => $dia,
                'franja' => null,
                'genero' => $genero,
                'confianza' => $confianza,
                'soporte' => $pesoTotal,
                'texto' => "Prefiero practicar {$genero} los {$this->diasNombreCompleto[$dia]}, sin importar la hora"
            ];
        }
        return $candidatos;
    }

    private function entrenarInsights(array $eventos)
    {
        $totalEventos = count($eventos);

        $sumaPesos = array_sum(array_column($eventos, 'peso'));
        $usarPesoUniforme = $sumaPesos <= 0;
        $pesoPromedio = $usarPesoUniforme ? 1 : ($sumaPesos / $totalEventos);

        $samples = [];
        $labels = [];
        $conteoPorCombo = [];
        $pesoPorCombo = [];
        $pesoPorTipo = [];
        $pesoTotalGeneral = 0;

        foreach ($eventos as $evento) {
            $peso = $usarPesoUniforme ? 1 : $evento['peso'];

            $repeticiones = max(1, min(5, (int) round($peso / $pesoPromedio)));
            for ($i = 0; $i < $repeticiones; $i++) {
                $samples[] = $this->vectorizar($evento['dia'], $evento['franja']);
                $labels[] = $evento['tipo'];
            }

            $clave = $evento['dia'] . '|' . $evento['franja'];
            $conteoPorCombo[$clave] = ($conteoPorCombo[$clave] ?? 0) + 1;
            $pesoPorCombo[$clave] = ($pesoPorCombo[$clave] ?? 0) + $peso;

            $pesoPorTipo[$evento['tipo']] = ($pesoPorTipo[$evento['tipo']] ?? 0) + $peso;
            $pesoTotalGeneral += $peso;
        }

        $dataset = new Labeled($samples, $labels);

        $holdOut = count($samples) >= 30 ? 0.1 : 0.0;

        srand(42);
        mt_srand(42);

        $estimator = new MultilayerPerceptron(
            [
                new Dense(16),
                new Activation(new ReLU()),
                new Dense(8),
                new Activation(new ReLU()),
            ],
            16,
            new Adam(0.001),
            1e-4,
            200,
            1e-4,
            3,
            $holdOut
        );

        $estimator->train($dataset);

        $probPorCombo = $this->evaluarGrid($estimator);

        $especificos = $this->generarCandidatosEspecificos($probPorCombo, $conteoPorCombo, $pesoPorCombo);
        $porFranja = $this->generarCandidatosPorFranja($probPorCombo, $conteoPorCombo, $pesoPorCombo, $especificos);
        $porDia = $this->generarCandidatosPorDia($probPorCombo, $conteoPorCombo, $pesoPorCombo, $especificos);

        $todos = array_merge($especificos, $porFranja, $porDia);

        $vistos = [];
        $unicos = [];
        foreach ($todos as $c) {
            $clave = $c['tipo'] . '|' . $c['dia'] . '|' . $c['franja'] . '|' . $c['genero'];
            if (isset($vistos[$clave])) continue;
            $vistos[$clave] = true;
            $unicos[] = $c;
        }

        $priorTipos = $this->calcularPriorTipos($pesoPorTipo, $pesoTotalGeneral);

        foreach ($unicos as &$c) {
            $c['confianza'] = $this->ajustarConfianza($c['confianza'], $c['soporte'], $c['genero'], $priorTipos);
            $c['score'] = $c['confianza'] * (1 + log(1 + $c['soporte']));
        }
        unset($c);

        usort($unicos, fn($a, $b) => $b['score'] <=> $a['score']);

        $top3 = array_slice($unicos, 0, 3);

        foreach ($top3 as &$r) {
            $r['confianza'] = round($r['confianza'] * 100, 1);
            unset($r['score']);
        }
        unset($r);

        return $top3;
    }

    private function calcularPriorTipos($pesoPorTipo, $pesoTotalGeneral)
    {
        $prior = [];
        foreach ($pesoPorTipo as $tipo => $peso) {
            $prior[$tipo] = $pesoTotalGeneral > 0 ? $peso / $pesoTotalGeneral : 0;
        }
        return $prior;
    }

    private function ajustarConfianza($confianza, $soporte, $genero, $priorTipos, $k = 3)
    {
        $prior = $priorTipos[$genero] ?? 0;
        return (($confianza * $soporte) + ($prior * $k)) / ($soporte + $k);
    }

    private function calcularFranjasPersonalizadas($perfilId)
    {
        $accesoModelo = new PerfilAccesoModelo();
        $fechas = $accesoModelo->getFechasAcceso($perfilId);

        if (count($fechas) < 6) {
            return $this->franjasDefault;
        }

        $timestamps = array_map('strtotime', $fechas);
        sort($timestamps);

        $sueños = [];
        for ($i = 0; $i < count($timestamps) - 1; $i++) {
            $gapHoras = ($timestamps[$i + 1] - $timestamps[$i]) / 3600;

            if ($gapHoras >= 6 && $gapHoras <= 11) {
                $sueños[] = [
                    'inicio' => (int) date('G', $timestamps[$i]),
                    'fin' => (int) date('G', $timestamps[$i + 1])
                ];
            }
        }

        if (count($sueños) < 3) {
            return $this->franjasDefault;
        }

        $promedioInicio = round(array_sum(array_column($sueños, 'inicio')) / count($sueños));
        $promedioFin = round(array_sum(array_column($sueños, 'fin')) / count($sueños));

        $despertar = $promedioFin % 24;
        $dormir = $promedioInicio % 24;

        return [
            'madrugada' => $dormir,
            'manana' => $despertar,
            'tarde' => ($despertar + 6) % 24,
            'noche' => ($despertar + 12) % 24
        ];
    }

    private function clasificarHora($hora, $franjas)
    {
        $orden = [
            'Madrugada' => $franjas['madrugada'],
            'Manana' => $franjas['manana'],
            'Tarde' => $franjas['tarde'],
            'Noche' => $franjas['noche']
        ];

        asort($orden);
        $nombres = array_keys($orden);
        $inicios = array_values($orden);

        $resultado = end($nombres);
        for ($i = 0; $i < count($inicios); $i++) {
            if ($hora >= $inicios[$i]) {
                $resultado = $nombres[$i];
            }
        }
        return $resultado;
    }

    public function generarPerfilado($perfilId)
    {
        $qdrant = $this->crearQdrantCliente();
        $hashdatos = $this->calcularHashDatos($perfilId);

        $punto = $qdrant->obtenerPunto((int) $perfilId);
        if ($punto !== null
            && isset($punto['payload']['hashdatos'], $punto['payload']['resultados'])
            && $punto['payload']['hashdatos'] === $hashdatos) {
            return [
                'exito' => true,
                'desdeCache' => true,
                'totalSwipes' => (int) ($punto['payload']['totalSwipes'] ?? 0),
                'modoResultado' => $punto['payload']['modoResultado'] ?? 'basico',
                'resultados' => $punto['payload']['resultados']
            ];
        }

        $conteoPorTipo = $this->obtenerConteoPorTipo($perfilId);
        $totalSwipes = (int) array_sum(array_column($conteoPorTipo, 'total'));
        $totalLikes = (int) array_sum(array_column($conteoPorTipo, 'likes'));

        if ($totalSwipes < $this->minimoSwipes) {
            return [
                'exito' => false,
                'mensaje' => "Necesitas al menos {$this->minimoSwipes} swipes para generar tu perfil de deporte (llevas {$totalSwipes}).",
                'totalSwipes' => $totalSwipes
            ];
        }

        if ($totalLikes <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'No diste "me gusta" a ningún deporte, así que todavía no hay perfil que calcular.',
                'totalSwipes' => $totalSwipes
            ];
        }

        $eventos = $this->obtenerEventos($perfilId);
        $modoResultado = count($eventos) >= $this->minimoEventos ? 'red' : 'basico';

        if ($modoResultado === 'basico') {
            usort($conteoPorTipo, fn($a, $b) => (int) $b['likes'] <=> (int) $a['likes']);

            $resultados = [];
            foreach (array_slice($conteoPorTipo, 0, 2) as $fila) {
                $resultados[] = [
                    'tipo' => $fila['tbtipodeportenombre'],
                    'porcentaje' => round(((int) $fila['likes'] / $totalLikes) * 100, 1)
                ];
            }
        } else {
            try {
                $resultados = $this->entrenarInsights($eventos);
            } catch (\Throwable $e) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se pudo entrenar el modelo de deporte: ' . $e->getMessage(),
                    'totalSwipes' => $totalSwipes
                ];
            }
        }

        $vector = $this->construirVectorPerfil($conteoPorTipo, $totalLikes);

        if (!$qdrant->crearColeccionSiNoExiste(count($vector))) {
            error_log("Qdrant: no se pudo garantizar la colección {$this->coleccion}");
        }

        $guardado = $qdrant->guardarVector((int) $perfilId, $vector, $hashdatos, [
            'resultados' => $resultados,
            'totalSwipes' => $totalSwipes,
            'modoResultado' => $modoResultado
        ]);

        if (!$guardado) {
            error_log("Qdrant: no se pudo guardar el vector de deporte del perfil {$perfilId}");
        }

        return [
            'exito' => true,
            'totalSwipes' => $totalSwipes,
            'modoResultado' => $modoResultado,
            'resultados' => $resultados
        ];
    }

    public function buscarCoincidencias($perfilId)
    {
        $qdrant = $this->crearQdrantCliente();
        $punto = $qdrant->obtenerPunto((int) $perfilId);

        if ($punto === null || !isset($punto['vector'])) {
            return [
                'exito' => false,
                'mensaje' => 'No tienes un perfil de deporte calculado. Juega el mini-juego y presiona "Ver mi resultado" primero.'
            ];
        }

        $similares = $qdrant->buscarSimilares($punto['vector'], 5);

        if ($similares === null) {
            return ['exito' => false, 'mensaje' => 'No se pudo consultar Qdrant (servicio no disponible)'];
        }

        $ids = array_values(array_unique(array_map('intval', array_column($similares, 'tbperfilid'))));
        $ids = array_values(array_filter($ids, fn($id) => $id !== (int) $perfilId));

        $perfilModelo = new PerfilModelo();
        $coincidencias = $perfilModelo->getCoincidencias($ids, (int) $perfilId);

        $scorePorId = [];
        foreach ($similares as $s) {
            $scorePorId[(int) $s['tbperfilid']] = $s['score'];
        }

        foreach ($coincidencias as &$c) {
            $c['score'] = $scorePorId[(int) $c['tbperfilid']] ?? 0.0;
        }
        unset($c);

        usort($coincidencias, fn($a, $b) => $b['score'] <=> $a['score']);

        return ['exito' => true, 'data' => $coincidencias];
    }
}