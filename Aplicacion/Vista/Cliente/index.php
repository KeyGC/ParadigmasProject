<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente-MatchCitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <section class="container-fluid px-5 py-4" id="seccionMusica">
            <h3 class="mb-3">En UnaMatch también puedes escuchar tus canciones favoritas 🎧</h3>

            <div class="carrusel-canciones" id="carruselCanciones">
                <!-- Las tarjetas de canciones se insertan aquí vía JS -->
            </div>
        </section>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <!-- Modal reproductor -->
    <div class="modal fade" id="modalReproductor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>
    <script src="js/reproductor.js"></script>
    <script src="js/ubicacionauto.js"></script>

</body>

</html>