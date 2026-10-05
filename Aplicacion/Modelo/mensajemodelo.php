<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/conversacionmodelo.php';

class MensajeModelo
{
    private $conexion;

    private $conversacionModelo;

    private $longitudMaxima = 1000;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->conversacionModelo = new ConversacionModelo();
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

        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbmensaje
                    (tbconversacionid, tbperfilidemisor, tbmensajetexto, tbmensajefechahora, tbmensajeleido, tbmensajebloqueado)
                VALUES (:conversacionId, :emisor, :texto, :fecha, FALSE, FALSE)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':conversacionId', $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':emisor', $emisorId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $texto);
        $stmt->bindValue(':fecha', $ahora);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }

        return [
            'exito' => true,
            'data' => [
                'tbmensajeid' => (int) $this->conexion->lastInsertId(),
                'tbconversacionid' => $conversacionId,
                'tbperfilidemisor' => $emisorId,
                'tbmensajetexto' => $texto,
                'tbmensajefechahora' => $ahora,
                'tbmensajeleido' => false
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
                       tbmensajetexto, tbmensajefechahora, tbmensajeleido
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
            $mensajes[] = [
                'tbmensajeid' => (int) $fila['tbmensajeid'],
                'tbconversacionid' => (int) $fila['tbconversacionid'],
                'tbperfilidemisor' => (int) $fila['tbperfilidemisor'],
                'tbmensajetexto' => $fila['tbmensajetexto'],
                'tbmensajefechahora' => $fila['tbmensajefechahora'],
                'tbmensajeleido' => (bool) $fila['tbmensajeleido']
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