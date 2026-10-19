<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/conversacionmodelo.php';
require_once __DIR__ . '/moderacionmodelo.php';
require_once __DIR__ . '/perfiltemamodelo.php';

class MensajeModelo
{
    private $conexion;

    private $conversacionModelo;

    private $moderacionModelo;

    private $perfilTemaModelo;

    private $longitudMaxima = 1000;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->conversacionModelo = new ConversacionModelo();
        $this->moderacionModelo = new ModeracionModelo();
        $this->perfilTemaModelo = new PerfilTemaModelo();
    }

    public function enviarMensaje($conversacionId, $emisorId, $texto)
    {
        $conversacionId = (int) $conversacionId;
        $emisorId = (int) $emisorId;
        $texto = trim((string) $texto);

        if ($conversacionId <= 0 || $emisorId <= 0) {
            return ['exito' => false, 'mensaje' => 'Datos incompletos o inválidos.'];
        }

        if ($texto === '') {
            return ['exito' => false, 'mensaje' => 'El mensaje no puede estar vacío.'];
        }

        if (mb_strlen($texto) > $this->longitudMaxima) {
            return ['exito' => false, 'mensaje' => "El mensaje supera los {$this->longitudMaxima} caracteres."];
        }

        if (!$this->conversacionModelo->existe($conversacionId)) {
            return ['exito' => false, 'mensaje' => 'La conversación no existe.'];
        }

        if (!$this->conversacionModelo->esParticipante($conversacionId, $emisorId)) {
            return ['exito' => false, 'mensaje' => 'No perteneces a esta conversación.'];
        }

        $moderacion = $this->moderacionModelo->evaluar($texto);

        if (!$moderacion['exito']) {
            error_log('Moderacion: envio rechazado en conversacion ' . $conversacionId . ' - ' . $moderacion['mensaje']);

            return [
                'exito' => true,
                'data' => [
                    'tbmensajeid' => null,
                    'tbconversacionid' => $conversacionId,
                    'tbperfilidemisor' => $emisorId,
                    'tbmensajetexto' => $moderacion['textoEnmascarado'],
                    'tbmensajefechahora' => date('Y-m-d H:i:s'),
                    'tbmensajeleido' => false,
                    'tbmensajebloqueado' => true,
                    'tbmensajecategoria' => $moderacion['categoria'],
                    'tbmensajescore' => $moderacion['score'],
                    'moderacionDisponible' => false
                ]
            ];
        }

        $bloqueado = (bool) $moderacion['bloqueado'];
        $categoria = $moderacion['categoria'];
        $score = (float) $moderacion['score'];

        if ($bloqueado && $score > MODERACION_UMBRAL_APRENDIZAJE && $categoria !== null) {
            $aprendido = $this->moderacionModelo->aprenderDeConfirmado($texto, $categoria);

            if (!$aprendido['exito']) {
                error_log('Moderacion: no se pudo aprender el bloqueado - ' . $aprendido['mensaje']);
            }
        }

        $textoGuardado = $bloqueado ? $moderacion['textoEnmascarado'] : $texto;

        $categoriaGuardada = $bloqueado ? $categoria : null;
        $scoreGuardado = $bloqueado ? $score : null;
        $ahora = date('Y-m-d H:i:s');

        $comida = null;
        $comidaId = null;
        if (!$bloqueado) {
            require_once __DIR__ . '/comidalinkmodelo.php';
            $comidaLinkModelo = new ComidaLinkModelo();
            $comida = $comidaLinkModelo->procesarMensaje($texto, $emisorId);
            if ($comida && !empty($comida['id'])) {
                $comidaId = (int) $comida['id'];
            }
        }

        $sql = "INSERT INTO tbmensaje
                    (tbconversacionid, tbperfilidemisor, tbmensajetexto, tbmensajefechahora, tbmensajeleido, tbmensajebloqueado, tbmensajecategoria, tbmensajescore, tbcomidaid)
                VALUES (:conversacionId, :emisor, :texto, :fecha, FALSE, :bloqueado, :categoria, :score, :comidaId)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':conversacionId', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':emisor', $emisorId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $textoGuardado);
        $stmt->bindValue(':fecha', $ahora);
        $stmt->bindValue(':bloqueado', $bloqueado ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $categoriaGuardada, $categoriaGuardada === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':score', $scoreGuardado);
        $stmt->bindValue(':comidaId', $comidaId, $comidaId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }

        $nuevoId = (int) $this->conexion->lastInsertId();

        $this->registrarSenalesTema($emisorId, $bloqueado, $categoria, $texto, 'chat');

        return [
            'exito' => true,
            'data' => [
                'tbmensajeid' => $nuevoId,
                'tbconversacionid' => $conversacionId,
                'tbperfilidemisor' => $emisorId,
                'tbmensajetexto' => $textoGuardado,
                'tbmensajefechahora' => $ahora,
                'tbmensajeleido' => false,
                'tbmensajebloqueado' => $bloqueado,
                'tbmensajecategoria' => $categoriaGuardada,
                'tbmensajescore' => $scoreGuardado,
                'comida' => $comida
            ]
        ];
    }

    public function getMensajesNuevos($conversacionId, $desdeId = 0)
    {
        $conversacionId = (int) $conversacionId;
        $desdeId = (int) $desdeId;

        if ($conversacionId <= 0) {
            return ['exito' => false, 'mensaje' => 'Conversación inválida.'];
        }

        $sql = "SELECT m.tbmensajeid, m.tbconversacionid, m.tbperfilidemisor,
                       m.tbmensajetexto, m.tbmensajefechahora, m.tbmensajeleido,
                       m.tbmensajebloqueado, m.tbmensajecategoria, m.tbmensajescore,
                       m.tbcomidaid,
                       c.tbcomidanombre, c.tbcomidaimagenurl, tc.tbtipocomidanombre
                FROM tbmensaje m
                LEFT JOIN tbcomida c ON m.tbcomidaid = c.tbcomidaid
                LEFT JOIN tbtipocomida tc ON c.tbtipocomidaid = tc.tbtipocomidaid
                WHERE m.tbconversacionid = :conversacionId AND m.tbmensajeid > :desdeId
                ORDER BY m.tbmensajeid ASC
                LIMIT 100";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':conversacionId', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':desdeId', $desdeId, PDO::PARAM_INT);
        $stmt->execute();

        $mensajes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $bloqueado = (bool) $fila['tbmensajebloqueado'];

            $item = [
                'tbmensajeid' => (int) $fila['tbmensajeid'],
                'tbconversacionid' => (int) $fila['tbconversacionid'],
                'tbperfilidemisor' => (int) $fila['tbperfilidemisor'],
                'tbmensajetexto' => $bloqueado ? MODERACION_TEXTO_BLOQUEADO : $fila['tbmensajetexto'],
                'tbmensajefechahora' => $fila['tbmensajefechahora'],
                'tbmensajeleido' => (bool) $fila['tbmensajeleido'],
                'tbmensajebloqueado' => $bloqueado,
                'tbmensajecategoria' => $fila['tbmensajecategoria'],
                'tbmensajescore' => $fila['tbmensajescore'] !== null ? (float) $fila['tbmensajescore'] : null
            ];
            if (!empty($fila['tbcomidaid'])) {
                $item['comida'] = [
                    'id' => (int) $fila['tbcomidaid'],
                    'nombre' => $fila['tbcomidanombre'],
                    'imagenUrl' => $fila['tbcomidaimagenurl'],
                    'tipoNombre' => $fila['tbtipocomidanombre']
                ];
            }
            $mensajes[] = $item;
        }

        return ['exito' => true, 'data' => $mensajes];
    }

    public function marcarLeidos($conversacionId, $perfilId)
    {
        $conversacionId = (int) $conversacionId;
        $perfilId = (int) $perfilId;

        if ($conversacionId <= 0 || $perfilId <= 0) {
            return ['exito' => false, 'mensaje' => 'Datos incompletos o inválidos.'];
        }

        $sql = "UPDATE tbmensaje
                SET tbmensajeleido = TRUE
                WHERE tbconversacionid = :conversacionId
                  AND tbperfilidemisor <> :perfil
                  AND tbmensajeleido = FALSE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':conversacionId', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return ['exito' => true, 'data' => ['tbmensajesactualizados' => $stmt->rowCount()]];
    }

    private function registrarSenalesTema($perfilId, $bloqueado, $categoria, $texto, $origen)
    {
        try {
            $registro = $this->perfilTemaModelo->registrarDesdeMensaje(
                $perfilId,
                $bloqueado,
                $categoria,
                $texto,
                $origen
            );

            if (!$registro['exito']) {
                error_log('Afinidad: no se registro la senal - ' . ($registro['mensaje'] ?? 'desconocido'));
            }
        } catch (Throwable $e) {
            error_log('Afinidad: excepcion al registrar senal - ' . $e->getMessage());
        }
    }
}