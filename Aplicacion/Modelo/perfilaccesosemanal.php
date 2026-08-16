<?php

class PerfilAccesoSemanal
{
    private $tbperfilaccesosemanalid;
    private $tbperfilaccesosemanaldata;

    public function __construct($id = null, $data = "")
    {
        $this->tbperfilaccesosemanalid = $id;
        $this->tbperfilaccesosemanaldata = $data;
    }

    public function get_tbperfilaccesosemanalid()
    {
        return $this->tbperfilaccesosemanalid;
    }

    public function get_tbperfilaccesosemanaldata()
    {
        return $this->tbperfilaccesosemanaldata;
    }

    public function toArray()
    {
        return [
            "tbperfilaccesosemanalid" => $this->tbperfilaccesosemanalid,
            "tbperfilaccesosemanaldata" => $this->tbperfilaccesosemanaldata
        ];
    }
}