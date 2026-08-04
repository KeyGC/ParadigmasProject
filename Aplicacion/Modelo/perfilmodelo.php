<?php
// Aplicacion/Modelo/PerfilModelo.php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/perfil.php';

class PerfilModelo
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    // Convierte una fila de la BD en un array a través de la clase Perfil (evita repetir el mapeo)
    private function mapearFila($fila)
    {
        return (new Perfil(
            $fila['tbperfilid'],
            $fila['tbperfilnombre'],
            $fila['tbperfilcontra'],
            $fila['tbperfilcorreo'],
            $fila['tbperfilcambiocontra'],
            $fila['tbubicacionid'],
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
                (tbubicacionid, tbperfilnombre, tbperfilcontra, tbperfilcorreo, tbperfilcambiocontra, tbperfilactivo)
                VALUES (:tbubicacionid, :nombre, :contra, :correo, :cambioContra, :activo)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':tbubicacionid', $perfil->get_tbubicacionid(), PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $perfil->get_tbperfilnombre());
        $stmt->bindValue(':contra', $perfil->get_tbperfilcontra());
        $stmt->bindValue(':correo', $perfil->get_tbperfilcorreo());
        $stmt->bindValue(':cambioContra', $perfil->get_tbperfilcambiocontra(), PDO::PARAM_INT);
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
                    tbperfilactivo = :activo
                WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $perfil->get_tbperfilnombre());
        $stmt->bindValue(':contra', $perfil->get_tbperfilcontra());
        $stmt->bindValue(':correo', $perfil->get_tbperfilcorreo());
        $stmt->bindValue(':cambioContra', $perfil->get_tbperfilcambiocontra(), PDO::PARAM_INT);
        $stmt->bindValue(':tbubicacionid', $perfil->get_tbubicacionid(), PDO::PARAM_INT);
        $stmt->bindValue(':activo', $perfil->get_tbperfilactivo(), PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $perfil->get_tbperfilid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM tbperfil WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

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
}
