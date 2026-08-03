<?php
// Aplicacion/Modelo/Perfil.php

class Perfil
{
    private $tbperfilid;
    private $tbperfilnombre;
    private $tbperfilcontra;
    private $tbperfilcorreo;
    private $tbperfilcambiocontra;


    public function __construct($id = null, $nombre = "", $contra = "", $correo = "", $cambioContra = 0)
    {
        $this->tbperfilid = $id;
        $this->tbperfilnombre = $nombre;
        $this->tbperfilcontra = $contra;
        $this->tbperfilcorreo = $correo;
        $this->tbperfilcambiocontra = $cambioContra;
    }

    // Getters
    public function get_tbperfilid()
    {
        return $this->tbperfilid;
    }
    public function get_tbperfilnombre()
    {
        return $this->tbperfilnombre;
    }
    public function get_tbperfilcontra()
    {
        return $this->tbperfilcontra;
    }
    public function get_tbperfilcorreo()
    {
        return $this->tbperfilcorreo;
    }
    public function get_tbperfilcambiocontra()
    {
        return $this->tbperfilcambiocontra;
    }


    // Para convertir a array/JSON fácilmente (sin exponer password si no quieres)
    public function toArray()
    {
        return [
            "tbperfilid" => $this->tbperfilid,
            "tbperfilnombre" => $this->tbperfilnombre,
            "tbperfilcontra" => $this->tbperfilcontra,
            "tbperfilcorreo" => $this->tbperfilcorreo,
            "tbperfilcambiocontra" => $this->tbperfilcambiocontra
        ];
    }
}
