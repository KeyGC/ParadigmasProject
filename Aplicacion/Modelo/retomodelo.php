<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/moderacionmodelo.php';

class RetoModelo
{
    private $conexion;
    private static $esquemaListo = false;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->asegurarEsquema();
    }

    private function asegurarEsquema()
    {
        if (self::$esquemaListo) {
            return;
        }
        self::$esquemaListo = true;

        try {
            $this->conexion->exec("CREATE TABLE IF NOT EXISTS `tbreto` (
              `tbretoid` int NOT NULL AUTO_INCREMENT,
              `tbperfilid` int NOT NULL,
              `tbretotematica` varchar(150) NOT NULL,
              `tbretodescripcion` text NOT NULL,
              `tbretofechainicioparticipacion` datetime NOT NULL,
              `tbretofechafinparticipacion` datetime NOT NULL,
              `tbretofechainiciovotacion` datetime NOT NULL,
              `tbretofechafinvotacion` datetime NOT NULL,
              `tbretofechacreacion` datetime NOT NULL,
              `tbretocerrado` tinyint(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`tbretoid`),
              KEY `idx_reto_fechas` (`tbretofechafinparticipacion`, `tbretofechafinvotacion`),
              KEY `idx_reto_perfil` (`tbperfilid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->conexion->exec("CREATE TABLE IF NOT EXISTS `tbretofoto` (
              `tbretofotoid` int NOT NULL AUTO_INCREMENT,
              `tbretoid` int NOT NULL,
              `tbperfilid` int NOT NULL,
              `tbretofototitulo` varchar(200) NOT NULL,
              `tbretofotodescripcion` text DEFAULT NULL,
              `tbretofotoarchivo` varchar(255) NOT NULL,
              `tbretofotofechasubida` datetime NOT NULL,
              `tbretofotoactivo` tinyint(1) NOT NULL DEFAULT 1,
              `tbretofotoancho` int DEFAULT NULL,
              `tbretofotoalto` int DEFAULT NULL,
              `tbretofotorelacionaspecto` varchar(20) DEFAULT NULL,
              `tbretofotobrillo` float DEFAULT NULL,
              `tbretofotocontraste` float DEFAULT NULL,
              `tbretofotocolordominante` varchar(50) DEFAULT NULL,
              `tbretofotocomposicion` varchar(50) DEFAULT NULL,
              `tbretofotoestilo` varchar(50) DEFAULT NULL,
              `tbretofotoaprobada` tinyint(1) NOT NULL DEFAULT 1,
              `tbretofotomotivobloqueo` varchar(255) DEFAULT NULL,
              `tbretofotoscoremoderacion` float DEFAULT 0,
              PRIMARY KEY (`tbretofotoid`),
              KEY `idx_retofoto_reto_perfil` (`tbretoid`, `tbperfilid`),
              KEY `idx_retofoto_perfil` (`tbperfilid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->conexion->exec("CREATE TABLE IF NOT EXISTS `tbretovoto` (
              `tbretovotoid` int NOT NULL AUTO_INCREMENT,
              `tbretofotoid` int NOT NULL,
              `tbretoid` int NOT NULL,
              `tbperfilid` int NOT NULL,
              `tbretovotoestrellas` tinyint NOT NULL,
              `tbretovotofechahora` datetime NOT NULL,
              PRIMARY KEY (`tbretovotoid`),
              UNIQUE KEY `idx_voto_foto_perfil` (`tbretofotoid`, `tbperfilid`),
              KEY `idx_voto_reto` (`tbretoid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->conexion->exec("CREATE TABLE IF NOT EXISTS `tbretoinvitacion` (
              `tbretoinvitacionid` int NOT NULL AUTO_INCREMENT,
              `tbretoid` int NOT NULL,
              `tbperfilid` int NOT NULL,
              `tbretoinvitacionfecha` datetime NOT NULL,
              `tbretoinvitacionleida` tinyint(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`tbretoinvitacionid`),
              UNIQUE KEY `idx_reto_invitacion_perfil` (`tbretoid`, `tbperfilid`),
              KEY `idx_reto_invitacion_user` (`tbperfilid`, `tbretoinvitacionleida`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            error_log('RetoModelo: error al verificar esquema - ' . $e->getMessage());
        }
    }

    public function crearReto($perfilId, $tematica, $descripcion, $inicioPart, $finPart, $inicioVot, $finVot)
    {
        $perfilId = (int) $perfilId;
        $tematica = trim((string) $tematica);
        $descripcion = trim((string) $descripcion);

        if ($perfilId <= 0) {
            return ['exito' => false, 'mensaje' => 'Perfil no válido.'];
        }

        if ($tematica === '' || mb_strlen($tematica, 'UTF-8') > 150) {
            return ['exito' => false, 'mensaje' => 'La temática es requerida y debe tener máximo 150 caracteres.'];
        }

        if ($descripcion === '') {
            return ['exito' => false, 'mensaje' => 'La descripción del reto es obligatoria.'];
        }

        $timeInicioPart = strtotime($inicioPart);
        $timeFinPart = strtotime($finPart);
        $timeInicioVot = strtotime($inicioVot);
        $timeFinVot = strtotime($finVot);

        if (!$timeInicioPart || !$timeFinPart || !$timeInicioVot || !$timeFinVot) {
            return ['exito' => false, 'mensaje' => 'Las fechas ingresadas tienen un formato inválido.'];
        }

        if ($timeFinPart <= $timeInicioPart) {
            return ['exito' => false, 'mensaje' => 'El fin de la participación debe ser posterior a su inicio.'];
        }

        if ($timeInicioVot < $timeFinPart) {
            return ['exito' => false, 'mensaje' => 'El inicio de votación debe ser igual o posterior al fin de participación.'];
        }

        if ($timeFinVot <= $timeInicioVot) {
            return ['exito' => false, 'mensaje' => 'El fin de la votación debe ser posterior al inicio de votación.'];
        }

        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbreto (
            tbperfilid, tbretotematica, tbretodescripcion,
            tbretofechainicioparticipacion, tbretofechafinparticipacion,
            tbretofechainiciovotacion, tbretofechafinvotacion,
            tbretofechacreacion
        ) VALUES (
            :perfilId, :tematica, :descripcion,
            :inicioPart, :finPart,
            :inicioVot, :finVot,
            :ahora
        )";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':tematica', $tematica);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':inicioPart', date('Y-m-d H:i:s', $timeInicioPart));
        $stmt->bindValue(':finPart', date('Y-m-d H:i:s', $timeFinPart));
        $stmt->bindValue(':inicioVot', date('Y-m-d H:i:s', $timeInicioVot));
        $stmt->bindValue(':finVot', date('Y-m-d H:i:s', $timeFinVot));
        $stmt->bindValue(':ahora', $ahora);

        if (!$stmt->execute()) {
            return ['exito' => false, 'mensaje' => 'Error al guardar el reto en la base de datos.'];
        }

        $retoId = (int) $this->conexion->lastInsertId();

        $invitados = $this->invitarUsuariosPorTematica($retoId, $tematica, $perfilId);

        return [
            'exito' => true,
            'mensaje' => 'Reto fotográfico creado exitosamente.',
            'retoId' => $retoId,
            'invitados' => $invitados
        ];
    }

    public function invitarUsuariosPorTematica($retoId, $tematica, $creadorId)
    {
        $retoId = (int) $retoId;
        $creadorId = (int) $creadorId;
        $tematicaNormalizada = mb_strtolower(trim($tematica), 'UTF-8');

        $palabras = array_filter(
            preg_split('/[\s,\.\-_]+/', $tematicaNormalizada),
            fn($p) => mb_strlen($p, 'UTF-8') >= 3
        );

        $perfilesInteresados = [];

        if (!empty($palabras)) {
            $clausulas = [];
            $params = [':creadorId' => $creadorId];
            $i = 0;
            foreach ($palabras as $palabra) {
                $i++;
                $key = ':palabra' . $i;
                $clausulas[] = "LOWER(tbperfiltematema) LIKE {$key} OR LOWER(tbperfiltemacategoria) LIKE {$key}";
                $params[$key] = '%' . $palabra . '%';
            }

            $sqlTemas = "SELECT DISTINCT tbperfilid FROM tbperfiltema 
                         WHERE tbperfilid != :creadorId AND (" . implode(' OR ', $clausulas) . ")
                         LIMIT 50";

            $stmt = $this->conexion->prepare($sqlTemas);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            $perfilesInteresados = array_column($stmt->fetchAll(), 'tbperfilid');
        }

        if (count($perfilesInteresados) < 5) {
            $sqlActivos = "SELECT DISTINCT tbperfilid FROM tbretofoto 
                           WHERE tbperfilid != :creadorId 
                           ORDER BY tbretofotoid DESC 
                           LIMIT 30";
            $stmtActivos = $this->conexion->prepare($sqlActivos);
            $stmtActivos->bindValue(':creadorId', $creadorId, PDO::PARAM_INT);
            $stmtActivos->execute();
            $otros = array_column($stmtActivos->fetchAll(), 'tbperfilid');
            $perfilesInteresados = array_unique(array_merge($perfilesInteresados, $otros));
        }

        if (count($perfilesInteresados) < 3) {
            $sqlGeneral = "SELECT tbperfilid FROM tbperfil 
                           WHERE tbperfilid != :creadorId AND tbperfilactivo = 1 
                           ORDER BY RAND() LIMIT 20";
            $stmtGeneral = $this->conexion->prepare($sqlGeneral);
            $stmtGeneral->bindValue(':creadorId', $creadorId, PDO::PARAM_INT);
            $stmtGeneral->execute();
            $generales = array_column($stmtGeneral->fetchAll(), 'tbperfilid');
            $perfilesInteresados = array_unique(array_merge($perfilesInteresados, $generales));
        }

        $ahora = date('Y-m-d H:i:s');
        $insertados = 0;

        $stmtInv = $this->conexion->prepare("INSERT IGNORE INTO tbretoinvitacion (tbretoid, tbperfilid, tbretoinvitacionfecha) VALUES (:retoId, :perfilId, :fecha)");

        foreach ($perfilesInteresados as $pid) {
            $stmtInv->bindValue(':retoId', $retoId, PDO::PARAM_INT);
            $stmtInv->bindValue(':perfilId', (int) $pid, PDO::PARAM_INT);
            $stmtInv->bindValue(':fecha', $ahora);
            if ($stmtInv->execute() && $stmtInv->rowCount() > 0) {
                $insertados++;
            }
        }

        return $insertados;
    }

    public function determinarEstadoReto(array $reto)
    {
        $ahora = time();
        $iniPart = strtotime($reto['tbretofechainicioparticipacion']);
        $finPart = strtotime($reto['tbretofechafinparticipacion']);
        $iniVot = strtotime($reto['tbretofechainiciovotacion']);
        $finVot = strtotime($reto['tbretofechafinvotacion']);

        if ($ahora > $finVot) {
            return 'finalizado';
        }
        if ($ahora >= $iniVot) {
            return 'votacion';
        }
        if ($ahora >= $iniPart && $ahora <= $finPart) {
            return 'participacion';
        }
        if ($ahora > $finPart && $ahora < $iniVot) {
            return 'espera_votacion';
        }
        return 'proximo';
    }

    public function actualizarCierresAutomaticos()
    {
        $ahora = date('Y-m-d H:i:s');
        $sql = "UPDATE tbreto 
                SET tbretocerrado = 1 
                WHERE tbretocerrado = 0 AND tbretofechafinvotacion < :ahora";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->execute();
    }

    public function getRetos($filtro = 'todos', $perfilIdActual = null)
    {
        $this->actualizarCierresAutomaticos();

        $sql = "SELECT r.*, p.tbperfilnombre AS creadornombre,
                       (SELECT COUNT(*) FROM tbretofoto f WHERE f.tbretoid = r.tbretoid AND f.tbretofotoactivo = 1) AS totalfotos,
                       (SELECT COUNT(*) FROM tbretovoto v WHERE v.tbretoid = r.tbretoid) AS totalvotos
                FROM tbreto r
                INNER JOIN tbperfil p ON r.tbperfilid = p.tbperfilid
                ORDER BY r.tbretoid DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $retos = $stmt->fetchAll();

        $resultado = [];
        foreach ($retos as $r) {
            $estado = $this->determinarEstadoReto($r);
            $r['estado'] = $estado;

            $r['misFotosCount'] = 0;
            if ($perfilIdActual) {
                $stmtMis = $this->conexion->prepare("SELECT COUNT(*) FROM tbretofoto WHERE tbretoid = :retoId AND tbperfilid = :perfilId AND tbretofotoactivo = 1");
                $stmtMis->bindValue(':retoId', (int) $r['tbretoid'], PDO::PARAM_INT);
                $stmtMis->bindValue(':perfilId', (int) $perfilIdActual, PDO::PARAM_INT);
                $stmtMis->execute();
                $r['misFotosCount'] = (int) $stmtMis->fetchColumn();
            }

            if ($filtro === 'activos' && ($estado === 'participacion' || $estado === 'votacion' || $estado === 'espera_votacion')) {
                $resultado[] = $r;
            } elseif ($filtro === 'finalizados' && $estado === 'finalizado') {
                $resultado[] = $r;
            } elseif ($filtro === 'todos') {
                $resultado[] = $r;
            }
        }

        return $resultado;
    }

    public function getRetoDetalle($retoId, $perfilIdActual = null)
    {
        $this->actualizarCierresAutomaticos();

        $sql = "SELECT r.*, p.tbperfilnombre AS creadornombre
                FROM tbreto r
                INNER JOIN tbperfil p ON r.tbperfilid = p.tbperfilid
                WHERE r.tbretoid = :retoId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':retoId', (int) $retoId, PDO::PARAM_INT);
        $stmt->execute();
        $reto = $stmt->fetch();

        if (!$reto) {
            return null;
        }

        $estado = $this->determinarEstadoReto($reto);
        $reto['estado'] = $estado;

        $misFotos = [];
        $misFotosCount = 0;
        if ($perfilIdActual) {
            $stmtFotos = $this->conexion->prepare("SELECT * FROM tbretofoto WHERE tbretoid = :retoId AND tbperfilid = :perfilId AND tbretofotoactivo = 1 ORDER BY tbretofotoid DESC");
            $stmtFotos->bindValue(':retoId', (int) $retoId, PDO::PARAM_INT);
            $stmtFotos->bindValue(':perfilId', (int) $perfilIdActual, PDO::PARAM_INT);
            $stmtFotos->execute();
            $misFotos = $stmtFotos->fetchAll();
            $misFotosCount = count($misFotos);
        }

        $reto['misFotos'] = $misFotos;
        $reto['misFotosCount'] = $misFotosCount;
        $reto['puedeSubir'] = ($estado === 'participacion' && $misFotosCount < 3);
        $reto['puedeRetirar'] = ($estado === 'participacion');
        $reto['puedeVotar'] = ($estado === 'votacion');

        return $reto;
    }

    public function inscribirFotografia($retoId, $perfilId, $titulo, $descripcion, $archivoTmp, $nombreOriginal)
    {
        $retoId = (int) $retoId;
        $perfilId = (int) $perfilId;
        $titulo = trim((string) $titulo);
        $descripcion = trim((string) $descripcion);

        $reto = $this->getRetoDetalle($retoId, $perfilId);
        if (!$reto) {
            return ['exito' => false, 'mensaje' => 'El reto no existe.'];
        }

        if ($reto['estado'] !== 'participacion') {
            return ['exito' => false, 'mensaje' => 'El período de participación para este reto ya no está activo.'];
        }

        if ($reto['misFotosCount'] >= 3) {
            return ['exito' => false, 'mensaje' => 'Ya has presentado el máximo de 3 fotografías permitidas para este reto.'];
        }

        if ($titulo === '' || mb_strlen($titulo, 'UTF-8') > 200) {
            return ['exito' => false, 'mensaje' => 'El título es obligatorio y debe tener máximo 200 caracteres.'];
        }

        if (empty($archivoTmp) || !is_uploaded_file($archivoTmp)) {
            return ['exito' => false, 'mensaje' => 'Debe seleccionar un archivo de imagen válido desde su dispositivo.'];
        }

        $info = @getimagesize($archivoTmp);
        if (!$info) {
            return ['exito' => false, 'mensaje' => 'El archivo proporcionado no es una imagen válida.'];
        }

        $mime = $info['mime'] ?? '';
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $mimesPermitidos, true)) {
            return ['exito' => false, 'mensaje' => 'Formato no soportado. Se permiten únicamente imágenes JPG, PNG o WebP.'];
        }

        $analisisModeracion = $this->analizarContenidoProhibido($archivoTmp, $mime, $titulo . ' ' . $descripcion);
        if (!$analisisModeracion['aprobada']) {
            return [
                'exito' => false,
                'mensaje' => 'La fotografía no cumple con las políticas de contenido: ' . $analisisModeracion['motivo']
            ];
        }

        $analisisEstructural = $this->analizarEstructuraImagen($archivoTmp, $mime);

        $ext = 'jpg';
        if ($mime === 'image/png') {
            $ext = 'png';
        } elseif ($mime === 'image/webp') {
            $ext = 'webp';
        }

        $nombreArchivo = 'reto_' . $retoId . '_' . $perfilId . '_' . time() . '_' . substr(bin2hex(random_bytes(6)), 0, 8) . '.' . $ext;
        $directorioDestino = BASE_PATH . '/Publico/imagenes/retos';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $rutaFinal = $directorioDestino . '/' . $nombreArchivo;
        if (!move_uploaded_file($archivoTmp, $rutaFinal)) {
            return ['exito' => false, 'mensaje' => 'No se pudo guardar la fotografía en el servidor.'];
        }

        $rutaRelativa = 'imagenes/retos/' . $nombreArchivo;
        $ahora = date('Y-m-d H:i:s');

        $sql = "INSERT INTO tbretofoto (
            tbretoid, tbperfilid, tbretofototitulo, tbretofotodescripcion,
            tbretofotoarchivo, tbretofotofechasubida, tbretofotoactivo,
            tbretofotoancho, tbretofotoalto, tbretofotorelacionaspecto,
            tbretofotobrillo, tbretofotocontraste, tbretofotocolordominante,
            tbretofotocomposicion, tbretofotoestilo,
            tbretofotoaprobada, tbretofotomotivobloqueo, tbretofotoscoremoderacion
        ) VALUES (
            :retoId, :perfilId, :titulo, :descripcion,
            :archivo, :ahora, 1,
            :ancho, :alto, :relacion,
            :brillo, :contraste, :color,
            :composicion, :estilo,
            1, NULL, :scoreMod
        )";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':retoId', $retoId, PDO::PARAM_INT);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $titulo);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':archivo', $rutaRelativa);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->bindValue(':ancho', $analisisEstructural['ancho'], PDO::PARAM_INT);
        $stmt->bindValue(':alto', $analisisEstructural['alto'], PDO::PARAM_INT);
        $stmt->bindValue(':relacion', $analisisEstructural['relacionAspecto']);
        $stmt->bindValue(':brillo', $analisisEstructural['brillo']);
        $stmt->bindValue(':contraste', $analisisEstructural['contraste']);
        $stmt->bindValue(':color', $analisisEstructural['colorDominante']);
        $stmt->bindValue(':composicion', $analisisEstructural['composicion']);
        $stmt->bindValue(':estilo', $analisisEstructural['estilo']);
        $stmt->bindValue(':scoreMod', $analisisModeracion['score']);

        if (!$stmt->execute()) {
            @unlink($rutaFinal);
            return ['exito' => false, 'mensaje' => 'Error al registrar la fotografía en la base de datos.'];
        }

        $fotoId = (int) $this->conexion->lastInsertId();

        return [
            'exito' => true,
            'mensaje' => 'Fotografía inscrita con éxito.',
            'fotoId' => $fotoId,
            'archivo' => $rutaRelativa,
            'analisis' => $analisisEstructural
        ];
    }

    public function retirarFotografia($fotoId, $perfilId)
    {
        $fotoId = (int) $fotoId;
        $perfilId = (int) $perfilId;

        $stmt = $this->conexion->prepare("SELECT f.*, r.tbretofechainicioparticipacion, r.tbretofechafinparticipacion, r.tbretofechainiciovotacion, r.tbretofechafinvotacion 
                                          FROM tbretofoto f
                                          INNER JOIN tbreto r ON f.tbretoid = r.tbretoid
                                          WHERE f.tbretofotoid = :fotoId");
        $stmt->bindValue(':fotoId', $fotoId, PDO::PARAM_INT);
        $stmt->execute();
        $foto = $stmt->fetch();

        if (!$foto) {
            return ['exito' => false, 'mensaje' => 'Fotografía no encontrada.'];
        }

        if ((int) $foto['tbperfilid'] !== $perfilId) {
            return ['exito' => false, 'mensaje' => 'Solo el autor puede retirar su fotografía.'];
        }

        $estado = $this->determinarEstadoReto($foto);
        if ($estado !== 'participacion') {
            return ['exito' => false, 'mensaje' => 'Solo se pueden retirar fotografías durante el período de participación.'];
        }

        $stmtDel = $this->conexion->prepare("UPDATE tbretofoto SET tbretofotoactivo = 0 WHERE tbretofotoid = :fotoId");
        $stmtDel->bindValue(':fotoId', $fotoId, PDO::PARAM_INT);
        $stmtDel->execute();

        return ['exito' => true, 'mensaje' => 'Fotografía retirada correctamente del reto.'];
    }

    public function votarFotografia($fotoId, $perfilId, $estrellas)
    {
        $fotoId = (int) $fotoId;
        $perfilId = (int) $perfilId;
        $estrellas = (int) $estrellas;

        if ($estrellas < 1 || $estrellas > 5) {
            return ['exito' => false, 'mensaje' => 'La valoración debe ser entre 1 y 5 estrellas.'];
        }

        $stmt = $this->conexion->prepare("SELECT f.*, r.tbretofechainicioparticipacion, r.tbretofechafinparticipacion, r.tbretofechainiciovotacion, r.tbretofechafinvotacion 
                                          FROM tbretofoto f
                                          INNER JOIN tbreto r ON f.tbretoid = r.tbretoid
                                          WHERE f.tbretofotoid = :fotoId AND f.tbretofotoactivo = 1");
        $stmt->bindValue(':fotoId', $fotoId, PDO::PARAM_INT);
        $stmt->execute();
        $foto = $stmt->fetch();

        if (!$foto) {
            return ['exito' => false, 'mensaje' => 'Fotografía no encontrada o no disponible.'];
        }

        $retoId = (int) $foto['tbretoid'];
        $estado = $this->determinarEstadoReto($foto);

        if ($estado !== 'votacion') {
            return ['exito' => false, 'mensaje' => 'No se pueden emitir votos fuera del período de votación establecido.'];
        }

        if ((int) $foto['tbperfilid'] === $perfilId) {
            return ['exito' => false, 'mensaje' => 'No puedes votar por tus propias fotografías.'];
        }

        $stmtCheck = $this->conexion->prepare("SELECT tbretovotoid FROM tbretovoto WHERE tbretofotoid = :fotoId AND tbperfilid = :perfilId");
        $stmtCheck->bindValue(':fotoId', $fotoId, PDO::PARAM_INT);
        $stmtCheck->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            return ['exito' => false, 'mensaje' => 'Ya has votado por esta fotografía. Solo se permite un voto por fotografía.'];
        }

        $ahora = date('Y-m-d H:i:s');
        $stmtIns = $this->conexion->prepare("INSERT INTO tbretovoto (tbretofotoid, tbretoid, tbperfilid, tbretovotoestrellas, tbretovotofechahora) VALUES (:fotoId, :retoId, :perfilId, :estrellas, :ahora)");
        $stmtIns->bindValue(':fotoId', $fotoId, PDO::PARAM_INT);
        $stmtIns->bindValue(':retoId', $retoId, PDO::PARAM_INT);
        $stmtIns->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmtIns->bindValue(':estrellas', $estrellas, PDO::PARAM_INT);
        $stmtIns->bindValue(':ahora', $ahora);

        if (!$stmtIns->execute()) {
            return ['exito' => false, 'mensaje' => 'No se pudo registrar el voto.'];
        }

        return ['exito' => true, 'mensaje' => 'Voto registrado con éxito.'];
    }

    public function getRankingReto($retoId, $perfilIdActual = null)
    {
        $this->actualizarCierresAutomaticos();

        $reto = $this->getRetoDetalle($retoId, $perfilIdActual);
        if (!$reto) {
            return null;
        }

        $sql = "SELECT f.*, p.tbperfilnombre AS autornombre,
                       COALESCE(AVG(v.tbretovotoestrellas), 0) AS promediovotos,
                       COUNT(v.tbretovotoid) AS totalvotos,
                       SUM(CASE WHEN v.tbretovotoestrellas = 5 THEN 1 ELSE 0 END) AS votoscincoestrellas
                FROM tbretofoto f
                INNER JOIN tbperfil p ON f.tbperfilid = p.tbperfilid
                LEFT JOIN tbretovoto v ON f.tbretofotoid = v.tbretofotoid
                WHERE f.tbretoid = :retoId AND f.tbretofotoactivo = 1
                GROUP BY f.tbretofotoid
                ORDER BY promediovotos DESC,
                         votoscincoestrellas DESC,
                         totalvotos DESC,
                         f.tbretofotofechasubida ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':retoId', (int) $retoId, PDO::PARAM_INT);
        $stmt->execute();
        $fotos = $stmt->fetchAll();

        $misVotos = [];
        if ($perfilIdActual) {
            $stmtMisVotos = $this->conexion->prepare("SELECT tbretofotoid, tbretovotoestrellas FROM tbretovoto WHERE tbretoid = :retoId AND tbperfilid = :perfilId");
            $stmtMisVotos->bindValue(':retoId', (int) $retoId, PDO::PARAM_INT);
            $stmtMisVotos->bindValue(':perfilId', (int) $perfilIdActual, PDO::PARAM_INT);
            $stmtMisVotos->execute();
            foreach ($stmtMisVotos->fetchAll() as $mv) {
                $misVotos[(int) $mv['tbretofotoid']] = (int) $mv['tbretovotoestrellas'];
            }
        }

        $posicion = 1;
        foreach ($fotos as &$foto) {
            $foto['posicion'] = $posicion++;
            $foto['promediovotos'] = round((float) $foto['promediovotos'], 2);
            $foto['totalvotos'] = (int) $foto['totalvotos'];
            $foto['votoscincoestrellas'] = (int) $foto['votoscincoestrellas'];
            $foto['esPropia'] = ($perfilIdActual && (int) $foto['tbperfilid'] === (int) $perfilIdActual);
            $foto['miVoto'] = $misVotos[(int) $foto['tbretofotoid']] ?? null;
        }

        return [
            'reto' => $reto,
            'fotos' => $fotos
        ];
    }

    public function getRetosAnteriores()
    {
        $this->actualizarCierresAutomaticos();

        $sql = "SELECT r.*, p.tbperfilnombre AS creadornombre
                FROM tbreto r
                INNER JOIN tbperfil p ON r.tbperfilid = p.tbperfilid
                WHERE r.tbretocerrado = 1 OR r.tbretofechafinvotacion < :ahora
                ORDER BY r.tbretofechafinvotacion DESC";

        $ahora = date('Y-m-d H:i:s');
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':ahora', $ahora);
        $stmt->execute();
        $retos = $stmt->fetchAll();

        $resultado = [];
        foreach ($retos as $r) {
            $r['estado'] = 'finalizado';
            $ranking = $this->getRankingReto($r['tbretoid']);
            $r['totalfotos'] = count($ranking['fotos'] ?? []);
            $r['totalvotos'] = 0;
            foreach ($ranking['fotos'] ?? [] as $f) {
                $r['totalvotos'] += $f['totalvotos'];
            }
            $r['podio'] = array_slice($ranking['fotos'] ?? [], 0, 3);
            $r['ganador'] = $r['podio'][0] ?? null;
            $resultado[] = $r;
        }

        return $resultado;
    }

    public function getInvitacionesPerfil($perfilId)
    {
        $perfilId = (int) $perfilId;
        $sql = "SELECT i.*, r.tbretotematica, r.tbretodescripcion, 
                       r.tbretofechainicioparticipacion, r.tbretofechafinparticipacion,
                       r.tbretofechainiciovotacion, r.tbretofechafinvotacion,
                       p.tbperfilnombre AS creadornombre
                FROM tbretoinvitacion i
                INNER JOIN tbreto r ON i.tbretoid = r.tbretoid
                INNER JOIN tbperfil p ON r.tbperfilid = p.tbperfilid
                WHERE i.tbperfilid = :perfilId
                ORDER BY i.tbretoinvitacionid DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        $invitaciones = $stmt->fetchAll();

        foreach ($invitaciones as &$inv) {
            $inv['estadoReto'] = $this->determinarEstadoReto($inv);
        }

        return $invitaciones;
    }

    public function contarInvitacionesNoLeidas($perfilId)
    {
        $perfilId = (int) $perfilId;
        $stmt = $this->conexion->prepare("SELECT COUNT(*) FROM tbretoinvitacion WHERE tbperfilid = :perfilId AND tbretoinvitacionleida = 0");
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function marcarInvitacionLeida($invitacionId, $perfilId)
    {
        $stmt = $this->conexion->prepare("UPDATE tbretoinvitacion SET tbretoinvitacionleida = 1 WHERE tbretoinvitacionid = :id AND tbperfilid = :perfilId");
        $stmt->bindValue(':id', (int) $invitacionId, PDO::PARAM_INT);
        $stmt->bindValue(':perfilId', (int) $perfilId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getPerfilFotografico($perfilId)
    {
        $perfilId = (int) $perfilId;

        $sqlFotos = "SELECT f.*, r.tbretotematica, r.tbretocerrado, r.tbretofechafinvotacion,
                            COALESCE(AVG(v.tbretovotoestrellas), 0) AS promediovotos,
                            COUNT(v.tbretovotoid) AS totalvotos
                     FROM tbretofoto f
                     INNER JOIN tbreto r ON f.tbretoid = r.tbretoid
                     LEFT JOIN tbretovoto v ON f.tbretofotoid = v.tbretofotoid
                     WHERE f.tbperfilid = :perfilId AND f.tbretofotoactivo = 1
                     GROUP BY f.tbretofotoid
                     ORDER BY f.tbretofotoid DESC";

        $stmtFotos = $this->conexion->prepare($sqlFotos);
        $stmtFotos->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmtFotos->execute();
        $misFotos = $stmtFotos->fetchAll();

        $retosParticipados = [];
        $totalVotosRecibidos = 0;
        $sumaPromedios = 0;
        $totalVictorias = 0;
        $totalPodios = 0;
        $estilosCount = [];
        $coloresCount = [];
        $composicionesCount = [];

        foreach ($misFotos as &$foto) {
            $foto['promediovotos'] = round((float) $foto['promediovotos'], 2);
            $foto['totalvotos'] = (int) $foto['totalvotos'];
            $totalVotosRecibidos += $foto['totalvotos'];
            $sumaPromedios += $foto['promediovotos'];

            if (!empty($foto['tbretofotoestilo'])) {
                $estilosCount[$foto['tbretofotoestilo']] = ($estilosCount[$foto['tbretofotoestilo']] ?? 0) + 1;
            }
            if (!empty($foto['tbretofotocolordominante'])) {
                $coloresCount[$foto['tbretofotocolordominante']] = ($coloresCount[$foto['tbretofotocolordominante']] ?? 0) + 1;
            }
            if (!empty($foto['tbretofotocomposicion'])) {
                $composicionesCount[$foto['tbretofotocomposicion']] = ($composicionesCount[$foto['tbretofotocomposicion']] ?? 0) + 1;
            }

            $retoId = (int) $foto['tbretoid'];
            if (!isset($retosParticipados[$retoId])) {
                $ranking = $this->getRankingReto($retoId);
                $miPosicion = null;
                foreach ($ranking['fotos'] ?? [] as $rf) {
                    if ((int) $rf['tbretofotoid'] === (int) $foto['tbretofotoid']) {
                        $miPosicion = $rf['posicion'];
                        break;
                    }
                }
                $foto['posicionEnReto'] = $miPosicion;
                if ($miPosicion === 1) {
                    $totalVictorias++;
                    $totalPodios++;
                } elseif ($miPosicion === 2 || $miPosicion === 3) {
                    $totalPodios++;
                }

                $retosParticipados[$retoId] = [
                    'tbretoid' => $retoId,
                    'tematica' => $foto['tbretotematica'],
                    'estado' => $ranking['reto']['estado'] ?? 'finalizado',
                    'miPosicion' => $miPosicion,
                    'fotos' => []
                ];
            } else {
                $ranking = $this->getRankingReto($retoId);
                $miPosicion = null;
                foreach ($ranking['fotos'] ?? [] as $rf) {
                    if ((int) $rf['tbretofotoid'] === (int) $foto['tbretofotoid']) {
                        $miPosicion = $rf['posicion'];
                        break;
                    }
                }
                $foto['posicionEnReto'] = $miPosicion;
            }

            $retosParticipados[$retoId]['fotos'][] = $foto;
        }

        arsort($estilosCount);
        arsort($coloresCount);
        arsort($composicionesCount);

        $estiloPrincipal = !empty($estilosCount) ? array_key_first($estilosCount) : 'Versátil';
        $colorPrincipal = !empty($coloresCount) ? array_key_first($coloresCount) : 'Equilibrado';
        $composicionPrincipal = !empty($composicionesCount) ? array_key_first($composicionesCount) : 'Libre';

        $totalFotos = count($misFotos);
        $promedioGeneral = $totalFotos > 0 ? round($sumaPromedios / $totalFotos, 2) : 0;

        return [
            'totalFotos' => $totalFotos,
            'totalRetos' => count($retosParticipados),
            'totalVictorias' => $totalVictorias,
            'totalPodios' => $totalPodios,
            'totalVotosRecibidos' => $totalVotosRecibidos,
            'promedioGeneral' => $promedioGeneral,
            'perfilEstructural' => [
                'estiloPrincipal' => $estiloPrincipal,
                'colorPrincipal' => $colorPrincipal,
                'composicionPrincipal' => $composicionPrincipal,
                'distribucionEstilos' => $estilosCount,
                'distribucionColores' => $coloresCount,
                'distribucionComposicion' => $composicionesCount
            ],
            'retosParticipados' => array_values($retosParticipados),
            'fotos' => $misFotos
        ];
    }

    private function analizarContenidoProhibido($rutaArchivo, $mime, $textoAsociado)
    {
        $palabrasProhibidas = [
            'porn', 'porno', 'xxx', 'nude', 'desnudo', 'desnuda', 'sex', 'gore',
            'violencia', 'sangre', 'drogas', 'suicidio', 'terrorismo', 'armas', 'odio'
        ];

        $textoNorm = mb_strtolower($textoAsociado, 'UTF-8');
        foreach ($palabrasProhibidas as $palabra) {
            if (mb_strpos($textoNorm, $palabra) !== false) {
                return [
                    'aprobada' => false,
                    'motivo' => 'El texto asociado contiene términos prohibidos.',
                    'score' => 0.95
                ];
            }
        }

        $img = null;
        if ($mime === 'image/jpeg') {
            $img = @imagecreatefromjpeg($rutaArchivo);
        } elseif ($mime === 'image/png') {
            $img = @imagecreatefrompng($rutaArchivo);
        } elseif ($mime === 'image/webp') {
            $img = @imagecreatefromwebp($rutaArchivo);
        }

        if (!$img) {
            return [
                'aprobada' => false,
                'motivo' => 'No se pudo procesar la imagen para su validación.',
                'score' => 0.8
            ];
        }

        $ancho = imagesx($img);
        $alto = imagesy($img);

        $muestraAncho = 40;
        $muestraAlto = 40;
        $pequena = imagecreatetruecolor($muestraAncho, $muestraAlto);
        imagecopyresampled($pequena, $img, 0, 0, 0, 0, $muestraAncho, $muestraAlto, $ancho, $alto);

        $pixelesTonoPiel = 0;
        $totalPixeles = $muestraAncho * $muestraAlto;

        for ($y = 0; $y < $muestraAlto; $y++) {
            for ($x = 0; $x < $muestraAncho; $x++) {
                $rgb = imagecolorat($pequena, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                if ($r > 95 && $g > 40 && $b > 20 &&
                    ($r - $g) > 15 && $r > $b &&
                    abs($r - $g) > 15 &&
                    max($r, $g, $b) - min($r, $g, $b) > 15) {
                    $pixelesTonoPiel++;
                }
            }
        }

        imagedestroy($pequena);
        imagedestroy($img);

        $ratioPiel = $pixelesTonoPiel / $totalPixeles;

        if ($ratioPiel > 0.65) {
            return [
                'aprobada' => false,
                'motivo' => 'La imagen presenta un porcentaje excesivo de patrones corporales o posible desnudez.',
                'score' => round($ratioPiel, 3)
            ];
        }

        return [
            'aprobada' => true,
            'motivo' => null,
            'score' => round($ratioPiel, 3)
        ];
    }

    private function analizarEstructuraImagen($rutaArchivo, $mime)
    {
        $info = @getimagesize($rutaArchivo);
        $ancho = $info[0] ?? 800;
        $alto = $info[1] ?? 600;

        $relacionNumerica = $alto > 0 ? ($ancho / $alto) : 1;
        $composicion = 'horizontal';
        $relacionAspecto = '4:3';

        if ($relacionNumerica >= 1.6) {
            $composicion = 'panoramica';
            $relacionAspecto = '16:9';
        } elseif ($relacionNumerica >= 1.2) {
            $composicion = 'horizontal';
            $relacionAspecto = '4:3';
        } elseif ($relacionNumerica >= 0.85) {
            $composicion = 'cuadrada';
            $relacionAspecto = '1:1';
        } else {
            $composicion = 'vertical';
            $relacionAspecto = '3:4';
        }

        $img = null;
        if ($mime === 'image/jpeg') {
            $img = @imagecreatefromjpeg($rutaArchivo);
        } elseif ($mime === 'image/png') {
            $img = @imagecreatefrompng($rutaArchivo);
        } elseif ($mime === 'image/webp') {
            $img = @imagecreatefromwebp($rutaArchivo);
        }

        $brilloMedio = 50.0;
        $contraste = 20.0;
        $colorDominante = 'neutro';
        $estilo = 'clasico';

        if ($img) {
            $muestraAncho = 32;
            $muestraAlto = 32;
            $pequena = imagecreatetruecolor($muestraAncho, $muestraAlto);
            imagecopyresampled($pequena, $img, 0, 0, 0, 0, $muestraAncho, $muestraAlto, $ancho, $alto);

            $luminancias = [];
            $sumaR = 0;
            $sumaG = 0;
            $sumaB = 0;
            $total = $muestraAncho * $muestraAlto;

            for ($y = 0; $y < $muestraAlto; $y++) {
                for ($x = 0; $x < $muestraAncho; $x++) {
                    $rgb = imagecolorat($pequena, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    $sumaR += $r;
                    $sumaG += $g;
                    $sumaB += $b;

                    $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                    $luminancias[] = $lum;
                }
            }

            imagedestroy($pequena);
            imagedestroy($img);

            $avgLum = array_sum($luminancias) / $total;
            $brilloMedio = round(($avgLum / 255) * 100, 1);

            $varianza = 0;
            foreach ($luminancias as $l) {
                $varianza += pow($l - $avgLum, 2);
            }
            $desviacion = sqrt($varianza / $total);
            $contraste = round(($desviacion / 128) * 100, 1);

            $avgR = $sumaR / $total;
            $avgG = $sumaG / $total;
            $avgB = $sumaB / $total;

            if (abs($avgR - $avgG) < 8 && abs($avgG - $avgB) < 8) {
                $colorDominante = 'monocromatico';
            } elseif ($avgR > $avgB + 15) {
                $colorDominante = 'calido';
            } elseif ($avgB > $avgR + 15) {
                $colorDominante = 'frio';
            } else {
                $colorDominante = 'equilibrado';
            }

            if ($contraste > 45) {
                $estilo = 'alto_contraste';
            } elseif ($brilloMedio > 70) {
                $estilo = 'clave_alta';
            } elseif ($brilloMedio < 30) {
                $estilo = 'clave_baja';
            } elseif ($colorDominante === 'calido') {
                $estilo = 'tonos_calidos';
            } elseif ($colorDominante === 'frio') {
                $estilo = 'tonos_frios';
            } elseif ($colorDominante === 'monocromatico') {
                $estilo = 'blanco_y_negro';
            } else {
                $estilo = 'vibrante_natural';
            }
        }

        return [
            'ancho' => (int) $ancho,
            'alto' => (int) $alto,
            'relacionAspecto' => $relacionAspecto,
            'composicion' => $composicion,
            'brillo' => (float) $brilloMedio,
            'contraste' => (float) $contraste,
            'colorDominante' => $colorDominante,
            'estilo' => $estilo
        ];
    }
}
