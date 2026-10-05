<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/comunidadmodelo.php';

class ComunidadMensajeModelo
{
    private $conexion;

    private $comunidadModelo;

    private $longitudMaxima = 1000;

    private $limiteMensajes = 100;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->comunidadModelo = new ComunidadModelo();
    }

    private function perfilExisteYActivo($perfilId)
    {
        $sql = "SELECT COUNT(*)
                FROM tbperfil
                WHERE tbperfilid = :id AND tbperfilactivo = TRUE";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function enviarMensaje($comunidadId, $emisorId, $texto)
    {
        $comunidadId = (int) $comunidadId;
        $emisorId = (int) $emisorId;
        $texto = trim((string) $texto);

        if ($comunidadId <= 0 || $emisorId <= 0) {
            return ['exito' => false, 'mensaje' => 'Datos incompletos o inválidos.'];
        }

        if ($texto === '') {
            return ['exito' => false, 'mensaje' => 'El mensaje no puede estar vacío.'];
        }

        if (mb_strlen($texto) > $this->longitudMaxima) {
            return ['exito' => false, 'mensaje' => "El mensaje supera los {$this->longitudMaxima} caracteres."];
        }

        if (!$this->comunidadModelo->getComunidadPorId($comunidadId)['exito']) {
            return ['exito' => false, 'mensaje' => 'La comunidad no existe o está inactiva.'];
        }

        if (!$this->perfilExisteYActivo($emisorId)) {
            return ['exito' => false, 'mensaje' => 'El perfil no existe o está inactivo.'];
        }

        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbcomunidadmensaje
                    (tbcomunidadid, tbperfilidemisor, tbcomunidadmensajetexto,
                     tbcomunidadmensajefechahora, tbcomunidadmensajebloqueado)
                VALUES (:comunidadId, :emisor, :texto, :fecha, FALSE)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':comunidadId', $comunidadId, PDO::PARAM_INT);
        $stmt->bindValue(':emisor', $emisorId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $texto);
        $stmt->bindValue(':fecha', $ahora);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }

        // Se toma antes de cualquier otra consulta: lastInsertId() se resetea
        $nuevoId = (int) $this->conexion->lastInsertId();

        $stmtNombre = $this->conexion->prepare('SELECT tbperfilnombre FROM tbperfil WHERE tbperfilid = :id');
        $stmtNombre->bindValue(':id', $emisorId, PDO::PARAM_INT);
        $stmtNombre->execute();
        $perfilNombre = $stmtNombre->fetchColumn();

        return [
            'exito' => true,
            'data' => [
                'tbcomunidadmensajeid' => $nuevoId,
                'tbcomunidadid' => $comunidadId,
                'tbperfilidemisor' => $emisorId,
                'tbperfilnombre' => $perfilNombre !== false ? $perfilNombre : null,
                'tbcomunidadmensajetexto' => $texto,
                'tbcomunidadmensajefechahora' => $ahora,
                'tbcomunidadmensajebloqueado' => false
            ]
        ];
    }

    public function getMensajesNuevos($comunidadId, $desdeId = 0)
    {
        $comunidadId = (int) $comunidadId;
        $desdeId = (int) $desdeId;

        if ($comunidadId <= 0) {
            return ['exito' => false, 'mensaje' => 'Comunidad inválida.'];
        }

        // Con desdeId = 0 se carga la cola de la sala (ultimos mensajes),
        // invertida para entregarlos en orden cronologico.
        $sql = "SELECT m.tbcomunidadmensajeid, m.tbcomunidadid, m.tbperfilidemisor,
                       m.tbcomunidadmensajetexto, m.tbcomunidadmensajefechahora,
                       p.tbperfilnombre
                FROM (
                    SELECT x.tbcomunidadmensajeid, x.tbcomunidadid, x.tbperfilidemisor,
                           x.tbcomunidadmensajetexto, x.tbcomunidadmensajefechahora
                    FROM tbcomunidadmensaje x
                    WHERE x.tbcomunidadid = :comunidadId AND x.tbcomunidadmensajeid > :desdeId
                    ORDER BY x.tbcomunidadmensajeid DESC
                    LIMIT :limite
                ) m
                INNER JOIN tbperfil p ON p.tbperfilid = m.tbperfilidemisor
                ORDER BY m.tbcomunidadmensajeid ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':comunidadId', $comunidadId, PDO::PARAM_INT);
        $stmt->bindValue(':desdeId', $desdeId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $this->limiteMensajes, PDO::PARAM_INT);
        $stmt->execute();

        $mensajes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $mensajes[] = [
                'tbcomunidadmensajeid' => (int) $fila['tbcomunidadmensajeid'],
                'tbcomunidadid' => (int) $fila['tbcomunidadid'],
                'tbperfilidemisor' => (int) $fila['tbperfilidemisor'],
                'tbperfilnombre' => $fila['tbperfilnombre'],
                'tbcomunidadmensajetexto' => $fila['tbcomunidadmensajetexto'],
                'tbcomunidadmensajefechahora' => $fila['tbcomunidadmensajefechahora']
            ];
        }

        return ['exito' => true, 'data' => $mensajes];
    }
}