<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? 'Hola';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <section class="bienvenida">
            <h2>Hola, <?= htmlspecialchars($usuarioActivo) ?></h2>
            <p>Bienvenido de nuevo a UnaMatch. La música también dice mucho de ti.</p>
        </section>

        <section class="musica-seccion" id="seccionMusica">
            <div class="musica-cabecera">
                <h3>La banda sonora de tu próxima cita</h3>
                <p>Haz clic en una canción para escucharla y haz que marque el momento.</p>
            </div>

            <div class="carrusel-canciones" id="carruselCanciones">
                <!-- Las tarjetas de canciones se insertan aquí vía JS -->
            </div>
        </section>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <!-- Modal reproductor -->
    <div class="modal fade" id="modalReproductor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-reproductor">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="tituloCancionModal">Reproduciendo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="btnCerrarModal"></button>
                </div>
                <div class="modal-body">
                    <div id="reproductorYoutube"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://www.youtube.com/iframe_api"></script>
    <script src="js/reproductor.js"></script>
    <script src="js/ubicacionauto.js"></script>

</body>

</html>