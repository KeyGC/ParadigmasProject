<?php
// Aplicacion/Modelo/PerfilModelo.php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/Perfil.php';

class PerfilModelo {
    private $conexion;

    public function __construct() {
        $this->conexion = Basedatos::conectar();
    }

    // GETLIST - Obtener todos los perfiles
    public function getList() {
        $sql = "SELECT * FROM tbperfil ORDER BY tbperfilid ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $perfiles = [];
        foreach ($filas as $fila) {
            $perfiles[] = (new Perfil($fila['tbperfilid'], $fila['tbperfilnombre'], $fila['tbperfilcontra'], $fila['tbperfilcorreo']))->toArray();
        }
        return $perfiles;
    }

    // GETPERFIL - Obtener un perfil por id
    public function getPerfil($id) {
        $sql = "SELECT * FROM tbperfil WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return (new Perfil($fila['tbperfilid'], $fila['tbperfilnombre'], $fila['tbperfilcontra'], $fila['tbperfilcorreo']))->toArray();
            
        }
        return null;
    }

    // INSERT - Crear un nuevo perfil
    public function insert(Perfil $perfil) {
        $sql = "INSERT INTO tbperfil (tbperfilnombre, tbperfilcontra, tbperfilcorreo) VALUES (:nombre, :contra, :correo)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $perfil->get_tbperfilnombre());
        $stmt->bindValue(':contra', $perfil->get_tbperfilcontra());
        $stmt->bindValue(':correo', $perfil->get_tbperfilcorreo());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }
    
    // UPDATE - Actualizar un perfil existente
    public function update(Perfil $perfil) {
        $sql = "UPDATE tbperfil SET tbperfilnombre = :nombre, tbperfilcontra = :contra, tbperfilcorreo = :correo WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nombre', $perfil->get_tbperfilnombre());
        $stmt->bindValue(':contra', $perfil->get_tbperfilcontra());
        $stmt->bindValue(':correo', $perfil->get_tbperfilcorreo());
        $stmt->bindValue(':id', $perfil->get_tbperfilid(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    // DELETE - Eliminar un perfil por id
    public function delete($id) {
        $sql = "DELETE FROM tbperfil WHERE tbperfilid = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}