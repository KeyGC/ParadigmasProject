<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
    echo json_encode(["exito" => false, "mensaje" => "No autorizado"]);
    exit;
}

$accion   = $_REQUEST['accion'] ?? '';
$tipo     = $_GET['tipo'] ?? 'musical';
$perfilId = $_GET['perfilId'] ?? null;

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

    default:
        echo json_encode(["exito" => false, "mensaje" => "Acción no reconocida"]);
        break;
}