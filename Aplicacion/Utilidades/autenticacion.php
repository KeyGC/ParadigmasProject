<?php
if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
    header('Location: index.php?vista=login');
    exit;
}

$idPerfil = $_GET['id'] ?? null;
$tipo     = $_GET['tipo'] ?? 'musical';

if (!$idPerfil) {
    header('Location: index.php?vista=perfiles');
    exit;
}

$tiposDisponibles = [
    'musical'      => ['label' => 'Perfilado Musical',       'icono' => '🎵'],
    'gastronomico' => ['label' => 'Perfilado Gastronómico',  'icono' => '🍽️'],
    'deportes'     => ['label' => 'Perfilado Deportivo',     'icono' => '⚽'],
];

if (!array_key_exists($tipo, $tiposDisponibles)) {
    $tipo = 'musical';
}

$labelTipo  = $tiposDisponibles[$tipo]['label'];
$iconoTipo  = $tiposDisponibles[$tipo]['icono'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($labelTipo) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="cuerpoAdmin">

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid fondo-panel" id="contenedorPrincipal">
        <div class="container contenedor-panel">

            <div class="encabezado-panel d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <a href="index.php?vista=perfiles" class="btn btn-secondary btn-sm mb-2">
                        &larr; Volver a Perfiles
                    </a>
                    <h1 class="titulo-pagina" id="tituloPerfil">
                        <?= $iconoTipo ?> <?= htmlspecialchars($labelTipo) ?>
                    </h1>
                    <p class="subtitulo-pagina">
                        Resultados generados mediante una red neuronal entrenada con el historial de este perfil.
                    </p>
                </div>

                <!-- Selector de tipo de perfilado (futuro) -->
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($tiposDisponibles as $clave => $info): ?>
                        <a href="index.php?vista=perfilado&tipo=<?= $clave ?>&id=<?= htmlspecialchars($idPerfil) ?>"
                           class="btn btn-sm <?= $clave === $tipo ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <?= $info['icono'] ?> <?= htmlspecialchars($info['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Estado de carga -->
            <div class="tarjeta-panel" id="contenedorCargando">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Entrenando el modelo, un momento...</p>
                </div>
            </div>

            <!-- Sin datos -->
            <div class="alert alert-warning" id="contenedorSinDatos" style="display:none;"></div>

            <!-- Resultados -->
            <div id="contenedorResultados" style="display:none;">
                <div class="tarjeta-panel">
                    <div class="row g-3" id="tarjetasResultado"></div>
                    <p class="text-secondary mt-3 small" id="notaEventos"></p>
                </div>
            </div>

        </div>
    </div>

    <?php require_once APP_PATH . '/Vista/Componentes/footeradmin.php'; ?>

    <input type="hidden" id="idPerfil" value="<?= htmlspecialchars($idPerfil) ?>">
    <input type="hidden" id="tipoPerfil" value="<?= htmlspecialchars($tipo) ?>">
    <script src="js/perfilado.js"></script>
</body>
</html>