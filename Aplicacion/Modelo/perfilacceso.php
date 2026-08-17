<?php

class PerfilAcceso
{
    private $tbperfilaccesoid;
    private $tbperfilid;
    private $tbperfilaccesosemanalid;
    private $tbperfilaccesofechacreacion;
    private $tbperfilaccesofechaultima;

    public function __construct($id = null, $tbperfilid = null, $tbperfilaccesosemanalid = null, $fechaCreacion = null, $fechaUltima = null)
    {
        $this->tbperfilaccesoid = $id;
        $this->tbperfilid = $tbperfilid;
        $this->tbperfilaccesosemanalid = $tbperfilaccesosemanalid;
        $this->tbperfilaccesofechacreacion = $fechaCreacion;
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
    public function get_tbperfilaccesofechacreacion()
    {
        return $this->tbperfilaccesofechacreacion;
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
            "tbperfilaccesofechacreacion" => $this->tbperfilaccesofechacreacion,
            "tbperfilaccesofechaultima" => $this->tbperfilaccesofechaultima
        ];
    }
}