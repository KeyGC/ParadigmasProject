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

    // Convierte el string "34|Dom|2026-01-15 08:00:00\n34|Lun|2026-01-16 09:30:00" en un arreglo de eventos
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

    // Agrega una línea nueva al string existente (nunca modifica ni suma, solo añade)
    private function agregarLinea($dataActual, $semana, $dia, $fecha)
    {
        $nuevaLinea = $semana . "|" . $dia . "|" . $fecha;
        $dataActual = trim($dataActual ?? '');

        if ($dataActual === '') {
            return $nuevaLinea;
        }
        return $dataActual . "\n" . $nuevaLinea;
    }

    private function getByPerfilId($idPerfil)
    {
        $sql = "SELECT pa.tbperfilaccesoid, pa.tbperfilid, pa.tbperfilaccesosemanalid,
                    pa.tbperfilaccesofechacreacion, pa.tbperfilaccesofechaultima,
                    pa.tbperfilaccesoestado, pas.tbperfilaccesosemanaldata
                FROM tbperfilacceso pa
                INNER JOIN tbperfilaccesosemanal pas
                    ON pa.tbperfilaccesosemanalid = pas.tbperfilaccesosemanalid
                WHERE pa.tbperfilid = :idPerfil";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
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
    // Ahora simplemente AGREGA una línea nueva, sin buscar ni sumar
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

        $nuevoData = $this->agregarLinea(
            $acceso['tbperfilaccesosemanaldata'],
            $semanaActual,
            $diaActual,
            $ahora
        );

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

    // Para el dashboard: agrupa todos los eventos por semana+día y cuenta cuántos hay,
    // devolviendo el mismo formato que antes (semana, dia, cantidad) para no tocar el JS/vista
    public function getMatriz($idPerfil)
    {
        $acceso = $this->getByPerfilId($idPerfil);
        if (!$acceso) {
            return null;
        }

        $eventos = $this->parsearData($acceso['tbperfilaccesosemanaldata']);

        // Agrupa: clave "semana|dia" -> cantidad de eventos
        $conteo = [];
        foreach ($eventos as $evento) {
            $clave = $evento['semana'] . '|' . $evento['dia'];
            if (!isset($conteo[$clave])) {
                $conteo[$clave] = [
                    'semana' => $evento['semana'],
                    'dia' => $evento['dia'],
                    'cantidad' => 0
                ];
            }
            $conteo[$clave]['cantidad']++;
        }

        $lineas = array_values($conteo);
        usort($lineas, fn($a, $b) => $a['semana'] <=> $b['semana']);

        return [
            'fechaCreacion' => $acceso['tbperfilaccesofechacreacion'],
            'fechaUltima' => $acceso['tbperfilaccesofechaultima'],
            'estado' => $acceso['tbperfilaccesoestado'],
            'semanas' => $lineas
        ];
    }

    public function toggleEstado($idPerfil)
    {
        $sql = "UPDATE tbperfilacceso SET tbperfilaccesoestado = NOT tbperfilaccesoestado WHERE tbperfilid = :idPerfil";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        return $stmt->execute();
    }
}