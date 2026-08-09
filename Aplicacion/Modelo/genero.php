<?php

class Genero
{
    private $tbgeneroid;
    private $tbgeneronombre;

    public function __construct($id = null, $nombre = "")
    {
        $this->tbgeneroid = $id;
        $this->tbgeneronombre = $nombre;
    }

    public function get_tbgeneroid() { return $this->tbgeneroid; }
    public function get_tbgeneronombre() { return $this->tbgeneronombre; }

    public function toArray()
    {
        return [
            "tbgeneroid" => $this->tbgeneroid,
            "tbgeneronombre" => $this->tbgeneronombre
        ];
    }
}