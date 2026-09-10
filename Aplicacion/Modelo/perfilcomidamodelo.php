<?php
require_once __DIR__ . '/../../Configuracion/basedatos.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';
require_once __DIR__ . '/perfilmodelo.php';

class PerfilComidaModelo
{
    private $conexion;

    private $coleccion = 'comida';

    private $minimoSwipes = 8;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    protected function crearQdrantCliente()
    {
        return new QdrantCliente(null, null, $this->coleccion);
    }

    protected function calcularHashDatos($perfilId)
    {
        $sql = "SELECT tbcomidaid, tbcomidaswipelike, tbcomidaswipefecha
                FROM tbcomidaswipe
                WHERE tbperfilid = :perfilId
                ORDER BY tbcomidaswipeid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return hash('sha256', serialize($stmt->fetchAll()));
    }

    private function obtenerConteoPorTipo($perfilId)
    {
        $sql = "SELECT c.tbtipocomidaid, t.tbtipocomidanombre,
                       SUM(s.tbcomidaswipelike) AS likes,
                       COUNT(*) AS total
                FROM tbcomidaswipe s
                INNER JOIN tbcomida c ON s.tbcomidaid = c.tbcomidaid
                INNER JOIN tbtipocomida t ON c.tbtipocomidaid = t.tbtipocomidaid
                WHERE s.tbperfilid = :perfilId
                GROUP BY c.tbtipocomidaid, t.tbtipocomidanombre
                ORDER BY c.tbtipocomidaid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bindValue(':perfilId', $perfilId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function construirVectorPerfil(array $conteoPorTipo, $totalLikes)
    {
        $sql = "SELECT tbtipocomidaid FROM tbtipocomida WHERE tbtipocomidaestado = 1 ORDER BY tbtipocomidaid";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $tipos = $stmt->fetchAll();

        $likesPorTipo = [];
        foreach ($conteoPorTipo as $fila) {
            $likesPorTipo[(int) $fila['tbtipocomidaid']] = (int) $fila['likes'];
        }

        $vector = [];
        foreach ($tipos as $tipo) {
            $vector[] = $totalLikes > 0 ? ($likesPorTipo[(int) $tipo['tbtipocomidaid']] ?? 0) / $totalLikes : 0.0;
        }

        $norma = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        if ($norma > 0) {
            $vector = array_map(fn($v) => $v / $norma, $vector);
        }

        return array_values($vector);
    }

    public function generarPerfilado($perfilId)
    {
        $qdrant = $this->crearQdrantCliente();
        $hashdatos = $this->calcularHashDatos($perfilId);

        $punto = $qdrant->obtenerPunto((int) $perfilId);
        if ($punto !== null
            && isset($punto['payload']['hashdatos'], $punto['payload']['resultados'])
            && $punto['payload']['hashdatos'] === $hashdatos) {
            return [
                'exito' => true,
                'desdeCache' => true,
                'totalSwipes' => (int) ($punto['payload']['totalSwipes'] ?? 0),
                'resultados' => $punto['payload']['resultados']
            ];
        }

        $conteoPorTipo = $this->obtenerConteoPorTipo($perfilId);
        $totalSwipes = (int) array_sum(array_column($conteoPorTipo, 'total'));
        $totalLikes = (int) array_sum(array_column($conteoPorTipo, 'likes'));

        if ($totalSwipes < $this->minimoSwipes) {
            return [
                'exito' => false,
                'mensaje' => "Necesitas al menos {$this->minimoSwipes} swipes para generar tu perfil de comida (llevas {$totalSwipes}).",
                'totalSwipes' => $totalSwipes
            ];
        }

        if ($totalLikes <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'No diste "me gusta" a ninguna comida, así que todavía no hay perfil que calcular.',
                'totalSwipes' => $totalSwipes
            ];
        }

        $vector = $this->construirVectorPerfil($conteoPorTipo, $totalLikes);

        usort($conteoPorTipo, fn($a, $b) => (int) $b['likes'] <=> (int) $a['likes']);

        $resultados = [];
        foreach (array_slice($conteoPorTipo, 0, 2) as $fila) {
            $resultados[] = [
                'tipo' => $fila['tbtipocomidanombre'],
                'porcentaje' => round(((int) $fila['likes'] / $totalLikes) * 100, 1)
            ];
        }

        if (!$qdrant->crearColeccionSiNoExiste(count($vector))) {
            error_log("Qdrant: no se pudo garantizar la colección {$this->coleccion}");
        }

        $guardado = $qdrant->guardarVector((int) $perfilId, $vector, $hashdatos, [
            'resultados' => $resultados,
            'totalSwipes' => $totalSwipes
        ]);

        if (!$guardado) {
            error_log("Qdrant: no se pudo guardar el vector de comida del perfil {$perfilId}");
        }

        return [
            'exito' => true,
            'totalSwipes' => $totalSwipes,
            'resultados' => $resultados
        ];
    }

    public function buscarCoincidencias($perfilId)
    {
        $qdrant = $this->crearQdrantCliente();
        $punto = $qdrant->obtenerPunto((int) $perfilId);

        if ($punto === null || !isset($punto['vector'])) {
            return [
                'exito' => false,
                'mensaje' => 'No tienes un perfil de comida calculado. Juega el mini-juego y presiona "Ver mi resultado" primero.'
            ];
        }

        $similares = $qdrant->buscarSimilares($punto['vector'], 5);

        if ($similares === null) {
            return ['exito' => false, 'mensaje' => 'No se pudo consultar Qdrant (servicio no disponible)'];
        }

        $ids = array_values(array_unique(array_map('intval', array_column($similares, 'tbperfilid'))));
        $ids = array_values(array_filter($ids, fn($id) => $id !== (int) $perfilId));

        $perfilModelo = new PerfilModelo();
        $coincidencias = $perfilModelo->getCoincidencias($ids, (int) $perfilId);

        $scorePorId = [];
        foreach ($similares as $s) {
            $scorePorId[(int) $s['tbperfilid']] = $s['score'];
        }

        foreach ($coincidencias as &$c) {
            $c['score'] = $scorePorId[(int) $c['tbperfilid']] ?? 0.0;
        }
        unset($c);

        usort($coincidencias, fn($a, $b) => $b['score'] <=> $a['score']);

        return ['exito' => true, 'data' => $coincidencias];
    }
}