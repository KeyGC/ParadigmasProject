<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/perfilacceso.php';
require_once __DIR__ . '/perfilaccesosemanal.php';

class PerfilAccesoModelo
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

    // Convierte el string "33|Mie|45\n34|Lun|2" en un arreglo de filas
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
                    'cantidad' => (int) $partes[2]
                ];
            }
        }
        return $lineas;
    }

    // Vuelve a armar el string a partir del arreglo
    private function construirData($lineas)
    {
        $filas = [];
        foreach ($lineas as $linea) {
            $filas[] = $linea['semana'] . "|" . $linea['dia'] . "|" . $linea['cantidad'];
        }
        return implode("\n", $filas);
    }

    private function getByPerfilId($idPerfil)
    {
        $sql = "SELECT pa.tbperfilaccesoid, pa.tbperfilid, pa.tbperfilaccesosemanalid,
                    pa.tbperfilaccesofechacreacion, pa.tbperfilaccesofechaultima,
                    pa.tbperfilaccesoestado, pas.tbperfilaccesosemanaldata
                FROM tbperfilacceso pa
                INNER JOIN tbperfilaccesosemanal pas
                    ON pa.tbperfilaccesosemanalid = pas.tbperfilaccesosemanalid
                WHERE pa.tbperfilid = :idPerfil AND pa.tbperfilaccesoestado = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function toggleEstado($idPerfil)
    {
        $sql = "UPDATE tbperfilacceso SET tbperfilaccesoestado = NOT tbperfilaccesoestado WHERE tbperfilid = :idPerfil";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        return $stmt->execute();
}

    // Se llama cuando se crea el perfil (registro), NO en el login
    public function crearRegistroAcceso($idPerfil)
    {
        $ahora = date('Y-m-d H:i:s');

        $sqlSemanal = "INSERT INTO tbperfilaccesosemanal (tbperfilaccesosemanaldata) VALUES ('')";
        $stmt = $this->conexion->prepare($sqlSemanal);
        $stmt->execute();
        $idSemanal = $this->conexion->lastInsertId();

        $sqlAcceso = "INSERT INTO tbperfilacceso
                        (tbperfilid, tbperfilaccesosemanalid, tbperfilaccesofechacreacion, tbperfilaccesofechaultima)
                    VALUES (:idPerfil, :idSemanal, :ahora, :ahora)";
        $stmt = $this->conexion->prepare($sqlAcceso);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        $stmt->bindValue(':idSemanal', $idSemanal, PDO::PARAM_INT);
        $stmt->bindValue(':ahora', $ahora);
        return $stmt->execute();
    }

    // Se llama cada vez que un perfil (admin o cliente) hace login
    public function registrarAcceso($idPerfil)
    {
        $ahora = date('Y-m-d H:i:s');
        $semanaActual = (int) date('W');
        $diaActual = $this->diasSemana[(int) date('N')];

        $acceso = $this->getByPerfilId($idPerfil);

        // Fallback por si el perfil se creó antes de existir esta funcionalidad
        if (!$acceso) {
            $this->crearRegistroAcceso($idPerfil);
            $acceso = $this->getByPerfilId($idPerfil);
        }

        $lineas = $this->parsearData($acceso['tbperfilaccesosemanaldata']);
        $encontrado = false;

        foreach ($lineas as &$linea) {
            if ($linea['semana'] === $semanaActual && $linea['dia'] === $diaActual) {
                $linea['cantidad']++;
                $encontrado = true;
                break;
            }
        }
        unset($linea);

        if (!$encontrado) {
            $lineas[] = ['semana' => $semanaActual, 'dia' => $diaActual, 'cantidad' => 1];
        }

        $nuevoData = $this->construirData($lineas);

        $sqlUpdateSemanal = "UPDATE tbperfilaccesosemanal
                            SET tbperfilaccesosemanaldata = :data
                            WHERE tbperfilaccesosemanalid = :id";
        $stmt = $this->conexion->prepare($sqlUpdateSemanal);
        $stmt->bindValue(':data', $nuevoData);
        $stmt->bindValue(':id', $acceso['tbperfilaccesosemanalid'], PDO::PARAM_INT);
        $stmt->execute();

        $sqlUpdateAcceso = "UPDATE tbperfilacceso
                            SET tbperfilaccesofechaultima = :ahora
                            WHERE tbperfilaccesoid = :id";
        $stmt = $this->conexion->prepare($sqlUpdateAcceso);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->bindValue(':id', $acceso['tbperfilaccesoid'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Para el dashboard: devuelve el historial ya parseado y ordenado
    public function getMatriz($idPerfil)
    {
        $acceso = $this->getByPerfilId($idPerfil);
        if (!$acceso) {
            return null;
        }

        $lineas = $this->parsearData($acceso['tbperfilaccesosemanaldata']);
        usort($lineas, fn($a, $b) => $a['semana'] <=> $b['semana']);

        return [
            'fechaCreacion' => $acceso['tbperfilaccesofechacreacion'],
            'fechaUltima' => $acceso['tbperfilaccesofechaultima'],
            'semanas' => $lineas
        ];
    }
}