<?php
// Aplicacion/Modelo/Perfil.php

class Perfil {
    private $id;
    private $nickname;
    private $password;

    public function __construct($id = null, $nickname = "", $password = "") {
        $this->id = $id;
        $this->nickname = $nickname;
        $this->password = $password;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNickname() { return $this->nickname; }
    public function getPassword() { return $this->password; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setNickname($nickname) { $this->nickname = $nickname; }
    public function setPassword($password) { $this->password = $password; }

    // Para convertir a array/JSON fácilmente (sin exponer password si no quieres)
    public function toArray() {
        return [
            "id" => $this->id,
            "nickname" => $this->nickname,
            "password" => $this->password
        ];
    }
}