<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/../Utilidades/feriados.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';
require_once __DIR__ . '/perfilaccesomodelo.php';

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Classifiers\MultilayerPerceptron;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\NeuralNet\Layers\Activation;
use Rubix\ML\NeuralNet\ActivationFunctions\ReLU;
use Rubix\ML\NeuralNet\Optimizers\Adam;

class PerfilMusicalModelo
{
    private $conexion;

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

    private $minimoEventos = 8;

    private $umbralConcentracion = 0.45;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function parsearData($data)
    {
        $lineas = [];
        $data = trim($data ?? '');
        if ($data === '') {
            return $lineas;
        }

        $filas = explode("\n", $data);
        foreach ($filas as $fila) {
            $partes = explode("|", trim($fila));
            if (count($partes) === 3) {
                $lineas[] = [
                    'semana' => (int) $partes[0],
                    'dia' => $partes[1],
                    'fecha' => $partes[2]
                ];
            }
        }
        return $lineas;
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
        $franjasPersonalizadas = $this->calcularFranjasPersonalizadas($perfilId);
        $this->guardarFranjas($perfilId, $franjasPersonalizadas);

        refrescarFeriados((int) date('Y'));

        $sql = "SELECT c.tbgeneroid, g.tbgeneronombre, s.tbreproduccionsemanaldata, r.tbreproducciontiempo
                FROM tbreproduccion r
                INNER JOIN tbcancion c ON r.tbcancionid = c.tbcancionid
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                INNER JOIN tbreproduccionsemanal s ON r.tbreproduccionsemanalid = s.tbreproduccionsemanalid
                WHERE r.tbperfilid = :perfilId AND r.tbreproduccionestado = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $eventos = [];
        foreach ($filas as $fila) {
            $lineas = $this->parsearData($fila['tbreproduccionsemanaldata']);
            $contador = count($lineas);

            $pesoPorEvento = $contador > 0 ? ($fila['tbreproducciontiempo'] / $contador) : 1;

            foreach ($lineas as $linea) {
                $hora = (int) date('H', strtotime($linea['fecha']));
                $franjasAUsar = esFeriado($linea['fecha']) ? $this->franjasHoliday : $franjasPersonalizadas;

                $eventos[] = [
                    'dia' => $linea['dia'],
                    'franja' => $this->clasificarHora($hora, $franjasAUsar),
                    'genero' => $fila['tbgeneronombre'],
                    'peso' => $pesoPorEvento > 0 ? $pesoPorEvento : 1
                ];
            }
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
                    'texto' => "Me gusta escuchar {$genero} los {$this->diasNombreCompleto[$dia]} en {$this->franjasNombre[$franja]}"
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
                'texto' => "Me gusta escuchar {$genero} en {$this->franjasNombre[$franja]}, sin importar el día"
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
                'texto' => "Me gusta escuchar {$genero} los {$this->diasNombreCompleto[$dia]}, sin importar la hora"
            ];
        }
        return $candidatos;
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
                'totalEventos' => $punto['payload']['totalEventos'] ?? 0,
                'resultados' => $punto['payload']['resultados']
            ];
        }

        $eventos = $this->obtenerEventos($perfilId);
        $totalEventos = count($eventos);

        if ($totalEventos < $this->minimoEventos) {
            return [
                'exito' => false,
                'mensaje' => "No hay suficientes datos para generar un perfilado confiable. Se necesitan al menos {$this->minimoEventos} reproducciones registradas (hay {$totalEventos}).",
                'totalEventos' => $totalEventos
            ];
        }

        $sumaTiempos = array_sum(array_column($eventos, 'peso'));
        $usarPesoUniforme = $sumaTiempos <= 0;
        $pesoPromedio = $usarPesoUniforme ? 1 : ($sumaTiempos / $totalEventos);

        $samples = [];
        $labels = [];
        $conteoPorCombo = [];
        $pesoPorCombo = [];
        $pesoPorGenero = [];
        $pesoTotalGeneral = 0;

        foreach ($eventos as $evento) {
            $peso = $usarPesoUniforme ? 1 : $evento['peso'];

            $repeticiones = max(1, min(5, (int) round($peso / $pesoPromedio)));
            for ($i = 0; $i < $repeticiones; $i++) {
                $samples[] = $this->vectorizar($evento['dia'], $evento['franja']);
                $labels[] = $evento['genero'];
            }

            $clave = $evento['dia'] . '|' . $evento['franja'];
            $conteoPorCombo[$clave] = ($conteoPorCombo[$clave] ?? 0) + 1;
            $pesoPorCombo[$clave] = ($pesoPorCombo[$clave] ?? 0) + $peso;

            $pesoPorGenero[$evento['genero']] = ($pesoPorGenero[$evento['genero']] ?? 0) + $peso;
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

        try {
            $estimator->train($dataset);
        } catch (\Throwable $e) {
            return [
                'exito' => false,
                'mensaje' => "Error al entrenar el modelo: " . $e->getMessage(),
                'totalEventos' => $totalEventos
            ];
        }

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

        $priorGeneros = $this->calcularPriorGeneros($pesoPorGenero, $pesoTotalGeneral);

        foreach ($unicos as &$c) {
            $c['confianza'] = $this->ajustarConfianza($c['confianza'], $c['soporte'], $c['genero'], $priorGeneros);
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

        $vector = $this->construirVectorPerfil($probPorCombo, $pesoPorCombo);

        if (!$qdrant->crearColeccionSiNoExiste(count($vector))) {
            error_log("Qdrant: no se pudo garantizar la colección perfiles");
        }

        $guardado = $qdrant->guardarVector((int) $perfilId, $vector, $hashdatos, [
            'resultados' => $top3,
            'totalEventos' => $totalEventos
        ]);

        if (!$guardado) {
            error_log("Qdrant: no se pudo guardar el vector del perfil {$perfilId}");
        }

        return [
            'exito' => true,
            'totalEventos' => $totalEventos,
            'resultados' => $top3
        ];
    }

    protected function crearQdrantCliente()
    {
        return new QdrantCliente();
    }

    protected function calcularHashDatos($perfilId)
    {
        $sql = "SELECT r.tbcancionid, r.tbreproducciontiempo, r.tbreproduccionestado,
                       s.tbreproduccionsemanaldata
                FROM tbreproduccion r
                INNER JOIN tbreproduccionsemanal s ON r.tbreproduccionsemanalid = s.tbreproduccionsemanalid
                WHERE r.tbperfilid = :perfilId
                ORDER BY r.tbreproduccionid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return hash('sha256', serialize($stmt->fetchAll()));
    }

    private function construirVectorPerfil(array $probPorCombo, array $pesoPorCombo)
    {
        $sql = "SELECT tbgeneroid, tbgeneronombre FROM tbgenero WHERE tbgeneroestado = 1 ORDER BY tbgeneroid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $generosActivos = $stmt->fetchAll();

        $indice = [];
        $vector = [];
        foreach ($generosActivos as $i => $genero) {
            $indice[$genero['tbgeneronombre']] = (int) $i;
            $vector[$i] = 0.0;
        }

        foreach ($probPorCombo as $clave => $probs) {
            $pesoCombo = $pesoPorCombo[$clave] ?? 1.0;
            foreach ($probs as $genero => $probabilidad) {
                if (isset($indice[$genero])) {
                    $vector[$indice[$genero]] += $probabilidad * $pesoCombo;
                }
            }
        }

        $norma = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        if ($norma > 0) {
            $vector = array_map(fn($v) => $v / $norma, $vector);
        } else {
            $vector[0] = 1.0;
        }

        return array_values($vector);
    }

    private function calcularPriorGeneros($pesoPorGenero, $pesoTotalGeneral)
    {
        $prior = [];
        foreach ($pesoPorGenero as $genero => $peso) {
            $prior[$genero] = $pesoTotalGeneral > 0 ? $peso / $pesoTotalGeneral : 0;
        }
        return $prior;
    }

    private function ajustarConfianza($confianza, $soporte, $genero, $priorGeneros, $k = 3)
    {
        $prior = $priorGeneros[$genero] ?? 0;
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

    private function guardarFranjas($perfilId, $franjas)
    {
        $ahora = date('Y-m-d H:i:s');
        $sql = "INSERT INTO tbperfilfranjas
                (tbperfilid, tbperfilfranjasmadrugadainicio, tbperfilfranjasmananainicio,
                tbperfilfranjastardeinicio, tbperfilfranjasnocheinicio, tbperfilfranjasfechacalculo)
                VALUES (:perfilId, :madrugada, :manana, :tarde, :noche, :ahora)
                ON DUPLICATE KEY UPDATE
                    tbperfilfranjasmadrugadainicio = :madrugada2,
                    tbperfilfranjasmananainicio = :manana2,
                    tbperfilfranjastardeinicio = :tarde2,
                    tbperfilfranjasnocheinicio = :noche2,
                    tbperfilfranjasfechacalculo = :ahora2";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':madrugada', $franjas['madrugada'], PDO::PARAM_INT);
        $stmt->bindValue(':manana', $franjas['manana'], PDO::PARAM_INT);
        $stmt->bindValue(':tarde', $franjas['tarde'], PDO::PARAM_INT);
        $stmt->bindValue(':noche', $franjas['noche'], PDO::PARAM_INT);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->bindValue(':madrugada2', $franjas['madrugada'], PDO::PARAM_INT);
        $stmt->bindValue(':manana2', $franjas['manana'], PDO::PARAM_INT);
        $stmt->bindValue(':tarde2', $franjas['tarde'], PDO::PARAM_INT);
        $stmt->bindValue(':noche2', $franjas['noche'], PDO::PARAM_INT);
        $stmt->bindValue(':ahora2', $ahora);
        $stmt->execute();
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
}

