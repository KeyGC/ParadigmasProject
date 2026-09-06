<?php

class Concierto
{
    private $tbconciertoid;
    private $tbgeneroid;
    private $tbconciertonombre;
    private $tbconciertoartista;
    private $tbconciertoubicacion;
    private $tbconciertolatitud;
    private $tbconciertolongitud;
    private $tbconciertofecha;
    private $tbconciertohora;

    public function __construct(
        $id = null,
        $generoId = null,
        $nombre = "",
        $artista = "",
        $ubicacion = "",
        $latitud = null,
        $longitud = null,
        $fecha = "",
        $hora = ""
    ) {
        $this->tbconciertoid = $id;
        $this->tbgeneroid = $generoId;
        $this->tbconciertonombre = $nombre;
        $this->tbconciertoartista = $artista;
        $this->tbconciertoubicacion = $ubicacion;
        $this->tbconciertolatitud = $latitud;
        $this->tbconciertolongitud = $longitud;
        $this->tbconciertofecha = $fecha;
        $this->tbconciertohora = $hora;
    }

    public function get_tbconciertoid() { return $this->tbconciertoid; }
    public function get_tbgeneroid() { return $this->tbgeneroid; }
    public function get_tbconciertonombre() { return $this->tbconciertonombre; }
    public function get_tbconciertoartista() { return $this->tbconciertoartista; }
    public function get_tbconciertoubicacion() { return $this->tbconciertoubicacion; }
    public function get_tbconciertolatitud() { return $this->tbconciertolatitud; }
    public function get_tbconciertolongitud() { return $this->tbconciertolongitud; }
    public function get_tbconciertofecha() { return $this->tbconciertofecha; }
    public function get_tbconciertohora() { return $this->tbconciertohora; }

    public function toArray()
    {
        return [
            "tbconciertoid" => $this->tbconciertoid,
            "tbgeneroid" => $this->tbgeneroid,
            "tbconciertonombre" => $this->tbconciertonombre,
            "tbconciertoartista" => $this->tbconciertoartista,
            "tbconciertoubicacion" => $this->tbconciertoubicacion,
            "tbconciertolatitud" => $this->tbconciertolatitud,
            "tbconciertolongitud" => $this->tbconciertolongitud,
            "tbconciertofecha" => $this->tbconciertofecha,
            "tbconciertohora" => $this->tbconciertohora
        ];
    }
}