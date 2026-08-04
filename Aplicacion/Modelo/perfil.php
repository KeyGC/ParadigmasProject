<?php


class Perfil
{
    private $tbperfilid;
    private $tbperfilnombre;
    private $tbperfilcontra;
    private $tbperfilcorreo;
    private $tbperfilcambiocontra;
    
    private $tbubicacionid;

    private $tbperfilactivo;


    public function __construct($id = null, $nombre = "", $contra = "", $correo = "", $cambioContra = 0, $tbubicacionid = null, $tbperfilactivo = false)
    {
        $this->tbperfilid = $id;
        $this->tbperfilnombre = $nombre;
        $this->tbperfilcontra = $contra;
        $this->tbperfilcorreo = $correo;
        $this->tbperfilcambiocontra = $cambioContra;
        $this->tbubicacionid = $tbubicacionid;
        $this->tbperfilactivo = $tbperfilactivo;
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

    public function get_tbubicacionid(){
        return $this->tbubicacionid;
    }

    public function get_tbperfilactivo(){
        return $this->tbperfilactivo;
    }


 
    public function toArray()
    {
        return [
            "tbperfilid" => $this->tbperfilid,
            "tbperfilnombre" => $this->tbperfilnombre,
            "tbperfilcontra" => $this->tbperfilcontra,
            "tbperfilcorreo" => $this->tbperfilcorreo,
            "tbperfilcambiocontra" => $this->tbperfilcambiocontra,
            "tbubicacionid" => $this->tbubicacionid,
            "tbperfilactivo" => $this->tbperfilactivo
        ];
    }
}
