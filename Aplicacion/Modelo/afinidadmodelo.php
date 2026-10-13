<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/grupomodelo.php';

class AfinidadModelo
{
    private $conexion;

    private $grupoModelo;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->grupoModelo = new GrupoModelo();
    }

    // Disparador oportunista: evita recalcular en cada sondeo. La marca vive
    // en la base de datos (tbafinidadcontrol), no en el sistema de archivos:
    // las rutas del proyecto no son escribibles por www-data en el contenedor
    // y se pierden al reiniciar.
    public function generarSiCorresponde($segundos = 15)
    {
        if (!$this->reservarEjecucion((int) $segundos)) {
            return ['exito' => true, 'data' => ['omitido' => true]];
        }

        return $this->generar();
    }

    // Reserva atomica de la ventana: solo la peticion que logra el UPDATE
    // corre generar(); las concurrentes dentro del plazo quedan omitidas.
    private function reservarEjecucion($segundos)
    {
        $zona = new DateTimeZone('America/Costa_Rica');
        $ahora = new DateTimeImmutable('now', $zona);
        $umbral = $ahora->modify('-' . $segundos . ' seconds');

        $this->asegurarFilaControl();

        $stmt = $this->conexion->prepare("UPDATE tbafinidadcontrol
                SET tbafinidadcontrolultimageneracion = :ahora,
                    tbafinidadcontrolfechaactualizacion = :ahora
                WHERE tbafinidadcontrolid = 1
                  AND (tbafinidadcontrolultimageneracion IS NULL
                       OR tbafinidadcontrolultimageneracion <= :umbral)");
        $stmt->bindValue(':ahora', $ahora->format('Y-m-d H:i:s'));
        $stmt->bindValue(':umbral', $umbral->format('Y-m-d H:i:s'));
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    private function asegurarFilaControl()
    {
        $this->conexion->exec("INSERT IGNORE INTO tbafinidadcontrol (tbafinidadcontrolid) VALUES (1)");
    }

    // Recorre las senales y crea/actualiza cohortes y miembros pendientes.
    public function generar()
    {
        $zona = new DateTimeZone('America/Costa_Rica');
        $ahora = (new DateTimeImmutable('now', $zona))->format('Y-m-d H:i:s');
        $inicioDia = (new DateTimeImmutable('today', $zona))->format('Y-m-d H:i:s');
        $finDia = (new DateTimeImmutable('tomorrow', $zona))->format('Y-m-d H:i:s');

        $cohortes = $this->cohortesCalificadas();
        $miembrosNuevos = 0;

        foreach ($cohortes as $cohorte) {
            $origen = $this->origenCohorte($cohorte['tipo'], $cohorte['categoria'], $cohorte['tema'], $cohorte['perfiles']);

            [$afinidadId, $estado] = $this->asegurarAfinidad(
                $cohorte['tipo'],
                $cohorte['categoria'],
                $cohorte['tema'],
                $origen,
                $ahora
            );

            if ($estado === 'cerrada') {
                continue;
            }

            foreach ($cohorte['perfiles'] as $perfilId) {
                if ($this->agregarMiembroPendiente($afinidadId, $perfilId, $ahora, $inicioDia, $finDia)) {
                    $miembrosNuevos++;
                }
            }
        }

        return ['exito' => true, 'data' => ['cohortes' => count($cohortes), 'miembrosNuevos' => $miembrosNuevos]];
    }

    // Perfiles que cumplen el minimo en cada (tipo, categoria, tema).
    private function cohortesCalificadas()
    {
        $sql = "SELECT tipo, categoria, tema, perfil FROM (
                    SELECT tbperfiltematipo AS tipo, tbperfiltemacategoria AS categoria,
                           tbperfiltematema AS tema, tbperfilid AS perfil,
                           SUM(tbperfiltemacount) AS total
                    FROM tbperfiltema
                    WHERE tbperfiltematipo = 'bueno' AND tbperfiltematema <> ''
                    GROUP BY tbperfiltematipo, tbperfiltemacategoria, tbperfiltematema, tbperfilid
                    UNION ALL
                    SELECT tbperfiltematipo, tbperfiltemacategoria, '' AS tema, tbperfilid,
                           SUM(tbperfiltemacount)
                    FROM tbperfiltema
                    WHERE tbperfiltematipo = 'malo'
                    GROUP BY tbperfiltematipo, tbperfiltemacategoria, tbperfilid
                ) q
                WHERE total >= :minimo";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':minimo', TEMAS_MINIMO_MENSAJES, PDO::PARAM_INT);
        $stmt->execute();

        $agrupadas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $clave = $fila['tipo'] . '|' . $fila['categoria'] . '|' . $fila['tema'];

            if (!isset($agrupadas[$clave])) {
                $agrupadas[$clave] = [
                    'tipo' => $fila['tipo'],
                    'categoria' => $fila['categoria'],
                    'tema' => $fila['tema'],
                    'perfiles' => []
                ];
            }

            $agrupadas[$clave]['perfiles'][] = (int) $fila['perfil'];
        }

        // Solo cohortes con dos o mas perfiles distintos.
        return array_values(array_filter($agrupadas, fn ($c) => count($c['perfiles']) >= 2));
    }

    private function origenCohorte($tipo, $categoria, $tema, array $perfiles)
    {
        if (empty($perfiles)) {
            return 'comunidad';
        }

        $marcadores = implode(',', array_fill(0, count($perfiles), '?'));
        $sql = "SELECT COUNT(*) FROM tbperfiltema
                WHERE tbperfiltematipo = ? AND tbperfiltemacategoria = ? AND tbperfiltematema = ?
                  AND tbperfiltemaorigen = 'chat' AND tbperfilid IN ($marcadores)";
        $stmt = $this->conexion->prepare($sql);

        $indice = 1;
        $stmt->bindValue($indice++, $tipo);
        $stmt->bindValue($indice++, $categoria);
        $stmt->bindValue($indice++, $tema);

        foreach ($perfiles as $perfilId) {
            $stmt->bindValue($indice++, (int) $perfilId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return ((int) $stmt->fetchColumn()) > 0 ? 'chat' : 'comunidad';
    }

    private function asegurarAfinidad($tipo, $categoria, $tema, $origen, $ahora)
    {
        $sel = $this->conexion->prepare("SELECT tbafinidadid, tbafinidadestado FROM tbafinidad
                WHERE tbafinidadtipo = :tipo AND tbafinidadcategoria = :categoria AND tbafinidadtema = :tema");
        $sel->bindValue(':tipo', $tipo);
        $sel->bindValue(':categoria', $categoria);
        $sel->bindValue(':tema', $tema);
        $sel->execute();
        $fila = $sel->fetch();

        if ($fila) {
            $afinidadId = (int) $fila['tbafinidadid'];

            if ($fila['tbafinidadestado'] !== 'cerrada') {
                $upd = $this->conexion->prepare("UPDATE tbafinidad
                        SET tbafinidadorigen = :origen, tbafinidadfechaactualizacion = :fecha
                        WHERE tbafinidadid = :id");
                $upd->bindValue(':origen', $origen);
                $upd->bindValue(':fecha', $ahora);
                $upd->bindValue(':id', $afinidadId, PDO::PARAM_INT);
                $upd->execute();
            }

            return [$afinidadId, $fila['tbafinidadestado']];
        }

        $ins = $this->conexion->prepare("INSERT INTO tbafinidad
                    (tbafinidadtipo, tbafinidadcategoria, tbafinidadtema, tbafinidadorigen,
                     tbafinidadestado, tbafinidadfechacreacion, tbafinidadfechaactualizacion)
                VALUES (:tipo, :categoria, :tema, :origen, 'abierta', :fecha, :fecha)");
        $ins->bindValue(':tipo', $tipo);
        $ins->bindValue(':categoria', $categoria);
        $ins->bindValue(':tema', $tema);
        $ins->bindValue(':origen', $origen);
        $ins->bindValue(':fecha', $ahora);
        $ins->execute();

        return [(int) $this->conexion->lastInsertId(), 'abierta'];
    }

    private function agregarMiembroPendiente($afinidadId, $perfilId, $ahora, $inicioDia, $finDia)
    {
        $afinidadId = (int) $afinidadId;
        $perfilId = (int) $perfilId;
        $zona = new DateTimeZone('America/Costa_Rica');

        $sel = $this->conexion->prepare("SELECT tbafinidadmiembroid, tbafinidadmiembroestado,
                        tbafinidadmiembrofecharespuesta
                FROM tbafinidadmiembro
                WHERE tbafinidadid = :afinidad AND tbperfilid = :perfil");
        $sel->bindValue(':afinidad', $afinidadId, PDO::PARAM_INT);
        $sel->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $sel->execute();
        $fila = $sel->fetch();

        if ($fila) {
            // No se reactivan filas aceptadas ni pendientes.
            if ($fila['tbafinidadmiembroestado'] !== 'rechazada') {
                return false;
            }

            $respuesta = $fila['tbafinidadmiembrofecharespuesta'];

            if ($respuesta === null) {
                return false;
            }

            $limite = (new DateTimeImmutable($respuesta, $zona))
                ->modify('+' . AFINIDAD_ENFRIAMIENTO_DIAS . ' days')
                ->format('Y-m-d H:i:s');

            // Dentro del enfriamiento: no se vuelve a proponer.
            if ($ahora < $limite) {
                return false;
            }

            // Fuera del enfriamiento: la reactivacion cuenta como propuesta del dia.
            if ($this->alcanzoTopeDiario($perfilId, $inicioDia, $finDia)) {
                return false;
            }

            $upd = $this->conexion->prepare("UPDATE tbafinidadmiembro
                    SET tbafinidadmiembroestado = 'pendiente',
                        tbafinidadmiembrofechapropuesta = :propuesta,
                        tbafinidadmiembrofecharespuesta = NULL
                    WHERE tbafinidadmiembroid = :id");
            $upd->bindValue(':propuesta', $ahora);
            $upd->bindValue(':id', (int) $fila['tbafinidadmiembroid'], PDO::PARAM_INT);
            $upd->execute();

            return true;
        }

        // Fila nueva: respeta el tope diario (si se alcanzo, la afinidad
        // queda elegible y se propone cuando haya cupo).
        if ($this->alcanzoTopeDiario($perfilId, $inicioDia, $finDia)) {
            return false;
        }

        $ins = $this->conexion->prepare("INSERT INTO tbafinidadmiembro
                    (tbafinidadid, tbperfilid, tbafinidadmiembroestado, tbafinidadmiembrofechapropuesta)
                VALUES (:afinidad, :perfil, 'pendiente', :fecha)
                ON DUPLICATE KEY UPDATE tbafinidadmiembroid = tbafinidadmiembroid");
        $ins->bindValue(':afinidad', $afinidadId, PDO::PARAM_INT);
        $ins->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $ins->bindValue(':fecha', $ahora);
        $ins->execute();

        return $ins->rowCount() > 0;
    }

    // Propuestas creadas para el perfil dentro del dia (America/Costa_Rica).
    private function alcanzoTopeDiario($perfilId, $inicioDia, $finDia)
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbafinidadmiembro
                WHERE tbperfilid = :perfil
                  AND tbafinidadmiembrofechapropuesta >= :inicio
                  AND tbafinidadmiembrofechapropuesta < :fin");
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicioDia);
        $stmt->bindValue(':fin', $finDia);
        $stmt->execute();

        return ((int) $stmt->fetchColumn()) >= AFINIDAD_MAX_DIARIAS;
    }

    public function contarPendientes($perfilId)
    {
        $stmt = $this->conexion->prepare("SELECT COUNT(*)
                FROM tbafinidadmiembro m
                INNER JOIN tbafinidad a ON a.tbafinidadid = m.tbafinidadid
                WHERE m.tbperfilid = :perfil AND m.tbafinidadmiembroestado = 'pendiente'
                  AND a.tbafinidadestado = 'abierta'");
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function pendientesDePerfil($perfilId)
    {
        $perfilId = (int) $perfilId;

        if ($perfilId <= 0) {
            return ['exito' => false, 'mensaje' => 'Perfil inválido.'];
        }

        $sql = "SELECT a.tbafinidadid, a.tbafinidadtipo, a.tbafinidadcategoria, a.tbafinidadtema,
                       a.tbafinidadorigen, a.tbafinidadfechacreacion
                FROM tbafinidadmiembro m
                INNER JOIN tbafinidad a ON a.tbafinidadid = m.tbafinidadid
                WHERE m.tbperfilid = :perfil AND m.tbafinidadmiembroestado = 'pendiente'
                  AND a.tbafinidadestado = 'abierta'
                ORDER BY a.tbafinidadfechaactualizacion DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        $pendientes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $afinidadId = (int) $fila['tbafinidadid'];
            $mostrarNombres = $fila['tbafinidadorigen'] !== 'chat';

            $otros = $this->otrosMiembros($afinidadId, $perfilId);

            $pendientes[] = [
                'tbafinidadid' => $afinidadId,
                'tbafinidadtipo' => $fila['tbafinidadtipo'],
                'tbafinidadcategoria' => $fila['tbafinidadcategoria'],
                'tbafinidadtema' => $fila['tbafinidadtema'],
                'tbafinidadorigen' => $fila['tbafinidadorigen'],
                'tbafinidadfechacreacion' => $fila['tbafinidadfechacreacion'],
                'tbafinidadcantidadotros' => count($otros),
                // Privacidad: con origen 'chat' no se exponen nombres ajenos
                // antes de que el perfil acepte.
                'tbafinidadmostrarnombres' => $mostrarNombres,
                'tbafinidadotros' => $mostrarNombres ? $otros : []
            ];
        }

        return ['exito' => true, 'data' => $pendientes];
    }

    public function misAfinidades($perfilId)
    {
        $perfilId = (int) $perfilId;

        if ($perfilId <= 0) {
            return ['exito' => false, 'mensaje' => 'Perfil inválido.'];
        }

        $sql = "SELECT a.tbafinidadid, a.tbafinidadtipo, a.tbafinidadcategoria, a.tbafinidadtema,
                       a.tbafinidadorigen, a.tbafinidadestado, a.tbafinidadfechacreacion,
                       m.tbafinidadmiembroestado,
                       g.tbgrupoid
                FROM tbafinidadmiembro m
                INNER JOIN tbafinidad a ON a.tbafinidadid = m.tbafinidadid
                LEFT JOIN tbgrupo g ON g.tbafinidadid = a.tbafinidadid
                WHERE m.tbperfilid = :perfil AND m.tbafinidadmiembroestado <> 'rechazada'
                ORDER BY a.tbafinidadfechaactualizacion DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        $afinidades = [];
        foreach ($stmt->fetchAll() as $fila) {
            $aceptada = $fila['tbafinidadmiembroestado'] === 'aceptada';
            $mostrarNombres = $fila['tbafinidadorigen'] !== 'chat' || $aceptada;

            $afinidades[] = [
                'tbafinidadid' => (int) $fila['tbafinidadid'],
                'tbafinidadtipo' => $fila['tbafinidadtipo'],
                'tbafinidadcategoria' => $fila['tbafinidadcategoria'],
                'tbafinidadtema' => $fila['tbafinidadtema'],
                'tbafinidadorigen' => $fila['tbafinidadorigen'],
                'tbafinidadestado' => $fila['tbafinidadestado'],
                'tbafinidadfechacreacion' => $fila['tbafinidadfechacreacion'],
                'tbafinidadmiembroestado' => $fila['tbafinidadmiembroestado'],
                'tbgrupoid' => $fila['tbgrupoid'] !== null ? (int) $fila['tbgrupoid'] : null,
                'tbafinidadmostrarnombres' => $mostrarNombres,
                'tbafinidadotros' => $mostrarNombres ? $this->otrosMiembros((int) $fila['tbafinidadid'], $perfilId) : []
            ];
        }

        return ['exito' => true, 'data' => $afinidades];
    }

    public function aceptar($afinidadId, $perfilId)
    {
        $respuesta = $this->responder($afinidadId, $perfilId, 'aceptada');

        if (!$respuesta['exito']) {
            return $respuesta;
        }

        $grupo = $this->grupoModelo->asegurarGrupoPara($afinidadId);

        return [
            'exito' => true,
            'data' => [
                'tbafinidadid' => (int) $afinidadId,
                'tbafinidadmiembroestado' => 'aceptada',
                'tbgrupoid' => $grupo['exito'] ? ($grupo['data']['tbgrupoid'] ?? null) : null
            ]
        ];
    }

    public function rechazar($afinidadId, $perfilId)
    {
        return $this->responder($afinidadId, $perfilId, 'rechazada');
    }

    private function responder($afinidadId, $perfilId, $estado)
    {
        $afinidadId = (int) $afinidadId;
        $perfilId = (int) $perfilId;

        if ($afinidadId <= 0 || $perfilId <= 0) {
            return ['exito' => false, 'mensaje' => 'Datos incompletos o inválidos.'];
        }

        $sql = "SELECT m.tbafinidadmiembroid, a.tbafinidadestado
                FROM tbafinidadmiembro m
                INNER JOIN tbafinidad a ON a.tbafinidadid = m.tbafinidadid
                WHERE m.tbafinidadid = :afinidad AND m.tbperfilid = :perfil";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':afinidad', $afinidadId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if (!$fila) {
            return ['exito' => false, 'mensaje' => 'No tienes una propuesta de afinidad con esos datos.'];
        }

        if ($fila['tbafinidadestado'] === 'cerrada') {
            return ['exito' => false, 'mensaje' => 'La afinidad fue desactivada.'];
        }

        $upd = $this->conexion->prepare("UPDATE tbafinidadmiembro
                SET tbafinidadmiembroestado = :estado, tbafinidadmiembrofecharespuesta = :fecha
                WHERE tbafinidadmiembroid = :id");
        $upd->bindValue(':estado', $estado);
        $upd->bindValue(':fecha', date('Y-m-d H:i:s'));
        $upd->bindValue(':id', (int) $fila['tbafinidadmiembroid'], PDO::PARAM_INT);
        $upd->execute();

        return ['exito' => true, 'data' => ['tbafinidadmiembroestado' => $estado]];
    }

    // Solo admin: desactiva una cohorte sin borrarla.
    public function cerrar($afinidadId)
    {
        $afinidadId = (int) $afinidadId;

        if ($afinidadId <= 0) {
            return ['exito' => false, 'mensaje' => 'Afinidad inválida.'];
        }

        $upd = $this->conexion->prepare("UPDATE tbafinidad
                SET tbafinidadestado = 'cerrada', tbafinidadfechaactualizacion = :fecha
                WHERE tbafinidadid = :id");
        $upd->bindValue(':fecha', date('Y-m-d H:i:s'));
        $upd->bindValue(':id', $afinidadId, PDO::PARAM_INT);
        $upd->execute();

        return ['exito' => true, 'data' => ['tbafinidadestado' => 'cerrada']];
    }

    private function otrosMiembros($afinidadId, $perfilId)
    {
        $sql = "SELECT m.tbperfilid, p.tbperfilnombre, m.tbafinidadmiembroestado
                FROM tbafinidadmiembro m
                INNER JOIN tbperfil p ON p.tbperfilid = m.tbperfilid
                WHERE m.tbafinidadid = :afinidad AND m.tbperfilid <> :perfil
                ORDER BY m.tbafinidadmiembrofechapropuesta ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':afinidad', (int) $afinidadId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        $otros = [];
        foreach ($stmt->fetchAll() as $fila) {
            $otros[] = [
                'tbperfilid' => (int) $fila['tbperfilid'],
                'tbperfilnombre' => $fila['tbperfilnombre'],
                'tbafinidadmiembroestado' => $fila['tbafinidadmiembroestado']
            ];
        }

        return $otros;
    }

    public function obtener($afinidadId)
    {
        $stmt = $this->conexion->prepare("SELECT tbafinidadid, tbafinidadtipo, tbafinidadcategoria,
                       tbafinidadtema, tbafinidadorigen, tbafinidadestado,
                       tbafinidadfechacreacion, tbafinidadfechaactualizacion
                FROM tbafinidad WHERE tbafinidadid = :id");
        $stmt->bindValue(':id', (int) $afinidadId, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if (!$fila) {
            return ['exito' => false, 'mensaje' => 'La afinidad no existe.'];
        }

        $fila['tbafinidadid'] = (int) $fila['tbafinidadid'];

        return ['exito' => true, 'data' => $fila];
    }

    // Solo admin: resumen agregado para analisis de discurso. No expone
    // nombres de perfiles, solo conteos.
    public function resumenAnalisis()
    {
        $temas = [];
        $sqlTemas = "SELECT tbperfiltematipo AS tipo, tbperfiltemacategoria AS categoria,
                            tbperfiltematema AS tema, SUM(tbperfiltemacount) AS senales,
                            COUNT(DISTINCT tbperfilid) AS perfiles
                     FROM tbperfiltema
                     GROUP BY tbperfiltematipo, tbperfiltemacategoria, tbperfiltematema
                     ORDER BY tbperfiltematipo ASC, tbperfiltemacategoria ASC, senales DESC";
        foreach ($this->conexion->query($sqlTemas) as $fila) {
            $temas[] = [
                'tipo' => $fila['tipo'],
                'categoria' => $fila['categoria'],
                'tema' => $fila['tema'],
                'senales' => (int) $fila['senales'],
                'perfiles' => (int) $fila['perfiles']
            ];
        }

        $cohortes = [];
        $sqlCohortes = "SELECT a.tbafinidadid, a.tbafinidadtipo, a.tbafinidadcategoria, a.tbafinidadtema,
                               a.tbafinidadorigen, a.tbafinidadestado, a.tbafinidadfechacreacion,
                               g.tbgrupoid,
                               (SELECT COUNT(*) FROM tbafinidadmiembro m WHERE m.tbafinidadid = a.tbafinidadid) AS miembros,
                               (SELECT COUNT(*) FROM tbafinidadmiembro m WHERE m.tbafinidadid = a.tbafinidadid AND m.tbafinidadmiembroestado = 'aceptada') AS aceptados,
                               (SELECT COUNT(*) FROM tbafinidadmiembro m WHERE m.tbafinidadid = a.tbafinidadid AND m.tbafinidadmiembroestado = 'pendiente') AS pendientes,
                               (SELECT COUNT(*) FROM tbafinidadmiembro m WHERE m.tbafinidadid = a.tbafinidadid AND m.tbafinidadmiembroestado = 'rechazada') AS rechazados
                        FROM tbafinidad a
                        LEFT JOIN tbgrupo g ON g.tbafinidadid = a.tbafinidadid
                        ORDER BY a.tbafinidadfechaactualizacion DESC";
        foreach ($this->conexion->query($sqlCohortes) as $fila) {
            $cohortes[] = [
                'tbafinidadid' => (int) $fila['tbafinidadid'],
                'tbafinidadtipo' => $fila['tbafinidadtipo'],
                'tbafinidadcategoria' => $fila['tbafinidadcategoria'],
                'tbafinidadtema' => $fila['tbafinidadtema'],
                'tbafinidadorigen' => $fila['tbafinidadorigen'],
                'tbafinidadestado' => $fila['tbafinidadestado'],
                'tbafinidadfechacreacion' => $fila['tbafinidadfechacreacion'],
                'tbgrupoid' => $fila['tbgrupoid'] !== null ? (int) $fila['tbgrupoid'] : null,
                'miembros' => (int) $fila['miembros'],
                'aceptados' => (int) $fila['aceptados'],
                'pendientes' => (int) $fila['pendientes'],
                'rechazados' => (int) $fila['rechazados']
            ];
        }

        return ['exito' => true, 'data' => ['temas' => $temas, 'cohortes' => $cohortes]];
    }
}
