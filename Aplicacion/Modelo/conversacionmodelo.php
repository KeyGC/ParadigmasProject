<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';

class ConversacionModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
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

    private function mapear($fila, $perfilId)
    {
        $otroId = ((int) $fila['tbperfilid1'] === (int) $perfilId)
            ? (int) $fila['tbperfilid2']
            : (int) $fila['tbperfilid1'];

        return [
            'tbconversacionid' => (int) $fila['tbconversacionid'],
            'tbperfilidotro' => $otroId,
            'tbperfilnombreotro' => $fila['tbperfilnombreotro'] ?? null,
            'tbconversacionfechacreacion' => $fila['tbconversacionfechacreacion']
        ];
    }

    private function buscar($menor, $mayor, $perfilId)
    {
        $sql = "SELECT c.tbconversacionid, c.tbperfilid1, c.tbperfilid2,
                       c.tbconversacionfechacreacion,
                       o.tbperfilnombre AS tbperfilnombreotro
                FROM tbconversacion c
                INNER JOIN tbperfil o
                    ON o.tbperfilid = CASE WHEN c.tbperfilid1 = :perfilCaso THEN c.tbperfilid2 ELSE c.tbperfilid1 END
                WHERE c.tbperfilid1 = :menor AND c.tbperfilid2 = :mayor";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilCaso', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':menor', (int) $menor, PDO::PARAM_INT);
        $stmt->bindValue(':mayor', (int) $mayor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function obtenerOCrear($perfilId1, $perfilId2)
    {
        $perfilId1 = (int) $perfilId1;
        $perfilId2 = (int) $perfilId2;

        if ($perfilId1 <= 0 || $perfilId2 <= 0) {
            return ['exito' => false, 'mensaje' => 'Perfiles inválidos.'];
        }

        if ($perfilId1 === $perfilId2) {
            return ['exito' => false, 'mensaje' => 'No puedes abrir un chat contigo mismo.'];
        }

        if (!$this->perfilExisteYActivo($perfilId1) || !$this->perfilExisteYActivo($perfilId2)) {
            return ['exito' => false, 'mensaje' => 'Alguno de los perfiles no existe o está inactivo.'];
        }

        $menor = min($perfilId1, $perfilId2);
        $mayor = max($perfilId1, $perfilId2);

        $fila = $this->buscar($menor, $mayor, $perfilId1);
        if ($fila) {
            return ['exito' => true, 'data' => $this->mapear($fila, $perfilId1)];
        }

        $sql = "INSERT INTO tbconversacion (tbperfilid1, tbperfilid2, tbconversacionfechacreacion)
                VALUES (:menor, :mayor, :fecha)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':menor', $menor, PDO::PARAM_INT);
        $stmt->bindValue(':mayor', $mayor, PDO::PARAM_INT);
        $stmt->bindValue(':fecha', date('Y-m-d H:i:s'));

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            return ['exito' => false, 'mensaje' => 'No se pudo crear la conversación.'];
        }

        $creada = $this->buscar($menor, $mayor, $perfilId1);
        if (!$creada) {
            return ['exito' => false, 'mensaje' => 'No se pudo leer la conversación recién creada.'];
        }

        return ['exito' => true, 'data' => $this->mapear($creada, $perfilId1)];
    }

    public function existe($conversacionId)
    {
        $sql = "SELECT COUNT(*)
                FROM tbconversacion
                WHERE tbconversacionid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $conversacionId, PDO::PARAM_INT);
        $stmt->execute();
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function esParticipante($conversacionId, $perfilId)
    {
        $sql = "SELECT COUNT(*)
                FROM tbconversacion
                WHERE tbconversacionid = :id
                  AND (tbperfilid1 = :perfil OR tbperfilid2 = :perfil2)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', (int) $conversacionId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil2', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function getConversacionesPorPerfil($perfilId)
    {
        $sql = "SELECT c.tbconversacionid,
                       c.tbperfilid1, c.tbperfilid2,
                       c.tbconversacionfechacreacion,
                       o.tbperfilnombre AS tbperfilnombreotro,
                       u.tbmensajetexto AS tbmensajetextoultimo,
                       u.tbmensajefechahora AS tbmensajefechahoraultimo,
                       u.tbperfilidemisor AS tbmensajeemisorultimo,
                       (SELECT COUNT(*)
                          FROM tbmensaje n
                         WHERE n.tbconversacionid = c.tbconversacionid
                           AND n.tbperfilidemisor <> :perfilNoLeidos
                           AND n.tbmensajeleido = FALSE
                           AND n.tbmensajebloqueado = FALSE) AS tbmensajenoleidos
                FROM tbconversacion c
                INNER JOIN tbperfil o
                    ON o.tbperfilid = CASE WHEN c.tbperfilid1 = :perfilCaso THEN c.tbperfilid2 ELSE c.tbperfilid1 END
                LEFT JOIN tbmensaje u
                    ON u.tbmensajeid = (SELECT MAX(x.tbmensajeid)
                                          FROM tbmensaje x
                                         WHERE x.tbconversacionid = c.tbconversacionid)
                WHERE c.tbperfilid1 = :perfil1 OR c.tbperfilid2 = :perfil2
                ORDER BY COALESCE(u.tbmensajefechahora, c.tbconversacionfechacreacion) DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilNoLeidos', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':perfilCaso', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil1', (int) $perfilId, PDO::PARAM_INT);
        $stmt->bindValue(':perfil2', (int) $perfilId, PDO::PARAM_INT);
        $stmt->execute();

        $filas = $stmt->fetchAll();

        $conversaciones = [];
        foreach ($filas as $fila) {
            $conversaciones[] = $this->mapear($fila, $perfilId) + [
                'tbmensajetextoultimo' => $fila['tbmensajetextoultimo'],
                'tbmensajefechahoraultimo' => $fila['tbmensajefechahoraultimo'],
                'tbmensajeemisorultimo' => $fila['tbmensajeemisorultimo'] !== null ? (int) $fila['tbmensajeemisorultimo'] : null,
                'tbmensajenoleidos' => (int) $fila['tbmensajenoleidos']
            ];
        }

        return ['exito' => true, 'data' => $conversaciones];
    }
}