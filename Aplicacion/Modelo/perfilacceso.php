<?php

class PerfilAcceso
{
    private $tbperfilaccesoid;
    private $tbperfilid;
    private $tbperfilaccesosemanalid;
    private $tbperfilaccesofechaprimera;
    private $tbperfilaccesofechaultima;

    public function __construct($id = null, $tbperfilid = null, $tbperfilaccesosemanalid = null, $fechaPrimera = null, $fechaUltima = null)
    {
        $this->tbperfilaccesoid = $id;
        $this->tbperfilid = $tbperfilid;
        $this->tbperfilaccesosemanalid = $tbperfilaccesosemanalid;
        $this->tbperfilaccesofechaprimera = $fechaPrimera;
        $this->tbperfilaccesofechaultima = $fechaUltima;
    }

    public function get_tbperfilaccesoid()
    {
        return $this->tbperfilaccesoid;
    }
    public function get_tbperfilid()
    {
        return $this->tbperfilid;
    }
    public function get_tbperfilaccesosemanalid()
    {
        return $this->tbperfilaccesosemanalid;
    }
    public function get_tbperfilaccesofechaprimera()
    {
        return $this->tbperfilaccesofechaprimera;
    }
    public function get_tbperfilaccesofechaultima()
    {
        return $this->tbperfilaccesofechaultima;
    }

    public function toArray()
    {
        return [
            "tbperfilaccesoid" => $this->tbperfilaccesoid,
            "tbperfilid" => $this->tbperfilid,
            "tbperfilaccesosemanalid" => $this->tbperfilaccesosemanalid,
            "tbperfilaccesofechaprimera" => $this->tbperfilaccesofechaprimera,
            "tbperfilaccesofechaultima" => $this->tbperfilaccesofechaultima
        ];
    }
}