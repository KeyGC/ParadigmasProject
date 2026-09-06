<?php

class Genero
{
    private $tbgeneroid;
    private $tbgeneronombre;
    private $tbgeneroestado;

    public function __construct($id = null, $nombre = "", $estado = true)
    {
        $this->tbgeneroid = $id;
        $this->tbgeneronombre = $nombre;
        $this->tbgeneroestado = $estado;
    }

    public function get_tbgeneroid() { return $this->tbgeneroid; }
    public function get_tbgeneronombre() { return $this->tbgeneronombre; }
    public function get_tbgeneroestado() { return $this->tbgeneroestado; }

    public function toArray()
    {
        return [
            "tbgeneroid" => $this->tbgeneroid,
            "tbgeneronombre" => $this->tbgeneronombre,
            "tbgeneroestado" => $this->tbgeneroestado
        ];
    }
}