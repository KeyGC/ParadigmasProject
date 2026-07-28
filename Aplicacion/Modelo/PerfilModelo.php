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
        $sql = "SELECT id, nickname, password FROM perfil ORDER BY id ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        $perfiles = [];
        foreach ($filas as $fila) {
            $perfiles[] = (new Perfil($fila['id'], $fila['nickname'], $fila['password']))->toArray();
        }
        return $perfiles;
    }

    // GETPERFIL - Obtener un perfil por id
    public function getPerfil($id) {
        $sql = "SELECT id, nickname, password FROM perfil WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return (new Perfil($fila['id'], $fila['nickname'], $fila['password']))->toArray();
        }
        return null;
    }

    // INSERT - Crear un nuevo perfil
    public function insert(Perfil $perfil) {
        $sql = "INSERT INTO perfil (nickname, password) VALUES (:nickname, :password)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nickname', $perfil->getNickname());
        $stmt->bindValue(':password', $perfil->getPassword());

        if ($stmt->execute()) {
            return $this->conexion->lastInsertId();
        }
        return false;
    }
    
    // UPDATE - Actualizar un perfil existente
    public function update(Perfil $perfil) {
        $sql = "UPDATE perfil SET nickname = :nickname, password = :password WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':nickname', $perfil->getNickname());
        $stmt->bindValue(':password', $perfil->getPassword());
        $stmt->bindValue(':id', $perfil->getId(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    // DELETE - Eliminar un perfil por id
    public function delete($id) {
        $sql = "DELETE FROM perfil WHERE id = :id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}