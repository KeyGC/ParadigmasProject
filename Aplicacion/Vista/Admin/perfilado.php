<?php
if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
    header('Location: index.php?vista=login');
    exit;
}

$idPerfil = $_GET['id'] ?? null;
if (!$idPerfil) {
    header('Location: index.php?vista=perfiles');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfilado Musical</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid bg-light text-primary p-5 text-center" id="contenedorPrincipal">
        <div class="container">
            <a href="index.php?vista=perfiles" class="btn btn-secondary mb-3">Volver a Perfiles</a>
            <h1 id="tituloPerfil">Perfilado Musical</h1>
            <p class="text-secondary">Generado mediante una red neuronal entrenada con el historial de reproducciones de este perfil.</p>

            <div id="contenedorCargando" class="my-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p>Entrenando el modelo, un momento...</p>
            </div>

            <div id="contenedorResultados" style="display:none;">
                <div class="row justify-content-center" id="tarjetasResultado"></div>
            </div>

            <div id="contenedorSinDatos" class="alert alert-warning" style="display:none;"></div>
        </div>
    </div>

    <?php require_once APP_PATH . '/Vista/Componentes/footeradmin.php'; ?>

    <input type="hidden" id="idPerfil" value="<?php echo htmlspecialchars($idPerfil); ?>">
    <script src="js/perfilado.js"></script>
</body>
</html>