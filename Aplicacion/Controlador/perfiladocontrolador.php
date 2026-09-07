<?php
require_once __DIR__ . '/../Modelo/perfilmodelo.php';
require_once __DIR__ . '/../Utilidades/qdrantcliente.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

$accion   = $_REQUEST['accion'] ?? '';
$tipo     = $_GET['tipo'] ?? 'musical';
$perfilId = $_REQUEST['perfilId'] ?? null;

$tiposModelos = [
    'musical' => [
        'archivo' => APP_PATH . '/Modelo/perfilmusicalmodelo.php',
        'clase'   => 'PerfilMusicalModelo',
    ],
    // Futuros perfilados se agregan acá:
    // 'gastronomico' => [
    //     'archivo' => APP_PATH . '/Modelo/perfilgastronomicomodelo.php',
    //     'clase'   => 'PerfilGastronomicoModelo',
    // ],
];

switch ($accion) {

    case 'getPerfilado':
        if (!$perfilId) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        if (!array_key_exists($tipo, $tiposModelos)) {
            echo json_encode(["exito" => false, "mensaje" => "Tipo de perfilado no reconocido: {$tipo}"]);
            break;
        }

        $config = $tiposModelos[$tipo];
        require_once $config['archivo'];
        $clase  = $config['clase'];
        $modelo = new $clase();

        $resultado = $modelo->generarPerfilado($perfilId);
        echo json_encode($resultado);
        break;

    case 'buscarCoincidencias':
        if (!$perfilId) {
            echo json_encode(["exito" => false, "mensaje" => "ID de perfil no proporcionado"]);
            break;
        }

        $qdrant = new QdrantCliente();
        $punto = $qdrant->obtenerPunto((int) $perfilId);

        if ($punto === null || !isset($punto['vector'])) {
            echo json_encode(["exito" => false, "mensaje" => "El perfil no tiene un vector guardado en Qdrant (genera primero su perfilado)"]);
            break;
        }

        $similares = $qdrant->buscarSimilares($punto['vector'], 5);

        if ($similares === null) {
            echo json_encode(["exito" => false, "mensaje" => "No se pudo consultar Qdrant (servicio no disponible)"]);
            break;
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

        echo json_encode(["exito" => true, "data" => $coincidencias]);
        break;

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}