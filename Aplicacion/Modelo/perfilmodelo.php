<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/perfil.php';

class PerfilModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function mapearFila($fila)
    {
        return (new Perfil(
            $fila['tbperfilid'],
            $fila['tbperfilnombre'],
            $fila['tbperfilcontra'],
            $fila['tbperfilcorreo'],
            $fila['tbperfilcambiocontra'],
            $fila['tbubicacionid'],
            $fila['tbperfilrol'],
            $fila['tbperfilactivo']
        ))->toArray();
    }

    public function getList()
    {
        $sql = "SELECT * FROM tbperfil ORDER BY tbperfilid ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $perfiles = [];
        foreach ($filas as $fila) {
            $perfiles[] = $this->mapearFila($fila);
        }
        return $perfiles;
    }

    public function getPerfil($id)
    {
        $sql = "SELECT * FROM tbperfil WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? $this->mapearFila($fila) : null;
    }

    public function getPerfilByNombre($nombre)
    {
        $sql = "SELECT * FROM tbperfil WHERE tbperfilnombre = :nombre";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? $this->mapearFila($fila) : null;
    }

    public function insert(Perfil $perfil)
    {
        $sql = "INSERT INTO tbperfil
                (tbubicacionid, tbperfilnombre, tbperfilcontra, tbperfilcorreo, tbperfilcambiocontra, tbperfilrol, tbperfilactivo)
                VALUES (:tbubicacionid, :nombre, :contra, :correo, :cambioContra, :rol, :activo)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':tbubicacionid', $perfil->get_tbubicacionid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $perfil->get_tbperfilnombre());
        $stmt->bindValue(':contra', $perfil->get_tbperfilcontra());
        $stmt->bindValue(':correo', $perfil->get_tbperfilcorreo());
        $stmt->bindValue(':cambioContra', $perfil->get_tbperfilcambiocontra(), PDO::PARAM_INT);
        $stmt->bindValue(':rol', $perfil->get_tbperfilrol());
        $stmt->bindValue(':activo', $perfil->get_tbperfilactivo(), PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }

    public function update(Perfil $perfil)
    {
        $sql = "UPDATE tbperfil SET
                    tbperfilnombre = :nombre,
                    tbperfilcontra = :contra,
                    tbperfilcorreo = :correo,
                    tbperfilcambiocontra = :cambioContra,
                    tbubicacionid = :tbubicacionid,
                    tbperfilrol = :rol,
                    tbperfilactivo = :activo
                WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $perfil->get_tbperfilnombre());
        $stmt->bindValue(':contra', $perfil->get_tbperfilcontra());
        $stmt->bindValue(':correo', $perfil->get_tbperfilcorreo());
        $stmt->bindValue(':cambioContra', $perfil->get_tbperfilcambiocontra(), PDO::PARAM_INT);
        $stmt->bindValue(':tbubicacionid', $perfil->get_tbubicacionid(), PDO::PARAM_INT);
        $stmt->bindValue(':rol', $perfil->get_tbperfilrol());
        $stmt->bindValue(':activo', $perfil->get_tbperfilactivo(), PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $perfil->get_tbperfilid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function toggleEstado($id)
    {
        $sql = "UPDATE tbperfil SET tbperfilactivo = NOT tbperfilactivo WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM tbperfil WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function setUbicacion($perfilId, $ubicacionId)
    {
        $sql = "UPDATE tbperfil SET tbubicacionid = :ubicacionId WHERE tbperfilid = :perfilId";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':ubicacionId', $ubicacionId, PDO::PARAM_INT);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function login($nombre, $contra)
    {
        $sql = "SELECT * FROM tbperfil WHERE tbperfilnombre = :nombre AND tbperfilcontra = :contra";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':contra', $contra);
        $stmt->execute();
        $fila = $stmt->fetch();

        return $fila ? $this->mapearFila($fila) : null;
    }

    public function getCoincidencias(array $ids, $excluirId)
    {
        if (empty($ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        $marcadores = [];
        foreach ($ids as $i => $id) {
            $marcadores[] = ':id' . $i;
        }

        $sql = "SELECT p.tbperfilid, p.tbperfilnombre, p.tbperfilcorreo, p.tbperfilrol,
                       u.tbubicacionprovincia, u.tbubicacioncanton, u.tbubicaciondistrito,
                       u.tbubicacionlatitud, u.tbubicacionlongitud
                FROM tbperfil p
                LEFT JOIN tbubicacion u ON p.tbubicacionid = u.tbubicacionid
                WHERE p.tbperfilactivo = TRUE
                  AND p.tbperfilid IN (" . implode(',', $marcadores) . ")
                  AND p.tbperfilid <> :excluirId
                ORDER BY p.tbperfilnombre";
        $stmt = $this->conexion->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue(':id' . $i, $id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':excluirId', (int) $excluirId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}