<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/cancionmodelo.php';
require_once __DIR__ . '/moderacionmodelo.php';
require_once __DIR__ . '/../Utilidades/validacion.php';

class CancionChatModelo
{
    private $conexion;
    private $cancionModelo;
    private $moderacionModelo;
    private $longitudMaxima = 300;
    private $limiteMensajes = 50;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->cancionModelo = new CancionModelo();
        $this->moderacionModelo = new ModeracionModelo();
    }

    public function perfilExisteYActivo($perfilId)
    {
        $sql = "SELECT COUNT(*)
                FROM tbperfil
                WHERE tbperfilid = :id AND tbperfilactivo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function cancionExisteYActiva($cancionId)
    {
        $cancion = $this->cancionModelo->getCancion((int) $cancionId);
        return (!empty($cancion) && !empty($cancion['tbcancionactivo']));
    }

    public function enviarMensaje($cancionId, $emisorId, $texto)
    {
        $cancionId = (int) $cancionId;
        $emisorId = (int) $emisorId;
        $texto = trim((string) $texto);

        if ($cancionId <= 0 || $emisorId <= 0) {
            return ['exito' => false, 'mensaje' => 'Datos incompletos o inválidos.'];
        }

        $errorRequerido = campoRequerido($texto, 'mensaje');
        if ($errorRequerido !== null) {
            return ['exito' => false, 'mensaje' => 'El mensaje no puede estar vacío.'];
        }

        $errorLongitud = campoLongitud($texto, 'mensaje', $this->longitudMaxima, 1);
        if ($errorLongitud !== null) {
            return ['exito' => false, 'mensaje' => "El mensaje supera los {$this->longitudMaxima} caracteres."];
        }

        if (!$this->cancionExisteYActiva($cancionId)) {
            return ['exito' => false, 'mensaje' => 'La canción no existe o está inactiva.'];
        }

        if (!$this->perfilExisteYActivo($emisorId)) {
            return ['exito' => false, 'mensaje' => 'El perfil no existe o está inactivo.'];
        }

        $stmtNombre = $this->conexion->prepare('SELECT tbperfilnombre FROM tbperfil WHERE tbperfilid = :id');
        $stmtNombre->bindValue(':id', $emisorId, PDO::PARAM_INT);
        $stmtNombre->execute();
        $perfilNombre = $stmtNombre->fetchColumn();

        $ahora = date('Y-m-d H:i:s');
        $moderacion = $this->moderacionModelo->evaluar($texto);

        if (!$moderacion['exito']) {
            error_log('Moderacion: envio rechazado en chat cancion ' . $cancionId . ' - ' . $moderacion['mensaje']);

            return [
                'exito' => true,
                'data' => [
                    'tbcancionchatid' => null,
                    'tbcancionid' => $cancionId,
                    'tbperfilid' => $emisorId,
                    'tbperfilnombre' => $perfilNombre !== false ? $perfilNombre : ('Perfil ' . $emisorId),
                    'tbcancionchattexto' => $moderacion['textoEnmascarado'],
                    'tbcancionchatfechahora' => $ahora,
                    'tbcancionchatbloqueado' => true,
                    'tbcancionchatcategoria' => $moderacion['categoria'],
                    'tbcancionchatscore' => $moderacion['score'],
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

        $sql = "INSERT INTO tbcancionchat
                    (tbcancionid, tbperfilid, tbcancionchattexto,
                     tbcancionchatfechahora, tbcancionchatbloqueado,
                     tbcancionchatcategoria, tbcancionchatscore)
                VALUES (:cancionId, :perfilId, :texto, :fecha, :bloqueado, :categoria, :score)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->bindValue(':perfilId', $emisorId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $textoGuardado);
        $stmt->bindValue(':fecha', $ahora);
        $stmt->bindValue(':bloqueado', $bloqueado ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $categoriaGuardada, $categoriaGuardada === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':score', $scoreGuardado);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }

        $nuevoId = (int) $this->conexion->lastInsertId();

        return [
            'exito' => true,
            'data' => [
                'tbcancionchatid' => $nuevoId,
                'tbcancionid' => $cancionId,
                'tbperfilid' => $emisorId,
                'tbperfilnombre' => $perfilNombre !== false ? $perfilNombre : ('Perfil ' . $emisorId),
                'tbcancionchattexto' => $textoGuardado,
                'tbcancionchatfechahora' => $ahora,
                'tbcancionchatbloqueado' => $bloqueado,
                'tbcancionchatcategoria' => $categoriaGuardada,
                'tbcancionchatscore' => $scoreGuardado
            ]
        ];
    }

    public function getMensajesNuevos($cancionId, $desdeId = 0)
    {
        $cancionId = (int) $cancionId;
        $desdeId = (int) $desdeId;

        if ($cancionId <= 0) {
            return ['exito' => false, 'mensaje' => 'Canción inválida.'];
        }

        if (!$this->cancionExisteYActiva($cancionId)) {
            return ['exito' => false, 'mensaje' => 'La canción no existe o está inactiva.'];
        }

        $sql = "SELECT m.tbcancionchatid, m.tbcancionid, m.tbperfilid,
                       m.tbcancionchattexto, m.tbcancionchatfechahora,
                       m.tbcancionchatbloqueado, m.tbcancionchatcategoria,
                       m.tbcancionchatscore,
                       p.tbperfilnombre
                FROM (
                    SELECT x.tbcancionchatid, x.tbcancionid, x.tbperfilid,
                           x.tbcancionchattexto, x.tbcancionchatfechahora,
                           x.tbcancionchatbloqueado, x.tbcancionchatcategoria,
                           x.tbcancionchatscore
                    FROM tbcancionchat x
                    WHERE x.tbcancionid = :cancionId AND x.tbcancionchatid > :desdeId
                    ORDER BY x.tbcancionchatid DESC
                    LIMIT :limite
                ) m
                INNER JOIN tbperfil p ON p.tbperfilid = m.tbperfilid
                ORDER BY m.tbcancionchatid ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':cancionId', $cancionId, PDO::PARAM_INT);
        $stmt->bindValue(':desdeId', $desdeId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $this->limiteMensajes, PDO::PARAM_INT);
        $stmt->execute();

        $mensajes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $bloqueado = (bool) $fila['tbcancionchatbloqueado'];

            $mensajes[] = [
                'tbcancionchatid' => (int) $fila['tbcancionchatid'],
                'tbcancionid' => (int) $fila['tbcancionid'],
                'tbperfilid' => (int) $fila['tbperfilid'],
                'tbperfilnombre' => $fila['tbperfilnombre'],
                'tbcancionchattexto' => $bloqueado ? MODERACION_TEXTO_BLOQUEADO : $fila['tbcancionchattexto'],
                'tbcancionchatfechahora' => $fila['tbcancionchatfechahora'],
                'tbcancionchatbloqueado' => $bloqueado,
                'tbcancionchatcategoria' => $fila['tbcancionchatcategoria'],
                'tbcancionchatscore' => $fila['tbcancionchatscore'] !== null ? (float) $fila['tbcancionchatscore'] : null
            ];
        }

        return ['exito' => true, 'data' => $mensajes];
    }
}
