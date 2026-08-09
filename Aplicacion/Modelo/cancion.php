<?php

class Cancion
{
    private $tbcancionid;
    private $tbgeneroid;
    private $tbcancionnombre;
    private $tbcancionartista;
    private $tbcancionurl;
    private $tbcancionactivo;

    public function __construct($id = null, $tbgeneroid = null, $nombre = "", $artista = "", $url = "", $activo = true)
    {
        $this->tbcancionid = $id;
        $this->tbgeneroid = $tbgeneroid;
        $this->tbcancionnombre = $nombre;
        $this->tbcancionartista = $artista;
        $this->tbcancionurl = $url;
        $this->tbcancionactivo = $activo;
    }

    public function get_tbcancionid() { return $this->tbcancionid; }
    public function get_tbgeneroid() { return $this->tbgeneroid; }
    public function get_tbcancionnombre() { return $this->tbcancionnombre; }
    public function get_tbcancionartista() { return $this->tbcancionartista; }
    public function get_tbcancionurl() { return $this->tbcancionurl; }
    public function get_tbcancionactivo() { return $this->tbcancionactivo; }

    public function toArray()
    {
        return [
            "tbcancionid" => $this->tbcancionid,
            "tbgeneroid" => $this->tbgeneroid,
            "tbcancionnombre" => $this->tbcancionnombre,
            "tbcancionartista" => $this->tbcancionartista,
            "tbcancionurl" => $this->tbcancionurl,
            "tbcancionactivo" => $this->tbcancionactivo
        ];
    }
}