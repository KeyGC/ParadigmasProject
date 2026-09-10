<?php

require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';

class BiometriaModelo
{
    const COLECCION = 'biometria';
    const TAMANIO_VECTOR = 128;

    protected function crearQdrantCliente()
    {
        return new QdrantCliente(null, null, self::COLECCION);
    }

    public function guardarVectorPerfil($tbperfilid, array $vector)
    {
        $qdrant = $this->crearQdrantCliente();

        if (!$qdrant->crearColeccionSiNoExiste(self::TAMANIO_VECTOR)) {
            error_log("Qdrant: no se pudo garantizar la colección " . self::COLECCION);
            return false;
        }

        $hashdatos = substr(hash('sha256', json_encode($vector)), 0, 16);

        $guardado = $qdrant->guardarVector((int) $tbperfilid, $vector, $hashdatos);

        if (!$guardado) {
            error_log("Qdrant: no se pudo guardar el vector biométrico del perfil {$tbperfilid}");
        }

        return $guardado;
    }

    public function buscarRostro(array $vector, $umbral)
    {
        $qdrant = $this->crearQdrantCliente();

        if (!$qdrant->crearColeccionSiNoExiste(self::TAMANIO_VECTOR)) {
            error_log("Qdrant: no se pudo garantizar la colección " . self::COLECCION);
            return null;
        }

        $resultados = $qdrant->buscarSimilares($vector, 1, $umbral);

        if ($resultados === null) {
            error_log("Qdrant: fallo al buscar similitud biométrica");
            return null;
        }

        if (empty($resultados)) {
            return null;
        }

        return [
            'tbperfilid' => (int) $resultados[0]['tbperfilid'],
            'score' => (float) $resultados[0]['score']
        ];
    }

    public function validarVector(array $vector)
    {
        if (count($vector) !== self::TAMANIO_VECTOR) {
            return false;
        }

        foreach ($vector as $v) {
            if (!is_numeric($v) || !is_finite((float) $v)) {
                return false;
            }
        }

        return true;
    }
}