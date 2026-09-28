<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class ReproduccionModelo
{
    private $conexion;

    private $diasSemana = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mie',
        4 => 'Jue',
        5 => 'Vie',
        6 => 'Sab',
        7 => 'Dom'
    ];

    private $segundosGracia = 5;

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
            $n = count($partes);
            if ($n === 3 || $n === 4) {
                $lineas[] = [
                    'semana' => (int) $partes[0],
                    'dia' => $partes[1],
                    'fecha' => $partes[2],
                    'segundos' => $n === 4 ? (int) $partes[3] : 0
                ];
            }
        }
        return $lineas;
    }
    
    private function agregarLinea($dataActual, $semana, $dia, $fecha)
    {
        $nuevaLinea = $semana . "|" . $dia . "|" . $fecha . "|0";
        $dataActual = trim($dataActual ?? '');

        if ($dataActual === '') {
            return $nuevaLinea;
        }
        return $dataActual . "\n" . $nuevaLinea;
    }

    private function obtenerOCrearFila($perfilId, $cancionId)
    {
        $sql = "SELECT r.tbreproduccionid, r.tbreproduccionsemanalid, r.tbreproducciontiempo,
                    s.tbreproduccionsemanaldata
                FROM tbreproduccion r
                INNER JOIN tbreproduccionsemanal s ON r.tbreproduccionsemanalid = s.tbreproduccionsemanalid
                WHERE r.tbperfilid = :perfilId AND r.tbcancionid = :cancionId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return $fila;
        }

        $sqlSemanal = "INSERT INTO tbreproduccionsemanal (tbreproduccionsemanaldata) VALUES ('')";
        $this->conexion->prepare($sqlSemanal)->execute();
        $idSemanal = $this->conexion->lastInsertId();

        $sqlInsert = "INSERT INTO tbreproduccion (tbperfilid, tbcancionid, tbreproduccionsemanalid)
                      VALUES (:perfilId, :cancionId, :idSemanal)";
        $stmt = $this->conexion->prepare($sqlInsert);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->bindValue(':idSemanal', $idSemanal, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'tbreproduccionid' => $this->conexion->lastInsertId(),
            'tbreproduccionsemanalid' => $idSemanal,
            'tbreproducciontiempo' => 0,
            'tbreproduccionsemanaldata' => ''
        ];
    }

    public function acumularTiempo($perfilId, $cancionId, $segundos)
    {
        $fila = $this->obtenerOCrearFila($perfilId, $cancionId);

        try {
            $this->conexion->beginTransaction();

            $stmt = $this->conexion->prepare(
                "SELECT tbreproduccionsemanaldata FROM tbreproduccionsemanal
                WHERE tbreproduccionsemanalid = :id FOR UPDATE"
            );
            $stmt->bindValue(':id', $fila['tbreproduccionsemanalid'], PDO::PARAM_INT);
            $stmt->execute();
            $data = trim((string) $stmt->fetchColumn());

            $filas = $data === '' ? [] : explode("\n", $data);

            if (empty($filas)) {
                $filas[] = (int) date('W') . '|' . $this->diasSemana[(int) date('N')]
                        . '|' . date('Y-m-d H:i:s') . '|0';
            }

            $i = count($filas) - 1;
            $partes = explode('|', trim($filas[$i]));
            $previo = count($partes) >= 4 ? (int) $partes[3] : 0;
            $nuevo = $previo + $segundos;
            $filas[$i] = implode('|', array_slice($partes, 0, 3)) . '|' . $nuevo;

            $incremento = max(0, $nuevo - $this->segundosGracia)
                        - max(0, $previo - $this->segundosGracia);

            $upd = $this->conexion->prepare(
                "UPDATE tbreproduccionsemanal SET tbreproduccionsemanaldata = :data
                WHERE tbreproduccionsemanalid = :id"
            );
            $upd->bindValue(':data', implode("\n", $filas));
            $upd->bindValue(':id', $fila['tbreproduccionsemanalid'], PDO::PARAM_INT);
            if (!$upd->execute()) {
                throw new RuntimeException('No se pudo actualizar la data semanal');
            }

            if ($incremento > 0) {
                $tot = $this->conexion->prepare(
                    "UPDATE tbreproduccion SET tbreproducciontiempo = tbreproducciontiempo + :inc
                    WHERE tbreproduccionid = :id"
                );
                $tot->bindValue(':inc', $incremento, PDO::PARAM_INT);
                $tot->bindValue(':id', $fila['tbreproduccionid'], PDO::PARAM_INT);
                if (!$tot->execute()) {
                    throw new RuntimeException('No se pudo actualizar el total');
                }
            }

            $this->conexion->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            error_log('acumularTiempo: ' . $e->getMessage());
            return false;
        }
    }

    private function resolverMomento($fechaLocal)
    {
        $servidor = new DateTime('now');

        if ($fechaLocal) {
            $cliente = DateTime::createFromFormat('Y-m-d H:i:s', $fechaLocal);
            $errores = DateTime::getLastErrors();
            $valida = $cliente && (!$errores ||
                ($errores['warning_count'] == 0 && $errores['error_count'] == 0));

            if ($valida && abs($cliente->getTimestamp() - $servidor->getTimestamp()) <= 27 * 3600) {
                return $cliente;
            }
        }
        return $servidor;
    }

    public function incrementarContador($perfilId, $cancionId, $fechaLocal = null)
    {
        $momento = $this->resolverMomento($fechaLocal);
        $ahora = $momento->format('Y-m-d H:i:s');
        $semanaActual = (int) $momento->format('W');
        $diaActual = $this->diasSemana[(int) $momento->format('N')];

        $fila = $this->obtenerOCrearFila($perfilId, $cancionId);

        $nuevoData = $this->agregarLinea(
            $fila['tbreproduccionsemanaldata'],
            $semanaActual,
            $diaActual,
            $ahora
        );

        $sql = "UPDATE tbreproduccionsemanal SET tbreproduccionsemanaldata = :data
                WHERE tbreproduccionsemanalid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':data', $nuevoData);
        $stmt->bindValue(':id', $fila['tbreproduccionsemanalid'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getTiempoPorPerfilYCancion($perfilId, $cancionId)
    {
        $sql = "SELECT tbreproducciontiempo FROM tbreproduccion WHERE tbperfilid = :perfilId AND tbcancionid = :cancionId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? (int)$fila['tbreproducciontiempo'] : 0;
    }

    public function deleteByPerfilId($perfilId)
    {
        $sql = "DELETE FROM tbreproduccion WHERE tbperfilid = :perfilId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getPorPerfil($perfilId)
    {
        $sql = "SELECT r.tbreproduccionid, c.tbcancionnombre, c.tbcancionartista,
                    r.tbreproducciontiempo, r.tbreproduccionestado,
                    s.tbreproduccionsemanaldata
                FROM tbreproduccion r
                INNER JOIN tbcancion c ON r.tbcancionid = c.tbcancionid
                INNER JOIN tbreproduccionsemanal s ON r.tbreproduccionsemanalid = s.tbreproduccionsemanalid
                WHERE r.tbperfilid = :perfilId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $resultado = [];
        foreach ($filas as $fila) {
            $eventos = $this->parsearData($fila['tbreproduccionsemanaldata']);
            $resultado[] = [
                'tbreproduccionid' => $fila['tbreproduccionid'],
                'tbcancionnombre' => $fila['tbcancionnombre'],
                'tbcancionartista' => $fila['tbcancionartista'],
                'tbreproducciontiempo' => $fila['tbreproducciontiempo'],
                'tbreproduccioncontador' => count($eventos),
                'tbreproduccionestado' => $fila['tbreproduccionestado']
            ];
        }

        usort($resultado, fn($a, $b) => $b['tbreproduccioncontador'] <=> $a['tbreproduccioncontador']);

        return $resultado;
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbreproduccion SET tbreproduccionestado = NOT tbreproduccionestado WHERE tbreproduccionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}