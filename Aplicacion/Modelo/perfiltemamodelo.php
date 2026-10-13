<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/temasmodelo.php';

class PerfilTemaModelo
{
    private $conexion;

    private $temasModelo;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->temasModelo = new TemasModelo();
    }

    public function registrarDesdeMensaje($perfilId, $bloqueado, $categoria, $texto, $origen, $temaSala = null, $categoriaSala = null)
    {
        $perfilId = (int) $perfilId;
        $origen = trim((string) $origen);

        if ($perfilId <= 0 || $origen === '') {
            return ['exito' => false, 'mensaje' => 'Perfil u origen inválido.'];
        }

        if ($bloqueado) {
            $categoria = strtolower(trim((string) $categoria));

            if ($categoria === '') {
                return ['exito' => true, 'data' => ['registradas' => 0]];
            }

            $registro = $this->registrar($perfilId, 'malo', $categoria, '', $origen);

            return $registro['exito']
                ? ['exito' => true, 'data' => ['registradas' => 1]]
                : $registro;
        }

        $registradas = 0;

        $clasificado = $this->temasModelo->clasificarTema($texto);

        if ($clasificado['exito'] && !empty($clasificado['clasificado'])) {
            $registro = $this->registrar(
                $perfilId,
                'bueno',
                (string) $clasificado['tipo'],
                (string) $clasificado['tema'],
                $origen
            );

            if ($registro['exito']) {
                $registradas++;
            }
        }

        $temaSala = $temaSala !== null ? trim((string) $temaSala) : '';

        if ($temaSala !== '') {
            $registro = $this->registrar($perfilId, 'bueno', (string) $categoriaSala, $temaSala, $origen);

            if ($registro['exito']) {
                $registradas++;
            }
        }

        return ['exito' => true, 'data' => ['registradas' => $registradas]];
    }

    private function registrar($perfilId, $tipo, $categoria, $tema, $origen)
    {
        $tipo = strtolower(trim((string) $tipo));
        $categoria = strtolower(trim((string) $categoria));
        $tema = trim((string) $tema);

        if ($perfilId <= 0 || $tipo === '' || $categoria === '') {
            return ['exito' => false, 'mensaje' => 'Datos de senal inválidos.'];
        }

        $sql = "INSERT INTO tbperfiltema
                    (tbperfilid, tbperfiltematipo, tbperfiltemacategoria,
                     tbperfiltematema, tbperfiltemaorigen,
                     tbperfiltemacount, tbperfiltemafechaultima)
                VALUES (:perfil, :tipo, :categoria, :tema, :origen, 1, :fecha)
                ON DUPLICATE KEY UPDATE
                    tbperfiltemacount = tbperfiltemacount + 1,
                    tbperfiltemafechaultima = VALUES(tbperfiltemafechaultima)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':categoria', $categoria);
        $stmt->bindValue(':tema', $tema);
        $stmt->bindValue(':origen', $origen);
        $stmt->bindValue(':fecha', date('Y-m-d H:i:s'));

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo registrar la senal.'];
        }

        return ['exito' => true, 'data' => ['tbperfiltemaid' => (int) $this->conexion->lastInsertId()]];
    }
}
