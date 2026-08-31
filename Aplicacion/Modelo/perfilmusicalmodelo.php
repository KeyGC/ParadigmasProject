<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

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

    private $minimoEventos = 8;

    // Si un día (o franja) concentra esta proporción o más del total, se considera un patrón "específico"
    // en vez de un patrón general. 0.45 = mucho más que el reparto parejo esperado (1/7 ≈ 14%, 1/4 = 25%)
    private $umbralConcentracion = 0.45;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function obtenerFranja($hora)
    {
        if ($hora >= 0 && $hora <= 5) return 'Madrugada';
        if ($hora >= 6 && $hora <= 11) return 'Manana';
        if ($hora >= 12 && $hora <= 17) return 'Tarde';
        return 'Noche';
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
        $sql = "SELECT c.tbgeneroid, g.tbgeneronombre, r.tbreproducciontiempo, s.tbreproduccionsemanaldata
                FROM tbreproduccion r
                INNER JOIN tbcancion c ON r.tbcancionid = c.tbcancionid
                INNER JOIN tbgenero g ON c.tbgeneroid = g.tbgeneroid
                INNER JOIN tbreproduccionsemanal s ON r.tbreproduccionsemanalid = s.tbreproduccionsemanalid
                WHERE r.tbperfilid = :perfilId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $eventos = [];
        foreach ($filas as $fila) {
            $lineas = $this->parsearData($fila['tbreproduccionsemanaldata']);
            $numEventosCancion = count($lineas);
            if ($numEventosCancion === 0) continue;

            // Tiempo promedio real de escucha por cada reproducción registrada de esta canción
            $tiempoPromedioPorEvento = $fila['tbreproducciontiempo'] / $numEventosCancion;

            foreach ($lineas as $linea) {
                $hora = (int) date('H', strtotime($linea['fecha']));
                $eventos[] = [
                    'dia' => $linea['dia'],
                    'franja' => $this->obtenerFranja($hora),
                    'genero' => $fila['tbgeneronombre'],
                    'peso' => $tiempoPromedioPorEvento
                ];
            }
        }
        return $eventos;
    }

    // Evalúa el modelo entrenado sobre las 28 combinaciones día×franja, indexado por "dia|franja"
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

    // Candidatos tipo "específico": una combinación exacta de día + franja
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

    // Candidatos tipo "por franja": ignora el día, salvo que un solo día concentre el patrón
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

    // Candidatos tipo "por día": ignora la franja, salvo que una sola franja concentre el patrón
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
        $eventos = $this->obtenerEventos($perfilId);
        $totalEventos = count($eventos);

        if ($totalEventos < $this->minimoEventos) {
            return [
                'exito' => false,
                'mensaje' => "No hay suficientes datos para generar un perfilado confiable. Se necesitan al menos {$this->minimoEventos} reproducciones registradas (hay {$totalEventos}).",
                'totalEventos' => $totalEventos
            ];
        }

        // Si no hay tiempo real registrado todavía (perfil muy nuevo), usamos peso uniforme
        // para no perder toda la señal de entrenamiento
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

            // El tiempo real de escucha se traduce en más repeticiones de este evento durante
            // el entrenamiento, para que la red aprenda más de lo que la persona realmente
            // escucha (no solo lo que reprodujo más veces). Tope de 5x para evitar que una
            // canción con tiempo muy alto desbalancee todo el entrenamiento.
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

        return [
            'exito' => true,
            'totalEventos' => $totalEventos,
            'resultados' => $top3
        ];
    }

    // Proporción real de cada género en todo el historial del perfil, ponderada por tiempo escuchado
    private function calcularPriorGeneros($pesoPorGenero, $pesoTotalGeneral)
    {
        $prior = [];
        foreach ($pesoPorGenero as $genero => $peso) {
            $prior[$genero] = $pesoTotalGeneral > 0 ? $peso / $pesoTotalGeneral : 0;
        }
        return $prior;
    }

    // Suaviza la confianza cruda del modelo hacia el promedio general del género,
    // evitando que un combo con muy poco soporte muestre 100% de confianza por sobreajuste
    private function ajustarConfianza($confianza, $soporte, $genero, $priorGeneros, $k = 3)
    {
        $prior = $priorGeneros[$genero] ?? 0;
        return (($confianza * $soporte) + ($prior * $k)) / ($soporte + $k);
    }
}

