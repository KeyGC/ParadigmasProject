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

    private function agregarLinea($dataActual, $semana, $dia, $fecha)
    {
        $nuevaLinea = $semana . "|" . $dia . "|" . $fecha;
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

        $sql = "UPDATE tbreproduccion SET tbreproducciontiempo = tbreproducciontiempo + :segundos
                WHERE tbreproduccionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':segundos', $segundos, PDO::PARAM_INT);
        $stmt->bindValue(':id', $fila['tbreproduccionid'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function incrementarContador($perfilId, $cancionId)
    {
        $ahora = date('Y-m-d H:i:s');
        $semanaActual = (int) date('W');
        $diaActual = $this->diasSemana[(int) date('N')];

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