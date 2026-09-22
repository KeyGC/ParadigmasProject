<?php

class Evento
{
    private $tbeventoid;
    private $tbgeneroid;
    private $tbeventonombre;
    private $tbeventoartista;
    private $tbeventoubicaciongeneral;
    private $tbeventolatitud;
    private $tbeventolongitud;
    private $tbeventofecha;
    private $tbeventohora;

    public function __construct(
        $id = null,
        $generoId = null,
        $nombre = "",
        $artista = "",
        $ubicacionGeneral = "",
        $latitud = null,
        $longitud = null,
        $fecha = "",
        $hora = ""
    ) {
        $this->tbeventoid = $id;
        $this->tbgeneroid = $generoId;
        $this->tbeventonombre = $nombre;
        $this->tbeventoartista = $artista;
        $this->tbeventoubicaciongeneral = $ubicacionGeneral;
        $this->tbeventolatitud = $latitud;
        $this->tbeventolongitud = $longitud;
        $this->tbeventofecha = $fecha;
        $this->tbeventohora = $hora;
    }

    public function get_tbeventoid() { return $this->tbeventoid; }
    public function get_tbgeneroid() { return $this->tbgeneroid; }
    public function get_tbeventonombre() { return $this->tbeventonombre; }
    public function get_tbeventoartista() { return $this->tbeventoartista; }
    public function get_tbeventoubicaciongeneral() { return $this->tbeventoubicaciongeneral; }
    public function get_tbeventolatitud() { return $this->tbeventolatitud; }
    public function get_tbeventolongitud() { return $this->tbeventolongitud; }
    public function get_tbeventofecha() { return $this->tbeventofecha; }
    public function get_tbeventohora() { return $this->tbeventohora; }

    public function toArray()
    {
        return [
            "tbeventoid" => $this->tbeventoid,
            "tbgeneroid" => $this->tbgeneroid,
            "tbeventonombre" => $this->tbeventonombre,
            "tbeventoartista" => $this->tbeventoartista,
            "tbeventoubicaciongeneral" => $this->tbeventoubicaciongeneral,
            "tbeventolatitud" => $this->tbeventolatitud,
            "tbeventolongitud" => $this->tbeventolongitud,
            "tbeventofecha" => $this->tbeventofecha,
            "tbeventohora" => $this->tbeventohora
        ];
    }
}