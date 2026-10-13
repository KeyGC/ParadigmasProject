<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/comunidadmodelo.php';
require_once __DIR__ . '/moderacionmodelo.php';
require_once __DIR__ . '/perfiltemamodelo.php';

class ComunidadMensajeModelo
{
    private $conexion;

    private $comunidadModelo;

    private $moderacionModelo;

    private $perfilTemaModelo;

    private $longitudMaxima = 1000;

    private $limiteMensajes = 100;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
        $this->comunidadModelo = new ComunidadModelo();
        $this->moderacionModelo = new ModeracionModelo();
        $this->perfilTemaModelo = new PerfilTemaModelo();
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

        $comunidad = $this->comunidadModelo->getComunidadPorId($comunidadId);

        if (!$comunidad['exito']) {
            return ['exito' => false, 'mensaje' => 'La comunidad no existe o está inactiva.'];
        }

        if (!$this->perfilExisteYActivo($emisorId)) {
            return ['exito' => false, 'mensaje' => 'El perfil no existe o está inactivo.'];
        }

        $moderacion = $this->moderacionModelo->evaluar($texto);

        $ahora = date('Y-m-d H:i:s');

        if (!$moderacion['exito']) {
            error_log('Moderacion: envio rechazado en comunidad ' . $comunidadId . ' - ' . $moderacion['mensaje']);

            return [
                'exito' => true,
                'data' => [
                    'tbcomunidadmensajeid' => null,
                    'tbcomunidadid' => $comunidadId,
                    'tbperfilidemisor' => $emisorId,
                    'tbperfilnombre' => null,
                    'tbcomunidadmensajetexto' => $moderacion['textoEnmascarado'],
                    'tbcomunidadmensajefechahora' => $ahora,
                    'tbcomunidadmensajebloqueado' => true,
                    'tbcomunidadmensajecategoria' => $moderacion['categoria'],
                    'tbcomunidadmensajescore' => $moderacion['score'],
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

        $sql = "INSERT INTO tbcomunidadmensaje
                    (tbcomunidadid, tbperfilidemisor, tbcomunidadmensajetexto,
                     tbcomunidadmensajefechahora, tbcomunidadmensajebloqueado,
                     tbcomunidadmensajecategoria, tbcomunidadmensajescore)
                VALUES (:comunidadId, :emisor, :texto, :fecha, :bloqueado, :categoria, :score)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':comunidadId', $comunidadId, PDO::PARAM_INT);
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

        $stmtNombre = $this->conexion->prepare('SELECT tbperfilnombre FROM tbperfil WHERE tbperfilid = :id');
        $stmtNombre->bindValue(':id', $emisorId, PDO::PARAM_INT);
        $stmtNombre->execute();
        $perfilNombre = $stmtNombre->fetchColumn();

        $datosComunidad = $comunidad['data'];
        $temaSala = preg_replace('/^Comunidad de\s+/iu', '', (string) $datosComunidad['tbcomunidadnombre']);

        $this->registrarSenalesTema(
            $emisorId,
            $bloqueado,
            $categoria,
            $texto,
            'comunidad',
            $temaSala,
            $datosComunidad['tbcomunidadcategoria']
        );

        return [
            'exito' => true,
            'data' => [
                'tbcomunidadmensajeid' => $nuevoId,
                'tbcomunidadid' => $comunidadId,
                'tbperfilidemisor' => $emisorId,
                'tbperfilnombre' => $perfilNombre !== false ? $perfilNombre : null,
                'tbcomunidadmensajetexto' => $textoGuardado,
                'tbcomunidadmensajefechahora' => $ahora,
                'tbcomunidadmensajebloqueado' => $bloqueado,
                'tbcomunidadmensajecategoria' => $categoriaGuardada,
                'tbcomunidadmensajescore' => $scoreGuardado
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

        $sql = "SELECT m.tbcomunidadmensajeid, m.tbcomunidadid, m.tbperfilidemisor,
                       m.tbcomunidadmensajetexto, m.tbcomunidadmensajefechahora,
                       m.tbcomunidadmensajebloqueado, m.tbcomunidadmensajecategoria,
                       m.tbcomunidadmensajescore,
                       p.tbperfilnombre
                FROM (
                    SELECT x.tbcomunidadmensajeid, x.tbcomunidadid, x.tbperfilidemisor,
                           x.tbcomunidadmensajetexto, x.tbcomunidadmensajefechahora,
                           x.tbcomunidadmensajebloqueado, x.tbcomunidadmensajecategoria,
                           x.tbcomunidadmensajescore
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
            $bloqueado = (bool) $fila['tbcomunidadmensajebloqueado'];

            $mensajes[] = [
                'tbcomunidadmensajeid' => (int) $fila['tbcomunidadmensajeid'],
                'tbcomunidadid' => (int) $fila['tbcomunidadid'],
                'tbperfilidemisor' => (int) $fila['tbperfilidemisor'],
                'tbperfilnombre' => $fila['tbperfilnombre'],
                'tbcomunidadmensajetexto' => $bloqueado ? MODERACION_TEXTO_BLOQUEADO : $fila['tbcomunidadmensajetexto'],
                'tbcomunidadmensajefechahora' => $fila['tbcomunidadmensajefechahora'],
                'tbcomunidadmensajebloqueado' => $bloqueado,
                'tbcomunidadmensajecategoria' => $fila['tbcomunidadmensajecategoria'],
                'tbcomunidadmensajescore' => $fila['tbcomunidadmensajescore'] !== null ? (float) $fila['tbcomunidadmensajescore'] : null
            ];
        }

        return ['exito' => true, 'data' => $mensajes];
    }

    private function registrarSenalesTema($perfilId, $bloqueado, $categoria, $texto, $origen, $temaSala = null, $categoriaSala = null)
    {
        try {
            $registro = $this->perfilTemaModelo->registrarDesdeMensaje(
                $perfilId,
                $bloqueado,
                $categoria,
                $texto,
                $origen,
                $temaSala,
                $categoriaSala
            );

            if (!$registro['exito']) {
                error_log('Afinidad: no se registro la senal - ' . ($registro['mensaje'] ?? 'desconocido'));
            }
        } catch (Throwable $e) {
            error_log('Afinidad: excepcion al registrar senal - ' . $e->getMessage());
        }
    }
}