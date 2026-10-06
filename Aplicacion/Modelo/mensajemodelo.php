<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/conversacionmodelo.php';
require_once __DIR__ . '/moderacionmodelo.php';

class MensajeModelo
{
    private $conexion;

    private $conversacionModelo;

    private $moderacionModelo;

    private $longitudMaxima = 1000;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->conversacionModelo = new ConversacionModelo();
        $this->moderacionModelo = new ModeracionModelo();
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

        // Moderacion antes de tocar la base de datos. Con fail-closed, si no hay
        // veredicto el mensaje no se guarda: se registra bloqueado y enmascarado.
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

        // Solo los bloqueos con confianza alta alimentan el aprendizaje; asi el
        // dataset no se contamina con falsos positivos.
        if ($bloqueado && $score > MODERACION_UMBRAL_APRENDIZAJE && $categoria !== null) {
            $aprendido = $this->moderacionModelo->aprenderDeConfirmado($texto, $categoria);

            if (!$aprendido['exito']) {
                error_log('Moderacion: no se pudo aprender el bloqueado - ' . $aprendido['mensaje']);
            }
        }

        $textoGuardado = $bloqueado ? $moderacion['textoEnmascarado'] : $texto;

        // Categoria y score solo son significativos cuando la moderacion
        // decidio el bloqueo; si no, se guardan NULL para no etiquetar
        // mensajes legitimos con la categoria del vecino mas cercano.
        $categoriaGuardada = $bloqueado ? $categoria : null;
        $scoreGuardado = $bloqueado ? $score : null;
        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbmensaje
                    (tbconversacionid, tbperfilidemisor, tbmensajetexto, tbmensajefechahora, tbmensajeleido, tbmensajebloqueado, tbmensajecategoria, tbmensajescore)
                VALUES (:conversacionId, :emisor, :texto, :fecha, FALSE, :bloqueado, :categoria, :score)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':conversacionId', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':emisor', $emisorId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $textoGuardado);
        $stmt->bindValue(':fecha', $ahora);
        $stmt->bindValue(':bloqueado', $bloqueado ? 1 : 0, PDO::PARAM_INT);
        // Sin esto PDO emulado podria guardar '' en vez de NULL.
        $stmt->bindValue(':categoria', $categoriaGuardada, $categoriaGuardada === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':score', $scoreGuardado);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }

        return [
            'exito' => true,
            'data' => [
                'tbmensajeid' => (int) $this->conexion->lastInsertId(),
                'tbconversacionid' => $conversacionId,
                'tbperfilidemisor' => $emisorId,
                'tbmensajetexto' => $textoGuardado,
                'tbmensajefechahora' => $ahora,
                'tbmensajeleido' => false,
                'tbmensajebloqueado' => $bloqueado,
                'tbmensajecategoria' => $categoriaGuardada,
                'tbmensajescore' => $scoreGuardado
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

        $sql = "SELECT tbmensajeid, tbconversacionid, tbperfilidemisor,
                       tbmensajetexto, tbmensajefechahora, tbmensajeleido,
                       tbmensajebloqueado, tbmensajecategoria, tbmensajescore
                FROM tbmensaje
                WHERE tbconversacionid = :conversacionId AND tbmensajeid > :desdeId
                ORDER BY tbmensajeid ASC
                LIMIT 100";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':conversacionId', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':desdeId', $desdeId, PDO::PARAM_INT);
        $stmt->execute();

        $mensajes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $bloqueado = (bool) $fila['tbmensajebloqueado'];

            $mensajes[] = [
                'tbmensajeid' => (int) $fila['tbmensajeid'],
                'tbconversacionid' => (int) $fila['tbconversacionid'],
                'tbperfilidemisor' => (int) $fila['tbperfilidemisor'],
                // Doble barrera: el texto ya se guardo enmascarado y aun asi se
                // enmascara al leer, para que ningun registro legacy filtre el original.
                'tbmensajetexto' => $bloqueado ? MODERACION_TEXTO_BLOQUEADO : $fila['tbmensajetexto'],
                'tbmensajefechahora' => $fila['tbmensajefechahora'],
                'tbmensajeleido' => (bool) $fila['tbmensajeleido'],
                'tbmensajebloqueado' => $bloqueado,
                'tbmensajecategoria' => $fila['tbmensajecategoria'],
                'tbmensajescore' => $fila['tbmensajescore'] !== null ? (float) $fila['tbmensajescore'] : null
            ];
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
}