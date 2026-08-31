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
        $sql = "SELECT c.tbgeneroid, g.tbgeneronombre, s.tbreproduccionsemanaldata
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
            foreach ($lineas as $linea) {
                $hora = (int) date('H', strtotime($linea['fecha']));
                $eventos[] = [
                    'dia' => $linea['dia'],
                    'franja' => $this->obtenerFranja($hora),
                    'genero' => $fila['tbgeneronombre']
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
    private function generarCandidatosEspecificos($probPorCombo, $soportePorCombo)
    {
        $candidatos = [];
        foreach ($this->diasSemana as $dia) {
            foreach ($this->franjas as $franja) {
                $clave = $dia . '|' . $franja;
                $soporte = $soportePorCombo[$clave] ?? 0;
                if ($soporte < 1) continue;

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
                    'soporte' => $soporte,
                    'texto' => "Le gusta escuchar {$genero} los {$this->diasNombreCompleto[$dia]} en {$this->franjasNombre[$franja]}"
                ];
            }
        }
        return $candidatos;
    }

    // Candidatos tipo "por franja": ignora el día, salvo que un solo día concentre el patrón
    private function generarCandidatosPorFranja($probPorCombo, $soportePorCombo, $especificos)
    {
        $candidatos = [];
        foreach ($this->franjas as $franja) {
            $pesoTotal = 0;
            $acumulado = [];
            $soportePorDia = [];

            foreach ($this->diasSemana as $dia) {
                $clave = $dia . '|' . $franja;
                $soporte = $soportePorCombo[$clave] ?? 0;
                if ($soporte < 1) continue;

                $soportePorDia[$dia] = $soporte;
                $pesoTotal += $soporte;

                foreach ($probPorCombo[$clave] as $genero => $prob) {
                    $acumulado[$genero] = ($acumulado[$genero] ?? 0) + $prob * $soporte;
                }
            }

            if ($pesoTotal < 1) continue;

            foreach ($acumulado as $genero => $suma) {
                $acumulado[$genero] = $suma / $pesoTotal;
            }
            arsort($acumulado);
            $genero = array_key_first($acumulado);
            $confianza = $acumulado[$genero];

            $soporteMaxDia = !empty($soportePorDia) ? max($soportePorDia) : 0;
            $concentracion = $pesoTotal > 0 ? $soporteMaxDia / $pesoTotal : 0;

            if ($concentracion >= $this->umbralConcentracion) {
                // El patrón depende de verdad de un día específico dentro de esta franja:
                // se usa la versión específica en su lugar
                $diaConcentrado = array_search($soporteMaxDia, $soportePorDia);
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
                'texto' => "Le gusta escuchar {$genero} en {$this->franjasNombre[$franja]}, sin importar el día"
            ];
        }
        return $candidatos;
    }

    // Candidatos tipo "por día": ignora la franja, salvo que una sola franja concentre el patrón
    private function generarCandidatosPorDia($probPorCombo, $soportePorCombo, $especificos)
    {
        $candidatos = [];
        foreach ($this->diasSemana as $dia) {
            $pesoTotal = 0;
            $acumulado = [];
            $soportePorFranja = [];

            foreach ($this->franjas as $franja) {
                $clave = $dia . '|' . $franja;
                $soporte = $soportePorCombo[$clave] ?? 0;
                if ($soporte < 1) continue;

                $soportePorFranja[$franja] = $soporte;
                $pesoTotal += $soporte;

                foreach ($probPorCombo[$clave] as $genero => $prob) {
                    $acumulado[$genero] = ($acumulado[$genero] ?? 0) + $prob * $soporte;
                }
            }

            if ($pesoTotal < 1) continue;

            foreach ($acumulado as $genero => $suma) {
                $acumulado[$genero] = $suma / $pesoTotal;
            }
            arsort($acumulado);
            $genero = array_key_first($acumulado);
            $confianza = $acumulado[$genero];

            $soporteMaxFranja = !empty($soportePorFranja) ? max($soportePorFranja) : 0;
            $concentracion = $pesoTotal > 0 ? $soporteMaxFranja / $pesoTotal : 0;

            if ($concentracion >= $this->umbralConcentracion) {
                $franjaConcentrada = array_search($soporteMaxFranja, $soportePorFranja);
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
                'texto' => "Le gusta escuchar {$genero} los {$this->diasNombreCompleto[$dia]}, sin importar la hora"
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

        $samples = [];
        $labels = [];
        $soportePorCombo = [];

        foreach ($eventos as $evento) {
            $samples[] = $this->vectorizar($evento['dia'], $evento['franja']);
            $labels[] = $evento['genero'];

            $clave = $evento['dia'] . '|' . $evento['franja'];
            $soportePorCombo[$clave] = ($soportePorCombo[$clave] ?? 0) + 1;
        }

        $dataset = new Labeled($samples, $labels);

        $holdOut = $totalEventos >= 30 ? 0.1 : 0.0;
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

        $especificos = $this->generarCandidatosEspecificos($probPorCombo, $soportePorCombo);
        $porFranja = $this->generarCandidatosPorFranja($probPorCombo, $soportePorCombo, $especificos);
        $porDia = $this->generarCandidatosPorDia($probPorCombo, $soportePorCombo, $especificos);

        $todos = array_merge($especificos, $porFranja, $porDia);

        // Deduplicar candidatos idénticos (mismo tipo + día + franja + género)
        $vistos = [];
        $unicos = [];
        foreach ($todos as $c) {
            $clave = $c['tipo'] . '|' . $c['dia'] . '|' . $c['franja'] . '|' . $c['genero'];
            if (isset($vistos[$clave])) continue;
            $vistos[$clave] = true;
            $unicos[] = $c;
        }

        // Puntaje final: confianza ponderada por soporte (log evita que el volumen bruto domine todo)
        foreach ($unicos as &$c) {
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
}