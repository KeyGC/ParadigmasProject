<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class PerfilRegistrosModelo
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

    private $columnasPorTipo = [
        'contra' => 'tbperfilregistroscontradata',
        'correo' => 'tbperfilregistroscorreodata',
        'nombre' => 'tbperfilregistrosnombredata'
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
            if (count($partes) === 4) {
                $lineas[] = [
                    'dato' => $partes[0],
                    'semana' => (int) $partes[1],
                    'dia' => $partes[2],
                    'fecha' => $partes[3]
                ];
            }
        }
        return $lineas;
    }

    private function agregarLinea($dataActual, $dato, $semana, $dia, $fecha)
    {
        $nuevaLinea = $dato . "|" . $semana . "|" . $dia . "|" . $fecha;
        $dataActual = trim($dataActual ?? '');

        if ($dataActual === '') {
            return $nuevaLinea;
        }
        return $dataActual . "\n" . $nuevaLinea;
    }

    private function getByPerfilId($idPerfil)
    {
        $sql = "SELECT * FROM tbperfilregistrossemanal WHERE tbperfilid = :idPerfil";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Se llama al crear el perfil: crea la fila y guarda la primera línea de cada campo
    public function crearRegistro($idPerfil, $contra, $correo, $nombre)
    {
        $ahora = date('Y-m-d H:i:s');
        $semana = (int) date('W');
        $dia = $this->diasSemana[(int) date('N')];

        $lineaContra = $contra . "|" . $semana . "|" . $dia . "|" . $ahora;
        $lineaCorreo = $correo . "|" . $semana . "|" . $dia . "|" . $ahora;
        $lineaNombre = $nombre . "|" . $semana . "|" . $dia . "|" . $ahora;

        $sql = "INSERT INTO tbperfilregistrossemanal
                (tbperfilid, tbperfilregistroscontradata, tbperfilregistroscorreodata, tbperfilregistrosnombredata)
                VALUES (:idPerfil, :contra, :correo, :nombre)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':idPerfil', $idPerfil, PDO::PARAM_INT);
        $stmt->bindValue(':contra', $lineaContra);
        $stmt->bindValue(':correo', $lineaCorreo);
        $stmt->bindValue(':nombre', $lineaNombre);
        return $stmt->execute();
    }

    // Se llama cada vez que se edita contraseña, correo o nickname
    // $tipo debe ser 'contra', 'correo' o 'nombre'
    public function registrarCambio($idPerfil, $tipo, $valorNuevo)
    {
        if (!isset($this->columnasPorTipo[$tipo])) {
            return false;
        }

        $columna = $this->columnasPorTipo[$tipo];
        $ahora = date('Y-m-d H:i:s');
        $semana = (int) date('W');
        $dia = $this->diasSemana[(int) date('N')];

        $registro = $this->getByPerfilId($idPerfil);

        // Fallback por si el perfil se creó antes de existir esta funcionalidad
        if (!$registro) {
            $this->crearRegistro($idPerfil, '', '', '');
            $registro = $this->getByPerfilId($idPerfil);
        }

        $dataActual = $registro[$columna];
        $nuevoData = $this->agregarLinea($dataActual, $valorNuevo, $semana, $dia, $ahora);

        $sql = "UPDATE tbperfilregistrossemanal
                SET {$columna} = :data
                WHERE tbperfilregistrossemanalid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':data', $nuevoData);
        $stmt->bindValue(':id', $registro['tbperfilregistrossemanalid'], PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Devuelve el historial completo, separado por tipo de campo
    public function getHistorial($idPerfil)
    {
        $registro = $this->getByPerfilId($idPerfil);
        if (!$registro) {
            return null;
        }

        return [
            'contra' => $this->parsearData($registro['tbperfilregistroscontradata']),
            'correo' => $this->parsearData($registro['tbperfilregistroscorreodata']),
            'nombre' => $this->parsearData($registro['tbperfilregistrosnombredata'])
        ];
    }
}