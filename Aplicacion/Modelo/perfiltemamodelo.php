<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/temasmodelo.php';

/**
 * Registra las senales tematicas de un perfil (tabla tbperfiltema).
 *
 * Cada fila es un contador por (perfil, tipo, categoria, tema, origen):
 *  - tipo 'bueno': categoria en genero/comida/deporte y tema concreto.
 *  - tipo 'malo': categoria de moderacion (drogas/sexual/violencia) y tema ''.
 *
 * Los bloqueos SOLO se registran cuando la moderacion dio una categoria real.
 * La rama fail-closed no llega aqui porque no hay INSERT previo (por diseno).
 */
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

        // Participar en la sala de una comunidad es en si una senal del tema
        // de la sala, ademas de lo que el propio texto clasifique.
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
