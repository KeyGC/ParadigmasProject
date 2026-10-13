<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/moderacionmodelo.php';
require_once __DIR__ . '/perfiltemamodelo.php';

class GrupoModelo
{
    private $conexion;

    private $moderacionModelo;

    private $perfilTemaModelo;

    private $longitudMaxima = 1000;

    private $limiteMensajes = 100;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->moderacionModelo = new ModeracionModelo();
        $this->perfilTemaModelo = new PerfilTemaModelo();
    }

    public function existe($grupoId)
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbgrupo WHERE tbgrupoid = :id AND tbgrupoestado = 'activo'");
        $stmt->bindValue(':id', (int) $grupoId, PDO::PARAM_INT);
        $stmt->execute();

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function esMiembro($grupoId, $perfilId)
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbgrupomiembro WHERE tbgrupoid = :grupo AND tbperfilid = :perfil");
        $stmt->bindValue(':grupo', (int) $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function asegurarGrupoPara($afinidadId)
    {
        $afinidadId = (int) $afinidadId;

        $stmt = $this->conexion->prepare("SELECT tbafinidadid, tbafinidadtipo, tbafinidadcategoria, tbafinidadtema, tbafinidadorigen
                                          FROM tbafinidad WHERE tbafinidadid = :id");
        $stmt->bindValue(':id', $afinidadId, PDO::PARAM_INT);
        $stmt->execute();
        $afinidad = $stmt->fetch();

        if (!$afinidad) {
            return ['exito' => false, 'mensaje' => 'La afinidad no existe.'];
        }

        $aceptados = $this->miembrosAceptados($afinidadId);

        if (count($aceptados) < 2) {
            return ['exito' => true, 'data' => ['tbgrupoid' => null, 'motivo' => 'menos_de_dos']];
        }

        $ahora = date('Y-m-d H:i:s');
        $grupoId = $this->buscarGrupo($afinidadId);

        if ($grupoId === null) {
            $tema = trim((string) $afinidad['tbafinidadtema']);
            $nombre = $tema !== '' ? ('Grupo de ' . $tema) : ('Grupo de ' . $afinidad['tbafinidadcategoria']);

            $ins = $this->conexion->prepare("INSERT INTO tbgrupo
                        (tbafinidadid, tbgrupocategoria, tbgrupotema, tbgrupoorigen, tbgruponombre, tbgrupoestado, tbgrupofechacreacion)
                    VALUES (:afinidad, :categoria, :tema, :origen, :nombre, 'activo', :fecha)");
            $ins->bindValue(':afinidad', $afinidadId, PDO::PARAM_INT);
            $ins->bindValue(':categoria', $afinidad['tbafinidadcategoria']);
            $ins->bindValue(':tema', $tema);
            $ins->bindValue(':origen', $afinidad['tbafinidadorigen']);
            $ins->bindValue(':nombre', $nombre);
            $ins->bindValue(':fecha', $ahora);
            $ins->execute();
            $grupoId = (int) $this->conexion->lastInsertId();
        }

        foreach ($aceptados as $perfilId) {
            $this->agregarMiembro($grupoId, $perfilId, $ahora);
        }

        return ['exito' => true, 'data' => ['tbgrupoid' => $grupoId]];
    }

    private function buscarGrupo($afinidadId)
    {
        $stmt = $this->conexion->prepare("SELECT tbgrupoid FROM tbgrupo WHERE tbafinidadid = :id");
        $stmt->bindValue(':id', (int) $afinidadId, PDO::PARAM_INT);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private function miembrosAceptados($afinidadId)
    {
        $stmt = $this->conexion->prepare("SELECT tbperfilid FROM tbafinidadmiembro
                                          WHERE tbafinidadid = :id AND tbafinidadmiembroestado = 'aceptada'
                                          ORDER BY tbperfilid ASC");
        $stmt->bindValue(':id', (int) $afinidadId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function agregarMiembro($grupoId, $perfilId, $ahora)
    {
        $cursor = $this->ultimoMensajeId($grupoId);

        $ins = $this->conexion->prepare("INSERT INTO tbgrupomiembro
                    (tbgrupoid, tbperfilid, tbgrupomiembrofechaingreso, tbgrupomiembroultimomensajeid)
                VALUES (:grupo, :perfil, :fecha, :cursor)
                ON DUPLICATE KEY UPDATE tbgrupoid = tbgrupoid");
        $ins->bindValue(':grupo', (int) $grupoId, PDO::PARAM_INT);
        $ins->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $ins->bindValue(':fecha', $ahora);
        $ins->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        $ins->execute();
    }

    private function ultimoMensajeId($grupoId)
    {
        $stmt = $this->conexion->prepare("SELECT COALESCE(MAX(tbgrupomensajeid), 0) FROM tbgrupomensaje WHERE tbgrupoid = :id");
        $stmt->bindValue(':id', (int) $grupoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function gruposDePerfil($perfilId)
    {
        $perfilId = (int) $perfilId;

        if ($perfilId <= 0) {
            return ['exito' => false, 'mensaje' => 'Perfil inválido.'];
        }

        $sql = "SELECT g.tbgrupoid, g.tbgrupocategoria, g.tbgrupotema, g.tbgrupoorigen, g.tbgruponombre,
                       g.tbgrupofechacreacion, m.tbgrupomiembroultimomensajeid,
                       u.tbgrupomensajetexto AS ultimotexto,
                       u.tbgrupomensajefechahora AS ultimafecha,
                       u.tbperfilidemisor AS ultimoemisor
                FROM tbgrupomiembro m
                INNER JOIN tbgrupo g ON g.tbgrupoid = m.tbgrupoid
                LEFT JOIN tbgrupomensaje u ON u.tbgrupomensajeid = (
                    SELECT MAX(x.tbgrupomensajeid) FROM tbgrupomensaje x WHERE x.tbgrupoid = g.tbgrupoid
                )
                WHERE m.tbperfilid = :perfil AND g.tbgrupoestado = 'activo'
                ORDER BY COALESCE(u.tbgrupomensajefechahora, g.tbgrupofechacreacion) DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        $grupos = [];
        foreach ($stmt->fetchAll() as $fila) {
            $grupoId = (int) $fila['tbgrupoid'];
            $cursor = (int) $fila['tbgrupomiembroultimomensajeid'];

            $grupos[] = [
                'tbgrupoid' => $grupoId,
                'tbgrupocategoria' => $fila['tbgrupocategoria'],
                'tbgrupotema' => $fila['tbgrupotema'],
                'tbgrupoorigen' => $fila['tbgrupoorigen'],
                'tbgruponombre' => $fila['tbgruponombre'],
                'tbgrupofechacreacion' => $fila['tbgrupofechacreacion'],
                'tbgrupocantidadmiembros' => $this->cantidadMiembros($grupoId),
                'tbgrupomensajenoleidos' => $this->contarNoLeidosDeGrupo($grupoId, $perfilId, $cursor),
                'tbgrupoultimotexto' => $fila['ultimotexto'],
                'tbgrupoultimafecha' => $fila['ultimafecha'],
                'tbgrupoultimoemisor' => $fila['ultimoemisor'] !== null ? (int) $fila['ultimoemisor'] : null
            ];
        }

        return ['exito' => true, 'data' => $grupos];
    }

    public function contarNoLeidos($perfilId)
    {
        $sql = "SELECT COALESCE(SUM((
                    SELECT COUNT(*) FROM tbgrupomensaje s
                    WHERE s.tbgrupoid = m.tbgrupoid
                      AND s.tbgrupomensajeid > m.tbgrupomiembroultimomensajeid
                      AND s.tbperfilidemisor <> m.tbperfilid
                      AND s.tbgrupomensajebloqueado = 0
                )), 0)
                FROM tbgrupomiembro m
                INNER JOIN tbgrupo g ON g.tbgrupoid = m.tbgrupoid
                WHERE m.tbperfilid = :perfil AND g.tbgrupoestado = 'activo'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function contarNoLeidosDeGrupo($grupoId, $perfilId, $cursor)
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbgrupomensaje
            WHERE tbgrupoid = :grupo AND tbgrupomensajeid > :cursor
              AND tbperfilidemisor <> :perfil AND tbgrupomensajebloqueado = 0");
        $stmt->bindValue(':grupo', (int) $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':cursor', (int) $cursor, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function cantidadMiembros($grupoId)
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbgrupomiembro WHERE tbgrupoid = :id");
        $stmt->bindValue(':id', (int) $grupoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function marcarAbierto($grupoId, $perfilId)
    {
        $grupoId = (int) $grupoId;
        $perfilId = (int) $perfilId;

        if (!$this->esMiembro($grupoId, $perfilId)) {
            return ['exito' => false, 'mensaje' => 'No perteneces a este grupo.'];
        }

        $cursor = $this->ultimoMensajeId($grupoId);

        $stmt = $this->conexion->prepare("UPDATE tbgrupomiembro
                SET tbgrupomiembroultimomensajeid = :cursor
                WHERE tbgrupoid = :grupo AND tbperfilid = :perfil");
        $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        $stmt->bindValue(':grupo', $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return ['exito' => true, 'data' => ['tbgrupomiembroultimomensajeid' => $cursor]];
    }

    public function enviarMensaje($grupoId, $emisorId, $texto)
    {
        $grupoId = (int) $grupoId;
        $emisorId = (int) $emisorId;
        $texto = trim((string) $texto);

        if ($grupoId <= 0 || $emisorId <= 0) {
            return ['exito' => false, 'mensaje' => 'Datos incompletos o inválidos.'];
        }

        if ($texto === '') {
            return ['exito' => false, 'mensaje' => 'El mensaje no puede estar vacío.'];
        }

        if (mb_strlen($texto) > $this->longitudMaxima) {
            return ['exito' => false, 'mensaje' => "El mensaje supera los {$this->longitudMaxima} caracteres."];
        }

        if (!$this->existe($grupoId)) {
            return ['exito' => false, 'mensaje' => 'El grupo no existe o está inactivo.'];
        }

        if (!$this->esMiembro($grupoId, $emisorId)) {
            return ['exito' => false, 'mensaje' => 'No perteneces a este grupo.'];
        }

        $moderacion = $this->moderacionModelo->evaluar($texto);

        if (!$moderacion['exito']) {
            error_log('Moderacion: envio rechazado en grupo ' . $grupoId . ' - ' . $moderacion['mensaje']);

            return [
                'exito' => true,
                'data' => [
                    'tbgrupomensajeid' => null,
                    'tbgrupoid' => $grupoId,
                    'tbperfilidemisor' => $emisorId,
                    'tbgrupomensajetexto' => $moderacion['textoEnmascarado'],
                    'tbgrupomensajefechahora' => date('Y-m-d H:i:s'),
                    'tbgrupomensajebloqueado' => true,
                    'tbgrupomensajecategoria' => $moderacion['categoria'],
                    'tbgrupomensajescore' => $moderacion['score'],
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

        $sql = "INSERT INTO tbgrupomensaje
                    (tbgrupoid, tbperfilidemisor, tbgrupomensajetexto, tbgrupomensajefechahora,
                     tbgrupomensajebloqueado, tbgrupomensajecategoria, tbgrupomensajescore)
                VALUES (:grupo, :emisor, :texto, :fecha, :bloqueado, :categoria, :score)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':grupo', $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':emisor', $emisorId, PDO::PARAM_INT);
        $stmt->bindValue(':texto', $textoGuardado);
        $stmt->bindValue(':fecha', $ahora);
        $stmt->bindValue(':bloqueado', $bloqueado ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':categoria', $categoriaGuardada, $categoriaGuardada === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':score', $scoreGuardado);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar el mensaje.'];
        }

        $nuevoId = (int) $this->conexion->lastInsertId();

        $this->registrarSenalesTema($emisorId, $bloqueado, $categoria, $texto);

        return [
            'exito' => true,
            'data' => [
                'tbgrupomensajeid' => $nuevoId,
                'tbgrupoid' => $grupoId,
                'tbperfilidemisor' => $emisorId,
                'tbgrupomensajetexto' => $textoGuardado,
                'tbgrupomensajefechahora' => $ahora,
                'tbgrupomensajebloqueado' => $bloqueado,
                'tbgrupomensajecategoria' => $categoriaGuardada,
                'tbgrupomensajescore' => $scoreGuardado
            ]
        ];
    }

    public function getMensajesNuevos($grupoId, $perfilId, $desdeId = 0)
    {
        $grupoId = (int) $grupoId;
        $desdeId = (int) $desdeId;

        if (!$this->existe($grupoId) || !$this->esMiembro($grupoId, $perfilId)) {
            return ['exito' => false, 'mensaje' => 'Grupo inválido o sin acceso.'];
        }

        $sql = "SELECT m.tbgrupomensajeid, m.tbgrupoid, m.tbperfilidemisor,
                       m.tbgrupomensajetexto, m.tbgrupomensajefechahora,
                       m.tbgrupomensajebloqueado, m.tbgrupomensajecategoria, m.tbgrupomensajescore,
                       p.tbperfilnombre
                FROM (
                    SELECT x.tbgrupomensajeid, x.tbgrupoid, x.tbperfilidemisor,
                           x.tbgrupomensajetexto, x.tbgrupomensajefechahora,
                           x.tbgrupomensajebloqueado, x.tbgrupomensajecategoria, x.tbgrupomensajescore
                    FROM tbgrupomensaje x
                    WHERE x.tbgrupoid = :grupo AND x.tbgrupomensajeid > :desde
                    ORDER BY x.tbgrupomensajeid DESC
                    LIMIT :limite
                ) m
                INNER JOIN tbperfil p ON p.tbperfilid = m.tbperfilidemisor
                ORDER BY m.tbgrupomensajeid ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':grupo', $grupoId, PDO::PARAM_INT);
        $stmt->bindValue(':desde', $desdeId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $this->limiteMensajes, PDO::PARAM_INT);
        $stmt->execute();

        $mensajes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $bloqueado = (bool) $fila['tbgrupomensajebloqueado'];

            $mensajes[] = [
                'tbgrupomensajeid' => (int) $fila['tbgrupomensajeid'],
                'tbgrupoid' => (int) $fila['tbgrupoid'],
                'tbperfilidemisor' => (int) $fila['tbperfilidemisor'],
                'tbperfilnombre' => $fila['tbperfilnombre'],
                'tbgrupomensajetexto' => $bloqueado ? MODERACION_TEXTO_BLOQUEADO : $fila['tbgrupomensajetexto'],
                'tbgrupomensajefechahora' => $fila['tbgrupomensajefechahora'],
                'tbgrupomensajebloqueado' => $bloqueado,
                'tbgrupomensajecategoria' => $fila['tbgrupomensajecategoria'],
                'tbgrupomensajescore' => $fila['tbgrupomensajescore'] !== null ? (float) $fila['tbgrupomensajescore'] : null
            ];
        }

        return ['exito' => true, 'data' => $mensajes];
    }

    public function miembros($grupoId)
    {
        $stmt = $this->conexion->prepare("SELECT m.tbperfilid, p.tbperfilnombre, m.tbgrupomiembrofechaingreso
                FROM tbgrupomiembro m
                INNER JOIN tbperfil p ON p.tbperfilid = m.tbperfilid
                WHERE m.tbgrupoid = :grupo
                ORDER BY m.tbgrupomiembrofechaingreso ASC");
        $stmt->bindValue(':grupo', (int) $grupoId, PDO::PARAM_INT);
        $stmt->execute();

        $miembros = [];
        foreach ($stmt->fetchAll() as $fila) {
            $fila['tbperfilid'] = (int) $fila['tbperfilid'];
            $miembros[] = $fila;
        }

        return $miembros;
    }

    private function registrarSenalesTema($perfilId, $bloqueado, $categoria, $texto)
    {
        try {
            $registro = $this->perfilTemaModelo->registrarDesdeMensaje($perfilId, $bloqueado, $categoria, $texto, 'chat');

            if (!$registro['exito']) {
                error_log('Afinidad: no se registro la senal de grupo - ' . ($registro['mensaje'] ?? 'desconocido'));
            }
        } catch (Throwable $e) {
            error_log('Afinidad: excepcion al registrar senal de grupo - ' . $e->getMessage());
        }
    }
}
