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
    <title>Accesos del Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="cuerpoAdmin">

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid fondo-panel" id="contenedorPrincipal">
        <div class="container contenedor-panel">

            <div class="encabezado-panel d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <a href="index.php?vista=perfiles" class="btn btn-secondary btn-sm mb-2">&larr; Volver a Perfiles</a>
                    <h1 class="titulo-pagina" id="tituloPerfil">Accesos</h1>
                    <p class="subtitulo-pagina" id="resumenFechas"></p>
                </div>
                <button class="btn btn-danger mb-3" id="btnToggleEstado" onclick="toggleEstadoAcceso()">
                    Desactivar registro de accesos
                </button>
            </div>

            <div class="tarjeta-panel">
                <div class="table-responsive">
                    <table class="table table-bordered tabla-panel cabecera-oscura" id="tablaMatriz">
                        <thead>
                            <tr>
                                <th>Semana</th>
                                <th>Lun</th>
                                <th>Mar</th>
                                <th>Mie</th>
                                <th>Jue</th>
                                <th>Vie</th>
                                <th>Sab</th>
                                <th>Dom</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoMatriz">
                            <!-- AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php require_once APP_PATH . '/Vista/Componentes/footeradmin.php'; ?>

    <input type="hidden" id="idPerfil" value="<?php echo htmlspecialchars($idPerfil); ?>">
    <script src="js/perfilacceso.js"></script>
</body>
</html>